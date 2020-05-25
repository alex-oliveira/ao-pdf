<?php

namespace AOPDF;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AOPDFService
{

    /**
     * @param array $items
     * @return array
     *
     * @throws \Exception
     */
    public function process(array $items)
    {
        if (key_exists(0, $items) == false) {
            $items = [$items];
        }

        $pdf_locations = [];

        foreach ($items as $item) {
            $pdf_locations[] = $this->makePDF($item);
        }

        $pdf_location = $this->mergePDF($pdf_locations);

        if (empty($pdf_location)) {
            abort(400, 'No files were generated.');
        }

        //$pdf_location = $this->makeZIP($pdf_location);

        return $pdf_location;
    }

    public function makePDF($data)
    {
        $disk = AOPDF::disk();

        $template = Arr::get($data, 'template');
        $config = Arr::get($data, 'config', []);
        $params = Arr::get($data, 'params', []);

        //
        // DEFINE TEMPLATE
        //
        $template_name = md5($template) . '.pdf';
        $template_location = 'templates/' . $template_name;
        if ($disk->exists($template_location) == false) {
            $disk->put($template_location, file_get_contents($template));
        }

        //
        // FORMAT PARAMS
        //
        $name = AOPDF::uniqid();
        $params = $this->formatParams($params, $config);

        //
        // MAKE FDF
        //
        $fdf_location = 'tmp/' . $name . '.fdf';
        $disk->put($fdf_location, $this->makeFDF($params));

        //
        // MAKE PDF
        //
        $pdf_location = 'tmp/' . $name . '.pdf';
        $pdf_output = AOPDF::exec([
            'pdftk', $disk->path($template_location),
            'fill_form', $disk->path($fdf_location),
            'output', $disk->path($pdf_location),
            'flatten',
        ]);

        $disk->delete($fdf_location);

        if (count($pdf_output) > 0) {
            abort(412, 'Failed to generate PDF. ' . json_encode($pdf_output));
        }

        if ($disk->exists($pdf_location) == false) {
            abort(412, 'PDF file was not created.');
        }

        return $pdf_location;
    }

    protected function formatParams(array $params, array $config)
    {
        //
        // RESOLVE SELECT
        //
        $selects = Arr::get($config, 'select', []);
        foreach ($selects as $select) {
            $values = Arr::get($params, $select, []);

            if (is_string($values)) {
                $values = [$values];
            } else if (!is_array($values)) {
                continue;
            }

            $data = [];

            foreach ($values as $value) {
                $data[Str::slug($value)] = 'X';
            }

            Arr::set($params, $select, $data);
        }

        //
        // TO DOT
        //
        $params = Arr::dot($params);
        foreach ($params as $field => $value) {
            if (is_array($value) || is_object($value)) {
                unset($params[$field]);
                continue;
            }
            $params[$field] = is_null($value) ? '' : $value;
        }

        $allowed_formatters = get_class_methods(AOPDFFormatter::class);
        $required_formatters = [];

        //
        // ALL
        //
        $config_all = array_intersect($allowed_formatters, Arr::get($config, 'all', []));
        foreach ($config_all as $formatter) {
            foreach ($params as $field => $value) {
                $required_formatters[$field] = array_merge(Arr::get($required_formatters, $field, []), [$formatter]);
            }
        }

        //
        // BY FORMATTER
        //
        foreach ($allowed_formatters as $formatter) {
            $fields = Arr::get($config, $formatter, []);
            foreach ($fields as $field) {
                if (is_string($field)) {
                    $required_formatters[$field] = array_merge(Arr::get($required_formatters, $field, []), [$formatter]);
                }
            }
        }

        //
        // BY FIELD
        //
        $config_fields = Arr::get($config, 'fields', []);
        foreach ($config_fields as $field => $formatters) {
            if (is_array($formatters)) {
                $formatters = array_intersect($allowed_formatters, $formatters);
            } else if (is_string($formatters) && in_array($formatters, $allowed_formatters)) {
                $formatters = [$formatters];
            } else {
                continue;
            }
            $required_formatters[$field] = array_merge(Arr::get($required_formatters, $field, []), $formatters);
        }

        //
        // APPLY
        //
        foreach ($required_formatters as $field => $formatters) {
            if (isset($params[$field])) {
                foreach ($formatters as $formatter) {
                    $params[$field] = forward_static_call([AOPDFFormatter::class, $formatter], $params[$field]);
                }
            }
        }

        return $params;
    }

    protected function makeFDF($params, $name = 'params.fdf')
    {
        $fdf = "%FDF-1.2\n%âãÏÓ\n1 0 obj\n<< \n/FDF << /Fields [ ";

        foreach ($params as $key => $value) {
            $fdf .= "<</T($key)/V(" . AOPDF::fixFDFvalue(mb_convert_encoding($value, 'UTF-8')) . ")>>";
        }

        $fdf .= "] \n/F (" . $name . ") /ID [ <" . md5(time()) . ">\n] >>";
        $fdf .= " \n>> \nendobj\ntrailer\n";
        $fdf .= "<<\n/Root 1 0 R \n\n>>\n%%EOF\n";

        return $fdf;
    }

    protected function mergePDF(array $pdf_locations)
    {
        if (count($pdf_locations) <= 1) {
            return Arr::first($pdf_locations);
        }

        $disk = AOPDF::disk();

        $merge_name = AOPDF::uniqid('.pdf');
        $merge_location = 'tmp/' . $merge_name;

        $parts = ['pdftk'];
        foreach ($pdf_locations as $pdf_location) {
            $parts[] = '"' . $disk->path($pdf_location) . '"';
        }
        $parts[] = 'output';
        $parts[] = '"' . $disk->path($merge_location) . '"';

        $output = AOPDF::exec($parts);

        if (count($output) > 0) {
            abort(412, 'Failed to merge PDF. ' . json_encode($output));
        }

        foreach ($pdf_locations as $pdf_location) {
            if ($disk->exists($pdf_location)) {
                $disk->delete($pdf_location);
            }
        }

        return $merge_location;
    }

    protected function makeZIP($pdf_location)
    {
        $disk = AOPDF::disk();

        $zip_name = AOPDF::uniqid('.zip');
        $zip_location = 'tmp/' . $zip_name;

        $zip = new \ZipArchive();
        if ($zip->open($disk->path($zip_location), \ZIPARCHIVE::CREATE) !== true)
            return false;

        $zip->addFile($disk->path($pdf_location), basename($pdf_location));
        $zip->close();

        $disk->delete($pdf_location);

        return $zip_location;
    }

}
