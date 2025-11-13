<?php

use Slim\Routing\RouteCollectorProxy;

return function ($app) {

    $app->group('/categories', function (RouteCollectorProxy $group) {

        /**
             * @OA\Get(
             *     path="/categories",
             *     summary="view a list of all categories",
             *     tags={"List category"},
             *     @OA\Parameter(
             *         name="",
             *         in="",
             *         required=true,
             *         description="",
             *         @OA\Schema(
             *             type="",
             *             example=""
             *         )
             *     ),
             *     @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"),
             *     @OA\Response(response="400", description="Missing required fields"),
             *     @OA\Response(response="404", description="Not found")
         */
        $group->get('', function ($request, $response) {
            require_once __DIR__ . '/../config/database.php';

            $db = new Database();
            $conn = $db->connect();

            //select all ifo from categrs
            $stmt = $conn->prepare("SELECT * FROM categories");
            $stmt->execute();

            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode($categories));
            return $response->withHeader('Content-Type', 'application/json');
        });

        /**
             * @OA\Get(
             *     path="/categories/{category_id}",
             *     summary="search for a category by its ID",
             *     tags={"seach category ID"},
             *     @OA\Parameter(
             *         name="category_id",
             *         in="/{path}",
             *         required=true,
             *         description="Each category has a unique ID",
             *         @OA\Schema(
             *             type="integer",
             *             example="1"
             *         )
             *     ),
             *     @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"),
             *     @OA\Response(response="400", description="Missing required fields"),
             *     @OA\Response(response="404", description="Not found")
         */
        $group->get('/{category_id}', function ($request, $response, $args) {
            require_once __DIR__ . '/../config/database.php';

            $db = new Database();
            $conn = $db->connect();

            $category_id = $args['category_id'];

            // seach ID
            $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = :category_id");
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->execute();

            $category = $stmt->fetch(PDO::FETCH_ASSOC);

            //if dont exist error 404
            if (!$category) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode([
                    'error' => 'Category not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($category));
            return $response->withHeader('Content-Type', 'application/json');
        });

        /**
             * @OA\Post(
             *     path="/categories",
             *     summary="be able to create a new category",
             *     tags={},
             *     requestBody=@OA\RequestBody(
             *         request="/categories",
             *         required=true,
             *         description="the necessary information, name, and active",
             *         @OA\MediaType(
             *             mediaType="application/json",
             *             @OA\Schema(
             *                 @OA\Property(property="name", type="string", example="electronic"),
             *                 @OA\Property(property="activ", type="bool", example="true")
             *             )
             *         )
             *     ),
             *     @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"),
             *     @OA\Response(response="400", description="Missing required fields"),
             *     @OA\Response(response="404", description="Not found")
             * )
        */
        $group->post('', function ($request, $response) {
            require_once __DIR__ . '/../config/database.php';

            $db = new Database();
            $conn = $db->connect();

            $data = $request->getParsedBody();

            // Validate required field
            if (!isset($data['name'])) {
                $response->getBody()->write(json_encode([
                    'error' => 'Field "name" is required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $name = $data['name'];
            $active = isset($data['active']) ? (int)$data['active'] : 1;

            // Create new category
            $stmt = $conn->prepare("INSERT INTO categories (name, active) VALUES (:name, :active)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':active', $active);
            $stmt->execute();

            $category_id = $conn->lastInsertId();

            $response->getBody()->write(json_encode([
                'message' => 'Category created successfully',
                'category_id' => $category_id
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        });
        
        /**
             * @OA\Put(
             *     path="/categories/{category_id}",
             *     summary="update an existing category",
             *     tags={"categoty_id"},
             *     @OA\Parameter(
             *         name="category_id",
             *         in="/{path}",
             *         required=true,
             *         description="Each category has a unique ID",
             *         @OA\Schema(
             *             type="integer",
             *             example="1"
             *         )
             *     ),
             *     requestBody=@OA\RequestBody(
             *         request="/categories",
             *         required=true,
             *         description="the necessary information, name, and active",
             *         @OA\MediaType(
             *             mediaType="application/json",
             *             @OA\Schema(
             *                 @OA\Property(property="name", type="string", example="electronic"),
             *                 @OA\Property(property="activ", type="bool", example="true")
             *             )
             *         )
             *     ),
             *     @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"),
             *     @OA\Response(response="400", description="Missing required fields"),
             *     @OA\Response(response="404", description="Not found")
             * )
        */
        $group->put('/{category_id}', function ($request, $response, $args) {
            require_once __DIR__ . '/../config/database.php';

            $db = new Database();
            $conn = $db->connect();

            $category_id = $args['category_id'];
            $data = $request->getParsedBody();

            // Validate required field
            if (!isset($data['name']) || !isset($data['active'])) {
                $response->getBody()->write(json_encode([
                    'error' => 'Fields "name" and "active" are required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Category Exist
            $check = $conn->prepare("SELECT * FROM categories WHERE category_id = :id");
            $check->bindParam(':id', $category_id, PDO::PARAM_INT);
            $check->execute();

            if ($check->rowCount() === 0) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode([
                    'error' => 'Category not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            // Update Category
            $stmt = $conn->prepare("UPDATE categories SET name = :name, active = :active WHERE category_id = :id");
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':active', $data['active'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $category_id, PDO::PARAM_INT);
            $stmt->execute();

            $response->getBody()->write(json_encode([
                'message' => 'Category updated successfully',
                'category_id' => $category_id
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        });

        /**
             * @OA\Delete(
             *     path="/categories/{category_id}",
             *     summary="Delete a category",
             *     tags={"category_id"},
             *     @OA\Parameter(
             *         name="category_id",
             *         in="/path",
             *         required=true,
             *         description="Each category has a unique ID",
             *         @OA\Schema(
             *             type="integer",
             *             example="1"
             *         )
             *     ),
             *     @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"),
             *     @OA\Response(response="400", description="Missing required fields"),
             *     @OA\Response(response="404", description="Not found")
             * )
        */
        $group->delete('/{category_id}', function ($request, $response, $args) {
            require_once __DIR__ . '/../config/database.php';

            $db = new Database();
            $conn = $db->connect();

            $category_id = intval($args['category_id']);

            // Category Exist
            $check = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
            $check->execute([$category_id]);

            if ($check->rowCount() === 0) {
                $response->getBody()->write(json_encode([
                    'error' => 'Category not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Delete Category
            $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->execute([$category_id]);

            $response->getBody()->write(json_encode([
                'message' => 'Category deleted successfully',
                'category_id' => $category_id
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        });

    });

};
