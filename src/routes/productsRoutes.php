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

        //GET /products/{id}
        $group->get('/{id}', function ($request, $response, $args) {
            require_once __DIR__ . '/../config/database.php';

            $db = new Database();
            $conn = $db->connect();

            $id = $args['id'];

            $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode([
                'error' => 'Product not found'
            ]));
        return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($product));
        return $response->withHeader('Content-Type', 'application/json');
        });

        //PUT products/{id}
        $group->put('/{id}', function ($request, $response, $args) {
        require_once __DIR__ . '/../config/database.php';

        $db = new Database();
        $conn = $db->connect();

        $id = $args['id'];
        $data = $request->getParsedBody();

        // Validate
        if (!isset($data['name'], $data['price'], $data['stock'])) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode([
                "error" => "Missing fields: name, price, stock"
            ]));
            return $response->withHeader('Content-Type', 'application/json');
    }

        // Product exist
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            $response = $response->withStatus(404);
            $response->getBody()->write(json_encode([
                "error" => "Product not found"
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Update Product
        $stmt = $conn->prepare("
            UPDATE products 
            SET name = :name, price = :price, stock = :stock
            WHERE id = :id
        ");

        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':stock', $data['stock']);
        $stmt->bindParam(':id', $id);

        $stmt->execute();

        $response->getBody()->write(json_encode([
            "message" => "Product updated successfully",
            "id" => $id
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    });

    // DELETE /products/{id}
    $group->delete('/{id}', function ($request, $response, $args) {
        require_once __DIR__ . '/../config/database.php';

        $db = new Database();
        $conn = $db->connect();

        $id = intval($args['id']);

        // Exist
        $check = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $check->execute([$id]);

        if ($check->rowCount() === 0) {
            $response->getBody()->write(json_encode(['error' => 'Product not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Delete
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        $response->getBody()->write(json_encode(['message' => 'Product deleted']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    });

};
