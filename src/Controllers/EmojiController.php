<?php 

namespace Demo;

use Illuminate\Database\Capsule\Manager as Capsule;
use Firebase\JWT\JWT;
use Carbon\Carbon;
use Demo\AuthMiddleware;
use Demo\Emoji;
use Demo\Keyword;
use Demo\User;
use Demo\Category;
use Exception;


class EmojiController
{
 
	/**
	 * @route GET /emojis
	 *
	 * @method  emojis (GET) Return all records of emojis from database.
	 *
	 * @requiredParams none
	 * @queryParams none
	 *
	 * @return JSON     List of all emojis
	 */
	public function getAllEmojis($request, $response, $args)
	{
		$emoji = Emoji::with('keywords', 'category', 'created_by')->get();

        if (count($emoji) > 0) {
            return $response->withJson($this->formatEmoji($emoji));
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
        $emoji = Emoji::create([
            'name'       => strtolower($requestParams['name']),
            'chars'      => $requestParams['chars'],
            'category'   => $requestParams['category'],
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),        
            'created_by' => $this->getUserId($request, $response),
        ]);

        if ($emoji->id) {
            $createdKeyword = $this->createEmojiKeywords($emoji->id, $requestParams['keywords']);
            return $response->withJson($emoji->toArray(), 201);
        }
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
    public function updateEmoji($emoji, $response, $updateParams)
    {
    	$user = $request->getAttribute('user');
        $emoji = $user->emoji()->find($args['id']);
        if (!$emoji) {
            $emoji = Emoji::find($args['id']);
            if (!$emoji) {
                return $this->create($request, $response, $args);
            }

            return $response->withJson(['message' => 'Access denied, you are not the creator of this Emoji.'], 404);
        }

        $emoji->update($request->getParsedBody());
        return $response->withJson(['message' => 'Emoji updated successfully.'], 200);
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
        $user = $request->getAttribute('user');
        $emoji = $user->emojis()->find($args['id']);
        if (!$emoji) {
             return $response->withJson(['message' => 'Access denied, you are not the creator of this Emoji.'], 404);
        }

        $emoji->delete();
        return $response->withJson(['message' => 'Emoji successfully deleted.'], 200);
    }

    /**
     * Search for emojis.
     *
     * @param Slim\Http\Request  $request
     * @param Slim\Http\Response $response
     * @param array              $args
     *
     * @return Slim\Http\Response
     */
    public function searchEmoji($request, $response, $args)
    {
        switch ($field) {
		    case "name":
		        $result = Emoji::searchByName($searchValue)->get();
		        break;

		    case "category":
		        $result = Emoji::searchByCategoryName($searchValue)->get();
		        break;

		    case "created_by":
		        $result = Emoji::searchByCreatorName($searchValue)->get();
		        break;
		}

        return $response->withJson($result);
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
    	// $appSecret = getenv('APP_SECRET');
     //    $jwtAlgorithm = getenv('JWT_ALGORITHM');
     //    $timeIssued = time();
     //    $tokenId = base64_encode(getenv('TOKENID'));
     //    $token = [
     //        'iss'  => 'http://suyabay-staging.herokuapp.com/',
     //        'iat'  => $timeIssued,   // Issued at: time when the token was generated
     //        'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
     //        'nbf'  => $timeIssued, //Not before time
     //        'exp'  => $timeIssued + 60 * 60 * 24 * 30, // expires in 30 days
     //        'data' => [                  // Data related to the signer user
     //        ],
     //    ];
        
     //    return JWT::encode($token, $appSecret, $jwtAlgorithm);

        $userJwt = new AuthMiddleware();
    	$userJwt = $this->getUserToken($request);
        $jwtToken = JWT::decode($userJwt, getenv('APP_SECRET'), [getenv('JWT_ALGORITHM')]);

        if (isset($jwtoken)) {
            $secretKey = base64_decode(getenv('secret'));
            $jwt = json_decode($jwtoken[0], true);
            $decodedToken = JWT::decode($jwt['jwt'], $secretKey, ['HS512']);
            $tokenInfo = (array) $decodedToken;
            $userInfo = (array) $tokenInfo['dat'];

            return $userInfo['id'];
        } 

        return $response->withJson(['status' => $e->getMessage()], 401);
    }

    /**
     * Format emoji information return by Eloquent for API format.
     *
     * @param Illuminate\Database\Eloquent\Collection $emojis
     *
     * @return void
     */
    private function formatEmoji($emojis)
    {
        $emojis = $emojis->toArray();
        foreach ($emojis as $key => &$value) {
            $value['keywords'] = array_map(function ($arr) { return $arr['name']; }, $value['keywords']);
            $value['category'] = $value['category']['category_name'];
            $value['created_by'] = $value['created_by']['username'];
        }

        return $emojis;
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
            foreach ($splittedKeywords as $keyword) {
                $emojiKeyword = Keyword::create([
                        'emoji_id'     => $emoji_id,
                        'keyword_name' => $keyword,
                        'created_at'   => $created_at,
                ]);
            }
        }
        return $emojiKeyword->id;
    }
}
