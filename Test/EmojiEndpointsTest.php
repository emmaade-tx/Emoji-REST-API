<?php
/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */
namespace Tests;

require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Slim\Http\Environment;
use Slim\Http\Request;

class EmojiEndpointsTest extends PHPUnit_Framework_TestCase
{
    private $root;
    private $dotEnvFile;

    /*
     *
     */
    public function setUp()
    {
        $this->root = vfsStream::setup('home');
        $this->dotEnvFile = vfsStream::url('home/.env');

        $data = [
                'APP_SECRET=secretKey',
                'JWT_ALGORITHM = HS256',
                '[Database]',
                'driver = mysql',
                'host = 127.0.0.1:33060',
                'database = naijaEmoji',
                'username = homestead',
                'password = secret',
            ];

        $fileEnv = fopen($this->dotEnvFile, 'a');

        foreach ($data as $val) {
            fwrite($fileEnv, $val."\n");
        }

        fclose($fileEnv);

        $this->app = new App('vfs://home/');
        $this->capsult = new Capsule();
    }

    public function request($method, $path, $options = [])
    {
        // Prepare a mock environment
         $env = Environment::mock(array_merge([
            'REQUEST_METHOD' => $method,
            'PATH_INFO'      => $path,
            'CONTENT_TYPE'   => 'application/json',
            'SERVER_NAME'    => 'slim-test.dev',
            ], $options));
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $this->response = $this->app->run(true);
    }

    /**
     * This method defines a get request for all emojis endpoint.
     *
     * @param  $path
     * @param  $options
     *
     * @return $request
     */
    public function get($path, $options = [])
    {
        $this->request('GET', $path, $options);
    }

    /**
     * @param  $path
     * @param  $options
     *
     * @return $request
     */
    public function post($path, $options = [])
    {
        $this->request('POST', $path, $options);
    }

    public function testSetUpDatabaseManager()
    {
    }
}
