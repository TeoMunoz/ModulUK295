<?php

use Slim\Routing\RouteCollectorProxy;

return function ($app) {

    $app->group('/products', function (RouteCollectorProxy $group) {

        /**
        * @OA\Get(
        *   path="/products {products}",
        *   summary="you see a list of all existing products",
        *   tags={"list products"},
        *   @OA\Parameter(
        *       name="products",
        *       in="path",
        *       required=true,
        *       description="the products available in the store",
        *       @OA\Schema(
        *           type="string",
        *           example="Monitor"
        *       )
        *   ),
        *   @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"))
        *   @OA\Response(response="404", description="Not found"))
        */
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

    /**
     * @OA\Post(
     *     path="/products",
     *     summary="you can create a new product",
     *     tags={"create product"},
     *     requestBody=@OA\RequestBody(
     *         request="/products",
     *         required=true,
     *         description="products",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Laptop"),
     *                  @OA\Property(property="price", type="number", format="float", example=1000.90),
     *                  @OA\Property(property="stock", type="integer", example=10)
     *                  )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Created product"))
     *     @OA\Response(response="400", description="Missing required fields"))
     * )
    */
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

        /**
             * @OA\Get(
             *     path="/products/{id}",
             *     summary="search for a product by its ID",
             *     tags={"search ID"},
             *     @OA\Parameter(
             *         name="id",
             *         in="/{id}",
             *         required=true,
             *         description="Each product has an ID and is unique",
             *         @OA\Schema(
             *             type="int",
             *             example="1"
             *         )
             *     ),
             *     @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"))
             *     @OA\Response(response="400", description="invalid input"))
             *     @OA\Response(response="404", description="Not found"))
         */
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

        /**
             * @OA\Put(
             *     path="/products/{1}",
             *     summary="update an existing product",
             *     tags={"Update prosuct},
             *     @OA\Parameter(
             *         name="id",
             *         in="/{1}",
             *         required=true,
             *         description="Each product has an ID and is unique",
             *         @OA\Schema(
             *             type="int",
             *             example="1"
             *         )
             *     ),
             *     requestBody=@OA\RequestBody(
             *         request="/products/{1}",
             *         required=true,
             *         description="all product information such as name, price, ID, etc",
             *         @OA\MediaType(
             *             mediaType="application/json",
             *             @OA\Schema(
             *              @OA\Property(property="name", type="string", example="Laptop"),
             *              @OA\Property(property="price", type="number", format="float", example=1000.90),
             *              @OA\Property(property="stock", type="integer", example=10)
             *             )
             *         )
             *     ),
             *     @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"))
             *     @OA\Response(response="400", description="invalid input"))
             *     @OA\Response(response="404", description="Not found"))
             * )
        */
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

    /**
         * @OA\Delete(
         *     path="/products/{1}",
         *     summary="delete an existing product)",
         *     tags={"Delete prosuct"},
         *     @OA\Parameter(
         *         name="ID",
         *         in="/{1}",
         *         required=true,
         *         description="Each product has an ID and is unique",
         *         @OA\Schema(
         *             type="int",
         *             example="1"
         *         )
         *     ),
         *          @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"))
         *          @OA\Response(response="400", description="invalid input"))
         *          @OA\Response(response="404", description="Not found"))
         * )
    */
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
