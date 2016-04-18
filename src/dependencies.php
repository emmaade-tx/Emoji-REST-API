<?php

/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */
namespace Demo;




use Demo\AuthMiddleware;
use Demo\AuthController;
use Demo\EmojiController;

// DIC configuration
$container = $app->getContainer();

$container['EmojiController'] = function ($container) {
    return new EmojiController();
};

$container['AuthController'] = function ($container) {
    return new AuthController();
};

$container['AuthMiddleware'] = function ($container) {
    return new AuthMiddleware();
};
