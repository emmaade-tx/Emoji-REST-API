<?php

namespace Demo;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Demo\Middleware\AuthMiddleware;

/*
|--------------------------------------------------------------------------
| This verb returns error 200
|
| @param $request
|
| @param $response
|
| @return json $response
|--------------------------------------------------------------------------
*/
$app->get('/', function (Request $request, Response $response) {
    return $response->withJson(['message' => 'Welcome to Sweet Emoji'], 200);
});

/*
|--------------------------------------------------------------------------
| This verb returns error 404
|
| @param $request
|
| @param $response
|
| @return json $response
|--------------------------------------------------------------------------
*/
$app->post('/', function (Request $request, Response $response) {
    return $response->withStatus(404);
});

/*
|--------------------------------------------------------------------------
| These endpoints authenticate the user: login, register and logout
|
| @param $request
|
| @param $request
|
| @return json $response
|--------------------------------------------------------------------------
*/

$app->group('/auth', function () {
    $this->post('/login', "AuthController:login");
    $this->post('/register', "AuthController:register");
    $this->post('/logout', "AuthController:logout")
         ->add("AuthMiddleware");
});

/*
|--------------------------------------------------------------------------
| This verb returns all emoji
|
| @param $request
|
| @param $request
|
| @return json $response
|--------------------------------------------------------------------------
*/
$app->get('/emojis', 'EmojiController:getAllEmojis');

/*
|--------------------------------------------------------------------------
| This verb returns a single emoji
|
| @param $request
|
| @param $request
|
| @return json $response
|--------------------------------------------------------------------------
*/
$app->get('/emojis/{id}', 'EmojiController:getSingleEmoji');

/*
|--------------------------------------------------------------------------
| This verb creates a new emoji
|
| @param $request
|
| @param $request
|
| @return json $response
|--------------------------------------------------------------------------
*/
$app->post('/emojis', 'EmojiController:CreateEmoji');

/*
|--------------------------------------------------------------------------
| This verb updates an emoji
|
| @param $request
|
| @param $args
|
| @param $emoji
|
| @param $request
|
| @return json $response
|--------------------------------------------------------------------------
*/
$app->put('/emojis/{id}', 'EmojiController:updateEmojiByPut');

/*
|--------------------------------------------------------------------------
| This verb patially updates an emoji
|
| @param $request
|
| @param $args
|
| @param $emoji
|
| @param $request
|
| @return json $response
|--------------------------------------------------------------------------
*/
$app->patch('/emojis/{id}', 'EmojiController:updateEmojiByPatch');

/*
|--------------------------------------------------------------------------
| This verb deletes an emoji
|
| @param $request
|
| @param $args
|
| @param $request
|
| @return json $response
|--------------------------------------------------------------------------
*/
$app->delete('/emojis/{id}', 'EmojiController:deleteEmoji');





