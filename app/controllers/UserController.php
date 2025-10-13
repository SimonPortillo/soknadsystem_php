<?php

namespace app\controllers;

use flight\Engine;
use app\models\User;

/**
 * UserController
 * 
 * Handles user profile management functionality including viewing and editing
 * user profile information.
 * 
 * Responsibilities:
 *   - Display user profile page ("Min Side")
 *   - Allow users to view their profile information
 *   - Allow users to edit certain profile fields
 *   - Protect profile routes (require authentication)
 * 
 * @package app\controllers
 */
class UserController {

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
     * Display the user profile page (Min Side)
     * 
     * Shows the authenticated user's profile information with fields for viewing
     * and editing. Some fields are disabled (cannot be changed), while others
     * can be edited.
     * 
     * Route: GET /min-side
     * 
     * Disabled fields (cannot be changed):
     *   - Username
     *   - Email
     *   - Role
     *   - Account creation date
     * 
     * Editable fields (can be changed):
     *   - Full name
     *   - Phone number
     *   - Password (via separate action)
     * 
     * @return void Redirects to login if not authenticated, renders profile page otherwise
     */
    public function index() {
        // Redirect to login if not authenticated
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        // Get the current user's ID from session
        $userId = $this->app->session()->get('user_id');
        
        // Fetch user data from database
        $userModel = new User($this->app->db());
        $user = $userModel->findById($userId);
        
        if (!$user) {
            // User not found - clear session and redirect to login
            $this->app->session()->clear();
            $this->app->redirect('/login');
            return;
        }

        // Render the profile page with user data
        $this->app->latte()->render(__DIR__ . '/../views/user/min-side.latte', [
            'isLoggedIn' => true,
            'username' => $user->username,
            'user' => $user
        ]);
    }
}
