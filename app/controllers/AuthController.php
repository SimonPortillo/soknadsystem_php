<?php

namespace app\controllers;

use flight\Engine;

class AuthController {

	protected Engine $app;

	public function __construct(Engine $app) {
		$this->app = $app;
	}

	public function index() {
		$this->app->latte()->render(__DIR__ . '/../views/login.latte');
	}

    public function showRegister() {
        $this->app->latte()->render(__DIR__ . '/../views/register.latte');
    }

    public function register() {
        $data = $this->app->request()->data;
        $errors = [];

        if (empty($data->username)) {
            $errors[] = 'Brukernavn er påkrevd.';
        }
        if (empty($data->email) || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Gyldig e-postadresse er påkrevd.';
        }
        if (empty($data->password) || strlen($data->password) < 8) {
            $errors[] = 'Passord må være minst 8 tegn.';
        }

        if ($errors) {
        // Pass errors to the view
            $this->app->latte()->render(__DIR__ . '/../views/register.latte', [
                'errors' => $errors
            ]);
            return;
        }

        $username = $data->username ?? '';
        $password = $data->password ?? '';
        $email = $data->email ?? '';
        $password = password_hash($password, PASSWORD_DEFAULT);
        // database logic here

        // return page on success
        $this->app->latte()->render(__DIR__ . '/../views/positions.latte');
    }

    public function login() {
        $data = $this->app->request()->data;
        $username = $data->username ?? '';
        $password = $data->password ?? '';
        // create validation logic by comparing with database

        
        // return page on success
        $this->app->latte()->render(__DIR__ . '/../views/positions.latte');
    }

    public function logout() {
        // logic for logging out
    }
}