<?php

/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */
namespace Demo;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
| These endpoints groups route that requires Middleware
|
| @param $request
|
| @param $request
|
|--------------------------------------------------------------------------
*/
$app->group('/', function () {
    $this->post('emojis', 'EmojiController:CreateEmoji');
    $this->patch('emojis/{id}', 'EmojiController:updateEmojiByPatch');
    $this->put('emojis/{id}', 'EmojiController:updateEmojiByPut');
    $this->delete('emojis/{id}', 'EmojiController:deleteEmoji');
})->add('AuthMiddleware');

/*
|--------------------------------------------------------------------------
| These verb logs out a user
|
| @param $request
|
| @param $request
|
|--------------------------------------------------------------------------
*/
$app->get('/auth/logout', 'AuthController:logout')->add('AuthMiddleware');

/*
|--------------------------------------------------------------------------
| This verb returns all emoji
|
| @param $request
|
| @param $request
|
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
|--------------------------------------------------------------------------
*/
$app->get('/emojis/{id}', 'EmojiController:getSingleEmoji');

/*
|--------------------------------------------------------------------------
| This verb authenticate an existing user
|
| @param $request
|
| @param $request
|
|--------------------------------------------------------------------------
*/
$app->post('/auth/login', 'AuthController:login');

/*
|--------------------------------------------------------------------------
| This verb registers a new user
|
| @param $request
|
| @param $args
|
| @param $emoji
|
| @param $request
|
|--------------------------------------------------------------------------
*/
$app->post('/auth/register', 'AuthController:register');
