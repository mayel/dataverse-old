# README #

Dataverse is a user-friendly tool for creating conversational forms that can also be accessed as chatbots, which store their data in dynamic databases.


### How do I get set up? ###

There's a Dockerfile to configure nginx and php-fpm together, and Composer. Database server is not included for flexibility (so you can choose to use MariaDB, MySQL, or Postgres).

Some variables can be set in Dockerfile, such as `ERRORS` to toggle error reporting: `ERRORS=1` will display PHP errors in the browser.

Within the container, the app is in `/home/app` and the public web root is `/home/app/web` which will be served on port 80.

Your custom config directory (start by copying `config/secrets.sample.php` to that directory, rename to `secrets.php`, and configure with your DB details, etc) should be mounted as `/home/app/custom`:

  `docker build -t dataverse . && docker run -d -p 8080:80 --mount type=bind,source="/path/to/your/custom_directory",target=/home/app/custom dataverse`

Once you're up and running, go to http://localhost/build?token=123changeme to create your first questionnaire.

### Without Docker ###

* Dependencies

PHP 7.0+

MySQL 5.6+ (or MariaDB, or Postgres)

PHP Extensions: pdo, xml, mbstring

* Configuration

Run `composer install` to get PHP libraries / dependencies

Import the SQL DB dump (or just create a DB and the tables will be created for you)

Copy `config/secrets.sample.php` to `custom/secrets.php` and enter DB details, along with other options (such as Mandrill details for email notifications).

Configure your web server to serve the `web/` folder


### Who do I talk to? ###

* Repo admin
* Original author at http://mayel.space
