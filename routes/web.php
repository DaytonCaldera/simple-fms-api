<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes();

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::post('/upload', [App\Http\Controllers\HomeController::class, 'store'])->name('file.store');
Route::post('/agregar-carpeta', [App\Http\Controllers\FMSController::class, 'addFolder'])->name('add.folder');
Route::get('/data',[App\Http\Controllers\FMSController::class,'listFiles'])->name('api.get.data');
Route::patch('/editar',[App\Http\Controllers\FMSController::class,'renameFile'])->name('api.patch.data');
Route::delete('/eliminar',[App\Http\Controllers\FMSController::class,'deleteFile'])->name('api.delete.data');
Route::get('/{fileName}', [App\Http\Controllers\FMSController::class, 'show'])->where('fileName', '^(?!(favicon\.ico|robots\.txt)).*$');
