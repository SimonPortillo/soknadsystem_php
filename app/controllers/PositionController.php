<?php

namespace app\controllers;

use flight\Engine;
use app\models\Position;
use app\models\User;
use app\models\Application;
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
     * Display the positions page
     * 
     * Shows the job positions page to authenticated users. If the user is not
     * authenticated, they are redirected to the login page.
     * 
     * Route: GET /positions
     * 
     * @return void
     */
    public function showPositions() {
        // Redirect to login if not authenticated
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }
        
        // Get all positions
        $positionModel = new Position($this->app->db());
        $positions = $positionModel->getAll();
        // used to show count of open positions in navbar
        $openPositionsCount = $positionModel->getCount();
        
        // For students, get the positions they have applied to
        $appliedPositionIds = [];
        $userId = $this->app->session()->get('user_id');
        $role = $this->app->session()->get('role');
        
        if ($role === 'student') {
            $applicationModel = new Application($this->app->db());
            $userApplications = $applicationModel->getByUser($userId);
            
            // Extract position IDs from user's applications
            foreach ($userApplications as $application) {
                $appliedPositionIds[] = $application['position_id'];
            }
        }
        
        // Get any success message from session and clear it
        $successMessage = $this->app->session()->get('login_success') 
            ?? $this->app->session()->get('position_success')
            ?? $this->app->session()->get('registration_success')
            ?? $this->app->session()->get('application_success');
        
        $this->app->session()->delete('login_success');
        $this->app->session()->delete('position_success');
        $this->app->session()->delete('registration_success');
        $this->app->session()->delete('application_success');
        
        // Get any error message from session and clear it
        $errorMessage = $this->app->session()->get('position_error')
            ?? $this->app->session()->get('application_error');
        
        $this->app->session()->delete('position_error');
        $this->app->session()->delete('application_error');


        $this->app->latte()->render(__DIR__ . '/../views/user/positions.latte', [
            'isLoggedIn' => true,
            'username' => $this->app->session()->get('username'),
            'role' => $role,
            'userId' => $userId,
            'positions' => $positions,
            'openPositionsCount' => $openPositionsCount,
            'appliedPositionIds' => $appliedPositionIds,
            'message' => $successMessage,
            'errors' => $errorMessage ? [$errorMessage] : null,
            'csp_nonce' => $this->app->get('csp_nonce')
        ]);
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
        
        // Get position count for navbar
        $positionModel = new Position($this->app->db());
        $openPositionsCount = $positionModel->getCount();
        
        // Base view data
        $viewData = [
            'isLoggedIn' => true,
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
            'openPositionsCount' => $openPositionsCount,
            'csp_nonce' => $this->app->get('csp_nonce')
        ];
        
        // Render the create position form
        $this->app->latte()->render(__DIR__ . '/../views/user/create-position.latte', $viewData);
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
        $amount = $data->amount ?? 1;
        $description = $data->description ?? null;
        $resourceUrl = $data->resource_url ?? null;
        
        // Instantiate position model
        $positionModel = new Position($this->app->db());
        
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
        if ($amount < 1 || $amount > 25) {
            $errors[] = 'Antall stillinger må være mellom 1 og 25.';
        }
        if (!empty($resourceUrl) && filter_var($resourceUrl, FILTER_VALIDATE_URL) === false) {
            $errors[] = 'Universitetsressurs må være en gyldig URL.';
        }
        
        // Base view data for re-renders
        $openPositionsCount = $positionModel->getCount();
        $baseViewData = [
            'isLoggedIn' => true,
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
            'openPositionsCount' => $openPositionsCount,
            'csp_nonce' => $this->app->get('csp_nonce'),
            'title' => $title,
            'department' => $department,
            'location' => $location,
            'amount' => $amount,
            'description' => $description,
            'resource_url' => $resourceUrl,
        ];
        
	    // If validation fails, re-render form with errors
        if (!empty($errors)) {
            $viewData = array_merge($baseViewData, ['errors' => $errors]);
            $this->app->latte()->render(__DIR__ . '/../views/user/create-position.latte', $viewData);
            return;
        }
        
        // Create the position
        $result = $positionModel->create($userId, $title, $department, $location, $amount, $description, $resourceUrl);
        
        if ($result) {
            // Set success message and redirect
            $this->app->session()->set('position_success', 'Stillingen ble opprettet.');
            $this->app->redirect('/positions');
        } else {
		    // Re-render form with error
            $viewData = array_merge($baseViewData, ['errors' => ['Kunne ikke opprette stilling. Prøv igjen.']]);
            $this->app->latte()->render(__DIR__ . '/../views/user/create-position.latte', $viewData);
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
        
        // Get position count for navbar
        $openPositionsCount = $positionModel->getCount();
        
        // Base view data
        $viewData = [
            'isLoggedIn' => true,
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
            'csp_nonce' => $this->app->get('csp_nonce'),
            'position' => $position,
            'openPositionsCount' => $openPositionsCount,
        ];
        
        // Render the edit position form
        $this->app->latte()->render(__DIR__ . '/../views/user/edit-position.latte', $viewData);
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
        $amount = $data->amount ?? 1;
        $description = $data->description ?? null;
        $resourceUrl = $data->resource_url ?? null;
        
        // Instantiate position model and get current position
        $positionModel = new Position($this->app->db());
        $openPositionsCount = $positionModel->getCount();
        
        // Base view data for re-renders
        $baseViewData = [
            'isLoggedIn' => true,
            'username' => $user->getUsername(),
            'role' => $user->getRole(),
            'openPositionsCount' => $openPositionsCount,
            'csp_nonce' => $this->app->get('csp_nonce'),
            'position' => [
                'id' => $id,
                'title' => $title,
                'department' => $department,
                'location' => $location,
                'amount' => $amount,
                'description' => $description,
                'resource_url' => $resourceUrl
            ]
        ];
        
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
            $viewData = array_merge($baseViewData, ['errors' => $errors]);
            $this->app->latte()->render(__DIR__ . '/../views/user/edit-position.latte', $viewData);
            return;
        }
        
        // arrange data in array (the update method below expects an array)
        $data = [
            'title' => $title,
            'department' => $department,
            'location' => $location,
            'amount' => $amount,
            'description' => $description,
            'resource_url' => $resourceUrl
        ];

        // Check if data is unchanged
        $unchanged = (
            $currentPosition &&
            $currentPosition['title'] === $title &&
            $currentPosition['department'] === $department &&
            $currentPosition['location'] === $location &&
            ($currentPosition['amount'] ?? 1) === ($amount ?? 1) &&
            ($currentPosition['description'] ?? '') === ($description ?? '') &&
            ($currentPosition['resource_url'] ?? '') === ($resourceUrl ?? '')
        );
	    if ($unchanged) {
            $viewData = array_merge($baseViewData, ['errors' => ['Ingen endringer ble gjort.']]);
            $this->app->latte()->render(__DIR__ . '/../views/user/edit-position.latte', $viewData);
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
            $viewData = array_merge($baseViewData, ['errors' => ['Kunne ikke oppdatere stilling. Prøv igjen.']]);
            $this->app->latte()->render(__DIR__ . '/../views/user/edit-position.latte', $viewData);
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