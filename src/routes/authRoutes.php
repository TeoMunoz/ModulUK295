<?php

use Slim\Routing\RouteCollectorProxy;

use ReallySimpleJWT\Token;

$secret = 'sec!ReT423*&';

return function ($app) {

    $app->group('/auth', function (RouteCollectorProxy $group) {
        global $secret;

        /**
         * @OA\Post(
         *      path="/auth/login",
         *      summary="The user enters a username and password to authenticate themselves.",
         *      tags={Login},
         *      requestBody=@OA\RequestBody(
         *          request="/auth/login",
         *          required=true,
         *          description="Authenticate a name, password, and create a token",
         *          @OA\MediaType(
         *              mediaType="application/json",
         *              @OA\Schema(
         *                  @OA\Property(property="username", type="string", example="admin"),
         *                  @OA\Property(property="eigenpasswordschaft", type="string", example="1234")
         *              )
         *            )
         *          ),
         *          @OA\Response(response="200", description="Login successful"),
         *          @OA\Response(response="401", description="Invalid credentials")
         * )
        */
        $group->post('/login', function ($request, $response) {
            $data = $request->getParsedBody();
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            $validUser = 'admin';
            $validPass = '1234';

            if ($username === $validUser && $password === $validPass) {
                $response->getBody()->write(json_encode([
                    'message' => 'Login successful',
                    'user' => $username
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        });

        $userId = 1;
        $expiration = time() + 3600;
        $issuer = 'localhost';

        $token = Token::create($userId, $secret, $expiration, $issuer);

        setcookie("token", $token);

    });

};
