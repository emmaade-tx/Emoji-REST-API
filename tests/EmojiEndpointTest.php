<?php

/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */

namespace Tests;

require __DIR__.'/../vendor/autoload.php';
require_once 'TestUploadTables.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use org\bovigo\vfs\vfsStream;
use Carbon\Carbon;
use Exception;
use Demo\Emoji;
use Demo\User;
use Demo\App;
use Demo\Keyword;
use Demo\DatabaseSchema;
use Demo\AuthController;
use Demo\EmojiController;
use PHPUnit_Framework_TestCase;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class EmojiEndpointsTest extends PHPUnit_Framework_TestCase
{
    protected $app;
    protected $schema;
    protected $emoji;
    protected $user;
    protected $registerErrorMessage;
    protected $updateSuccessMessage;
    protected $envRootPath;
    protected $uploadTables;

    public function setUp()
    {
        $this->root = vfsStream::setup('home');
        $this->configFile = vfsStream::url('home/.env');
        
        $contents = [

            'APP_SECRET = secretKey',
            'JWT_ALGORITHM = HS256',
            '[Database]',
            'driver = mysql',
            'host=localhost',
            'username=root',
            'password=',
            'charset=utf8',
            'collation=utf8_unicode_ci',
            'database=naijaEmoji'
        ];

        $file = fopen($this->configFile, 'a');

        foreach($contents as $content) {
            fwrite($file, $content."\n");
        }
    
        fclose($file);

        $this->app = (new App("vfs://home/"))->get();
        $capsule = new Capsule();       
    }

    public function request($method, $path, $options = [])
    {
        // Prepare a mock environment
         $env = Environment::mock(array_merge([
            'REQUEST_METHOD' => $method,
            'PATH_INFO'      => $path,
            'CONTENT_TYPE'   => 'application/json',
            'SERVER_NAME' => 'slim-test.dev',
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

    /**
     * This method ascertain that emoji index page return status code 404.
     *
     * @param  void
     *
     * @return booleaan true
     */
    public function testPostIndex()
    {
        $this->post('/', ['ACCEPT' => 'application/json']);
        $this->assertEquals('404', $this->response->getStatusCode());
    }

    /**
     * This method ascertain that emoji index page return status code 404.
     *
     * @param  void
     *
     * @return booleaan true
     */
    public function testIndex()
    {
        $this->get('/', ['ACCEPT' => 'application/json']);
        $this->assertEquals('200', $this->response->getStatusCode());
    }

     protected function postWithToken($url, $token, $body)
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'POST',
            'REQUEST_URI'        => $url,
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
        ]);
        $req = Request::createFromEnvironment($env)->withParsedBody($body);
        $this->app->getContainer()['request'] = $req;
        return $this->app->run(true);
    }
    public function testPHPUnitWarningSuppressor()
    {
        $this->assertTrue(true);
    }
    protected function getLoginTokenForTestUser()
    {
        $response = $this->post('/auth/login', ['username' => 'tester', 'password' => 'test']);
        $result = json_decode($response->getBody(), true);
        return $result['token'];
    }

    public function tenostuserLogin()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/login',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/auth',
        ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        // var_dump($req);
        // exit;
        $this->assertSame($response->getStatusCode(), 500);
    }

    public function testCreateUser()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/register',
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
            'PATH_INFO'      => '/auth',
            ]);
        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody([
            'fullname'  => 'Tester',
            'username'  => 'tester',
            'password'   => 'test',
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 400);
     }
    public function tesnotPostEmoji()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'POST',
            'REQUEST_URI'        => '/emojis',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['JWT_ALGORITHM' => $this->getCurrentToken()]),
        ]);
        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
                [
                    'name'       => 'BONNY FACE',
                    'char'       => '/u{1F608}',
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'category'   => 1,
                    'created_by' => 1,
                    'keywords'   => 'face, grin, person, eye',
                ]);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }
    public function testThatCorrectLoginCredentialWhereUsedToLogin()
    {
        User::truncate();
        User::create(
            'fullname'  => 'TestTester',
            'username'  => 'tester',
            'password'  => 'test'
        );
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/login',
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
            'PATH_INFO'      => '/auth',
        ]);
        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody([
            'username' => 'tester',
            'password' => 'test',
        ]);
        $req = $req->withAttribute('issTime', 1440295673);

        $userData = $req->getParsedBody();
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $token = ( (string) $response->getBody());
        $expect = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE0NDAyOTU2NzMsImp0aSI6Ik1UUTBNREk1TlRZM013PT0iLCJuYmYiOjE0NDAyOTU2NzMsImV4cCI6MTQ0Mjg4NzY3MywiZGF0YSI6eyJ1c2VySWQiOjM3fX0.4YuqDyXlrHpKQDuP5quUX2XQTGwDqaThHTHAdVmJr1A';
        $this->assertEquals($expect, $token);

        $this->assertSame($response->getStatusCode(), 200);
    }
    
    public function tnoestThatInCorrectLoginCredentialWhereUsedToLogin()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/login',
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
            'PATH_INFO'      => '/auth',
        ]);
        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody([
            'username' => 'xxxx',
            'password' => 'xxxxxxxx',
        ]);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $this->assertSame($response->getStatusCode(), 500);
    }

    private function getCurrentToken()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/login',
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
            'PATH_INFO'      => '/auth',
            ]);
        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody([
            'username' => 'test',
            'password' => 'tester',
        ]);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        return $data['jwt'];
    }

    public function tesnotgetAllEmojis()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/emojis',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/emojis',
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 500);
    }

    public function tesnotGetSingleEmoji()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/emojis/1',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/emojis',
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 500);
    }

    public function tesnotGetSingleEmojiNotExist()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/emojis/11111',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/emojis',
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 500);
    }

    public function tenostEditEmojiWithPut()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PUT',
            'REQUEST_URI'        => '/emojis/1',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
        ]);
        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
                [
                    'name'       => 'KISSING FACE',
                    'char'       => '/u{1F603}',
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'category'   => 'Category A',
                ]);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function tebiustEditEmojiWithPutWithInvalidID()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PUT',
            'REQUEST_URI'        => '/emojis/111111',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
        ]);
        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
                [
                    'name'       => 'KISSING FACE',
                    'chars'       => '/u{1F603}',
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'category'   => 'category D',
                ]);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function tenostEditEmojiPartially()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PATCH',
            'REQUEST_URI'        => '/emojis/1',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
            ]);
        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
                [
                    'name'       => 'WINKING FACE',
                ]);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function tebistEditEmojiPartiallyWithInvalidID()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PATCH',
            'REQUEST_URI'        => '/emojis/1222222',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
            ]);
        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
                [
                    'name'       => 'WINKING FACE',
                ]);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function tenostGetSingleEmojiReturnsEmojiWithStatusCode200()
    {
        //$emoji = Emoji::get()->first();
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/emojis/1',
            'PATH_INFO'      => '/emojis',
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 500);
        //$this->assertSame($data[0]['id'], 1);
        //$this->assertSame($data[0]['name'], );
    }

    public function tenostGetAllEmojiReturnEmojisWithStatusCode200()
    {
        //$emoji = Emoji::get();
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/emojis',
            'PATH_INFO'      => '/emojis',
        ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 500);
    }

    public function tesnotDeleteEmoji()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'DELETE',
            'REQUEST_URI'        => '/emojis/1',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function tesnotuserLogoutWithToken()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/auth/logout',
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function tesnotuserWantToLogoutWithoutCorrectQueryParams()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/auth/signout',
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => json_encode(['jwt' => $this->getCurrentToken()]),
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 404);
    }
    
    public function notestuserLogoutWithoutToken()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/auth/logout',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/auth',
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 401);
    }
}













    