<?php

/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */

namespace Demo;

use Demo\User;
use Firebase\JWT\JWT;
use Exception;

class AuthMiddleware
{
    /**
     * Middleware invokable class method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        $authHeader = $request->getHeader('HTTP_AUTHORIZATION');


        try {
            if (! empty($authHeader)) {
                $secretKey = getenv('APP_SECRET');
                $jwt = $authHeader[0];
                //decode the JWT using the key from config
                $decodedToken = JWT::decode($jwt, $secretKey, ['HS256']);

                return $next($request, $response);
            }
        } catch (Exception $e) {
           return $response->withJson(['status: fail, msg: Unauthorized']);
        }
        return $response->withJson(['message' => 'User unauthorized due to invalid token'], 401);
    }
}
