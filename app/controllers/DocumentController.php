<?php

namespace App\Controllers;

use flight\Engine;

class DocumentController
{
    protected Engine $app;

	public function __construct(Engine $app) {
		$this->app = $app;
	}

    public function upload($app)
    {
        // Handle file upload logic here
    }
        
}