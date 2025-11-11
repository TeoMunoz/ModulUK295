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
             *     @OA\Response(response="200", description="Erklärung der Antwort mit Status 200"))
             *     @OA\Response(response="404", description="Not found"))
         */
        $group->get('', function ($request, $response) {
            require_once __DIR__ . '/../config/database.php';

            $db = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("SELECT * FROM categories");
            $stmt->execute();

            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode($categories));
            return $response->withHeader('Content-Type', 'application/json');
        });

    });

};
