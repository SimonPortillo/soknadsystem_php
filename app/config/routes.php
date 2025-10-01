<?php

use app\controllers\ApiExampleController;
use app\controllers\HomeController;
use app\controllers\AuthController;
use app\middlewares\SecurityHeadersMiddleware;
use flight\Engine;
use flight\net\Router;

/** 
 * @var Router $router 
 * @var Engine $app
 */

// This wraps all routes in the group with the SecurityHeadersMiddleware
$router->group('', function(Router $router) use ($app) {

	$router->get('/', [ HomeController::class, 'index' ]);

	$router->get('/login', [ AuthController::class, 'index' ]);
	$router->post('/login', [ AuthController::class, 'login' ]);

	$router->get('/register', [AuthController::class, 'showRegister' ]);
	$router->post('/register', [AuthController::class, 'register' ]);

	$router->group('/api', function() use ($router) {
		$router->get('/users', [ ApiExampleController::class, 'getUsers' ]);
		$router->get('/users/@id:[0-9]', [ ApiExampleController::class, 'getUser' ]);
		$router->post('/users/@id:[0-9]', [ ApiExampleController::class, 'updateUser' ]);
	});
	
}, [ SecurityHeadersMiddleware::class ]);