<?php

namespace app\utils;

use Latte\Engine;
use flight\Engine as FlightEngine;

/**
 * SessionAwareLatte
 * 
 * A wrapper around the Latte templating engine that automatically injects
 * session data into all rendered templates. This eliminates the need to
 * manually pass session variables (like isLoggedIn and username) to every
 * template render call throughout the application.
 * 
 * This class uses the FlightPHP session library to retrieve session data
 * and makes it available to all Latte templates as variables.
 * 
 * Automatically injected variables:
 *   - $isLoggedIn (bool): Whether the user is currently authenticated
 *   - $username (string|null): The username of the logged-in user
 * 
 * Usage:
 *   $this->app->latte()->render('template.latte', ['customData' => $value]);
 *   // Session data is automatically added to the template parameters
 * 
 * @package app\utils
 */
class SessionAwareLatte {
    /**
     * @var Engine The Latte template engine instance
     */
    private $latte;
    
    /**
     * @var FlightEngine The FlightPHP Engine instance
     */
    private $app;
    
    /**
     * Constructor
     * 
     * Initializes the Latte engine and sets up the cache directory
     * for compiled templates.
     * 
     * @param FlightEngine $app The FlightPHP Engine instance
     */
    public function __construct(FlightEngine $app) {
        $this->app = $app;
        $this->latte = new Engine();
        
        // Set temp directory for compiled templates
        $this->latte->setTempDirectory(__DIR__ . '/../../cache/latte');
        
        // Add custom filters, extensions, or providers here if needed
    }
    
    /**
     * Render a template with session data automatically added
     * 
     * This method wraps Latte's render() function and automatically injects
     * session data into the template parameters before rendering. This ensures
     * that all templates have access to authentication state and user information
     * without requiring manual parameter passing in every controller method.
     * 
     * The following variables are automatically added to $params:
     *   - isLoggedIn: Boolean indicating if user is authenticated
     *   - username: The username of the logged-in user (or null if not logged in)
     * 
     * @param string $template The absolute path to the template file
     * @param array $params Optional array of parameters to pass to the template
     * @return void Outputs the rendered template
     */
    public function render($template, array $params = []) {
        // Always add session data to the template parameters
        $params['isLoggedIn'] = $this->app->session()->get('is_logged_in');
        $params['username'] = $this->app->session()->get('username');
        
        // Render the template with the combined parameters
        return $this->latte->render($template, $params);
    }
}
