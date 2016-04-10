<?php

namespace Demo;

use Demo\Model\User;
use Firebase\JWT\JWT;

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
        $jwtoken = $request->getHeader('HTTP_AUTHORIZATION');

        try {
            if (is_array($jwtoken) && !empty($jwtoken)) {
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

        return $response->withJson(['message' => 'User unauthorized due to invalid token'], 401);
    }
}
