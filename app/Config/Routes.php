<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

/*
| ---------------------------------------------------------------------------
| API Kdramas
| ---------------------------------------------------------------------------
| Mengikuti app/README_KDRAMAS.md bagian 11.
| Controller: app/Controllers/Api/Kdramas.php
|
| Route yang dihasilkan:
|   GET    api/kdramas      -> Kdramas::index
|   POST   api/kdramas      -> Kdramas::create
|   GET    api/kdramas/(.*) -> Kdramas::show
|   PUT    api/kdramas/(.*) -> Kdramas::update
|   PATCH  api/kdramas/(.*) -> Kdramas::update
|   DELETE api/kdramas/(.*) -> Kdramas::delete
|
| 'except' membuang new & edit karena keduanya hanya untuk form HTML.
*/

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->resource('kdramas', ['except' => 'new,edit']);
});
