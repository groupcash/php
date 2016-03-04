# groupcash PHP [![Build Status](https://travis-ci.org/groupcash/php.png?branch=master)](https://travis-ci.org/groupcash/php)

Implementation of the [*groupcash* design][design] in PHP.

[design]: https://github.com/groupcash/core/blob/master/specifications/design.md

## Usage

To use the library in your own projects, require it with [composer]

    composer require groupcash/php

For development, download it with [composer] and execute the specification with [scrut]

    composer create-project groupcash/php groupcash-php -sdev
    cd groupcash-php
    vendor/bin/scrut

[composer]: http://getcomposer.org
[scrut]: https://github.com/rtens/scrut
