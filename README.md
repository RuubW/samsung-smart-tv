## Samsung Smart TV remote

Based in part on [benreidnet's PHP Samsung TV Remote](https://github.com/benreidnet/samsungtv).

Docker image generated through [PHPDocker.io](https://phpdocker.io/).

Tested on a 2019 Q-series TV.

## Prerequisites

- Your local system has Docker installed;
- Your TV is on the same network as your local system;
- Your TV is a 2016+ model. Anything older is untested.

## Installation

1. Clone this repository;
2. Set up a `.env.local` file in the project root and add the correct value for `TV_IP`. This value can be found under the `IP Settings` tab of the `Network` menu on your TV;
3. Create the SSL certificate files:
`openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout nginx.key -out nginx.crt`;
4. Run `docker-compose build` to build the environment;
5. Run `docker-compose up -d` to start the environment;
6. Run `docker-compose exec php-fpm bash` to bash into the PHP container;
7. Run `composer install`;
8. Run `yarn install` followed by `yarn encore dev` to build the assets.

## Usage

### Web client

Visit the client at `http://localhost`

### CLI

Bash into the PHP container:

`docker-compose exec php-fpm bash`

Run the following command with a valid key as the only argument:

`bin/console app:remote home`

### PHPCS

Bash into the PHP container:

`docker-compose exec php-fpm bash`

Then run the following command:

`bin/phpcs`

Or the following command to automatically fix errors where possible:
 
`bin/phpcbf`

### PHPUnit

Bash into the PHP container:

`docker-compose exec php-fpm bash`

Then run the following command:

`bin/phpunit`

## Quirks

- On development environments this application cuts some corners in security by disabling SSL peer verification. Be aware that for this application to run in a production environment it needs a valid certificate.
- For unclear reasons occasionally a command will be sent but not executed by the TV.
- Individual TV apps can disable the remote functionality resulting in a "ms.remote.touchDisable" error

## Disclaimer

Executing commands on your TV might result in unexpected results. Use this application at your own risk,
