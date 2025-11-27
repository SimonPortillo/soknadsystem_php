<?php

namespace app\controllers;

use flight\Engine;
use app\models\User;
use app\models\Document;
use app\models\Application;
use app\models\Position;
use app\utils\ApiUtil;
use app\utils\CacheUtil;

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
     * Display the user profile page ("Min Side")
     * 
     * Dynamically displays user information based on role (student, employee, admin)
     * and fetches related data such as documents, applications, and positions.
     * 
     * @return void
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

        // get buzzword from cache or API
        // caches for 5 minutes to illustrate both caching and API usage
        $cache = new CacheUtil();
        $buzzword = $cache->get('buzzword');
        if (!$buzzword) {
            $api = new ApiUtil();
            $buzzword = $api->get("https://corporatebs-generator.sameerkumar.website/");
            $cache->set('buzzword', $buzzword, 300); // cache for 5 minutes
        }

        // Get position count for navbar
        $positionModel = new Position($this->app->db());
        $openPositionsCount = $positionModel->getCount();

        // Prepare data for view
        $viewData = [
            'user' => $user,
            'csp_nonce' => $nonce,
            'message' => $successMessage,
            'errors' => $errorMessage,
            'isLoggedIn' => true,
            'openPositionsCount' => $openPositionsCount,
            'buzzword' => $buzzword,
        ];        

        // Student-specific data
        if ($user['role'] === 'student') {
            $docModel = new Document($this->app->db());
            $viewData['cv_documents'] = $docModel->findByUser($userId, 'cv');
            $viewData['cover_letter_documents'] = $docModel->findByUser($userId, 'cover_letter');
            $applicationModel = new Application($this->app->db());
            $viewData['applications'] = $applicationModel->getByUser($userId);
        }

        // Employee or admin-specific data
        if ($user['role'] === 'employee' || $user['role'] === 'admin') {
            $positionModel = new Position($this->app->db());
            $viewData['positions'] = $positionModel->findByCreatorId($userId, false, true);
        }

        // Admin-specific data
        if ($user['role'] === 'admin') {
            // Fetch all users, applications, positions, etc.
            $allUsers = $userModel->getAll();
            $applicationModel = new Application($this->app->db());
            $allApplications = $applicationModel->getAll();
            $positionModel = new Position($this->app->db());
            $allPositions = $positionModel->getAll(true, true);

            // Group positions by creator
            $positionsByCreator = [];
            foreach ($allPositions as $pos) {
                $positionsByCreator[$pos['creator_id']][] = $pos;
            }

            // Group applications by user
            $applicationsByUser = [];
            foreach ($allApplications as $app) {
                $applicationsByUser[$app['user_id']][] = $app;
            }

            $viewData['all_users'] = $allUsers;
            $viewData['all_applications'] = $allApplications;
            $viewData['all_positions'] = $allPositions;
            $viewData['positions_by_creator'] = $positionsByCreator;
            $viewData['applications_by_user'] = $applicationsByUser;
        }

        // Render the profile page with only relevant user data
        $this->app->latte()->render(__DIR__ . '/../views/user/min-side.latte', $viewData);
    }

    /**
     * Update user profile information
     * 
     * Allows users to update their full name and phone number.
     * Validates input and provides feedback messages.
     * 
     * @return void
     * route: POST /min-side/update
     */
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
        $isFullNameSame = ($fullName === null || $fullName === $user['full_name']);
        $isPhoneSame = ($phone === null || $phone === $user['phone']);

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

    /**
     * Delete a user account (self-deletion or admin deletion)
     * 
     * Deletes the user account and associated documents.
     * Admins can delete any user except themselves via the admin panel.
     * Users and admins can delete their own account via their profile settings.
     * 
     * @return void
     * route: POST /min-side/delete
     */
    public function delete() {
        // Redirect to login if not authenticated
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }

        $currentUserId = $this->app->session()->get('user_id');
        $userModel = new User($this->app->db());
        $currentUser = $userModel->findById($currentUserId);

        if (!$currentUser) {
            $this->app->redirect('/login');
            return;
        }

        // Check if this is an admin deleting another user or a user deleting themselves
        $targetUserId = (int) ($this->app->request()->data->user_id ?? $currentUserId);
        $isAdmin = $currentUser['role'] === 'admin';
        $isSelfDeletion = $targetUserId === $currentUserId;

        // If trying to delete another user, must be admin
        if (!$isSelfDeletion && !$isAdmin) {
            $this->app->session()->set('error_message', 'Ingen tilgang.');
            $this->app->redirect('/min-side');
            return;
        }

        // Prevent admin from deleting themselves via the admin panel (when user_id is explicitly passed)
        if ($isAdmin && $isSelfDeletion && isset($this->app->request()->data->user_id)) {
            $this->app->session()->set('error_message', 'Du kan ikke slette din egen konto via admin-panelet. Bruk "Slett konto" under dine kontoinnstillinger.');
            $this->app->redirect('/min-side');
            return;
        }

        // Delete user's documents (files and database records)
        $docModel = new Document($this->app->db());
        $docModel->deleteByUser($targetUserId);

        // Delete the user
        $success = $userModel->delete($targetUserId);

        if ($isSelfDeletion) {
            // User deleted their own account - clear session and redirect to home
            $this->app->session()->clear();
            $this->app->session()->set('deletion_message', 'Brukeren har blitt slettet.');
            $this->app->redirect('/');
        } else {
            // Admin deleted another user - show message and stay on min-side
            if ($success) {
                $this->app->session()->set('success_message', 'Bruker slettet.');
            } else {
                $this->app->session()->set('error_message', 'Kunne ikke slette bruker.');
            }
            $this->app->redirect('/min-side');
        }
    }

    /**
     * Update a user's role (admin only)
     * 
     * Allows an admin to update another user's role.
     * Cleans up related data when changing roles.
     * 
     * @return void
     * route: POST /admin/users/update-role
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

        if (!$currentUser || $currentUser['role'] !== 'admin') {
            $this->app->session()->set('error_message', 'Ingen tilgang.');
            $this->app->redirect('/min-side');
            return;
        }

        $targetUserId = (int) $this->app->request()->data->user_id;
        $newRole = $this->app->request()->data->role;

        // Prevent admin from changing their own role
        if ($targetUserId === $currentUserId) {
            $this->app->session()->set('error_message', 'Du kan ikke endre din egen rolle.');
            $this->app->redirect('/min-side');
            return;
        }

        // Validate role
        $allowedRoles = ['student', 'employee', 'admin'];
        if (!in_array($newRole, $allowedRoles, true)) {
            $this->app->session()->set('error_message', 'Ugyldig rolle.');
            $this->app->redirect('/min-side');
            return;
        }

        // Get target user to check current role
        $targetUser = $userModel->findById($targetUserId);
        if (!$targetUser) {
            $this->app->session()->set('error_message', 'Bruker ikke funnet.');
            $this->app->redirect('/min-side');
            return;
        }

        $currentRole = $targetUser['role'];

        // Clean up related data if role is changing
        if ($currentRole !== $newRole) {
            // If changing FROM student, delete their applications and documents
            if ($currentRole === 'student') {
                $applicationModel = new Application($this->app->db());
                $documentModel = new Document($this->app->db());
                
                // Delete applications
                $applicationModel->deleteAllByUserId($targetUserId);
                
                // Delete documents
                $documentModel->deleteByUser($targetUserId);
            }
            
            // If changing FROM employee or admin, delete their positions
            if ($currentRole === 'employee' || $currentRole === 'admin') {
                $positionModel = new Position($this->app->db());
                
                // Get all positions created by this user
                $positions = $positionModel->findByCreatorId($targetUserId, false, false);
                
                // Delete each position (cascades to applications via foreign key)
                foreach ($positions as $position) {
                    $positionModel->delete($position['id']);
                }
            }
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

}
