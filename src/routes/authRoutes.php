<?php

use Slim\Routing\RouteCollectorProxy;

return function ($app) {

    $app->group('/auth', function (RouteCollectorProxy $group) {

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

    });

};
