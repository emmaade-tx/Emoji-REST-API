<?php

/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */
namespace Demo;

use Carbon\Carbon;
use Firebase\JWT\JWT;
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
     * @return json response
     */
    public function login($request, $response)
    {
        $userData         = $request->getParsedBody();
        $validateResponse = $this->validateUserData(['username', 'password'], $userData);

        if (is_array($validateResponse)) {
            return $response->withJson($validateResponse, 400);
        }

        $user = $this->authenticate($userData['username'], $userData['password']);

        if (!$user) {
            return $response->withJson(['message' => 'Username or Password field not valid.'], 400);
        }

        $issTime = $request->getAttribute('issTime') == null ? time() : $request->getAttribute('issTime');
        $token   = $this->generateToken($user->id, $issTime);
   
        return $response->withAddedHeader('HTTP_AUTHORIZATION', $token)->withStatus(200)->write($token);
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
        $time         = $time == null ? time() : $time;
        $appSecret    = getenv('APP_SECRET');
        $jwtAlgorithm = getenv('JWT_ALGORITHM');
        $timeIssued   = $time;
        $tokenId      = base64_encode($time);
        $token = [
            'iat'     => $timeIssued,   // Issued at: time when the token was generated
            'jti'     => $tokenId,          // Json Token Id: an unique identifier for the token
            'nbf'     => $timeIssued, //Not before time
            'exp'     => $timeIssued + 60 * 60 * 24 * 30, // expires in 30 days
            'data'    => [                  // Data related to the signer user
                'userId'  => $userId, // userid from the users tableu;
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
     * @return json response
     */
    public function register($request, $response)
    {
        $requestParams    = $request->getParsedBody();
        $validateUserData = $this->validateUserData(['fullname', 'username', 'password'], $requestParams);

        if (is_array($validateUserData)) {
            return $response->withJson($validateUserData, 400);
        }

        $validateEmptyInput = $this->checkEmptyInput($requestParams['fullname'], $requestParams['username'], $requestParams['password']);

        if (is_array($validateEmptyInput)) {
            return $response->withJson($validateEmptyInput, 401);
        }

        if (User::where('username', $requestParams['username'])->first()) {
            return $response->withJson(['message' => 'Username already exist.'], 409);
        }

        User::create(
            [
                'fullname'   => $requestParams['fullname'],
                'username'   => strtolower($requestParams['username']),
                'password'   => password_hash($requestParams['password'], PASSWORD_DEFAULT),
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
     * @return json response
     */
    public function logout(Request $request, Response $response)
    {
        $request->getAttribute('users');
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
    public function authenticate($username, $password)
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
     * @param $expectedFields
     * @param $userData
     *
     * @return bool
     */
    public function validateUserData($expectedFields, $userData)
    {
        $tableFields = [];
        $tableValues = [];

        foreach ($userData as $key => $val) {
            $tableFields[] = $key;
            $tableValues[] = $val;
        }

        $result = array_diff($expectedFields, $tableFields);

        if (count($result) > 0 && empty($userData)) {
            return ['message' => 'All fields must be provided.'];
        }

        $tableValues = implode('', $tableValues);

        if (empty($tableValues)) {
            return ['message' => 'All fields are required'];
        }

        foreach ($userData as $key => $val) {
            if (!in_array($key, $expectedFields)) {
                return ['message' => 'Unwanted fields must be removed'];
            }
        }

        return true;
    }

    /**
     * This method checks for empty input from user.
     *
     * @param $inputName
     * @param $inputChars
     * @param $inputCategory
     * @param $inputKeywords
     *
     * @return bool
     */
    public function checkEmptyInput($inputFullname, $inputUsername, $inputPassword)
    {
        if (empty($inputFullname) || empty($inputUsername) || empty($inputPassword)) {
            return ['message' => 'All fields must be provided.'];
        }

        return true;
    }
}
