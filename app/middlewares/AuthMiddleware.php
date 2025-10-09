<?php

namespace app\middlewares;

use flight\Engine;

/**
 * AuthMiddleware
 * 
 * Middleware for handling authentication checks and protecting routes.
 * This middleware uses the FlightPHP session library to verify user authentication state.
 * 
 * Usage in routes:
 *   - Call isAuthenticated() to protect routes that require login
 *   - Call isGuest() to prevent logged-in users from accessing guest-only pages (login/register)
 * 
 * @package app\middlewares
 */
class AuthMiddleware {
    
    /**
     * @var Engine The FlightPHP Engine instance
     */
    protected Engine $app;
    
    /**
     * Constructor
     * 
     * @param Engine $app The FlightPHP Engine instance
     */
    public function __construct(Engine $app) {
        $this->app = $app;
    }
    
    /**
     * Check if the user is authenticated
     * 
     * Verifies that the user has a valid session with 'is_logged_in' set to true.
     * If the user is not authenticated, they are redirected to the login page.
     * 
     * Use this method to protect routes that require authentication.
     * 
     * @return bool True if user is authenticated, false if redirected to login
     */
    public function isAuthenticated() {
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return false;
        }
        return true;
    }
    
    /**
     * Check if the user is a guest (not authenticated)
     * 
     * Verifies that the user does NOT have an active session.
     * If the user is already logged in, they are redirected to the positions page.
     * 
     * Use this method for guest-only pages like login and registration forms
     * to prevent authenticated users from accessing them unnecessarily.
     * 
     * @return bool True if user is a guest, false if redirected to positions page
     */
    public function isGuest() {
        if ($this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/positions');
            return false;
        }
        return true;
    }
}