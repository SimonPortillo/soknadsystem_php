# IS-115 Søknadssystem for vitenskapelige assistenter
<img width="1902" height="906" alt="image" src="https://github.com/user-attachments/assets/d6cd1e1e-55ad-40f1-8633-ad6d6fa50091" />

## Kjør prosjektet lokalt
Forutsetninger:
 - du xampp installert https://www.apachefriends.org/ (apache og mysql)
 - du har composer dependency manager https://getcomposer.org/ (kun nødvending hvis du kloner fra github)
   
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

## Mysql database snapshot:
[database.sql](database.sql) (kan importeres eller kjøres direkte som sql-spørring i myphpadmin)




## Config 
du må endre din config fil til å bruke:
- dine mysql credentials
- smtp brukernavn og passord for PHPmailer (ikke kritisk for å kjøre siden)

se: [config_sample.php](app/config/config_sample.php)
> [!Important]
> Bruk config_sample som mal og lag en ny config.php i config mappen med dine credentials

## Testbrukere
Det finnes tre testbrukere i databasen som representer de ulike rollene i systemet:
- student (passord: Tester123)
- ansatt (passord: Tester123)
- admin (passord: Tester123)

## Funskjoner
### Alle brukere
- Registrere bruker (nye brukere har rollen student)
- oppdatere valgfrie personopplysninger (fullt navn og telefonnummer)
- logge inn og ut
- tilbakestille passord
### Studenter
- Søke på stillinger
- laste opp dokumenter (cv og søknadsbrev)
   - en student kan ha flere dokumenter
- slette dokumenter og søknader
- laste ned sine egne dokumenter
### Ansatt
- Opprette, redigere og slette stillinger
- Se søkere
- laste ned søkers dokumenter
- oppdatere status på søknaden
- svare på søknaden gjennom plaintext grensesnitt 
- svare ved å åpne epostklient med brukerens epost
### Admin
- Administrere andre brukere
   - tilgang til adminpanel som viser alle brukere i systemet, alle søknadene og alle stillingene deres
   - se mer detaljerte brukeropplysninger
   - slette brukere
   - Endre rolle til andre brukere
   - administrere og slette søknader på vegne av eieren
   - redigere og slette stillinger på vegne av eieren

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
## Database diagram
<img width="1321" height="669" alt="image" src="https://github.com/user-attachments/assets/f7289327-53cd-49e5-9d1d-54148e4cb54e" />


