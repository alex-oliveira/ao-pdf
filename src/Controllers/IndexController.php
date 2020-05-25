<?php

namespace AOPDF\Controllers;

use App\Http\Controllers\Controller;
use AOPDF\AOPDFService;
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
        $data = request()->get('data');

        $file_name = uniqid() . '.aopdf';

        Storage::put($file_name, $data);

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

        if (Storage::exists($file)) {
            abort(401, 'File not found.');
        }

        $data = Storage::get($file);
        Storage::delete($file);

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
        $data = json_decode(base64_decode($data), true);

        $pdf_path = (new AOPDFService())->process($data);

        return response()->download($pdf_path, basename($pdf_path), [])->deleteFileAfterSend(true);
    }

}
