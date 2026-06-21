<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;
use PDO;
use Exception;

class CustomerController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAll(Request $request, Response $response): Response {
        try {
            $query = "SELECT c.*, u.email 
                      FROM customers c
                      JOIN users u ON c.user_id = u.id
                      ORDER BY c.join_date DESC";
            
            $stmt = $this->db->query($query);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formatted = array_map(function($c) {
                return [
                    'id' => intval($c['id']),
                    'userId' => intval($c['user_id']),
                    'name' => $c['name'],
                    'email' => $c['email'],
                    'phone' => $c['phone'],
                    'address' => $c['address'] ?? '',
                    'joinDate' => $c['join_date'],
                    'totalBookings' => intval($c['total_bookings'])
                ];
            }, $customers);

            return $this->jsonResponse($response, $formatted);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Fetch failed: " . $e->getMessage()], 500);
        }
    }

    public function create(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');
        $password = $data['password'] ?? 'customer123'; // Default fallback password

        if (empty($name) || empty($email) || empty($phone)) {
            return $this->jsonResponse($response, ["message" => "Name, email, and phone are required"], 400);
        }

        try {
            $this->db->beginTransaction();

            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                return $this->jsonResponse($response, ["message" => "Email address already registered"], 400);
            }

            // Create user
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $userStmt = $this->db->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, 'customer')");
            $userStmt->execute([
                ':email' => $email,
                ':password' => $hashed
            ]);
            $userId = $this->db->lastInsertId();

            // Create customer profile
            $custStmt = $this->db->prepare("INSERT INTO customers (user_id, name, phone, address, join_date, total_bookings) 
                                            VALUES (:user_id, :name, :phone, :address, CURRENT_DATE(), 0)");
            
            $custStmt->execute([
                ':user_id' => $userId,
                ':name' => $name,
                ':phone' => $phone,
                ':address' => $address
            ]);
            $customerId = $this->db->lastInsertId();

            $this->db->commit();

            return $this->jsonResponse($response, [
                'id' => intval($customerId),
                'userId' => intval($userId),
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'joinDate' => date('Y-m-d'),
                'totalBookings' => 0
            ], 201);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->jsonResponse($response, ["message" => "Create failed: " . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Response $response, array $args): Response {
        $id = intval($args['id']);
        $data = json_decode($request->getBody()->getContents(), true);

        try {
            // Find existing customer
            $checkStmt = $this->db->prepare("SELECT * FROM customers WHERE id = :id");
            $checkStmt->execute([':id' => $id]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                return $this->jsonResponse($response, ["message" => "Customer not found"], 404);
            }

            $this->db->beginTransaction();

            // Update email in users table if changed
            if (isset($data['email'])) {
                $email = trim($data['email']);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $this->jsonResponse($response, ["message" => "Invalid email format"], 400);
                }

                // Check duplicate email
                $dupStmt = $this->db->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
                $dupStmt->execute([':email' => $email, ':user_id' => $existing['user_id']]);
                if ($dupStmt->fetch()) {
                    return $this->jsonResponse($response, ["message" => "Email address already in use"], 400);
                }

                $upUser = $this->db->prepare("UPDATE users SET email = :email WHERE id = :user_id");
                $upUser->execute([':email' => $email, ':user_id' => $existing['user_id']]);
            }

            // Update customer details
            $fields = [];
            $binds = [':id' => $id];

            if (isset($data['name'])) { $fields[] = "name = :name"; $binds[':name'] = trim($data['name']); }
            if (isset($data['phone'])) { $fields[] = "phone = :phone"; $binds[':phone'] = trim($data['phone']); }
            if (isset($data['address'])) { $fields[] = "address = :address"; $binds[':address'] = trim($data['address']); }

            if (!empty($fields)) {
                $query = "UPDATE customers SET " . implode(", ", $fields) . " WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->execute($binds);
            }

            $this->db->commit();

            // Fetch refreshed profile details
            $getStmt = $this->db->prepare("SELECT c.*, u.email FROM customers c JOIN users u ON c.user_id = u.id WHERE c.id = :id");
            $getStmt->execute([':id' => $id]);
            $updated = $getStmt->fetch(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, [
                'id' => intval($updated['id']),
                'userId' => intval($updated['user_id']),
                'name' => $updated['name'],
                'email' => $updated['email'],
                'phone' => $updated['phone'],
                'address' => $updated['address'] ?? '',
                'joinDate' => $updated['join_date'],
                'totalBookings' => intval($updated['total_bookings'])
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->jsonResponse($response, ["message" => "Update failed: " . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $id = intval($args['id']);

        try {
            // Find existing customer
            $checkStmt = $this->db->prepare("SELECT user_id FROM customers WHERE id = :id");
            $checkStmt->execute([':id' => $id]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                return $this->jsonResponse($response, ["message" => "Customer not found"], 404);
            }

            $this->db->beginTransaction();

            // Delete user (cascades automatically to delete customer details due to foreign key)
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $existing['user_id']]);

            $this->db->commit();

            return $this->jsonResponse($response, ["status" => "success", "message" => "Customer account deleted successfully"]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->jsonResponse($response, ["message" => "Delete failed: " . $e->getMessage()], 500);
        }
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
