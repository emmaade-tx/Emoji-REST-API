# Slim Framework 3 Skeleton Application

[![Coverage Status](https://coveralls.io/repos/github/andela-araimi/Emoji-REST-API/badge.svg?branch=master)](https://coveralls.io/github/andela-araimi/Emoji-REST-API?branch=master)  [![Build Status](https://travis-ci.org/andela-araimi/Emoji-REST-API.svg?branch=master)](https://travis-ci.org/andela-araimi/Emoji-REST-API)

Use this skeleton application to quickly setup and start working on a new Slim Framework 3 application. This application uses the latest Slim 3 with the PHP-View template renderer. It also uses the Monolog logger.

This skeleton application was built for Composer. This makes setting up a new Slim Framework application quick and easy.

## Install the Application

Run this command from the directory in which you want to install your new Slim Framework application.

    php composer.phar create-project slim/slim-skeleton [my-app-name]

Replace `[my-app-name]` with the desired directory name for your new application. You'll want to:

* Point your virtual host document root to your new application's `public/` directory.
* Ensure `logs/` is web writeable.

That's it! Now go build something cool.
