<?php

use Slim\Routing\RouteCollectorProxy;

return function ($app) {

    $app->group('/products', function (RouteCollectorProxy $group) {

        /**
        * @OA\Get(
        *   path="/products",
        *   summary="you see a list of all existing products",
        *   tags={"list products"},
        *   @OA\Parameter(
        *       name="",
        *       in="path",
        *       required=true,
        *       description="the products available in the store",
        *       @OA\Schema(
        *           type="string",
        *           example="Monitor"
        *       )
        *   ),
        *   @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"),
        *   @OA\Response(response="404", description="Not found")
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
     *     @OA\Response(response="201", description="Created product"),
     *     @OA\Response(response="400", description="Missing required fields")
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

            $stmt = $conn->prepare("
                INSERT INTO products (sku, active, id_category, name, image, description, price, stock)
                VALUES (:sku, :active, :id_category, :name, :image, :description, :price, :stock)
            ");

            $stmt->execute([
                ':sku' => $data['sku'] ?? null,
                ':active' => $data['active'] ?? 1,
                ':id_category' => $data['id_category'] ?? null,
                ':name' => $data['name'],
                ':image' => $data['image'] ?? null,
                ':description' => $data['description'] ?? null,
                ':price' => $data['price'],
                ':stock' => $data['stock'] ?? 0
            ]);

            $response->getBody()->write(json_encode([
                'message' => 'Product created successfully'
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        });

        /**
             * @OA\Get(
             *     path="/products/{product_id}",
             *     summary="search for a product by its ID",
             *     tags={"search ID"},
             *     @OA\Parameter(
             *         name="product_id",
             *         in="path",
             *         required=true,
             *         description="Each product has an ID and is unique",
             *         @OA\Schema(
             *             type="integer",
             *             example="1"
             *         )
             *     ),
             *     @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"),
             *     @OA\Response(response="400", description="invalid input"),
             *     @OA\Response(response="404", description="Not found")
         */
        $group->get('/{product_id}', function ($request, $response, $args) {
            require_once __DIR__ . '/../config/database.php';

            $db = new Database();
            $conn = $db->connect();

            $id = $args['product_id'];

            $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :product_id");
            $stmt->bindParam(':product_id', $id, PDO::PARAM_INT);
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
        $group->put('/{product_id}', function ($request, $response, $args) {
        require_once __DIR__ . '/../config/database.php';

        $db = new Database();
        $conn = $db->connect();

        $id = $args['product_id'];
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
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $id);
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
             *     path="/products/{product_id}",
             *     summary="update an existing product",
             *     tags={"Update prosuct"},
             *     @OA\Parameter(
             *         name="product_id",
             *         in="path",
             *         required=true,
             *         description="Each product has an ID and is unique",
             *         @OA\Schema(
             *             type="integer",
             *             example="1"
             *         )
             *     ),
             *     requestBody=@OA\RequestBody(
             *         request="/products/{product_id}",
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
             *     @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"),
             *     @OA\Response(response="400", description="invalid input"),
             *     @OA\Response(response="404", description="Not found")
             * )
        */
        $stmt = $conn->prepare("
            UPDATE products
            SET 
                sku = :sku,
                active = :active,
                id_category = :id_category,
                name = :name,
                image = :image,
                description = :description,
                price = :price,
                stock = :stock
            WHERE product_id = :id
        ");

        $stmt->execute([
            ':sku' => $data['sku'] ?? null,
            ':active' => $data['active'] ?? 1,
            ':id_category' => $data['id_category'] ?? null,
            ':name' => $data['name'],
            ':image' => $data['image'] ?? null,
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'],
            ':stock' => $data['stock'] ?? 0,
            ':id' => $id
        ]);

        $response->getBody()->write(json_encode([
            "message" => "Product updated successfully",
            "product_id" => $id
        ]));

return $response->withHeader('Content-Type', 'application/json');
    });

    /**
         * @OA\Delete(
         *     path="/products/{product_id}",
         *     summary="delete an existing product)",
         *     tags={"Delete prosuct"},
         *     @OA\Parameter(
         *         name="product_id",
         *         in="path",
         *         required=true,
         *         description="Each product has an ID and is unique",
         *         @OA\Schema(
         *             type="integer",
         *             example="1"
         *         )
         *     ),
         *          @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"),
         *          @OA\Response(response="400", description="invalid input"),
         *          @OA\Response(response="404", description="Not found")
         * )
    */
    $group->delete('/{product_id}', function ($request, $response, $args) {
        require_once __DIR__ . '/../config/database.php';

        $db = new Database();
        $conn = $db->connect();

        $id = intval($args['product_id']);

        // Exist
        $check = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $check->execute([$id]);

        if ($check->rowCount() === 0) {
            $response->getBody()->write(json_encode(['error' => 'Product not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Delete
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$id]);

        $response->getBody()->write(json_encode(['message' => 'Product deleted']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    });

};
