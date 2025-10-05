<?php

namespace app\middlewares;

use flight\Engine;

class AuthMiddleware {
    
    protected Engine $app;
    
    public function __construct(Engine $app) {
        $this->app = $app;
    }
    
    /**
     * Check if the user is authenticated
     * If not, redirect to login page
     * @return bool
     */
    public function isAuthenticated() {
        if (!$this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/login');
            return false;
        }
        return true;
    }
    
    /**
     * Check if the user is a guest (not authenticated)
     * If already logged in, redirect to home page
     * @return bool
     */
    public function isGuest() {
        if ($this->app->session()->get('is_logged_in')) {
            $this->app->redirect('/positions');
            return false;
        }
        return true;
    }
}