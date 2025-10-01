# IS-115 Søknadsystem for vitenskapelige assistenter
dependency manager https://getcomposer.org/ 
```bash
git clone https://github.com/SimonPortillo/soknadsystem_php.git

cd soknadsystem_php
```
```bash
composer install
```
```bash
composer start
```
After that, open `http://localhost:8000` in your browser.

__Note: If you run into an error similar to this `Failed to listen on localhost:8000 (reason: Address already in use)` then you'll need to change the port that the application is running on. You can do this by editing the `composer.json` file and changing the port in the `scripts.start` key.__

## Project Structure

This skeleton is organized for clarity and maintainability, and is also structured to be easily navigable by AI coding assistants. The following layout is recommended:

```
project-root/
│
├── app/                # Application-specific code
│   ├── controllers/    # Route controllers (e.g., HomeController.php)
│   ├── middlewares/    # Custom middleware classes/functions
│   ├── models/         # Data models (if needed)
│   ├── utils/          # Utility/helper functions
│   ├── views/          # View templates (if using)
│   └── commands/       # Custom CLI commands for Runway
│
├── public/             # Web root (index.php, assets, etc.)
│
├── config/             # Configuration files (database, app settings, routes)
│
├── vendor/             # Composer dependencies
│
├── tests/              # Unit and integration tests
│
├── composer.json       # Composer config
│
└── README.md           # Project overview
```

> _Predefined instructions for AI tools are included in this skeleton, making it easier for AI assistants to understand and help you with this structure._

## Do it!
That's it! Go build something flipping sweet!
