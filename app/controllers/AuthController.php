<?php

namespace app\controllers;

use flight\Engine;
use app\models\User;

class AuthController {

    protected Engine $app;

    public function __construct(Engine $app) {
        $this->app = $app;
    }

    /**
     * Show the login page
     */
    public function index() {
        // Don't show login if user is already logged in
        if ($this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/positions');
            return;
        }
        
        $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte', [
            'isLoggedIn' => false,
            'username' => null
        ]);
    }

    /**
     * Show the registration page
     */
    public function showRegister() {
        // Don't show register if user is already logged in
        if ($this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/positions');
            return;
        }
        
        $this->app->latte()->render(__DIR__ . '/../views/auth/register.latte', [
            'isLoggedIn' => false,
            'username' => null
        ]);
    }
    
    /**
     * Show the positions page (requires authentication)
     */
    public function showPositions() {
        // Redirect to login if not authenticated
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return;
        }
        
        $this->app->latte()->render(__DIR__ . '/../views/auth/positions.latte', [
            'isLoggedIn' => true,
            'username' => $this->app->session()->get('username')
        ]);
    }

    private function validateRegistration($username, $password, $email) {
        $errors = [];

        if (empty($username)) {
            $errors[] = 'Brukernavn er påkrevd.';
        }
        if (empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Gyldig e-postadresse er påkrevd.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Passord må være minst 8 tegn.';
        }

        return $errors;
    }

    public function register() {
        $data = $this->app->request()->data;
        $username = $data->username ?? '';
        $password = $data->password ?? '';
        $email = $data->email ?? '';
        
        $errors = $this->validateRegistration($username, $password, $email);
        
        if ($errors) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/register.latte', [
                'errors' => $errors
            ]);
            return;
        }
        
        // Check if username already exists
        $userModel = new User($this->app->db());
        $existingUser = $userModel->findByUsername($username);
        if ($existingUser) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/register.latte', [
                'errors' => ['Brukernavnet er allerede i bruk.']
            ]);
            return;
        }
        
        try {
            // Create new user
            $user = new User($this->app->db());
            $result = $user->create($username, $password);
            
            if (!$result) {
                throw new \Exception('Kunne ikke opprette bruker');
            }
            
            // Set up user session and redirect to positions page
            $this->createUserSession($user);
            
        } catch (\Exception $e) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/register.latte', [
                'errors' => ['En feil oppstod ved registrering. Vennligst prøv igjen.']
            ]);
        }
    }

    private function validateLogin($username, $password) {
        $errors = [];

        if (empty($username) || empty($password)) {
            $errors[] = 'Feil brukernavn eller passord.';
        }

        return $errors;
    }

    public function login() {
        $data = $this->app->request()->data;
        $username = $data->username ?? '';
        $password = $data->password ?? '';

        $errors = $this->validateLogin($username, $password);

        if ($errors) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte', [
                'errors' => $errors
            ]);
            return;
        }
        
        // Find user by username
        $userModel = new User($this->app->db());
        $user = $userModel->findByUsername($username);
        
        if (!$user || !$user->verifyPassword($password)) {
            $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte', [
                'errors' => ['Feil brukernavn eller passord.']
            ]);
            return;
        }
        
        // Login successful - set session variables using our modularized method
        $this->createUserSession($user);
    }

    public function logout() {
        // Clear all session data
        $this->app->session()->clear();
        
        // Redirect to login page
        $this->app->redirect('/login');
    }
    
    /**
     * Set up a user session after successful login or registration
     * 
     * @param \app\models\User $user The user to create a session for
     * @return void
     */
    private function createUserSession($user) {
        $this->app->session()->set('user_id', $user->id);
        $this->app->session()->set('username', $user->username);
        $this->app->session()->set('is_logged_in', true);
        
        // Redirect to the positions page
        $this->app->redirect('/positions');
    }
}