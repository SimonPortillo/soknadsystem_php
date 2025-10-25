<?php

namespace app\controllers;

use flight\Engine;
use app\models\User;
use app\models\Document;
use app\models\Application;

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

        $nonce = $this->app->get('csp_nonce');
        
        // Fetch user data from database
        $userModel = new User($this->app->db());
        $user = $userModel->findById($userId);
        
        if (!$user) {
            // User not found - clear session and redirect to login
            $this->app->session()->clear();
            $this->app->redirect('/login');
            return;
        }

        // Get flash messages and clear them
        $successMessage = $this->app->session()->get('success_message');
        $errorMessage = $this->app->session()->get('error_message');
        $this->app->session()->delete('success_message');
        $this->app->session()->delete('error_message');

        // Fetch user's documents 
        $docModel = new Document($this->app->db());
        $cvDocuments = $docModel->findByUser($userId, 'cv');
        $coverLetterDocuments = $docModel->findByUser($userId, 'cover_letter'); 
        
        // Fetch user's applications
        $applicationModel = new Application($this->app->db());
        $applications = $applicationModel->getByUser($userId);

        // Render the profile page with user data
        $this->app->latte()->render(__DIR__ . '/../views/user/min-side.latte', [
            'isLoggedIn' => true,
            'username' => $user->getUsername(),
            'user' => $user,
            'csp_nonce' => $nonce,
            'message' => $successMessage,
            'errors' => $errorMessage,
            'cv_documents' => $cvDocuments, 
            'cover_letter_documents' => $coverLetterDocuments,
            'applications' => $applications,
        ]);
    }

    public function update() {
        // Redirect to login if not authenticated
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        // Get the current user's ID from session
        $userId = $this->app->session()->get('user_id');

        // Get the submitted form data
        $fullName = $this->app->request()->data->full_name ?? null;
        $phone = $this->app->request()->data->phone ?? null;

        // Sanitize input (strip tags and trim whitespace)
        $fullName = $fullName ? trim(strip_tags($fullName)) : null;
        $phone = $phone ? trim(strip_tags($phone)) : null;
        // Validate phone number if provided
        if ($phone !== null && strlen($phone) !== 8) {
            $this->app->session()->set('error_message', 'Telefonnummeret må være nøyaktig 8 sifre.');
            $this->app->redirect('/min-side');
            return;
        }

        // Fetch current user data
        $userModel = new User($this->app->db());
        $user = $userModel->findById($userId);

        if (!$user) {
            $this->app->session()->set('error_message', 'Bruker ikke funnet.');
            $this->app->redirect('/min-side');
            return;
        }

        // Check if data is unchanged
        $isFullNameSame = ($fullName === null || $fullName === $user->getFullName());
        $isPhoneSame = ($phone === null || $phone === $user->getPhone());

        if ($isFullNameSame && $isPhoneSame) {
            $this->app->session()->set('error_message', 'Ingen endringer gjort.');
            $this->app->redirect('/min-side');
            return;
        }

        // Prepare data for update (only non-empty strings)
        $updateData = [];
        if ($fullName !== null && $fullName !== '') {
            $updateData['full_name'] = $fullName;
        }
        if ($phone !== null && $phone !== '') {
            $updateData['phone'] = $phone;
        }

        // Update user in database
        $success = $userModel->update($userId, $updateData);

        if ($success) {
            $this->app->session()->set('success_message', 'Profilen din har blitt oppdatert.');
        } else {
            $this->app->session()->set('error_message', 'Kunne ikke oppdatere profilen. Prøv igjen.');
        }

        $this->app->redirect('/min-side');
    }

    public function delete() {
        // Redirect to login if not authenticated
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        $userId = $this->app->session()->get('user_id');
        $docModel = new Document($this->app->db());
        if (!empty($docModel->findByUser($userId))) {
            $docModel->deleteByUser($userId); // ← Deletes files & DB records if user has documents
        }

        $userModel = new User($this->app->db());
        $userModel->delete($userId); // ← deletes the user

        // clear session and redirect to home
        $this->app->session()->clear();

        $this->app->session()->set('deletion_message', 'Brukeren har blitt slettet.');

        $this->app->redirect('/');
    }
}
