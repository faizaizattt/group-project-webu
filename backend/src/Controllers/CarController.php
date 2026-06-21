<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;
use PDO;
use Exception;

class CarController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAll(Request $request, Response $response): Response {
        $params = $request->getQueryParams();

        $search = trim($params['search'] ?? '');
        $category = trim($params['category'] ?? '');
        $transmission = trim($params['transmission'] ?? '');
        $fuelType = trim($params['fuelType'] ?? '');
        $availableOnly = filter_var($params['availableOnly'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $sort = trim($params['sort'] ?? '');

        // Base query
        $query = "SELECT * FROM cars WHERE 1=1";
        $binds = [];

        // Dynamic filters (SQL injection safe using parameter binding)
        if (!empty($search)) {
            $query .= " AND (LOWER(name) LIKE :search OR LOWER(brand) LIKE :search OR LOWER(category) LIKE :search)";
            $binds[':search'] = '%' . strtolower($search) . '%';
        }
        if (!empty($category) && $category !== 'all') {
            $query .= " AND category = :category";
            $binds[':category'] = $category;
        }
        if (!empty($transmission) && $transmission !== 'all') {
            $query .= " AND transmission = :transmission";
            $binds[':transmission'] = $transmission;
        }
        if (!empty($fuelType) && $fuelType !== 'all') {
            $query .= " AND fuel_type = :fuelType";
            $binds[':fuelType'] = $fuelType;
        }
        if ($availableOnly) {
            $query .= " AND available = 1";
        }

        // Sorting
        if ($sort === 'price-asc') {
            $query .= " ORDER BY price_per_day ASC";
        } else if ($sort === 'price-desc') {
            $query .= " ORDER BY price_per_day DESC";
        } else if ($sort === 'name') {
            $query .= " ORDER BY name ASC";
        } else if ($sort === 'year') {
            $query .= " ORDER BY year DESC";
        } else {
            $query .= " ORDER BY id ASC";
        }

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($binds);
            $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format response array to match frontend camelCase requirements
            $formatted = array_map([$this, 'formatCar'], $cars);

            return $this->jsonResponse($response, $formatted);
        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Database query failed: " . $e->getMessage()], 500);
        }
    }

    public function getById(Request $request, Response $response, array $args): Response {
        $id = $args['id'];

        try {
            $stmt = $this->db->prepare("SELECT * FROM cars WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $car = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$car) {
                return $this->jsonResponse($response, ["message" => "Car not found"], 404);
            }

            return $this->jsonResponse($response, $this->formatCar($car));
        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Database query failed: " . $e->getMessage()], 500);
        }
    }

    public function create(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);

        $name = trim($data['name'] ?? '');
        $brand = trim($data['brand'] ?? '');
        $category = trim($data['category'] ?? '');
        $year = intval($data['year'] ?? 0);
        $pricePerDay = floatval($data['pricePerDay'] ?? 0);
        $seats = intval($data['seats'] ?? 0);
        $transmission = trim($data['transmission'] ?? '');
        $fuelType = trim($data['fuelType'] ?? '');
        $imageUrl = trim($data['imageUrl'] ?? '');
        $description = trim($data['description'] ?? '');
        
        $featuresList = $data['features'] ?? [];
        $features = is_array($featuresList) ? implode(',', $featuresList) : trim($featuresList);

        if (empty($name) || empty($brand) || $pricePerDay <= 0) {
            return $this->jsonResponse($response, ["message" => "Missing required fields or price is invalid"], 400);
        }

        try {
            // Generate CAR-xxx format code dynamically based on inventory size
            $countStmt = $this->db->query("SELECT COUNT(*) as total FROM cars");
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $newId = "CAR-" . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $this->db->prepare("INSERT INTO cars (id, name, brand, category, year, price_per_day, seats, transmission, fuel_type, available, image_url, features, description) 
                                        VALUES (:id, :name, :brand, :category, :year, :price_per_day, :seats, :transmission, :fuel_type, 1, :image_url, :features, :description)");
            
            $stmt->execute([
                ':id' => $newId,
                ':name' => $name,
                ':brand' => $brand,
                ':category' => $category,
                ':year' => $year,
                ':price_per_day' => $pricePerDay,
                ':seats' => $seats,
                ':transmission' => $transmission,
                ':fuel_type' => $fuelType,
                ':image_url' => $imageUrl,
                ':features' => $features,
                ':description' => $description
            ]);

            // Retrieve created record
            $getStmt = $this->db->prepare("SELECT * FROM cars WHERE id = :id");
            $getStmt->execute([':id' => $newId]);
            $newCar = $getStmt->fetch(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, $this->formatCar($newCar), 201);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Create failed: " . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);

        try {
            // Check existence
            $checkStmt = $this->db->prepare("SELECT * FROM cars WHERE id = :id");
            $checkStmt->execute([':id' => $id]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                return $this->jsonResponse($response, ["message" => "Car not found"], 404);
            }

            // Build dynamic update query to only touch modified fields
            $fields = [];
            $binds = [':id' => $id];

            if (isset($data['name'])) { $fields[] = "name = :name"; $binds[':name'] = trim($data['name']); }
            if (isset($data['brand'])) { $fields[] = "brand = :brand"; $binds[':brand'] = trim($data['brand']); }
            if (isset($data['category'])) { $fields[] = "category = :category"; $binds[':category'] = trim($data['category']); }
            if (isset($data['year'])) { $fields[] = "year = :year"; $binds[':year'] = intval($data['year']); }
            if (isset($data['pricePerDay'])) { $fields[] = "price_per_day = :price_per_day"; $binds[':price_per_day'] = floatval($data['pricePerDay']); }
            if (isset($data['seats'])) { $fields[] = "seats = :seats"; $binds[':seats'] = intval($data['seats']); }
            if (isset($data['transmission'])) { $fields[] = "transmission = :transmission"; $binds[':transmission'] = trim($data['transmission']); }
            if (isset($data['fuelType'])) { $fields[] = "fuel_type = :fuel_type"; $binds[':fuel_type'] = trim($data['fuelType']); }
            if (isset($data['imageUrl'])) { $fields[] = "image_url = :image_url"; $binds[':image_url'] = trim($data['imageUrl']); }
            if (isset($data['description'])) { $fields[] = "description = :description"; $binds[':description'] = trim($data['description']); }
            if (isset($data['available'])) { $fields[] = "available = :available"; $binds[':available'] = $data['available'] ? 1 : 0; }
            
            if (isset($data['features'])) { 
                $featuresList = $data['features'];
                $fields[] = "features = :features"; 
                $binds[':features'] = is_array($featuresList) ? implode(',', $featuresList) : trim($featuresList);
            }

            if (empty($fields)) {
                return $this->jsonResponse($response, $this->formatCar($existing));
            }

            $query = "UPDATE cars SET " . implode(", ", $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute($binds);

            // Fetch refreshed item
            $checkStmt->execute([':id' => $id]);
            $updated = $checkStmt->fetch(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, $this->formatCar($updated));

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Update failed: " . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $id = $args['id'];

        try {
            $stmt = $this->db->prepare("DELETE FROM cars WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() === 0) {
                return $this->jsonResponse($response, ["message" => "Car not found"], 404);
            }

            return $this->jsonResponse($response, ["status" => "success", "message" => "Car deleted successfully"]);
        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Delete failed: " . $e->getMessage()], 500);
        }
    }

    public function toggleAvailability(Request $request, Response $response, array $args): Response {
        $id = $args['id'];

        try {
            // Get current state
            $stmt = $this->db->prepare("SELECT available FROM cars WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $car = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$car) {
                return $this->jsonResponse($response, ["message" => "Car not found"], 404);
            }

            $newVal = $car['available'] ? 0 : 1;
            $upStmt = $this->db->prepare("UPDATE cars SET available = :new_val WHERE id = :id");
            $upStmt->execute([':new_val' => $newVal, ':id' => $id]);

            // Return updated details
            $getStmt = $this->db->prepare("SELECT * FROM cars WHERE id = :id");
            $getStmt->execute([':id' => $id]);
            $updatedCar = $getStmt->fetch(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, $this->formatCar($updatedCar));

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Operation failed: " . $e->getMessage()], 500);
        }
    }

    // Helper formatter to convert snake_case (DB) to camelCase (JS/Vue)
    private function formatCar(array $car): array {
        return [
            'id' => $car['id'],
            'name' => $car['name'],
            'brand' => $car['brand'],
            'category' => $car['category'],
            'year' => intval($car['year']),
            'pricePerDay' => floatval($car['price_per_day']),
            'seats' => intval($car['seats']),
            'transmission' => $car['transmission'],
            'fuelType' => $car['fuel_type'],
            'available' => filter_var($car['available'], FILTER_VALIDATE_BOOLEAN),
            'imageUrl' => $car['image_url'],
            'features' => !empty($car['features']) ? explode(',', $car['features']) : [],
            'description' => $car['description']
        ];
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
