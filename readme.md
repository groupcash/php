# groupcash PHP [![Build Status](https://travis-ci.org/groupcash/php.png?branch=master)](https://travis-ci.org/groupcash/php)

Implementation of the [*groupcash* design][design] in PHP with command line interface.

[design]: https://github.com/groupcash/core/blob/master/design.md

## Usage

Download and install the project with [composer]

    php composer.phar create-project groupcash/php groupcash-php -sdev
    cd groupcash-php

Execute the specifications to make sure everything works

    vendor/bin/scrut

Run the CLI application

    php groupcash.php

[composer]: http://getcomposer.org