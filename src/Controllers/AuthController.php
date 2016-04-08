<?php 

namespace Demo;

use Demo\User;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    /**
     * Login a user.
     *
     * @param Slim\Http\Request  $request
     * @param Slim\Http\Response $response
     *
     * @return Slim\Http\Response
     */
    public function login($request, $response)
    {
        $userData = $request->getParsedBody();
        if (User::where('username', $userData['username'])->first() && User::where('password', $userData['password'])->first()) {
            return $response->withJson(['token' => $this->generateToken($user->id)]);
        }

        return $response->withJson(['message' => 'Username or Password field not valid.'], 400);
    }

    /**
     * Generate a token for user with passed Id.
     *
     * @param int $userId
     *
     * @return string
     */
    private function generateToken($userId)
    {
        $appSecret = getenv('APP_SECRET');
        $jwtAlgorithm = getenv('JWT_ALGORITHM');
        $timeIssued = time();
        $tokenId = base64_encode(mcrypt_create_iv(32));
        $token = [
            'iat'  => $timeIssued,   // Issued at: time when the token was generated
            'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
            'nbf'  => $timeIssued, //Not before time
            'exp'  => $timeIssued + 60 * 60 * 24 * 30, // expires in 30 days
            'data' => [                  // Data related to the signer user
                'userId'   => $userId, // userid from the users table
            ],
        ];
        return JWT::encode($token, $appSecret, $jwtAlgorithm);
    }

    /**
     * Register a user.
     *
     * @param Slim\Http\Request  $request
     * @param Slim\Http\Response $response
     *
     * @return Slim\Http\Response
     */
    public function register($request, $response)
    {
        $userData = $request->getParsedBody();
        if ($this->validateUserData($userData)) {
            return $response->withJson(['message' => 'Username, fullname or Password field not provided.'], 400);
        }
        if (User::where('username', $userData['username'])->first()) {
            return $response->withJson(['message' => 'Username already exist.'], 409);
        }
        User::firstOrCreate(
                [
                    'fullname'   => $userData['fullname'],
                    'username'   => strtolower($userData['username']),
                    'password'   => password_hash($userData['password'], PASSWORD_DEFAULT),
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]);
        return $response->withJson(['message' => 'User successfully created.'], 201);
    }

    /**
     * This method logout the user.
     *
     * @param $args logout
     *
     * @return $response
     */
    public function logout(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        return $response->withJson(['message' => 'Logout successful'], 200);
    }


    /**
     * Authenticate username and password against database.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function userAuthenticate($username, $password)
    {
        $user = User::where('username', $username)->get();
        if ($user->isEmpty()) {
            return false;
        }
        $user = $user->first();
        if (password_verify($password, $user->password)) {
            return $user;
        }

        return false;
    }

    
    /**
     * Validate user data are correct.
     *
     * @param array $userData
     *
     * @return bool
     */
    private function validateUserData($userData)
    {
        return !$userData || !$this->keysExistAndNotEmptyString(['username', 'password', 'fullname'], $userData);
    }

    /**
     * Checks if all keys in an array are in another array and their values are not empty string.
     *
     * @param array $requiredStrings
     * @param array $searchData
     *
     * @return bool
     */
    public function keysExistAndNotEmptyString($requiredStrings, $searchData)
    {
        foreach ($requiredStrings as $key => $value) {
            if (!$this->keyExistAndNotEmptyString($value, $searchData)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if a key is in an array and the value of the key is not an empty string.
     *
     * @param array $key
     * @param array $searchData
     *
     * @return bool
     */
    public function keyExistAndNotEmptyString($key, $searchData)
    {
        return isset($searchData[$key]) && !empty($searchData[$key]) && is_string($searchData[$key]) && trim($searchData[$key]);
    }
}

