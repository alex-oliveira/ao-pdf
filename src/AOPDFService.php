<?php

namespace AOPDF;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class AOPDFService
{

    protected $config = [
        'template' => '', // nome do tamplete que será utilizado
        'all' => [],
        'fields' => [],
    ];

    //------------------------------------------------------------------------------------------------------------------
    // MAIN FUNCTIONS
    //------------------------------------------------------------------------------------------------------------------

    /**
     * @param array $items
     * @return array
     *
     * @throws \Exception
     */
    public function process(array $items)
    {
        if (!key_exists(0, $items)) {
            $items = [$items];
        }

        $pdf_paths = [];

        foreach ($items as $item) {
            $pdf_paths[] = $this->makePDF($item);
        }

        if (count($pdf_paths) > 1) {
            $pdf_path = $this->makeMerge($pdf_paths);
        } else {
            $pdf_path = Arr::first($pdf_paths);
        }

        if (empty($pdf_path)) {
            abort(400, 'No files were generated.');
        }

        //$pdf_path = $this->makeZIP($pdf_path);

        return $pdf_path;
    }

    public function makePDF($data)
    {
        $template = Arr::get($data, 'template');
        $config = Arr::get($data, 'config', []);
        $params = Arr::get($data, 'params', []);

        //
        // DEFINE TEMPLATE
        //
        $template_hash = md5($template);
        $template_name = basename($template);
        $template_path = storage_path('app/templates/' . $template_hash);
        if (Storage::exists('templates/' . $template_hash) == false) {
            $template_content = file_get_contents($template);
            Storage::put('templates/' . $template_hash, $template_content);
        }

        //
        // FORMAT PARAMS
        //
        $params = $this->formatParams($params, $config);

        //
        // MAKE FDF
        //
        $fdf_name = time() . '_' . uniqid() . '.fdf';
        $fdf_content = $this->makeFDF($fdf_name, $params);
        $fdf_path = storage_path('app/tmp/' . $fdf_name);
        Storage::put('tmp/' . $fdf_name, $fdf_content);

        //
        // MAKE PDF
        //
        $pdf_name = time() . '_' . uniqid() . '_' . $template_name;
        $pdf_path = storage_path('app/tmp/' . $pdf_name);
        $pdf_output = [];
        $pdf_command = "pdftkx \"$template_path\" fill_form \"$fdf_path\" output \"$pdf_path\" flatten";
        exec($pdf_command, $pdf_output);

        //$pdf_command = "pdftk /home/vagrant/www/teste/my.template.pdf fill_form /home/vagrant/www/teste/my.fdf output /home/vagrant/www/teste/my.pdf flatten";
        //dd($pdf_command, $pdf_output);

        unlink($fdf_path);

        if (!Storage::exists('tmp/' . $pdf_name)) {
            abort(412, 'PDF file was not created.');
        }

        return $pdf_path;
    }

    //------------------------------------------------------------------------------------------------------------------
    // OTHER FUNCTIONS
    //------------------------------------------------------------------------------------------------------------------

    protected function formatParams(array $params, array $config)
    {
        // todo: resolve SELECT

        $params = Arr::dot($params);

        foreach ($params as $field => $value) {
            if (is_array($value) || is_object($value)) {
                unset($params[$field]);
                continue;
            }

//            if (in_array($key, $config['select'])) {
//                $params[$key . ':' . $value] = 'X';
//                unset($params[$key]);
//                continue;
//            }

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

    protected function makeFDF($name, $params)
    {
        $fdf = "%FDF-1.2\n%âãÏÓ\n1 0 obj\n<< \n/FDF << /Fields [ ";

        foreach ($params as $key => $value) {
            $fdf .= "<</T($key)/V(" . AOPDFHelper::fixFDFvalue(mb_convert_encoding($value, 'UTF-8')) . ")>>";
        }

        $fdf .= "] \n/F (" . $name . ") /ID [ <" . md5(time()) . ">\n] >>";
        $fdf .= " \n>> \nendobj\ntrailer\n";
        $fdf .= "<<\n/Root 1 0 R \n\n>>\n%%EOF\n";

        return $fdf;
    }

    protected function mergePDF(array $files, $merge_name = null)
    {
        if (is_null($merge_name))
            $merge_name = 'KIT-' . date('Y-m-d-H-i-s') . '-' . uniqid() . '.pdf';

        $merge_path = storage_path() . '/temp/' . $merge_name;

        if (count($files) <= 0)
            return false;

        exec('pdftk "' . implode('" "', $files) . '" output "' . $merge_path . '"');

        foreach ($files as $file) {
            if (is_string($file) && file_exists($file))
                unlink($file);
        }

        return $merge_path;
    }

    protected function makeZIP(array $files, $zip_name = null)
    {
        if (count($files) <= 0)
            return false;

        if (is_null($zip_name))
            $zip_name = 'KIT-' . date('Y-m-d-H-i-s') . '-' . uniqid() . '.zip';

        $zip_path = storage_path() . '/temp/' . $zip_name;

        $zip = new \ZipArchive();
        if ($zip->open($zip_path, \ZIPARCHIVE::CREATE) !== true)
            return false;

        foreach ($files as $file) {
            if (is_string($file) && file_exists($file))
                $zip->addFile($file, basename($file));
        }

        $zip->close();

        foreach ($files as $file) {
            if (is_string($file) && file_exists($file))
                unlink($file);
        }

        return $zip_path;
    }

}
