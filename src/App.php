<?php

/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */
namespace Demo;

use Exception;
use Dotenv\Dotenv;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class App
{
    protected $app;
    protected $schema;
    protected $capsule;

    /**
     * This is a constructor; a default method  that will be called automatically during slim app instantiation.
     */
    public function __construct($path = null)
    {
        //$settings = require  __DIR__.'/settings.php';
        //$app = new App($settings);
        // Set up dependencies
        require  __DIR__.'/dependencies.php';
        // Register routes
        require  __DIR__.'/routes.php';
        
        $this->app = $app;
        $this->capsule = new Capsule();
        $this->schema = new DatabaseSchema();
        $this->loadEnv($path); 
        $this->setUpDatabaseManager();
        $this->setupDatabaseSchema();
    }

    /**
     * Setup Eloquent ORM.
     */
    private function setUpDatabaseManager()
    {
        //Register the database connection with Eloquent
        $config = [
            'driver'    => getenv('driver'),
            'host'      => getenv('host'),
            'database'  => getenv('database'),
            'username'  => getenv('username'),
            'password'  => getenv('password'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ];

        $this->capsule->addConnection($config);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    /**
     * Create necessary database tables needed in the application.
     */
    public function setupDatabaseSchema()
    {
        $this->schema->createUsersTable();
        $this->schema->createEmojisTable();
        $this->schema->createKeywordsTable();
    }

    /**
     * Load Dotenv to grant getenv() access to environment variables in .env file.
     */
    public function loadEnv($path = null)
    {
        $path = $path == null ? __DIR__ . '/../' : $path;
        $dotenv = new Dotenv($path);
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
