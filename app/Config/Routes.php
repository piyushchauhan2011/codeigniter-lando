<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('/hello', 'Tutorial::hello');

$routes->get('/posts', 'Tutorial::posts');
$routes->get('/posts/new', 'Tutorial::newPost');
$routes->post('/posts', 'Tutorial::createPost');
