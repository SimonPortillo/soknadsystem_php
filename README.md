# IS-115 Søknadsystem for vitenskapelige assistenter
<img width="1903" height="909" alt="image" src="https://github.com/user-attachments/assets/5654b7ff-abfe-4765-8e7c-64534a73d89e" />

## Kjør prosjektet lokalt
Forutsetninger:
 - du xampp installert https://www.apachefriends.org/
 - du har composer dependency manager https://getcomposer.org/
 - du er klar for å få bakoversveis 😎
   
## kommandoer 
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

### Mysql database snapshot:
last/referer til endelig database

> [!NOTE]
> du må endre din config fil til å bruke dine mysql credentials og db navn: soknadsystemdb



## Prosjekt struktur

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

