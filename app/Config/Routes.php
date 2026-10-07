<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

/*
| ---------------------------------------------------------------------------
| API Kdramas
| ---------------------------------------------------------------------------
| Controller: app/Controllers/Api/Kdramas.php
| Prefix URL: /api/kdramas
|
| Daftar route yang dibuat oleh $routes->resource():
|   GET    /api/kdramas              Api\Kdramas::index
|   POST   /api/kdramas              Api\Kdramas::create
|   GET    /api/kdramas/{id}         Api\Kdramas::show
|   PUT    /api/kdramas/{id}         Api\Kdramas::update
|   PATCH  /api/kdramas/{id}         Api\Kdramas::update
|   DELETE /api/kdramas/{id}         Api\Kdramas::delete
|
| 'except' membuang new & edit karena keduanya hanya untuk form HTML,
| tidak relevan untuk API.
*/

$routes->get('api/kdramas/search/(:segment)', 'Api\Kdramas::search/$1');

$routes->resource('api/kdramas', [
    'controller'  => 'Api\Kdramas',
    'placeholder' => '(:num)',
    'except'      => 'new,edit',
]);
