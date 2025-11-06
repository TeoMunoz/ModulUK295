<?php

use Slim\Routing\RouteCollectorProxy;

use ReallySimpleJWT\Token;

$secret = 'sec!ReT423*&';

return function ($app) {

    $app->group('/auth', function (RouteCollectorProxy $group) {
        global $secret;

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
