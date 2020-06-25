## Samsung TV remote

Based in part on [benreidnet's PHP Samsung TV Remote](https://github.com/benreidnet/samsungtv).

Docker image generated through [PHPDocker.io](https://phpdocker.io/).

Tested on a 2019 Q-series TV.

## Installation

1. Run `composer install`
2. Set up the `.env` file by copying `.env.dist` and setting the correct value for `TV_IP`. This value can be found under the `IP Settings` tab of the `Network` menu of your TV,

## Usage

### Web client

Visit the client at `http://localhost:8080`

### CLI

Run the following command with a valid key as the first argument:

`./bin/phpremote KEY_HOME`

## Quirks

- This application cuts some corners in security by disabling SSL peer verification. Be aware of this before deploying in a production environment.
- For unclear reasons every now and then a command will be sent but not executed by the TV.
- Individual apps can disable the remote functionality resulting in a "ms.remote.touchDisable" error
