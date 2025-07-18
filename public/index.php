<?php

/**
 * Front controller
 *
 * PHP version 7.0
 */

session_start();

/**
 * Composer
 */
require dirname(__DIR__) . '/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Error and Exception handling
 */
error_reporting(E_ALL);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');


/**
 * Routing
 */
$router = new Core\Router();

// Add the routes
$router->add('', ['controller' => 'Home', 'action' => 'index']);
$router->add('login', ['controller' => 'User', 'action' => 'login']);
$router->add('register', ['controller' => 'User', 'action' => 'register']);
$router->add('logout', ['controller' => 'User', 'action' => 'logout', 'private' => true]);
$router->add('account', ['controller' => 'User', 'action' => 'account', 'private' => true]);
$router->add('product', ['controller' => 'Product', 'action' => 'index', 'private' => true]);
$router->add('product/{id:\d+}', ['controller'=>'Product','action'=>'show']);
$router->add('product/{id:\d+}', ['controller'=>'Product','action'=>'show'], 'POST');
$router->add('product/delete/{id:\d+}', ['controller' => 'Product', 'action' => 'delete'], 'POST');
$router->add('api/search', ['controller' => 'Api', 'action' => 'Search']);
$router->add('forgot', ['controller' => 'User', 'action' => 'forgotPassword']);
$router->add('reset-code', ['controller' => 'User', 'action' => 'resetCode']);
$router->add('resend-code', ['controller' => 'User', 'action' => 'resendCode']);
$router->add('reset-password', ['controller' => 'User', 'action' => 'resetPassword']);
$router->add('{controller}/{action}');
/*
 * Gestion des erreurs dans le routing
 */
try {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = ltrim($path, '/');
    $router->dispatch($path);
} catch(Exception $e){
    switch($e->getMessage()){
        case 'You must be logged in':
            header('Location: /login');
            break;
    }
}
