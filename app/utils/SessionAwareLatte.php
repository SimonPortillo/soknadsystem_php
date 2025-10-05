<?php

namespace app\utils;

use Latte\Engine;
use flight\Engine as FlightEngine;

/**
 * SessionAwareLatte - A wrapper around Latte that automatically adds session data to all templates
 */
class SessionAwareLatte {
    /**
     * @var Engine
     */
    private $latte;
    
    /**
     * @var FlightEngine
     */
    private $app;
    
    /**
     * Constructor
     * 
     * @param FlightEngine $app
     */
    public function __construct(FlightEngine $app) {
        $this->app = $app;
        $this->latte = new Engine();
        
        // Set temp directory for compiled templates
        $this->latte->setTempDirectory(__DIR__ . '/../../cache/latte');
        
        // Add default filters, etc. if needed
    }
    
    /**
     * Render a template with session data automatically added
     * 
     * @param string $template The template file path
     * @param array $params Parameters to pass to the template
     * @return void
     */
    public function render($template, array $params = []) {
        // Always add session data to the template
        $params['isLoggedIn'] = $this->app->session()->get('is_logged_in');
        $params['username'] = $this->app->session()->get('username');
        
        // Render the template with the combined parameters
        return $this->latte->render($template, $params);
    }
}
