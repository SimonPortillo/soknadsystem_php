<?php

namespace app\controllers;

use flight\Engine;
use app\models\User;

class AuthController {

    protected Engine $app;

    public function __construct(Engine $app) {
        $this->app = $app;
    }

    public function index() {
        $this->app->latte()->render(__DIR__ . '/../views/auth/login.latte');
    }

    public function showRegister() {
        $this->app->latte()->render(__DIR__ . '/../views/auth/register.latte');
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
            
            // Redirect to positions page on success
            $this->app->latte()->render(__DIR__ . '/../views/auth/positions.latte');
            
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
        
        // Login successful - set session variables 
        // $_SESSION['user_id'] = $user->id;
        // $_SESSION['username'] = $user->username;
        
        $this->app->latte()->render(__DIR__ . '/../views/auth/positions.latte');
    }

    public function logout() {
        // logic for logging out
    }
}