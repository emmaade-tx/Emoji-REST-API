<?php

/**
 * @author: Raimi Ademola <ademola.raimi@andela.com>
 * @copyright: 2016 Andela
 */
namespace Demo;

//use Exception;
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
       $authHeader = $request->getHeader('HTTP_AUTHORIZATION');

        try {
           if (!empty($authHeader)) {
                $appSecret    = getenv('APP_SECRET');
                $jwt          = $authHeader[0];   
                $decodedToken = JWT::decode($jwt, $appSecret, ['HS512']);
                
                return $next($request, $response);
            }
        } catch (Exception $e) {
            return $response->withJson(['status: Token invalid or Expired'], 500);
        }
       
        return $response->withJson(['message' => 'User unauthorized due to empty token'], 401);
   }
}
