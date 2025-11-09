<?php

namespace app\controllers;

use flight\Engine;
use app\models\User;
use app\models\Document;
use app\models\Application;
use app\models\Position;

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

        // Fetch user's documents (only for students)
        $docModel = new Document($this->app->db());
        $cvDocuments = $docModel->findByUser($userId, 'cv');
        $coverLetterDocuments = $docModel->findByUser($userId, 'cover_letter'); 
        
        // Fetch user's applications (only for students)
        $applicationModel = new Application($this->app->db());
        $applications = $applicationModel->getByUser($userId);

        // Fetch user's positions (only for employers)
        $positionModel = new Position($this->app->db());
        $positions = $positionModel->findByCreatorId($userId, false, true);

        // Fetch all users for admin (with pagination and search)
        $allUsers = [];
        $totalUsers = 0;
        $currentPage = 1;
        $usersPerPage = 20;
        $searchQuery = null;
        
        // Fetch all applications for admin (with pagination and search)
        $allApplications = [];
        $totalApplications = 0;
        $currentAppPage = 1;
        $applicationsPerPage = 20;
        $appSearchQuery = null;
        
        // Fetch all positions for admin
        $allPositions = [];
        
        if ($user->getRole() === 'admin') {
            $searchQuery = $this->app->request()->query->search ?? null;
            $currentPage = max(1, (int) ($this->app->request()->query->page ?? 1));
            
            $allUsers = $userModel->getAllPaginated($currentPage, $usersPerPage, $searchQuery);
            $totalUsers = $userModel->getTotalCount($searchQuery);

            // Fetch applications data
            $appSearchQuery = $this->app->request()->query->app_search ?? null;
            $currentAppPage = max(1, (int) ($this->app->request()->query->app_page ?? 1));
            
            $allApplications = $applicationModel->getAllPaginated($currentAppPage, $applicationsPerPage, $appSearchQuery);
            $totalApplications = $applicationModel->getTotalCount($appSearchQuery);

            // Fetch all positions
            $allPositions = $positionModel->getAll(true, true);
        }

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
            'positions' => $positions,
            'all_users' => $allUsers,
            'total_users' => $totalUsers,
            'current_page' => $currentPage,
            'users_per_page' => $usersPerPage,
            'search_query' => $searchQuery,
            'all_applications' => $allApplications,
            'total_applications' => $totalApplications,
            'current_app_page' => $currentAppPage,
            'applications_per_page' => $applicationsPerPage,
            'app_search_query' => $appSearchQuery,
            'all_positions' => $allPositions
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

    /**
     * Update a user's role (admin only)
     * 
     * @return void
     */
    public function updateUserRole() {
        // Redirect if not authenticated or not admin
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        $currentUserId = $this->app->session()->get('user_id');
        $userModel = new User($this->app->db());
        $currentUser = $userModel->findById($currentUserId);

        if (!$currentUser || $currentUser->getRole() !== 'admin') {
            $this->app->session()->set('error_message', 'Ingen tilgang.');
            $this->app->redirect('/min-side');
            return;
        }

        $targetUserId = (int) ($this->app->request()->data->user_id ?? 0);
        $newRole = $this->app->request()->data->role ?? '';

        // Validate role
        $allowedRoles = ['student', 'employee', 'admin'];
        if (!in_array($newRole, $allowedRoles, true)) {
            $this->app->session()->set('error_message', 'Ugyldig rolle.');
            $this->app->redirect('/min-side');
            return;
        }

        // Update role
        $success = $userModel->updateRole($targetUserId, $newRole);

        if ($success) {
            $this->app->session()->set('success_message', 'Brukerrolle oppdatert.');
        } else {
            $this->app->session()->set('error_message', 'Kunne ikke oppdatere brukerrolle.');
        }

        $this->app->redirect('/min-side');
    }

    /**
     * Delete a user (admin only)
     * 
     * @return void
     */
    public function deleteUser() {
        // Redirect if not authenticated or not admin
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        $currentUserId = $this->app->session()->get('user_id');
        $userModel = new User($this->app->db());
        $currentUser = $userModel->findById($currentUserId);

        if (!$currentUser || $currentUser->getRole() !== 'admin') {
            $this->app->session()->set('error_message', 'Ingen tilgang.');
            $this->app->redirect('/min-side');
            return;
        }

        $targetUserId = (int) ($this->app->request()->data->user_id ?? 0);

        // Delete user's documents first
        $docModel = new Document($this->app->db());
        if (!empty($docModel->findByUser($targetUserId))) {
            $docModel->deleteByUser($targetUserId);
        }

        // Delete user
        $success = $userModel->delete($targetUserId);

        if ($success) {
            $this->app->session()->set('success_message', 'Bruker slettet.');
        } else {
            $this->app->session()->set('error_message', 'Kunne ikke slette bruker.');
        }

        $this->app->redirect('/min-side');
    }

    /**
     * Update application status (admin only)
     * 
     * @return void
     */
    public function updateApplicationStatus() {
        // Redirect if not authenticated or not admin
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        $currentUserId = $this->app->session()->get('user_id');
        $userModel = new User($this->app->db());
        $currentUser = $userModel->findById($currentUserId);

        if (!$currentUser || $currentUser->getRole() !== 'admin') {
            $this->app->session()->set('error_message', 'Ingen tilgang.');
            $this->app->redirect('/min-side');
            return;
        }

        $applicationId = (int) ($this->app->request()->data->application_id ?? 0);
        $newStatus = $this->app->request()->data->status ?? '';
        $notes = $this->app->request()->data->notes ?? null;

        // Sanitize notes
        if ($notes !== null) {
            $notes = strip_tags($notes);
            if (mb_strlen($notes) > 1000) {
                $notes = mb_substr($notes, 0, 1000);
            }
        }

        // Validate status
        $allowedStatuses = ['pending', 'reviewed', 'accepted', 'rejected'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            $this->app->session()->set('error_message', 'Ugyldig status.');
            $this->app->redirect('/min-side');
            return;
        }

        // Update status
        $applicationModel = new Application($this->app->db());
        $success = $applicationModel->updateStatus($applicationId, $newStatus, $notes);

        if ($success) {
            $this->app->session()->set('success_message', 'Søknadsstatus oppdatert.');
        } else {
            $this->app->session()->set('error_message', 'Kunne ikke oppdatere søknadsstatus.');
        }

        $this->app->redirect('/min-side');
    }

    /**
     * Delete an application (admin only)
     * 
     * @return void
     */
    public function deleteApplication() {
        // Redirect if not authenticated or not admin
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        $currentUserId = $this->app->session()->get('user_id');
        $userModel = new User($this->app->db());
        $currentUser = $userModel->findById($currentUserId);

        if (!$currentUser || $currentUser->getRole() !== 'admin') {
            $this->app->session()->set('error_message', 'Ingen tilgang.');
            $this->app->redirect('/min-side');
            return;
        }

        $applicationId = (int) ($this->app->request()->data->application_id ?? 0);

        // Delete application
        $applicationModel = new Application($this->app->db());
        $success = $applicationModel->deleteById($applicationId);

        if ($success) {
            $this->app->session()->set('success_message', 'Søknad slettet.');
        } else {
            $this->app->session()->set('error_message', 'Kunne ikke slette søknad.');
        }

        $this->app->redirect('/min-side');
    }
}
