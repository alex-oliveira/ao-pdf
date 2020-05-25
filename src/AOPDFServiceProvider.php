<?php

namespace AOPDF;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AOPDFServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->commands([
            \AOPDF\Commands\StorageClean::class,
        ]);
    }

    public function register()
    {
        Route::group([
            'namespace' => 'AOPDF\Controllers',
            'as' => 'pdf.',
            'prefix' => 'pdf',
            'middleware' => []
        ], function () {

            Route::get('fill', ['as' => 'fill-by-get', 'uses' => 'IndexController@fillByGet']);
            Route::post('fill', ['as' => 'fill-by-post', 'uses' => 'IndexController@fillByPost']);
            Route::get('download', ['as' => 'download', 'uses' => 'IndexController@download']);

            Route::get('test', ['as' => 'test', 'uses' => 'TestController@test']);

        });
    }

}