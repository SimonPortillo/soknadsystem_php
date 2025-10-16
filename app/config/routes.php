<?php

/**
 * Route Configuration
 * 
 * This file defines all application routes using FlightPHP's routing system.
 * Routes are organized by functionality (home, auth, API) and wrapped in
 * middleware groups for security headers and other cross-cutting concerns.
 * 
 * Route Structure:
 *   - All routes use controller methods (following FlightPHP best practices)
 *   - Authentication checks are handled within controller methods
 *   - Middleware is applied via route groups
 * 
 * @var Router $router FlightPHP Router instance
 * @var Engine $app    FlightPHP Engine instance
 */

use app\controllers\ApiExampleController;
use app\controllers\HomeController;
use app\controllers\AuthController;
use app\controllers\UserController;
use app\controllers\DocumentController;
use app\middlewares\SecurityHeadersMiddleware;
use flight\Engine;
use flight\net\Router;

/** 
 * @var Router $router 
 * @var Engine $app
 */

/**
 * Main Route Group
 * 
 * All routes are wrapped in this group which applies SecurityHeadersMiddleware
 * to add security headers (CSP, X-Frame-Options, etc.) to all responses.
 */
$router->group('', function(Router $router) use ($app) {

	/**
	 * Home Route
	 * Displays the main home page. Accessible to all users.
	 */
	$router->get('/', [ HomeController::class, 'index' ]);

	/**
	 * Authentication Routes
	 * 
	 * Each controller method handles its own authentication checks:
	 *   - Guest routes (login/register) redirect authenticated users to /positions
	 *   - Protected routes (positions/logout) redirect guests to /login
	 */
	
	// Display login form (guest only)
	$router->get('/login', [ AuthController::class, 'index' ]);
	
	// Process login form submission
	$router->post('/login', [ AuthController::class, 'login' ]);
	
	// Display registration form (guest only)
	$router->get('/register', [ AuthController::class, 'showRegister' ]);
	
	// Process registration form submission
	$router->post('/register', [ AuthController::class, 'register' ]);
	
	// Display job positions page (authenticated users only)
	$router->get('/positions', [ AuthController::class, 'showPositions' ]);
	
	// Logout user and destroy session (authenticated users only)
	$router->get('/logout', [ AuthController::class, 'logout' ]);

	/**
	 * User Profile Routes
	 * 
	 * Routes for managing user profile information.
	 * All routes require authentication (checked within controller methods).
	 */
	
	// Display user profile page "Min Side" (authenticated users only)
	$router->get('/min-side', [ UserController::class, 'index' ]);

	$router->post('/min-side/update', [ UserController::class, 'update' ]);
	
	$router->post('/min-side/delete', [ UserController::class, 'delete' ]);

	/**
	 * Document Routes
	 */
	$router->post('/upload-document', [ DocumentController::class, 'upload' ]);
	
	/**
	 * API Route Group
	 * 
	 * RESTful API endpoints for user management.
	 * These routes may require additional authentication/authorization in production.
	 */
	$router->group('/api', function() use ($router) {
		// Get all users
		$router->get('/users', [ ApiExampleController::class, 'getUsers' ]);
		
		// Get specific user by ID
		$router->get('/users/@id:[0-9]', [ ApiExampleController::class, 'getUser' ]);
		
		// Update specific user by ID
		$router->post('/users/@id:[0-9]', [ ApiExampleController::class, 'updateUser' ]);
	});
	
}, [ SecurityHeadersMiddleware::class ]);