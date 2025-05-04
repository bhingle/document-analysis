<?php

use Illuminate\Support\Facades\Route;
//Default API endpoint
Route::get('/', function () {
    //return ['Laravel' => app()->version()];
    return "";
});

require __DIR__.'/auth.php';
