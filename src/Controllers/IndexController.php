<?php

namespace AOPDF\Controllers;

use AOPDF\AOPDF;
use AOPDF\AOPDFService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class IndexController extends Controller
{

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     *
     * @throws \Exception
     */
    public function fillByGet()
    {
        return $this->process(request()->get('data'));
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function fillByPost()
    {
        $file_name = AOPDF::uniqid('.txt');
        $file_content = request()->get('data', []);

        AOPDF::disk()->put('tmp/' . $file_name, $file_content);

        return response()->json(['file_name' => $file_name]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     *
     * @throws \Exception
     */
    public function download()
    {
        $file = request()->get('file');

        if (empty($file)) {
            abort(412, 'File is required.');
        }

        $disk = AOPDF::disk();

        $file_location = 'tmp/' . $file;
        if ($disk->exists($file_location) == false) {
            abort(401, 'File not found.');
        }

        $data = $disk->get($file_location);
        $disk->delete($file_location);

        return $this->process($data);
    }

    /**
     * @param $data
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     *
     * @throws \Exception
     */
    protected function process($data)
    {
        $disk = AOPDF::disk();

        $cache_location = 'cache/' . md5($data) . '.txt';

        $data = json_decode(base64_decode($data), true);

        $file_path = null;

        if ($disk->exists($cache_location)) {
            $path = $disk->get($cache_location);
            $disk->exists($path) ? $file_path = $path : $disk->delete($cache_location);
        }

        if (empty($file_path)) {
            $file_path = (new AOPDFService())->process($data);
            $disk->put($cache_location, $file_path);
        }

        $file_name = Arr::get($data, 'config.output_name', 'document.pdf');

        return response()->download($disk->path($file_path), $file_name);
    }

}
