<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;
use PDO;
use Exception;

class PaymentController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAll(Request $request, Response $response): Response {
        $user = $request->getAttribute('user');

        try {
            $query = "SELECT p.* FROM payments p 
                      JOIN bookings b ON p.booking_id = b.id";
            $binds = [];

            if ($user->role === 'customer') {
                $query .= " WHERE b.customer_id = :customer_id";
                $binds[':customer_id'] = $user->customerId;
            }

            $query .= " ORDER BY p.date DESC, p.created_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($binds);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $formatted = array_map([$this, 'formatPayment'], $payments);
            return $this->jsonResponse($response, $formatted);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Fetch failed: " . $e->getMessage()], 500);
        }
    }

    public function create(Request $request, Response $response): Response {
        $user = $request->getAttribute('user');
        $data = json_decode($request->getBody()->getContents(), true);

        $bookingId = $data['bookingId'] ?? '';
        $amount = floatval($data['amount'] ?? 0);
        $method = trim($data['method'] ?? '');
        $details = trim($data['details'] ?? '');

        if (empty($bookingId) || $amount <= 0 || empty($method)) {
            return $this->jsonResponse($response, ["message" => "Invalid payment data"], 400);
        }

        try {
            // Verify booking ownership if customer
            $bkgStmt = $this->db->prepare("SELECT customer_id FROM bookings WHERE id = :id");
            $bkgStmt->execute([':id' => $bookingId]);
            $booking = $bkgStmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                return $this->jsonResponse($response, ["message" => "Associated booking not found"], 404);
            }

            if ($user->role === 'customer' && intval($booking['customer_id']) !== intval($user->customerId)) {
                return $this->jsonResponse($response, ["message" => "Forbidden: Unauthorized booking transaction"], 403);
            }

            // Generate PAY-xxxx key
            $payId = "PAY-" . rand(1000, 9999);
            $date = date('Y-m-d');

            $stmt = $this->db->prepare("INSERT INTO payments (id, booking_id, amount, method, status, date, details) 
                                        VALUES (:id, :booking_id, :amount, :method, 'paid', :date, :details)");
            
            $stmt->execute([
                ':id' => $payId,
                ':booking_id' => $bookingId,
                ':amount' => $amount,
                ':method' => $method,
                ':date' => $date,
                ':details' => $details
            ]);

            // Fetch created payment
            $getStmt = $this->db->prepare("SELECT * FROM payments WHERE id = :id");
            $getStmt->execute([':id' => $payId]);
            $newPay = $getStmt->fetch(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, $this->formatPayment($newPay), 201);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Payment failed: " . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        $data = json_decode($request->getBody()->getContents(), true);
        $status = $data['status'] ?? ''; // 'paid' | 'pending' | 'flagged'

        if (!in_array($status, ['paid', 'pending', 'flagged'])) {
            return $this->jsonResponse($response, ["message" => "Invalid payment status"], 400);
        }

        try {
            $stmt = $this->db->prepare("UPDATE payments SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);

            if ($stmt->rowCount() === 0) {
                return $this->jsonResponse($response, ["message" => "Payment not found or status unchanged"], 404);
            }

            return $this->jsonResponse($response, [
                "status" => "success", 
                "message" => "Payment status updated to " . $status,
                "paymentId" => $id,
                "paymentStatus" => $status
            ]);

        } catch (Exception $e) {
            return $this->jsonResponse($response, ["message" => "Operation failed: " . $e->getMessage()], 500);
        }
    }

    private function formatPayment(array $p): array {
        return [
            'id' => $p['id'],
            'bookingId' => $p['booking_id'],
            'amount' => floatval($p['amount']),
            'method' => $p['method'],
            'status' => $p['status'],
            'date' => $p['date'],
            'details' => $p['details'] ?? ''
        ];
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
