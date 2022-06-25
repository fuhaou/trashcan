<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//Route::get('/user', [UserController::class, 'index']);


Route::get('/qr_scan', function () {
    return 'Hello Worldasd';
});
Route::get('/admin/login',  [AdminController::class, 'login']);
Route::post('/admin/login',  [AdminController::class, 'loginSubmit']);



Route::redirect('/' , '/admin');

Route::middleware([

])->group(function () {
    Route::get('/admin', function () {
        return 'Hello World';
    });
    Route::get('/admin/create_qr', function () {
        return 'Hello World';
    });
    Route::get('/admin/stats_group', function () {
        return 'Hello World';
    });
    Route::get('/admin/stats_group_type', function () {
        return 'Hello World';
    });
    Route::get('/admin/stats_line_week_group', function () {
        return 'Hello World';
    });
});
