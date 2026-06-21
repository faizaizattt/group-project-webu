<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;
use PDO;
use Exception;

class BookingController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAll(Request $request, Response $response): Response {
        $user = $request->getAttribute('user'); // Attached by JWT middleware

        try {
            $query = "SELECT b.*, c.name as customer_name, c.phone as customer_phone, 
                             car.brand as car_brand, car.model as car_model
                      FROM bookings b
                      JOIN customers c ON b.customer_id = c.id
                      JOIN cars car ON b.car_id = car.id";
            
            $binds = [];
            
            // If user is a customer, only return their own bookings
            if ($user->role === 'customer') {
                $query .= " WHERE b.customer_id = :customer_id";
                $binds[':customer_id'] = $user->customerId;
            }

            $query .= " ORDER BY b.created_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($binds);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formatted = array_map([$this, 'formatBooking'], $bookings);
            return $this->jsonResponse($response, $formatted);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Fetch failed: " . $e->getMessage()], 500);
        }
    }

    public function getById(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $user = $request->getAttribute('user');

        try {
            $stmt = $this->db->prepare("SELECT b.*, c.name as customer_name, c.phone as customer_phone, 
                                               car.brand as car_brand, car.model as car_model
                                        FROM bookings b
                                        JOIN customers c ON b.customer_id = c.id
                                        JOIN cars car ON b.car_id = car.id
                                        WHERE b.id = :id");
            $stmt->execute([':id' => $id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                return $this->jsonResponse($response, ["message" => "Booking not found"], 404);
            }

            // Customer security check
            if ($user->role === 'customer' && $booking['customer_id'] !== $user->customerId) {
                return $this->jsonResponse($response, ["message" => "Access denied: Unauthorized booking access"], 403);
            }

            return $this->jsonResponse($response, $this->formatBooking($booking));

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Fetch failed: " . $e->getMessage()], 500);
        }
    }

    public function create(Request $request, Response $response): Response {
        $user = $request->getAttribute('user');
        $data = json_decode($request->getBody()->getContents(), true);

        // Map frontend params
        $carId = $data['carId'] ?? '';
        $startDate = $data['startDate'] ?? '';
        $endDate = $data['endDate'] ?? '';
        $specialRequests = $data['specialRequests'] ?? '';
        $totalPrice = floatval($data['totalPrice'] ?? 0);
        
        // Admins can specify customerId, customers default to their own ID from token
        $customerId = ($user->role === 'admin' && isset($data['customerId'])) 
            ? intval($data['customerId']) 
            : intval($user->customerId);

        if (empty($carId) || empty($startDate) || empty($endDate) || $customerId <= 0) {
            return $this->jsonResponse($response, ["message" => "Missing required booking details"], 400);
        }

        try {
            $this->db->beginTransaction();

            // Validate car existence and availability
            $carStmt = $this->db->prepare("SELECT price_per_day, available FROM cars WHERE id = :id");
            $carStmt->execute([':id' => $carId]);
            $car = $carStmt->fetch(PDO::FETCH_ASSOC);

            if (!$car) {
                return $this->jsonResponse($response, ["message" => "Selected vehicle does not exist"], 404);
            }
            if (!$car['available']) {
                return $this->jsonResponse($response, ["message" => "Selected vehicle is currently unavailable"], 400);
            }

            // Check customer existence
            $custStmt = $this->db->prepare("SELECT id, name, phone FROM customers WHERE id = :id");
            $custStmt->execute([':id' => $customerId]);
            $customer = $custStmt->fetch(PDO::FETCH_ASSOC);
            if (!$customer) {
                return $this->jsonResponse($response, ["message" => "Selected customer account does not exist"], 404);
            }

            // Calculate duration and price (prevent incorrect pricing submission)
            $days = ceil((strtotime($endDate) - strtotime($startDate)) / (24 * 60 * 60));
            if ($days <= 0) {
                return $this->jsonResponse($response, ["message" => "Return date must be after pickup date"], 400);
            }

            $calculatedPrice = $days * floatval($car['price_per_day']);
            if ($totalPrice <= 0) {
                $totalPrice = $calculatedPrice;
            }

            // Generate BKG-xxxx format booking ID
            $bkgId = "BKG-" . rand(1000, 9999);

            $stmt = $this->db->prepare("INSERT INTO bookings (id, customer_id, car_id, pickup_date, return_date, total_price, status, special_requests) 
                                        VALUES (:id, :customer_id, :car_id, :pickup_date, :return_date, :total_price, 'pending', :special_requests)");
            
            $stmt->execute([
                ':id' => $bkgId,
                ':customer_id' => $customerId,
                ':car_id' => $carId,
                ':pickup_date' => $startDate,
                ':return_date' => $endDate,
                ':total_price' => $totalPrice,
                ':special_requests' => $specialRequests
            ]);

            // Update customer's total bookings count
            $upStmt = $this->db->prepare("UPDATE customers SET total_bookings = total_bookings + 1 WHERE id = :id");
            $upStmt->execute([':id' => $customerId]);

            // Automatically create a pending Rental record for tracking pickup/return
            $rentalStmt = $this->db->prepare("INSERT INTO rentals (booking_id, pickup_date, return_date, status) VALUES (:booking_id, :pickup_date, :return_date, 'booked')");
            $rentalStmt->execute([
                ':booking_id' => $bkgId,
                ':pickup_date' => $startDate,
                ':return_date' => $endDate
            ]);

            $this->db->commit();

            // Fetch the created record for response
            $getStmt = $this->db->prepare("SELECT b.*, c.name as customer_name, c.phone as customer_phone, 
                                                 car.brand as car_brand, car.model as car_model
                                          FROM bookings b
                                          JOIN customers c ON b.customer_id = c.id
                                          JOIN cars car ON b.car_id = car.id
                                          WHERE b.id = :id");
            $getStmt->execute([':id' => $bkgId]);
            $newBkg = $getStmt->fetch(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, $this->formatBooking($newBkg), 201);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->jsonResponse($response, ["message" => "Booking failed: " . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);
        $status = $data['status'] ?? ''; // 'confirmed' | 'cancelled' | 'active' | 'completed'
        $user = $request->getAttribute('user');

        if (!in_array($status, ['pending', 'confirmed', 'cancelled', 'active', 'completed'])) {
            return $this->jsonResponse($response, ["message" => "Invalid booking status"], 400);
        }

        try {
            // Check existence
            $checkStmt = $this->db->prepare("SELECT customer_id, car_id, status FROM bookings WHERE id = :id");
            $checkStmt->execute([':id' => $id]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                return $this->jsonResponse($response, ["message" => "Booking not found"], 404);
            }

            // Customer role constraints (can only cancel their own bookings, cannot confirm)
            if ($user->role === 'customer') {
                if ($existing['customer_id'] !== $user->customerId) {
                    return $this->jsonResponse($response, ["message" => "Forbidden: Unauthorized access"], 403);
                }
                if ($status !== 'cancelled') {
                    return $this->jsonResponse($response, ["message" => "Forbidden: Customers can only cancel bookings"], 403);
                }
            }

            $this->db->beginTransaction();

            $upStmt = $this->db->prepare("UPDATE bookings SET status = :status WHERE id = :id");
            $upStmt->execute([':status' => $status, ':id' => $id]);

            // Sync with rentals if status is cancelled
            if ($status === 'cancelled') {
                // Delete corresponding rental or set status
                $delRentStmt = $this->db->prepare("DELETE FROM rentals WHERE booking_id = :booking_id");
                $delRentStmt->execute([':booking_id' => $id]);
            }

            $this->db->commit();

            return $this->jsonResponse($response, [
                "status" => "success", 
                "message" => "Booking status updated to " . $status,
                "bookingId" => $id,
                "bookingStatus" => $status
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return $this->jsonResponse($response, ["message" => "Operation failed: " . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $user = $request->getAttribute('user');

        try {
            $checkStmt = $this->db->prepare("SELECT customer_id FROM bookings WHERE id = :id");
            $checkStmt->execute([':id' => $id]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                return $this->jsonResponse($response, ["message" => "Booking not found"], 404);
            }

            if ($user->role === 'customer' && $existing['customer_id'] !== $user->customerId) {
                return $this->jsonResponse($response, ["message" => "Forbidden: Unauthorized access"], 403);
            }

            $stmt = $this->db->prepare("DELETE FROM bookings WHERE id = :id");
            $stmt->execute([':id' => $id]);

            return $this->jsonResponse($response, ["status" => "success", "message" => "Booking deleted successfully"]);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Delete failed: " . $e->getMessage()], 500);
        }
    }

    // Helper formatter
    private function formatBooking(array $b): array {
        return [
            'id' => $b['id'],
            'customerId' => intval($b['customer_id']),
            'customerName' => $b['customer_name'] ?? '',
            'phone' => $b['customer_phone'] ?? '',
            'carId' => $b['car_id'],
            'carInfo' => isset($b['car_brand']) ? ($b['car_brand'] . ' ' . $b['car_model']) : '',
            'startDate' => $b['pickup_date'],
            'endDate' => $b['return_date'],
            'totalPrice' => floatval($b['total_price']),
            'status' => $b['status'],
            'specialRequests' => $b['special_requests'] ?? ''
        ];
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
