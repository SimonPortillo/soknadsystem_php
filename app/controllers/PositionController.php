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
        if (!$user || !in_array($user->getRole(), ['admin', 'employee'])) {
            $this->app->redirect('/positions');
            return;
        }
        
        // Render the create position form
        $this->app->latte()->render(__DIR__ . '/../views/user/create-position.latte', [
            'isLoggedIn' => true,
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
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
        if (!$user || !in_array($user->getRole(), ['admin', 'employee'])) {
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
            $this->app->latte()->render(__DIR__ . '/../views/user/create-position.latte', [
                'isLoggedIn' => true,
                'username' => $user->getUsername(),
                'role' => $user->getRole(),
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
            $this->app->latte()->render(__DIR__ . '/../views/user/create-position.latte', [
                'isLoggedIn' => true,
                'username' => $user->getUsername(),
                'role' => $user->getRole(),
                'errors' => ['Kunne ikke opprette stilling. Prøv igjen.'],
                'title' => $title,
                'department' => $department,
                'location' => $location,
                'description' => $description,
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
        }
    }

    /**
     * Display the edit position form
     * 
     * Shows the form for editing an existing position. Only accessible to users with
     * 'admin' or 'employee' roles. Redirects other users to positions page.
     * 
     * Route: GET /positions/@id/edit
     * 
     * @param int $id The ID of the position to edit
     * @return void
     */

    public function showEdit($id) {
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
        if (!$user || !in_array($user->getRole(), ['admin', 'employee'])) {
            $this->app->redirect('/positions');
            return;
        }

        // fetch the position by ID 
        $positionModel = new Position($this->app->db());
        $position = $positionModel->findById($id, false, false);
        
        // Render the edit position form
        $this->app->latte()->render(__DIR__ . '/../views/user/edit-position.latte', [
            'isLoggedIn' => true,
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
            'csp_nonce' => $this->app->get('csp_nonce'),
            'position' => $position,

        ]); 
    }

    /**
     * Update an existing position
     * param int $id The ID of the position to update
     * route: POST /positions/@id/edit
     * @return void
     */

    public function update($id) {
    // Fetch current position data for comparison
    $positionModel = new Position($this->app->db());
    $currentPosition = $positionModel->findById($id, false, false);

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
        if (!$user || !in_array($user->getRole(), ['admin', 'employee'])) {
            $this->app->redirect('/min-side');
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
            $this->app->latte()->render(__DIR__ . '/../views/user/edit-position.latte', [
                'isLoggedIn' => true,
                'username' => $user->getUsername(),
                'role' => $user->getRole(),
                'errors' => $errors,
                'position' => [
                    'id' => $id,
                    'title' => $title,
                    'department' => $department,
                    'location' => $location,
                    'description' => $description
                ],
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
            return;
        }

        // arrange data in array (the update method below expects an array)
        $data = [
            'title' => $title,
            'department' => $department,
            'location' => $location,
            'description' => $description
        ];

        // Check if data is unchanged
        $unchanged = (
            $currentPosition &&
            $currentPosition['title'] === $title &&
            $currentPosition['department'] === $department &&
            $currentPosition['location'] === $location &&
            ($currentPosition['description'] ?? '') === ($description ?? '')
        );
        if ($unchanged) {
            $this->app->latte()->render(__DIR__ . '/../views/user/edit-position.latte', [
                'isLoggedIn' => true,
                'username' => $user->getUsername(),
                'role' => $user->getRole(),
                'errors' => ['Ingen endringer ble gjort.'],
                'position' => [
                    'id' => $id,
                    'title' => $title,
                    'department' => $department,
                    'location' => $location,
                    'description' => $description
                ],
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
            return;
        }

        // update the position
        $result = $positionModel->update($id, $data);

        if ($result) {
            // Set success message and redirect
            $this->app->session()->set('success_message', 'Stillingen ble oppdatert.');
            $this->app->redirect('/min-side');
        } else {
            // Re-render form with error
            $this->app->latte()->render(__DIR__ . '/../views/user/edit-position.latte', [
                'isLoggedIn' => true,
                'username' => $user->getUsername(),
                'role' => $user->getRole(),
                'errors' => ['Kunne ikke oppdatere stilling. Prøv igjen.'],
                'position' => [
                    'id' => $id,
                    'title' => $title,
                    'department' => $department,
                    'location' => $location,
                    'description' => $description
                ],
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
        }
    }
   
    public function delete($id) {
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
        if (!$user || !in_array($user->getRole(), ['admin', 'employee'])) {
            $this->app->redirect('/positions');
            return;
        }

        // Delete the position
        $positionModel = new Position($this->app->db());
        $result = $positionModel->delete($id);

        if ($result) {
            // Set success message and redirect
            $this->app->session()->set('success_message', 'Stillingen ble slettet.');
        } else {
            // Set error message
            $this->app->session()->set('error_message', 'Kunne ikke slette stilling. Prøv igjen.');
        }
        $this->app->redirect('/min-side');
    }

}