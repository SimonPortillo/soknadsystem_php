<?php

use app\controllers\ApiExampleController;
use app\controllers\HomeController;
use app\controllers\AuthController;
use app\middlewares\SecurityHeadersMiddleware;
use app\middlewares\AuthMiddleware;
use flight\Engine;
use flight\net\Router;

/** 
 * @var Router $router 
 * @var Engine $app
 */

// Create auth middleware instance
$authMiddleware = new AuthMiddleware($app);

// This wraps all routes in the group with the SecurityHeadersMiddleware
$router->group('', function(Router $router) use ($app) {

	// Home route
	$router->get('/', [ HomeController::class, 'index' ]);

	// Auth routes - each controller method handles its own auth checks
	$router->get('/login', [ AuthController::class, 'index' ]);
	$router->post('/login', [ AuthController::class, 'login' ]);
	$router->get('/register', [ AuthController::class, 'showRegister' ]);
	$router->post('/register', [ AuthController::class, 'register' ]);
	$router->get('/positions', [ AuthController::class, 'showPositions' ]);
	$router->get('/logout', [ AuthController::class, 'logout' ]);
	
	// API routes
	$router->group('/api', function() use ($router) {
		$router->get('/users', [ ApiExampleController::class, 'getUsers' ]);
		$router->get('/users/@id:[0-9]', [ ApiExampleController::class, 'getUser' ]);
		$router->post('/users/@id:[0-9]', [ ApiExampleController::class, 'updateUser' ]);
	});

	$router->group('/api', function() use ($router) {
		$router->get('/users', [ ApiExampleController::class, 'getUsers' ]);
		$router->get('/users/@id:[0-9]', [ ApiExampleController::class, 'getUser' ]);
		$router->post('/users/@id:[0-9]', [ ApiExampleController::class, 'updateUser' ]);
	});
	
}, [ SecurityHeadersMiddleware::class ]);