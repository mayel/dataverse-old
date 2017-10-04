# README #

Dataverse is a user-friendly tool for creating conversational forms that can also be accessed as chatbots, which store their data in dynamic databases.


### How do I get set up? ###

There's a Dockerfile to configure nginx and php-fpm together, and Composer. Database server is not included for flexibility (so you can choose to use SQLite, MySQL, or Postgres).

Default doc root is in `/home/app/web` (this can be overridden by changing the env `NGINX_ROOT` in `Dockerfile`) so mounting files there (or mounting the repo root dir as `/home/app`) will get them served:

  `docker run -d -p 8080:80 --mount type=bind,source="$(pwd)",target=/home/app test`

Then run composer to fetch PHP libs:

  `docker exec [Container_ID] bash -c 'cd /home/app/ && composer install'`

An `ERRORS` env can be set to toggle error reporting. `ERRORS=1` will display PHP errors.

Besides the Dockerfile, check out `config/cmd.sh` to see what happens.


### Without Docker ###

* Dependencies

PHP 7.0+

MySQL 5.6+ (or SQLite, or Postgres)

PHP Extensions: pdo, xml, mbstring

* Configuration

Run `composer install` to get PHP libraries / dependencies

Import the MySQL DB dump (or just create a DB and the tables will be created for you)

Copy `config/secrets.sample.php` to `custom/secrets.php` and enter DB details, along with other options (such as Mandrill details for email notifications).

Configure your web server to serve the `web/` folder


### Who do I talk to? ###

* Repo owner or admin
* Author at http://mayel.space
