<?php

use Slim\Routing\RouteCollectorProxy;

return function ($app) {

    $app->group('/products', function (RouteCollectorProxy $group) {

        //GET products
        $group->get('', function ($request, $response) {
            require_once __DIR__ . '/../config/database.php';

            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("SELECT * FROM products");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode($products));
            return $response->withHeader('Content-Type', 'application/json');
        });

        //POST products
        $group->post('', function ($request, $response) {
            require_once __DIR__ . '/../config/database.php';

            $db = new Database();
            $conn = $db->connect();

            $data = $request->getParsedBody();

            if (!isset($data['name']) || !isset($data['price'])) {
                $response->getBody()->write(json_encode([
                    'error' => 'name and price are required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $name  = $data['name'];
            $price = $data['price'];
            $stock = $data['stock'] ?? 0;

            $stmt = $conn->prepare("INSERT INTO products (name, price, stock) VALUES (?, ?, ?)");
            $stmt->execute([$name, $price, $stock]);

            $response->getBody()->write(json_encode([
                'message' => 'Product created successfully'
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        });

    });

};
