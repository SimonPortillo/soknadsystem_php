<?php

namespace app\controllers;

use flight\Engine;

/**
 * HomeController
 * 
 * Handles requests for the home page and other main application pages.
 * This controller is accessible to both authenticated and guest users.
 * 
 * @package app\controllers
 */
class HomeController {

	/**
	 * @var Engine The FlightPHP Engine instance
	 */
	protected Engine $app;

	/**
	 * Constructor
	 * 
	 * @param Engine $app The FlightPHP Engine instance injected by the framework
	 */
	public function __construct(Engine $app) {
		$this->app = $app;
	}

	/**
	 * Display the home page
	 * 
	 * Renders the main home page template. This page is accessible to all users,
	 * whether authenticated or not. Session data is passed to allow the template
	 * to display different content based on authentication state.
	 * 
	 * Route: GET /
	 * 
	 * @return void
	 */
	public function index() {

		// Retrieve and clear deletion message from session
		$deletionMessage = $this->app->session()->get('deletion_message');
		$this->app->session()->delete('deletion_message');

		$this->app->latte()->render(__DIR__ . '/../views/home/home.latte', [
			'isLoggedIn' => $this->app->session()->get('is_logged_in'),
			'username' => $this->app->session()->get('username'),
			'message' => $deletionMessage,
			'csp_nonce' => $this->app->get('csp_nonce')
		]);
	}
}
