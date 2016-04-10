<?php

namespace Demo;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;

class EmojiController
{
    public $authController;
    public $app;

    public function __construct()
    {
        $this->authController = new AuthController();
        $this->app = new app();
    }

    /**
     * @route GET /emojis
     *
     * @method  emojis (GET) Return all records of emojis from database.
     *
     * @requiredParams none
     * @queryParams none
     *
     * @return JSON List of all emojis
     */
    public function getAllEmojis($request, $response, $args)
    {
        $emoji = Emoji::with('keywords', 'category', 'created_by')->get();

        if (count($emoji) > 0) {
            return $response->withJson($emoji);
        }

        return $response->withJson(['message' => 'Oops, No Emoji to display'], 404);
    }

    /**
     * Get a single emoji.
     *
     * @param Slim\Http\Request  $request
     * @param Slim\Http\Response $response
     * @param array              $args
     *
     * @return Slim\Http\Response
     */
    public function getSingleEmoji($request, $response, $args)
    {
        $emoji = Emoji::with('keywords', 'category', 'created_by')->find($args['id']);
        if (count($emoji) < 1) {
            return $response->withJson(['message' => 'The requested Emoji is not found.'], 404);
        }

        return $response->withJson($emoji);
    }

    /**
     * This method creates emoji and keywords associated with it.
     *
     * @param $request
     * @param $response
     * @param $requestParams
     *
     * @return json response
     */
    public function CreateEmoji($request, $response, $requestParams)
    {
        $requestParams = $request->getParsedBody();

        $validateResponse = $this->authController->validateUserData(['name', 'chars', 'category', 'keywords'], $requestParams);

        if (is_array($validateResponse)) {
            return $response->withJson($validateResponse, 400);
        }

        if (empty($requestParams['name']) || empty($requestParams['chars']) || empty($requestParams['category']) || empty($requestParams['keywords'])) {
            return $response->withJson(['message' => 'All fields must be provided.'], 401);
        }

        $emoji = Emoji::create([
            'name'       => strtolower($requestParams['name']),
            'chars'      => $requestParams['chars'],
            'category'   => $requestParams['category'],
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
            'created_by' => $this->getUserId($request, $response),
        ]);

        $createdKeyword = $this->createEmojiKeywords($emoji->id, $requestParams['keywords']);
        //$createdCategories = $this->createEmojiCategories($emojiCategory->id, $requestParams['category_name']);

        return $response->withJson($emoji->toArray(), 201);
    }

    /**
     * This method updates an emoji.
     *
     * @param $emoji
     * @param $response
     * @param $updateParams
     *
     * @return json $response
     */
    public function updateEmojiByPatch($request, $response, $args)
    {
        $updateParams = $request->getParsedBody();

        $validateResponse = $this->authController->validateUserData(['name'], $updateParams);

        if (is_array($validateResponse)) {
            return $response->withJson($validateResponse, 400);
        }

        $emoji = Emoji::find($args['id']);
        if (count($emoji) < 1) {
            return $response->withJson(['message' => 'No record to update because the id supplied is invalid'], 404);
        }

        if (is_null($this->getTheOwner($args, $request, $response)->first())) {
            return $response->withJson(['message' => 'Emoji cannot be updated because you are not the creator'], 401);
        }

        Emoji::where('id', '=', $args['id'])
        ->update(['name' => strtolower($updateParams['name']), 'updated_at' => Carbon::now()->toDateTimeString()]);

        return $response->withJson($emoji->toArray(), 200);
    }

    /**
     * This method updates an emoji.
     *
     * @param $emoji
     * @param $response
     * @param $updateParams
     *
     * @return json $response
     */
    public function updateEmojiByPut($request, $response, $args)
    {
        $updateParams = $request->getParsedBody();

        $validateResponse = $this->authController->validateUserData(['name', 'chars', 'category'], $updateParams);

        if (is_array($validateResponse)) {
            return $response->withJson($validateResponse, 400);
        }

        $emoji = Emoji::find($args['id']);
        if (count($emoji) < 1) {
            return $response->withJson(['message' => 'No record to update because the id supplied is invalid'], 404);
        }

        if (is_null($this->getTheOwner($args, $request, $response)->first())) {
            return $response->withJson(['message' => 'Emoji cannot be updated because you are not the creator'], 401);
        }

        Emoji::where('id', '=', $args['id'])
        ->update(['name' => strtolower($updateParams['name']), 'chars' => $updateParams['chars'], 'category' => $updateParams['category'], 'updated_at' => Carbon::now()->toDateTimeString()]);

        return $response->withJson($emoji, 200);
    }

    /**
     * Route for deleting an emoji.
     *
     * @param Slim\Http\Request  $request
     * @param Slim\Http\Response $response
     * @param array              $args
     *
     * @return Slim\Http\Response
     */
    public function deleteEmoji($request, $response, $args)
    {
        $emoji = Emoji::find($args['id']);
        if (count($emoji) < 1) {
            return $response->withJson(['message' => 'No record to delete because the id supplied is invalid'], 404);
        }

        if (is_null($this->getTheOwner($args, $request, $response)->first())) {
            return $response->withJson(['message' => 'Emoji cannot be deleted because you are not the creator'], 401);
        }

        $emoji->where('id', '=', $args['id'])->delete();

        return $response->withJson(['message' => 'Emoji successfully deleted.'], 200);
    }

    /**
     * This method checks for duplicate.
     *
     * @param Slim\Http\Request  $request
     * @param Slim\Http\Response $response
     * @param array              $args
     *
     * @return Slim\Http\Response
     */
    public function checkDuplicateEmoji($ExistedField, $userData)
    {
    }

    /**
     * This method authenticate and return user id.
     *
     * @param $response
     * @param $request
     *
     * @return user id
     */
    public function getUserId($request, $response)
    {
        $jwtoken = $request->getHeader('HTTP_AUTHORIZATION');

        try {
            if (isset($jwtoken)) {
                $secretKey = getenv('APP_SECRET');

                $jwt = $jwtoken[0];

                $decodedToken = JWT::decode($jwt, $secretKey, ['HS256']);

                $tokenInfo = (array) $decodedToken;
                $userInfo = (array) $tokenInfo['data'];

                return $userInfo['userId'];
            }
        } catch (Exception $e) {
            return $response->withJson(['status: fail, msg: Unauthorized']);
        }
    }

    /**
     * This method solves for rightful of a record.
     */
    public function getTheOwner($args, $request, $response)
    {
        return Capsule::table('emojis')->where('id', '=', $args['id'])
        ->where('created_by', '=', $this->getUserId($request, $response));
    }

    /**
     * This method creates emoji keywords.
     *
     * @param $request
     * @param $response
     * @param $args
     *
     * @return $id
     */
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
}
