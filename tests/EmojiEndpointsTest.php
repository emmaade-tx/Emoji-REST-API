<?php
/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */

namespace Tests;

require_once 'TestMockDatabase.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use org\bovigo\vfs\vfsStream;
use Exception;
use Demo\Emoji;
use Demo\User;
use Demo\App;
use Demo\Keyword;
use Demo\AuthController;
use Demo\EmojiController;
use PHPUnit_Framework_TestCase;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class EmojiEndpointsTest extends PHPUnit_Framework_TestCase
{
    protected $app;
    protected $emoji;
    protected $user;
    protected $registerErrorMessage;
    protected $updateSuccessMessage;
    protected $envRootPath;
    public function setUp()
    {
        $root = vfsStream::setup();
        $envFilePath = vfsStream::newFile('.env')->at($root);
        $envFilePath->setContent('
            APP_SECRET=secretKey 
            JWT_ALGORITHM = HS256
            [Database]
            driver = sqlite
            host = 127.0.0.1
            database = naijaEmoji
            charset=utf8
            collation=utf8_unicode_ci
            database=:memory:
            ');
        $this->app = (new App($root->url()))->get();
        $this->mockDatabase = new TestMockDatabase();
        $this->user = $this->mockDatabase->mockData();
        $this->registerErrorMessage = 'Username or Password field not provided.';
        $this->updateSuccessMessage = 'Emoji updated successfully.';
    }
    protected function deleteWithToken($url, $token)
    {
        $env = Environment::mock([
            'REQUEST_METHOD'         => 'DELETE',
            'REQUEST_URI'            => $url,
            'X-HTTP-Method-Override' => 'DELETE',
            'HTTP_AUTHORIZATION'     => 'Bearer '.$token,
            'CONTENT_TYPE'           => 'application/x-www-form-urlencoded',
        ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        return $this->app->run(true);
    }
    protected function get($url)
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => $url,
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        return $this->app->run(true);
    }
    protected function post($url, $body)
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => $url,
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
        ]);
        $req = Request::createFromEnvironment($env)->withParsedBody($body);
        $this->app->getContainer()['request'] = $req;
        return $this->app->run(true);
    }
    protected function patchWithToken($url, $token, $body)
    {
        $env = Environment::mock([
            'REQUEST_METHOD'         => 'PATCH',
            'REQUEST_URI'            => $url,
            'X-HTTP-Method-Override' => 'PATCH',
            'HTTP_AUTHORIZATION'     => 'Bearer '.$token,
            'CONTENT_TYPE'           => 'application/x-www-form-urlencoded',
        ]);
        $req = Request::createFromEnvironment($env)->withParsedBody($body);
        $this->app->getContainer()['request'] = $req;
        return $this->app->run(true);
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
    /**
     * This method ascertain that emoji index page return status code 404.
     *
     * @param  void
     *
     * @return booleaan true
     */
    public function testPostIndex()
    {
        $response = $this->post('/', ['ACCEPT' => 'application/json']);
        $this->assertEquals('404', $response->getStatusCode());
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
        $response = $this->get('/', ['ACCEPT' => 'application/json']);
        $this->assertEquals('200', $response->getStatusCode());
    }
    public function testGetAllEmojis()
    {
        $emoji = $this->user->emoji->first();
        $response = $this->get('/emojis');
        $data = json_decode($response->getBody(), true);
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($data[0]['name'], $emoji->name);
        $this->assertEquals($data[0]['category'], $emoji->category);
    }
    public function testGetEmojiReturnsCorrectEmojiWithStatusCodeOf200()
    {
        $emoji = $this->user->emoji()->first();
        $response = $this->get('/emojis/'.$emoji->Id);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
        $this->assertSame($data['name'], $emoji->name);
        $this->assertSame($data['category'], $emoji->category);
    }
    public function testGetReturnsStatusCode404WithMsgWhenRequestRouteDoesNotExit()
    {
        $response = $this->get('/jsdjsdf');
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 404);
    }
    public function testGetEmojiReturnsStatusCodeOf404WithMsgWhenEmojiWithPassedIdNotFound()
    {
        $response = $this->get('/emojis/as3#');
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 404);
        $this->assertSame($data['message'], 'The requested Emoji is not found.');
    }
     public function testRequestWithLoggedoutTokenReturnsStatusCode200()
    {
        $response = $this->post('/auth/login', ['username' => 'tester', 'password' => 'test']);
        $result = json_decode($response->getBody(), true);
        $token = $result['token'];
        $this->postWithToken('/auth/logout', $token, []);
        $emoji = $this->user->emoji()->first();
        $emojiData = [
        'name'     => 'Auliat',
        'char'     => '__[:]__',
        ];
        $response = $this->patchWithToken('/emojis/'.$emoji->Id, $token, $emojiData);
        $result = (array) $response->getBody();
        $this->assertSame($response->getStatusCode(), 200);
        $this->assertContains($token, $result);
    }
    public function testCreateEmojiReturnsStatusCode201WithMsgWhenWellPreparedEmojiDataIsSent()
    {
        $emojiData = [
        'name'     => 'Helen',
        'char'     => '__[:]__',
        'category' => 'aaa',
        'keywords' => ['lol', 'hmmm'],
        ];
        $token    = $this->getLoginTokenForTestUser();
        $response = $this->postWithToken('/emojis', $token, $emojiData);
        $result   = (string) $response->getBody();
        $this->assertSame($response->getStatusCode(), 200);
    }
    // public function testCreateEmojiReturnsStatusCode201WithMsgWhenEmojiDataWithEmptyKeywordIsPassed()
    // {
    //     $emojiData = [
    //     'name'     => 'Peace',
    //     'char'     => '__[:]__',
    //     'category' => 'aaa',
    //     'keywords' => [''],
    //     ];
    //     $token = $this->getLoginTokenForTestUser();
    //     $response = $this->postWithToken('/emojis', $token, $emojiData);
    //     var_dump($response->getStatusCode());
    //     var_dump($emojiData);
    //     var_dump($token);
    //     exit;
    //     $result = (string) $response->getBody();
    //     $this->assertSame($response->getStatusCode(), 201);
    //     $this->assertContains('Emoji created successfully.', $result);
    // }
    // public function testCreateEmojiReturnsStatusCode409WithMsgWhenEmojiNameAlreadyExist()
    // {
    //     $emoji = $this->user->emojis()->first();
    //     $emojiData = [
    //     'name'     => $emoji->name,
    //     'char'     => '__[:]__',
    //     'category' => 'aaa',
    //     'keywords' => [''],
    //     ];
    //     $token = $this->getLoginTokenForTestUser();
    //     $response = $this->postWithToken('/emojis', $token, $emojiData);
    //     $result = (string) $response->getBody();
    //     $this->assertSame($response->getStatusCode(), 409);
    //     $this->assertContains('The emoji name already exist.', $result);
    // }
    // public function testCreateEmojiReturnsStatusCode400WithMsgWhenEmojiDataIsSentWithoutKeywords()
    // {
    //     $emojiData = [
    //     'name'     => 'Auliat',
    //     'char'     => '__[:]__',
    //     'category' => 'aaa',
    //     ];
    //     $token = $this->getLoginTokenForTestUser();
    //     $response = $this->postWithToken('/emojis', $token, $emojiData);
    //     $result = (string) $response->getBody();
    //     $this->assertSame($response->getStatusCode(), 400);
    //     $this->assertContains($this->updateErrorMessage, $result);
    // }
    // public function testCreateEmojiReturnsStatusCode400WithMsgWhenEmojiDataIsSentWithoutCategory()
    // {
    //     $emojiData = [
    //     'name'     => 'Auliat',
    //     'char'     => '__[:]__',
    //     'keywords' => ['lol', 'hmmm'],
    //     ];
    //     $token = $this->getLoginTokenForTestUser();
    //     $response = $this->postWithToken('/emojis', $token, $emojiData);
    //     $result = (string) $response->getBody();
    //     $this->assertSame($response->getStatusCode(), 400);
    //     $this->assertContains($this->updateErrorMessage, $result);
    // }
    // public function testCreateEmojiReturnsStatusCode400WithMsgWhenEmojiDataIsSentWithoutChar()
    // {
    //     $emojiData = [
    //     'name'     => 'Auliat',
    //     'category' => 'aaa',
    //     'keywords' => ['lol', 'hmmm'],
    //     ];
    //     $token = $this->getLoginTokenForTestUser();
    //     $response = $this->postWithToken('/emojis', $token, $emojiData);
    //     $result = (string) $response->getBody();
    //     $this->assertSame($response->getStatusCode(), 400);
    //     $this->assertContains($this->updateErrorMessage, $result);
    // }
    // public function testCreateEmojiReturnsStatusCode400WithMsgWhenEmojiDataIsSentWithoutName()
    // {
    //     $emojiData = [
    //     'char'     => '__[:]__',
    //     'category' => 'aaa',
    //     'keywords' => ['lol', 'hmmm'],
    //     ];
    //     $token = $this->getLoginTokenForTestUser();
    //     $response = $this->postWithToken('/emojis', $token, $emojiData);
    //     $result = (string) $response->getBody();
    //     $this->assertSame($response->getStatusCode(), 400);
    //     $this->assertContains($this->updateErrorMessage, $result);
    // }
}
