## Samsung TV remote

Based in part on [benreidnet's PHP Samsung TV Remote](https://github.com/benreidnet/samsungtv).

Docker image generated through [PHPDocker.io](https://phpdocker.io/).

Tested on a 2019 Q-series TV.

## Installation

TODO: Composer install

## Usage

TODO: web client

## Quirks

- This application cuts some corners in security by disabling SSL peer verification. Be aware of this before deploying in a production environment.
- For unclear reasons every now and then a command will be sent but not executed by the TV.
- Individual apps can disable the remote functionality resulting in a "ms.remote.touchDisable" error
