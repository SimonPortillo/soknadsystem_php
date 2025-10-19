<?php

namespace app\controllers;

use flight\Engine;
use app\models\User;

/**
 * AuthController
 * 
 * Handles all authentication-related functionality including login, registration,
 * logout, and displaying authentication-protected pages. Uses the FlightPHP session
 * library for session management.
 * 
 * Responsibilities:
 *   - Display login and registration forms
 *   - Process login and registration submissions
 *   - Manage user sessions (create, destroy)
 *   - Protect routes that require authentication
 *   - Display authentication-protected pages (e.g., positions)
 * 
 * @package app\controllers
 */
class AuthController {

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
     * Display the login page
     * 
     * Shows the login form to guest users. If the user is already authenticated,
     * they are redirected to the positions page.
     * 
     * Route: GET /login
     * 
     * @return void
     */
    public function index() {
        // Don't show login if user is already logged in
        if ($this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/positions');
            return;
        }

        $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte', [
            'isLoggedIn' => false,
            'username' => null,
            'csp_nonce' => $this->app->get('csp_nonce')
        ]);
    }

    /**
     * Display the registration page
     * 
     * Shows the registration form to guest users. If the user is already authenticated,
     * they are redirected to the positions page.
     * 
     * Route: GET /register
     * 
     * @return void
     */
    public function showRegister() {
        // Don't show register if user is already logged in
        if ($this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/positions');
            return;
        }
        
        $this->app->latte()->render(__DIR__ . '/../views/auth/register.latte', [
            'isLoggedIn' => false,
            'username' => null,
            'csp_nonce' => $this->app->get('csp_nonce')
        ]);
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
        $positionModel = new \app\models\Position($this->app->db());
        $positions = $positionModel->getAll();
        
        // Get any success message from session and clear it
        $successMessage = $this->app->session()->get('position_success');
        $this->app->session()->delete('position_success');
        
        // Get any error message from session and clear it
        $errorMessage = $this->app->session()->get('position_error');
        $this->app->session()->delete('position_error');
        
        $this->app->latte()->render(__DIR__ . '/../views/auth/positions.latte', [
            'isLoggedIn' => true,
            'username' => $this->app->session()->get('username'),
            'role' => $this->app->session()->get('role'),
            'positions' => $positions,
            'message' => $successMessage,
            'errors' => $errorMessage ? [$errorMessage] : null,
            'csp_nonce' => $this->app->get('csp_nonce')
        ]);
    }

    /**
     * Validate registration input
     * 
     * Performs server-side validation on registration form data.
     * 
     * Validation rules:
     *   - Username must not be empty
     *   - Email must be valid format
     *   - Password must be at least 8 characters
     * 
     * @param string $username The username to validate
     * @param string $password The password to validate
     * @param string $email The email address to validate
     * @return array Array of error messages (empty if validation passes)
     */
    private function validateRegistration($username, $password, $email, $phone): array {
        $errors = [];

        if (empty($username)) {
            $errors[] = 'Brukernavn er påkrevd.';
        }
        if (empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Gyldig e-postadresse er påkrevd.';
        }
        if($phone && strlen($phone) !== 8) {
            $errors[] = "Telefonnummer må være nøyaktig 8 siffer.";
        }
        $passwordValidation = $this->validatePassword($password);
        if ($passwordValidation !== true) {
            $errors[] = $passwordValidation;
        }

        return $errors;
    }

    /**
     * Validate password complexity
     * 
     * Checks if the provided password meets the complexity requirements.
     * 
     * @param string $password The password to validate
     * @return string|true Error message if validation fails, true if it passes
     */

    private function validatePassword($password): string|true {
        if(strlen($password) < 8) { return "Passordet må være minst 8 tegn langt."; }

        if(!preg_match('/[A-ZÆØÅ]/', $password)) { return "Passordet må inneholde minst én stor bokstav."; }

        if(!preg_match('/[a-zæøå]/', $password)) { return "Passordet må inneholde minst én liten bokstav."; }

        if(preg_match_all('/[0-9]/', $password) < 2) { return "Passordet må inneholde minst to tall."; }

        return true;
    }


    /**
     * Process user registration
     * 
     * Handles registration form submission. Validates input, checks for existing
     * users, creates the new user account, and automatically logs them in by
     * creating a session.
     * 
     * Route: POST /register
     * 
     * Expected POST data:
     *   - username: User's chosen username
     *   - password: User's chosen password (will be hashed)
     *   - email: User's email address
     * 
     * @return void Redirects to positions page on success, re-renders form with errors on failure
     */
    public function register() {
        $data = $this->app->request()->data;
        $username = $data->username ?? '';
        $password = $data->password ?? '';
        $email = $data->email ?? '';
        $full_name = $data->full_name ?? null;
        $phone = $data->phone ?? null;
        
        // Trim whitespace and convert empty strings to null for optional fields
        $full_name = !empty(trim($full_name)) ? trim($full_name) : null;
        $phone = !empty(trim($phone)) ? trim($phone) : null;
        
        $errors = $this->validateRegistration($username, $password, $email, $phone);
        
        if ($errors) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/register.latte', [
                'errors' => $errors,
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'phone' => $phone,
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
            return;
        }
        
        // Check if username already exists
        $userModel = new User($this->app->db());
        $existingUser = $userModel->findByUsername($username);
        if ($existingUser) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/register.latte', [
                'errors' => ['Brukernavnet er allerede i bruk.'],
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'phone' => $phone,
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
            return;
        }

        // Check if email already exists
        $existingEmail = $userModel->findByEmail($email);
        if ($existingEmail) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/register.latte', [
                'errors' => ['En bruker med denne e-postadressen finnes allerede.'],
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'phone' => $phone,
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
            return;
        }
        
        try {
            // Create new user
            $user = new User($this->app->db());
            $result = $user->create($username, $password, $email, $full_name, $phone);
            
            if (!$result) {
                throw new \Exception('Kunne ikke opprette bruker');
            }
            
            // Set up user session and redirect to positions page (handled inside createUserSession)
            $this->createUserSession($user);
            
        } catch (\Exception $e) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/register.latte', [
                'errors' => ['En feil oppstod ved registrering. Vennligst prøv igjen.'],
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'phone' => $phone,
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
        }
    }

    /**
     * Validate login input
     * 
     * Performs basic validation on login form data.
     * 
     * Validation rules:
     *   - Username/Email must not be empty
     *   - Password must not be empty
     * 
     * @param string $usernameOrEmail The username or email to validate
     * @param string $password The password to validate
     * @return array Array of error messages (empty if validation passes)
     */
    private function validateLogin($usernameOrEmail, $password) {
        $errors = [];

        if (empty($usernameOrEmail) || empty($password)) {
            $errors[] = 'Feil brukernavn/e-post eller passord.';
        }

        return $errors;
    }

    /**
     * Process user login
     * 
     * Handles login form submission. Validates input, verifies credentials,
     * and creates a session if authentication is successful.
     * 
     * Route: POST /login
     * 
     * Expected POST data:
     *   - username: User's username or email address
     *   - password: User's password (will be verified against hashed password)
     * 
     * Security:
     *   - Uses password_verify() to check against hashed password
     *   - Returns generic error message to prevent username/email enumeration
     *   - Creates secure session upon successful authentication
     *   - Accepts both username and email for login
     * 
     * @return void Redirects to positions page on success, re-renders form with errors on failure
     */
    public function login() {
        $data = $this->app->request()->data;
        $usernameOrEmail = $data->username ?? '';
        $password = $data->password ?? '';

        $errors = $this->validateLogin($usernameOrEmail, $password);

        if ($errors) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte', [
                'errors' => $errors,
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
            return;
        }
        
        // Find user by username or email
        $userModel = new User($this->app->db());
        
        // Check if input looks like an email
        if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            $user = $userModel->findByEmail($usernameOrEmail);
        } else {
            $user = $userModel->findByUsername($usernameOrEmail);
        }
        
        if (!$user || !$user->verifyPassword($password)) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte', [
                'errors' => ['Feil brukernavn/e-post eller passord.'],
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
            return;
        }
        
        // Login successful - create session and redirect to positions page using createUserSession()
        $this->createUserSession($user);
    }

    /**
     * Logout user and destroy session
     * 
     * Clears all session data using the FlightPHP session library and
     * redirects the user to the login page.
     * 
     * Route: GET /logout
     * 
     * Security:
     *   - Completely clears session data
     *   - Redirects to login page to prevent unauthorized access
     * 
     * @return void Redirects to login page after clearing session
     */
    public function logout() {
        // Clear all session data
        $this->app->session()->clear();
        
        // Redirect to login page
        $this->app->redirect('/login');
    }
    
    /**
     * Create a user session after successful authentication
     * 
     * This private method centralizes session creation logic to follow the DRY
     * (Don't Repeat Yourself) principle. It's used by both login() and register()
     * methods to set up user sessions consistently.
     * 
     * Session variables set:
     *   - user_id: The unique ID of the authenticated user
     *   - username: The username of the authenticated user
     *   - is_logged_in: Boolean flag indicating successful authentication
     *   - role: The user's role (student, employee, admin)
     * 
     * After setting session variables, the user is redirected to the positions page.
     * 
     * @param User $user The authenticated user object
     * @return void Redirects to positions page after setting session
     */
    private function createUserSession($user) {
        $this->app->session()->set('user_id', $user->id);
        $this->app->session()->set('username', $user->username);
        $this->app->session()->set('role', $user->role);
        $this->app->session()->set('is_logged_in', true);
        
        // Redirect to the positions page
        $this->app->redirect('/positions');
    }
}