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

use app\controllers\HomeController;
use app\controllers\AuthController;
use app\controllers\UserController;
use app\controllers\DocumentController;
use app\controllers\PositionController;
use app\controllers\ApplicationController;
use app\middlewares\SecurityHeadersMiddleware;
use flight\Engine;
use flight\net\Router;

/** 
 * @var Router $router 
 * @var Engine $app
 */

Flight::map('notFound', function () {
    // Render a custom 404 page
    Flight::latte()->render(__DIR__ . '/../views/errors/404.latte', [
        'title' => 'Page Not Found',
		'csp_nonce' => Flight::get('csp_nonce'),
    ]);
});

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
	 * Authenticated Routes
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

	// Display password reset form (guest only)
	$router->get('/reset-password', [ AuthController::class, 'showResetPassword' ]);

	// Process password reset form submission
	$router->post('/reset-password', [ AuthController::class, 'resetPassword' ]);

	// Display set new password form (guest only)
	$router->get('/reset-password/@token', [ AuthController::class, 'showSetNewPassword' ]);
	
	// Process set new password form submission
	$router->post('/reset-password/@token', [ AuthController::class, 'setNewPassword' ]);

	// Logout user and destroy session (authenticated users only)
	$router->get('/logout', [ AuthController::class, 'logout' ]);
	
	// Display job positions page (authenticated users only)
	$router->get('/positions', [ PositionController::class, 'showPositions' ]);
	
	// Display create position form (admin/employee users only)
	$router->get('/positions/create', [ PositionController::class, 'showCreate' ]);
	
	// Process position creation form
	$router->post('/positions/create', [ PositionController::class, 'create' ]);

	// Edit a position
	$router->get('/positions/@id:[0-9]+/edit', [ PositionController::class, 'showEdit' ]);
	$router->post('/positions/@id:[0-9]+/edit', [ PositionController::class, 'update' ]);

	// Delete a position
	$router->post('/positions/@id:[0-9]+/delete', [ PositionController::class, 'delete' ]);
	
	// Apply for a position
	$router->get('/positions/@id:[0-9]+/apply', [ ApplicationController::class, 'index' ]);
	$router->post('/positions/@id:[0-9]+/apply', [ ApplicationController::class, 'apply' ]);

	// View applicants for a position (admin/employee only)
	$router->get('/positions/@id:[0-9]+/applicants', [ ApplicationController::class, 'viewApplicants' ]);

	// Update application status (admin/employee only)
	$router->post('/positions/@id:[0-9]+/applicants/@applicationId:[0-9]+/status', [ ApplicationController::class, 'updateStatus' ]);
	// Withdraw an application (authenticated users only)
	$router->post('/applications/@applicationId:[0-9]+/delete', [ ApplicationController::class, 'delete' ]);


	/**
	 * User Profile Routes
	 * 
	 * Routes for managing user profile information and documents.
	 * All routes require authentication (checked within controller methods).
	 */
	
	// Display user profile page "Min Side" (authenticated users only)
	$router->get('/min-side', [ UserController::class, 'index' ]);

	// Update user profile information
	$router->post('/min-side/update', [ UserController::class, 'update' ]);

	// Delete user account and associated documents
	$router->post('/min-side/delete', [ UserController::class, 'delete' ]);

	// Admin: Update user role
	$router->post('/admin/users/update-role', [ UserController::class, 'updateUserRole' ]);

	// Admin: Delete user
	$router->post('/admin/users/delete', [ UserController::class, 'deleteUser' ]);

	/**
	 * Document Management Routes (under min-side)
	 */

	// Upload user documents (CV, cover letter)
	$router->post('/min-side/upload', [ DocumentController::class, 'upload' ]);

	// Delete user document by ID
	$router->post('/min-side/documents/delete', [ DocumentController::class, 'delete' ]);

	// Download document file
	$router->get('/documents/@documentId:[0-9]+/download', [ DocumentController::class, 'download' ]);
	
}, [ SecurityHeadersMiddleware::class ]);