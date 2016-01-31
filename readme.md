# groupcash PHP

Implementation of the [*groupcash* protocol][protocol] in PHP with command line interface.

[protocol]: https://github.com/groupcash/core#protocol

## Usage

Download and install the project with [composer]

    php composer.phar create-project groupcash/php groupcash-php -sdev
    cd groupcash-php

Execute the specifications to make sure everything works

    vendor/bin/scrut spec

Run the CLI application

    php groupcash.php

[composer]: http://getcomposer.com