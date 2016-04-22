

/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */

namespace Tests;

use Demo\App;
use Demo\User;
use Exception;
use Demo\Emoji;
use Demo\Keyword;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Slim\Http\Request;
use Slim\Http\Response;
use Demo\DatabaseSchema;
use Demo\AuthController;
use Demo\EmojiController;
use Slim\Http\Environment;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

class EmojiEndpointsTest extends PHPUnit_Framework_TestCase
{
    protected $app;
    protected $schema;
    protected $emoji;
    protected $user;
    protected $envRootPath;

    public function setUp()
    {
        $this->root       = vfsStream::setup('home');
        $this->configFile = vfsStream::url('home/.env');
        
        $contents = [
            'APP_SECRET    = secretKey',
            'JWT_ALGORITHM = HS512',
            '[Database]',
            'driver = mysql',
            'host=localhost:33060',
            'username=homestead',
            'password=secret',
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
        $this->capsule = new Capsule();
        $this->schema = new DatabaseSchema();
        $this->schema->createUsersTable();
        $this->schema->createEmojisTable();
        $this->schema->createKeywordsTable();
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
    public function postIndex($path, $options = [])
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
        $this->postIndex('/', ['ACCEPT' => 'application/json']);
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

    private function generateToken($userId, $time = null)
    {
        $time = $time === null ? (time() - 10) : $time;
        $tokenId = base64_encode($time);
        $issuedAt = $time;
        $notBefore = $issuedAt + 10;
        $expire = $issuedAt + 200;
        $secretKey = getenv('APP_SECRET'); // or get the app key from the config file.
        $JWTToken = [
            'iat'  => $issuedAt,
            'jti'  => $tokenId,
            'nbf'  => 1455307623 + 10,
            'exp'  => 1481307683,
            'data' => ['userId' => $userId],
        ];
        $jwt = JWT::encode(
            $JWTToken,      //Data to be encoded in the JWT
            $secretKey, // The signing key
            'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );
    
        return $jwt;
    }

    protected function getLoginTokenForTestUser()
    {
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

        return $token;
    }

    private function populateUser()
    {
        User::create([
            'fullname' => 'John Tester',
            'username' => 'tester',
            'password' => 'test',
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);
    }

    private function populateEmoji()
    {
        $emoji = Emoji::create([
            'name'       => 'Grinning face',
            'chars'      => 'u-t1789',
            'category'   => 'Category A',
            'Keywords'   => ['happy','smile'],
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);

        $createdKeyword = $this->createEmojiKeywords($emoji->id, 'keywords');
    }

    public function createEmojiKeywords($emoji_id, $keywords)
    {
        if ($keywords) {
            $splittedKeywords = explode(',', $keywords);
            $created_at = Carbon::now()->toDateTimeString();
            $updated_at = Carbon::now()->toDateTimeString();
            foreach ($splittedKeywords as $keyword) {
                $emojiKeyword = Keyword::create([
                    'emoji_id'     => $emoji_id,
                    'keyword_name' => $keyword,
                    'created_at'   => $created_at,
                    'updated_at'   => $updated_at,
                ]);
            }
        }

        return $emojiKeyword->id;
    }

    public function testCreateUser()
    {   
        User::truncate();
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/register',
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
        ]);

        $body = [
            'fullname' => 'tester',
            'username' => 'tester',
            'password' => 'test',
        ];

        $req = Request::createFromEnvironment($env)->withParsedBody($body);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $result = json_decode($response->getBody(), true);
        $this->assertEquals($result['message'], 'User successfully created.');
        $this->assertSame($response->getStatusCode(), 201);
    }

    public function testuserLogin()
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/auth/login',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/auth',
        ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody([
            'username' => 'tester',
            'password' => 'test',
        ]);

        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testPostEmoji()
    {
        Emoji::truncate();
        User::truncate();
        Keyword::truncate();

        $this->populateUser();
        $user = User::find(1);
        $token = $this->generateToken($user->Id);

        

         $env = Environment::mock([
            'REQUEST_METHOD'     => 'POST',
            'REQUEST_URI'        => '/emojis',
            'HTTP_AUTHORIZATION' => $token,
        ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody([
            'name'       => 'Grinning Face',
            'chars'      => 'u-1F608',
            'category'   => 'Category A',
            'created_by' => 1,
            'keywords'   => ['Happy', 'smile'],
        ]);
        
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $result = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 500);
    }

    public function testThatCorrectLoginCredentialWhereUsedToLogin()
    {
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
        $this->assertSame($response->getStatusCode(), 400);
    }
    
    public function testThatInCorrectLoginCredentialWhereUsedToLogin()
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
        $data = json_decode($response->getBody(), true);
        $result = ['message' => 'Username or Password field not valid.'];
        $this->assertEquals($data, $result);
        $this->assertSame($response->getStatusCode(), 400);
    }

    public function testgetAllEmojis()
    {
        $this->populateEmoji();
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
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testGetSingleEmoji()
    {
        $this->populateEmoji();
        $env = Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/emojis/2',
            'CONTENT_TYPE'   => 'application/json',
            'PATH_INFO'      => '/emojis',
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 200);
    }

    public function testGetSingleEmojiNotExist()
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
        $result = ['message' => 'The requested Emoji is not found.'];
        $this->assertEquals($data, $result);
        $this->assertSame($response->getStatusCode(), 404);
    }

    public function testEditEmojiWithPut()
    {
        Emoji::truncate();
        User::truncate();
        Keyword::truncate();

        $this->populateUser();
        $user = User::find(1);
        $token = $this->generateToken($user->Id);

        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PUT',
            'REQUEST_URI'        => '/emojis/1',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => $token,
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
        $result = ['message' => 'Unwanted fields must be removed'];
        $this->assertEquals($data, $result);
        $this->assertSame($response->getStatusCode(), 400);
    }

    public function testEditEmojiWithPutWithInvalidID()
    {
        Emoji::truncate();
        User::truncate();
        Keyword::truncate();

        $this->populateUser();
        $user = User::find(1);
        $token = $this->generateToken($user->Id);

        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PUT',
            'REQUEST_URI'        => '/emojis/111111',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => $token,
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
        $result = ['message' => 'Unwanted fields must be removed'];
        $this->assertEquals($data, $result);
        $this->assertSame($response->getStatusCode(), 400);
    }

    public function testEditEmojiPartially()
    {
        Emoji::truncate();
        User::truncate();
        Keyword::truncate();

        $this->populateUser();
        $user = User::find(1);
        $token = $this->generateToken($user->Id);

        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PATCH',
            'REQUEST_URI'        => '/emojis/1',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => $token,
            ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
            [
                'name' => 'WINKING FACE',
            ]);

        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $this->assertSame($response->getStatusCode(), 401);
    }

    public function testEditEmojiPartiallyWithInvalidID()
    {
        Emoji::truncate();
        User::truncate();
        Keyword::truncate();

        $this->populateUser();
        $user = User::find(1);
        $token = $this->generateToken($user->Id);

        $env = Environment::mock([
            'REQUEST_METHOD'     => 'PATCH',
            'REQUEST_URI'        => '/emojis/1222222',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => $token,
        ]);

        $req = Request::createFromEnvironment($env);
        $req = $req->withParsedBody(
            [
                'name'       => 'WINKING FACE',
            ]);

        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $result = ['message' => 'Action cannot be performed because the id supplied is invalid'];
        $this->assertEquals($data, $result);
        $this->assertSame($response->getStatusCode(), 401);
    }

    public function testDeleteEmoji()
    {
        Emoji::truncate();
        User::truncate();
        Keyword::truncate();

        $this->populateUser();
        $user = User::find(1);
        $token = $this->generateToken($user->Id);

        $env = Environment::mock([
            'REQUEST_METHOD'     => 'DELETE',
            'REQUEST_URI'        => '/emojis/1',
            'CONTENT_TYPE'       => 'application/x-www-form-urlencoded',
            'HTTP_AUTHORIZATION' => $token,
            ]);

        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $result = ['message' => 'Action cannot be performed because the id supplied is invalid'];
        $this->assertEquals($data, $result);
        $this->assertSame($response->getStatusCode(), 401);
    }

    public function testuserLogoutWithToken()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/auth/logout',
            'CONTENT_TYPE'       => 'application/json',
            
            ]);

        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        //dd($response);
        $data = json_decode($response->getBody(), true);

        $result = ['message' => 'User unauthorized due to invalid token'];
        $this->assertEquals($data, $result);
        $this->assertSame($response->getStatusCode(), 401);
    }

    public function testuserLogoutWithoutCorrectToken()
    {
        $env = Environment::mock([
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/auth/logout',
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => '',
            ]);
        $req = Request::createFromEnvironment($env);
        $this->app->getContainer()['request'] = $req;
        $response = $this->app->run(true);
        $data = json_decode($response->getBody(), true);
        $result = ['0' => 'status: Token invalid or Expired'];
        $this->assertEquals($data, $result);
        $this->assertSame($response->getStatusCode(), 500);
    }
    
    public function testuserLogoutWithoutToken()
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
    