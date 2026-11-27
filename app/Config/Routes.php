<?php
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */
$routes->setAutoRoute(false);
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::authenticate');
$routes->group('', ['filter'=>'staff'], static function($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->post('logout', 'Auth::logout');
    $routes->get('media/(:segment)', 'Catalog::media/$1');
    foreach (['products','customers','users'] as $entity) {
        $routes->get($entity, 'Catalog::index/'.$entity);
        $routes->get($entity.'/new', 'Catalog::form/'.$entity);
        $routes->get($entity.'/(:num)/edit', 'Catalog::form/'.$entity.'/$1');
        $routes->post($entity.'/save', 'Catalog::save/'.$entity);
        $routes->post($entity.'/(:num)/delete', 'Catalog::delete/'.$entity.'/$1');
    }
    $routes->get('sales/new', 'Sales::create');
    $routes->post('sales', 'Sales::store');
    $routes->get('sales', 'Sales::index');
});
