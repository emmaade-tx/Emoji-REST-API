<?php

namespace Demo;

use Firebase\JWT\JWT;
use Demo\Model\User;

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
            if (is_array($jwtoken) && ! empty($jwtoken)) {
                    $secretKey = base64_decode(getenv('APP_SECRET'));
                    $jwt = json_decode($jwtoken[0], true);

                    $decodedToken = JWT::decode($jwt['jwt'], $secretKey, ['HS256']);
                    $tokenInfo = (array) $decodedToken;
                    $userInfo = (array) $tokenInfo['data'];
                    return $userInfo['id'];
            }
        }catch (Exception $e) {
            return $response->withJson(['status' : 'fail' ,'msg':'Unauthorized']);
        }
        
        return $response->withJson(['message' => 'User unauthorized due to invalid token'], 401);
    }
}