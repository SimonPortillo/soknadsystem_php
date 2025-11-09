<?php

namespace app\controllers;

use flight\Engine;
use app\models\User;
use app\models\Position;
use app\models\Application;

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

        $successMessage = $this->app->session()->get('logout_message');
        $this->app->session()->delete('logout_message');

        $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte', [
            'isLoggedIn' => false,
            'username' => null,
            'csp_nonce' => $this->app->get('csp_nonce'),
            'message' => $successMessage
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

    public function showResetPassword() {
        // Don't show reset password if user is already logged in
        if ($this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/positions');
            return;
        }
        
        $this->app->latte()->render(__DIR__ . '/../views/auth/reset-password.latte', [
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
        $positionModel = new Position($this->app->db());
        $positions = $positionModel->getAll();
        
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
            'positions' => $positions,
            'appliedPositionIds' => $appliedPositionIds,
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

        if (empty($username) || strlen(trim($username)) === 0) { // Check for empty or whitespace-only username
            $errors[] = 'Brukernavn er påkrevd.';
        }
        if (!empty($username) && !preg_match('/^[A-Za-z0-9ÆØÅæøå_-]+$/u', $username)) { // Limit allowed characters for usernames
            $errors[] = 'Brukernavn kan kun inneholde bokstaver, tall, understrek (_) og bindestrek (-).';
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
            
            $this->app->session()->set('registration_success', 'Registreringen var vellykket. Velkommen, ' . $username . '!');

            // Set up user session
            $this->createUserSession($user);
            // Redirect to positions page
            $this->app->redirect('/positions');
            
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

        // check if login is username or email and call correct function
        $user = filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL) 
            ? $userModel->findByEmail($usernameOrEmail)
            : $userModel->findByUsername($usernameOrEmail);

        if (!$user) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte', [
                'errors' => ['Feil brukernavn/e-post eller passord.'],
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
            return;
        }

        // Check if the account is locked
        if ($user->getLockoutUntil() && strtotime($user->getLockoutUntil()) > time()) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte', [
                'errors' => ['Kontoen din er midlertidig låst. Prøv igjen senere.'],
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
            return;
        }

        // Verify password
        if (!$user->verifyPassword($password)) {
            // Increment failed attempts
            $userModel->incrementFailedAttempts($user->getId());

            // Check if the account should be locked
            if ($user->getFailedAttempts() + 1 >= 3) { // Lock after 3 failed attempts
                $userModel->lockAccount($user->getId(), 60); // Lock for 60 minutes
                $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte', [
                    'errors' => ['For mange mislykkede forsøk. Kontoen din er låst i 60 minutter.'],
                    'csp_nonce' => $this->app->get('csp_nonce')
                ]);
                return;
            }

            $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte', [
                'errors' => ['Feil brukernavn/e-post eller passord.'],
                'csp_nonce' => $this->app->get('csp_nonce')
            ]);
            return;
        }

        // Reset failed attempts on successful login
        $userModel->resetFailedAttempts($user->getId());

       

        // Login successful - create session 
        $this->createUserSession($user);

        $this->app->session()->set('login_success', 'Velkommen tilbake, ' . $user->getUsername() . '!');

        // Redirect to positions page
        $this->app->redirect('/positions');
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

        $this->app->session()->set('logout_message', 'Du har blitt logget ut.');
        
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
        $this->app->session()->set('user_id', $user->getId());
        $this->app->session()->set('username', $user->getUsername());
        $this->app->session()->set('role', $user->getRole());
        $this->app->session()->set('is_logged_in', true);
    }
}