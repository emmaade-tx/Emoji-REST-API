<?php

namespace Demo;

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use PDO;

class App
{
    /**
     * Stores an instance of the Slim application.
     *
     * @var \Slim\App
     */
    protected $app;

    public function __construct()
    {
        $settings = require __DIR__.'/../src/settings.php';
        $app = new \Slim\App($settings);
        // Set up dependencies
        require __DIR__.'/../src/dependencies.php';
        // Register routes
        require __DIR__.'/../src/routes.php';
        $capsule = new Capsule;

        $this->loadEnv();
        $this->app = $app;
        $this->capsule = $capsule;
        $this->setUpDatabaseManager();
    }


    /**
     * Setup Eloquent ORM.
     */
    private function setUpDatabaseManager()
    {
        //Register the database connection with Eloquent
        $this->capsule->addConnection(
            [
                'driver'    => getenv('driver'),
                'host'      => getenv('host'),
                'database'  => getenv('database'),
                'username'  => getenv('username'),
                'password'  => getenv('password'),
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    /**
     * Load Dotenv to grant getenv() access to environment variables in .env file.
     */
    public function loadEnv()
    {
        $dotenv = new Dotenv(__DIR__.'/../');
        $dotenv->load();
    }
   
    /**
     * Get an instance of the application.
     *
     * @return \Slim\App
     */
    public function get()
    {
        return $this->app;
    }
}
