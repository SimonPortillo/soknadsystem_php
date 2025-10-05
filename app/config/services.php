<?php

use flight\Engine;
use flight\database\PdoWrapper;
use flight\debug\database\PdoQueryCapture;
use flight\debug\tracy\TracyExtensionLoader;
use Tracy\Debugger;

/*********************************************
 *         FlightPHP Service Setup           *
 *********************************************
 * This file registers services and integrations
 * for your FlightPHP application. Edit as needed.
 *
 * @var array  $config  From config.php
 * @var Engine $app     FlightPHP app instance
 **********************************************/



/*********************************************
 *           Session Service Setup           *
 *********************************************
 * Session configuration using the official flightphp/session package
 **********************************************/

// Register the session service with the Flight framework
$app->register('session', \flight\Session::class, [
    [
        // Set your preferred session configuration options
        'name'           => 'soknadsystem_session',    // Name of the session cookie
        'autostart'      => true,                      // Start the session automatically
        'use_cookies'    => true,                      // Use cookies to store session ID on client side
        'cookie_timeout' => 86400,                     // Session lifetime in seconds (24 hours)
        'cookie_secure'  => isset($_SERVER['HTTPS']),  // Set to true if using HTTPS
        'cookie_httponly'=> true                       // Make cookies inaccessible to client-side JS
    ]
]);

/*********************************************
 *           Tracy Debugger Setup            *
 *********************************************
 * Tracy is a powerful error handler and debugger for PHP.
 * Docs: https://tracy.nette.org/
 *
 * Key Tracy configuration options:
 *   - Debugger::enable([mode], [ip]);
 *       - mode: Debugger::Development or Debugger::Production
 *       - ip: restrict debug bar to specific IP(s)
 *   - Debugger::$logDirectory: where error logs are stored
 *   - Debugger::$strictMode: show all errors (true/E_ALL), or filter out deprecated notices
 *   - Debugger::$showBar: show/hide debug bar (auto-detected, can be forced)
 *   - Debugger::$maxLen: max length of dumped variables
 *   - Debugger::$maxDepth: max depth of dumped structures
 *   - Debugger::$editor: configure clickable file links (see docs)
 *   - Debugger::$email: send error notifications to email
 *
 * Example Tracy setups:
 *   Debugger::enable(); // Auto-detects environment
 *   Debugger::enable(Debugger::Development); // Explicitly set environment
 *   Debugger::enable('23.75.345.200'); // Restrict debug bar to specific IPs
 *
 * For more options, see https://tracy.nette.org/en/configuration
 **********************************************/
Debugger::enable(); // Auto-detects environment
// Debugger::enable(Debugger::Development); // Explicitly set environment
// Debugger::enable('23.75.345.200'); // Restrict debug bar to specific IPs
Debugger::$logDirectory = __DIR__ . $ds . '..' . $ds . 'log'; // Log directory
Debugger::$strictMode = true; // Show all errors (set to E_ALL & ~E_DEPRECATED for less noise)
// Debugger::$maxLen = 1000; // Max length of dumped variables (default: 150)
// Debugger::$maxDepth = 5; // Max depth of dumped structures (default: 3)
// Debugger::$editor = 'vscode'; // Enable clickable file links in debug bar
// Debugger::$email = 'your@email.com'; // Send error notifications
if (Debugger::$showBar === true && php_sapi_name() !== 'cli') {
	(new TracyExtensionLoader($app)); // Load FlightPHP Tracy extensions
}

/**********************************************
 *           Database Service Setup           *
 **********************************************/
// Uncomment and configure the following for your database:

// MySQL Example:
$dsn = 'mysql:host=' . $config['database']['host'] . ';dbname=' . $config['database']['dbname'] . ';charset=utf8mb4';

// SQLite Example:
// $dsn = 'sqlite:' . $config['database']['file_path'];

// Register Flight::db() service
// In development, use PdoQueryCapture to log queries; in production, use PdoWrapper for performance.
$pdoClass = Debugger::$showBar === true ? PdoQueryCapture::class : PdoWrapper::class;
$app->register('db', $pdoClass, [ $dsn, $config['database']['user'] ?? null, $config['database']['password'] ?? null ]);

/**********************************************
 *         Third-Party Integrations           *
 **********************************************/
// Google OAuth Example:
// $app->register('google_oauth', Google_Client::class, [ $config['google_oauth'] ]);

// Redis Example:
// $app->register('redis', Redis::class, [ $config['redis']['host'], $config['redis']['port'] ]);

// Add more service registrations below as needed

// Register Latte using our SessionAwareLatte wrapper
$app->register('latte', \app\utils\SessionAwareLatte::class, [$app]);