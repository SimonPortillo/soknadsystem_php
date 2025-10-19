<?php

namespace app\controllers;

use flight\Engine;
use app\models\Position;
use app\models\User;
/**
 * PositionController
 * 
 * Handles position management functionality including creating, viewing,
 * editing, and deleting job positions.
 * 
 * Responsibilities:
 *   - Display position management pages
 *   - Handle position creation
 *   - Handle position editing
 *   - Handle position deletion
 *   - Enforce role-based access control (Admin/Employee only)
 * 
 * @package app\controllers
 */
class PositionController {

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
     * Display the create position form
     * 
     * Shows the form for creating a new position. Only accessible to users with
     * 'admin' or 'employee' roles. Redirects other users to positions page.
     * 
     * Route: GET /positions/create
     * 
     * @return void
     */
    public function showCreate() {
        // Redirect to login if not authenticated
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        // Get the current user's role
        $userId = $this->app->session()->get('user_id');
        $userModel = new User($this->app->db());
        $user = $userModel->findById($userId);
        
        // Only allow admin and employee roles
        if (!$user || !in_array($user->role, ['admin', 'employee'])) {
            $this->app->redirect('/positions');
            return;
        }
        
        // Render the create position form
        $this->app->latte()->render(__DIR__ . '/../views/auth/create-position.latte', [
            'isLoggedIn' => true,
            'username' => $user->username,
            'role' => $user->role,
            'csp_nonce' => $this->app->get('csp_nonce')
        ]);
    }

    /**
     * Process the creation of a new position
     * 
     * Handles form submission for creating a new position.
     * Only accessible to users with 'admin' or 'employee' roles.
     * 
     * Route: POST /positions/create
     * 
     * @return void
     */
    public function create() {
        // Redirect to login if not authenticated
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        // Get the current user's role
        $userId = $this->app->session()->get('user_id');
        $userModel = new User($this->app->db());
        $user = $userModel->findById($userId);
        
        // Only allow admin and employee roles
        if (!$user || !in_array($user->role, ['admin', 'employee'])) {
            $this->app->redirect('/positions');
            return;
        }
        
        // Get form data
        $data = $this->app->request()->data;
        $title = $data->title ?? '';
        $department = $data->department ?? '';
        $location = $data->location ?? '';
        $description = $data->description ?? null;
        
        // Validate required fields
        $errors = [];
        if (empty($title)) {
            $errors[] = 'Stillingstittel er påkrevd.';
        }
        if (empty($department)) {
            $errors[] = 'Avdeling er påkrevd.';
        }
        if (empty($location)) {
            $errors[] = 'Lokasjon er påkrevd.';
        }
        
        // If validation fails, re-render form with errors
        if (!empty($errors)) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/create-position.latte', [
                'isLoggedIn' => true,
                'username' => $user->username,
                'role' => $user->role,
                'errors' => $errors,
                'title' => $title,
                'department' => $department,
                'location' => $location,
                'description' => $description,
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
            return;
        }
        
        // Create the position
        $positionModel = new Position($this->app->db());
        $result = $positionModel->create($userId, $title, $department, $location, $description);
        
        if ($result) {
            // Set success message and redirect
            $this->app->session()->set('position_success', 'Stillingen ble opprettet.');
            $this->app->redirect('/positions');
        } else {
            // Re-render form with error
            $this->app->latte()->render(__DIR__ . '/../views/auth/create-position.latte', [
                'isLoggedIn' => true,
                'username' => $user->username,
                'role' => $user->role,
                'errors' => ['Kunne ikke opprette stilling. Prøv igjen.'],
                'title' => $title,
                'department' => $department,
                'location' => $location,
                'description' => $description,
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
        }
    }
   
}