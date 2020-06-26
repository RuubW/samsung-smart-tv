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
3. Run `docker-compose build` to build the environment;
4. Run `docker-compose up -d` to start the environment;
5. Run `docker-compose exec php-fpm bash` to bash into the PHP container;
6. Run `composer install`.
7. Run `yarn encore dev` to build the assets.

## Usage

### Web client

Visit the client at `http://localhost:8080`

### CLI

Bash into the PHP container:

`docker-compose exec php-fpm bash`

Run the following command with a valid key as the only argument:

`bin/console app:remote home`

## Quirks

- This application cuts some corners in security by disabling SSL peer verification. Be aware of this before deploying in a production environment.
- For unclear reasons occasionally a command will be sent but not executed by the TV.
- Individual apps can disable the remote functionality resulting in a "ms.remote.touchDisable" error

## Disclaimer

Executing commands on your TV might result in unexpected results. Use this application at your own risk,
