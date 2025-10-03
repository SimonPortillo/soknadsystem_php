<?php

namespace app\controllers;

use flight\Engine;

class HomeController {

	protected Engine $app;

	public function __construct(Engine $app) {
		$this->app = $app;
	}

	public function index() {
		$this->app->latte()->render(__DIR__ . '/../views/home/home.latte');
	}
}
