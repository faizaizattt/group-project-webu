<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;
use PDO;
use Exception;

class FeedbackController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAll(Request $request, Response $response): Response {
        try {
            $query = "SELECT f.*, c.name as customer_name, car.brand as car_brand, car.model as car_model
                      FROM feedback f
                      JOIN customers c ON f.customer_id = c.id
                      JOIN bookings b ON f.booking_id = b.id
                      JOIN cars car ON b.car_id = car.id
                      ORDER BY f.date DESC, f.created_at DESC";
            
            $stmt = $this->db->query($query);
            $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formatted = array_map(function($f) {
                return [
                    'id' => $f['id'],
                    'author' => $f['customer_name'] ?? '',
                    'bookingId' => $f['booking_id'],
                    'stars' => intval($f['stars']),
                    'comment' => $f['comment'],
                    'date' => $f['date'],
                    'car' => isset($f['car_brand']) ? ($f['car_brand'] . ' ' . $f['car_model']) : ''
                ];
            }, $feedbacks);

            return $this->jsonResponse($response, $formatted);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Fetch failed: " . $e->getMessage()], 500);
        }
    }

    public function create(Request $request, Response $response): Response {
        $user = $request->getAttribute('user');
        $data = json_decode($request->getBody()->getContents(), true);

        $bookingId = $data['bookingId'] ?? '';
        $stars = intval($data['stars'] ?? 0);
        $comment = trim($data['comment'] ?? '');

        if (empty($bookingId) || $stars < 1 || $stars > 5 || empty($comment)) {
            return $this->jsonResponse($response, ["message" => "Missing or invalid feedback details"], 400);
        }

        try {
            // Verify booking ownership if customer
            $bkgStmt = $this->db->prepare("SELECT customer_id, car_id FROM bookings WHERE id = :id");
            $bkgStmt->execute([':id' => $bookingId]);
            $booking = $bkgStmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                return $this->jsonResponse($response, ["message" => "Associated booking not found"], 404);
            }

            $customerId = intval($user->customerId);
            if ($user->role === 'customer' && intval($booking['customer_id']) !== $customerId) {
                return $this->jsonResponse($response, ["message" => "Forbidden: Unauthorized access"], 403);
            }

            // Generate FDB-xxxx
            $fdbId = "FDB-" . rand(200, 999);
            $date = date('Y-m-d');

            $stmt = $this->db->prepare("INSERT INTO feedback (id, booking_id, customer_id, stars, comment, date) 
                                        VALUES (:id, :booking_id, :customer_id, :stars, :comment, :date)");
            
            $stmt->execute([
                ':id' => $fdbId,
                ':booking_id' => $bookingId,
                ':customer_id' => $customerId,
                ':stars' => $stars,
                ':comment' => $comment,
                ':date' => $date
            ]);

            // Fetch created feedback for response
            $getStmt = $this->db->prepare("SELECT f.*, c.name as customer_name, car.brand as car_brand, car.model as car_model
                                           FROM feedback f
                                           JOIN customers c ON f.customer_id = c.id
                                           JOIN bookings b ON f.booking_id = b.id
                                           JOIN cars car ON b.car_id = car.id
                                           WHERE f.id = :id");
            $getStmt->execute([':id' => $fdbId]);
            $newFdb = $getStmt->fetch(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, [
                'id' => $newFdb['id'],
                'author' => $newFdb['customer_name'] ?? '',
                'bookingId' => $newFdb['booking_id'],
                'stars' => intval($newFdb['stars']),
                'comment' => $newFdb['comment'],
                'date' => $newFdb['date'],
                'car' => ($newFdb['car_brand'] . ' ' . $newFdb['car_model'])
            ], 201);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Feedback submission failed: " . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $user = $request->getAttribute('user');
        $data = json_decode($request->getBody()->getContents(), true);

        $stars = intval($data['stars'] ?? 0);
        $comment = trim($data['comment'] ?? '');

        try {
            // Check ownership
            $checkStmt = $this->db->prepare("SELECT customer_id FROM feedback WHERE id = :id");
            $checkStmt->execute([':id' => $id]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                return $this->jsonResponse($response, ["message" => "Feedback record not found"], 404);
            }

            if ($user->role === 'customer' && intval($existing['customer_id']) !== intval($user->customerId)) {
                return $this->jsonResponse($response, ["message" => "Forbidden: Unauthorized access"], 403);
            }

            $fields = [];
            $binds = [':id' => $id];

            if ($stars >= 1 && $stars <= 5) {
                $fields[] = "stars = :stars";
                $binds[':stars'] = $stars;
            }
            if (!empty($comment)) {
                $fields[] = "comment = :comment";
                $binds[':comment'] = $comment;
            }

            if (empty($fields)) {
                return $this->jsonResponse($response, ["message" => "Nothing to update"], 400);
            }

            $query = "UPDATE feedback SET " . implode(", ", $fields) . ", date = CURRENT_DATE() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute($binds);

            // Fetch refreshed item
            $getStmt = $this->db->prepare("SELECT f.*, c.name as customer_name, car.brand as car_brand, car.model as car_model
                                           FROM feedback f
                                           JOIN customers c ON f.customer_id = c.id
                                           JOIN bookings b ON f.booking_id = b.id
                                           JOIN cars car ON b.car_id = car.id
                                           WHERE f.id = :id");
            $getStmt->execute([':id' => $id]);
            $updated = $getStmt->fetch(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, [
                'id' => $updated['id'],
                'author' => $updated['customer_name'] ?? '',
                'bookingId' => $updated['booking_id'],
                'stars' => intval($updated['stars']),
                'comment' => $updated['comment'],
                'date' => $updated['date'],
                'car' => ($updated['car_brand'] . ' ' . $updated['car_model'])
            ]);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Update failed: " . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $user = $request->getAttribute('user');

        try {
            $checkStmt = $this->db->prepare("SELECT customer_id FROM feedback WHERE id = :id");
            $checkStmt->execute([':id' => $id]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                return $this->jsonResponse($response, ["message" => "Feedback not found"], 404);
            }

            if ($user->role === 'customer' && intval($existing['customer_id']) !== intval($user->customerId)) {
                return $this->jsonResponse($response, ["message" => "Forbidden: Unauthorized access"], 403);
            }

            $stmt = $this->db->prepare("DELETE FROM feedback WHERE id = :id");
            $stmt->execute([':id' => $id]);

            return $this->jsonResponse($response, ["status" => "success", "message" => "Feedback deleted successfully"]);

        } catch (Exception $e) {
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
