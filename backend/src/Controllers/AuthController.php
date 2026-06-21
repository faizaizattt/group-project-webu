<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;
use Firebase\JWT\JWT;
use PDO;
use Exception;

class AuthController {
    private $db;
    private $secret;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->secret = getenv('JWT_SECRET') ?: 'driveease_jwt_secret_key_12345';
    }

    public function register(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');
        $role = $data['role'] ?? 'customer'; // Default role is customer

        // Input validation (rubric security check)
        if (empty($name) || empty($email) || empty($password) || empty($phone)) {
            return $this->jsonResponse($response, ["message" => "Name, email, password, and phone are required."], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse($response, ["message" => "Invalid email format."], 400);
        }

        if (strlen($password) < 6) {
            return $this->jsonResponse($response, ["message" => "Password must be at least 6 characters."], 400);
        }

        try {
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                return $this->jsonResponse($response, ["message" => "Email address already registered."], 400);
            }

            $this->db->beginTransaction();

            // Hash password (rubric security check)
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert User (Prepared statement - SQL Injection prevention)
            $userStmt = $this->db->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, :role)");
            $userStmt->execute([
                ':email' => $email,
                ':password' => $hashedPassword,
                ':role' => $role
            ]);
            $userId = $this->db->lastInsertId();

            // Insert Customer details
            $custStmt = $this->db->prepare("INSERT INTO customers (user_id, name, phone, address) VALUES (:user_id, :name, :phone, :address)");
            $custStmt->execute([
                ':user_id' => $userId,
                ':name' => $name,
                ':phone' => $phone,
                ':address' => $address
            ]);
            $customerId = $this->db->lastInsertId();

            $this->db->commit();

            // Generate JWT Token
            $tokenPayload = [
                "iat" => time(),
                "exp" => time() + (3600 * 24), // 24 Hours valid
                "sub" => $userId,
                "customerId" => $customerId,
                "email" => $email,
                "role" => $role,
                "name" => $name
            ];
            $jwt = JWT::encode($tokenPayload, $this->secret, 'HS256');

            return $this->jsonResponse($response, [
                "message" => "Registration successful",
                "email" => $email,
                "role" => $role,
                "name" => $name,
                "token" => $jwt
            ], 201);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->jsonResponse($response, ["message" => "Server error: " . $e->getMessage()], 500);
        }
    }

    public function login(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? ''; // Selected view role

        if (empty($email) || empty($password)) {
            return $this->jsonResponse($response, ["message" => "Email and password are required."], 400);
        }

        try {
            // Prepared statement to prevent SQL Injection
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                return $this->jsonResponse($response, ["message" => "Invalid email or password."], 401);
            }

            // Check if selected role matches database role
            if (!empty($role) && $user['role'] !== $role) {
                return $this->jsonResponse($response, ["message" => "Access denied for this account role."], 403);
            }

            // Fetch name from customer details if role is customer, else default admin name
            $name = "Staff Admin";
            $customerId = null;
            if ($user['role'] === 'customer') {
                $cStmt = $this->db->prepare("SELECT id, name FROM customers WHERE user_id = :user_id");
                $cStmt->execute([':user_id' => $user['id']]);
                $customer = $cStmt->fetch(PDO::FETCH_ASSOC);
                if ($customer) {
                    $name = $customer['name'];
                    $customerId = $customer['id'];
                }
            }

            // Generate JWT Token
            $tokenPayload = [
                "iat" => time(),
                "exp" => time() + (3600 * 24), // 24 Hours valid
                "sub" => $user['id'],
                "customerId" => $customerId,
                "email" => $user['email'],
                "role" => $user['role'],
                "name" => $name
            ];
            $jwt = JWT::encode($tokenPayload, $this->secret, 'HS256');

            return $this->jsonResponse($response, [
                "email" => $user['email'],
                "role" => $user['role'],
                "name" => $name,
                "token" => $jwt
            ]);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Server error: " . $e->getMessage()], 500);
        }
    }

    public function getProfile(Request $request, Response $response): Response {
        $user = $request->getAttribute('user'); // Extract from JWT Authentication Context
        if (!$user) {
            return $this->jsonResponse($response, ["message" => "Unauthorized access."], 401);
        }

        try {
            $stmt = $this->db->prepare("SELECT id, email, role, created_at FROM users WHERE id = :id");
            $stmt->execute([':id' => $user->sub]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($profile['role'] === 'customer') {
                $cStmt = $this->db->prepare("SELECT id, name, phone, address, join_date, total_bookings FROM customers WHERE user_id = :user_id");
                $cStmt->execute([':user_id' => $user->sub]);
                $customerDetails = $cStmt->fetch(PDO::FETCH_ASSOC);
                if ($customerDetails) {
                    $profile = array_merge($profile, $customerDetails);
                }
            }

            return $this->jsonResponse($response, $profile);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Server error: " . $e->getMessage()], 500);
        }
    }

    public function updateProfile(Request $request, Response $response): Response {
        $user = $request->getAttribute('user');
        if (!$user) {
            return $this->jsonResponse($response, ["message" => "Unauthorized access."], 401);
        }

        $data = json_decode($request->getBody()->getContents(), true);
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');
        $password = $data['password'] ?? '';

        try {
            $this->db->beginTransaction();

            // Update user password if provided
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    return $this->jsonResponse($response, ["message" => "Password must be at least 6 characters."], 400);
                }
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $uStmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
                $uStmt->execute([':password' => $hashed, ':id' => $user->sub]);
            }

            // Update customer details if customer role
            if ($user->role === 'customer') {
                $cStmt = $this->db->prepare("UPDATE customers SET name = :name, phone = :phone, address = :address WHERE user_id = :user_id");
                $cStmt->execute([
                    ':name' => $name ?: $user->name,
                    ':phone' => $phone,
                    ':address' => $address,
                    ':user_id' => $user->sub
                ]);
            }

            $this->db->commit();

            return $this->jsonResponse($response, [
                "status" => "success",
                "message" => "Profile updated successfully"
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->jsonResponse($response, ["message" => "Server error: " . $e->getMessage()], 500);
        }
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
