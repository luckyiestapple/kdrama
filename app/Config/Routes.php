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
|   GET    api/kdramas             -> Kdramas::index
|   POST   api/kdramas             -> Kdramas::create
|   GET    api/kdramas/search/(*)  -> Kdramas::search/*
|   GET    api/kdramas/(.*)        -> Kdramas::show
|   PUT    api/kdramas/(.*)        -> Kdramas::update
|   PATCH  api/kdramas/(.*)        -> Kdramas::update
|   DELETE api/kdramas/(.*)        -> Kdramas::delete
|
| 'except' membuang new & edit karena keduanya hanya untuk form HTML.
|
| PENTING: route search HARUS ditulis sebelum resource(). Kalau dibalik,
| GET api/kdramas/search/iberia akan tertangkap oleh route
| api/kdramas/(.*) dan memanggil show("search") yang hasilnya 404.
*/

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    $routes->get('kdramas/search/(:segment)', 'Kdramas::search/$1');

    $routes->resource('kdramas', ['except' => 'new,edit']);
});
