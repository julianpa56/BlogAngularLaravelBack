<?php

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

//Cargando clases

use App\Http\Middleware\ApiAuthMiddleware;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/pruebas/{nombre}',function($nombre){
   $texto = '<h1>Haciendo pruebas de nuevas rutas </h1>';
   $texto .= 'Nombre: '.$nombre;
   return $texto;
});

Route::get('/prueba_con_view/{nombre?}',function($nombre = null){
   $texto = '<h1>Haciendo pruebas de nuevas rutas </h1>';
   $texto .= 'Nombre: '.$nombre;
   return view('pruebas',array(
       'texto' => $texto
   ));
});


Route::get('/pruebas_controlador/animales','App\Http\Controllers\PruebasController@index');

Route::get('/test-orm','App\Http\Controllers\PruebasController@testOrm');

//  RUTAS DEL API

    /* METODOS HTTP
        - Get ( ruta , controlador + @ + funcion)
        - Post ( ruta , controlador + @ + funcion)
        - Put ( ruta , controlador + @ + funcion)
        - Delete ( ruta , controlador + @ + funcion)
     * 
     *  SI SE USA MIDDLEWARE "->middleware(Middleware::class)"
    */

    //Pruebas

    // Route::get('/users/pruebas','App\Http\Controllers\UserController@pruebas');

    // Route::get('/posts/pruebas','App\Http\Controllers\PostController@pruebas');

    // Route::get('/categories/pruebas','App\Http\Controllers\CategoryController@pruebas');
    
    // Rutas usuarios
    
    Route::post('/users/register','App\Http\Controllers\UserController@register');
    Route::post('/users/login','App\Http\Controllers\UserController@login');
    Route::put('/users/update','App\Http\Controllers\UserController@update');
    Route::post('/users/upload','App\Http\Controllers\UserController@upload')->middleware(ApiAuthMiddleware::class);
    Route::get('/users/avatar/{filename}','App\Http\Controllers\UserController@getImage');
    Route::get('/users/detail/{id}','App\Http\Controllers\UserController@detail');
    
    
    // RUTAS DEL CONTROLADOR DE CATEGORIA - RUTAS AUTOMARICAS
    
    Route::resource('/category', 'App\Http\Controllers\CategoryController');
    
    // RUTAS DEL CONTROLADOR POST - RUTAS AUTOMATICAS
    
    Route::resource('/post', 'App\Http\Controllers\PostController');
    
    // RUTAS ADICIONALES 
    Route::post('/post/upload','App\Http\Controllers\PostController@upload');
    Route::get('/post/image/{filename}','App\Http\Controllers\PostController@getImage');
    Route::get('/post/category/{id}','App\Http\Controllers\PostController@getPostsByCategory');
    Route::get('/post/user/{id}','App\Http\Controllers\PostController@getPostsByUser');