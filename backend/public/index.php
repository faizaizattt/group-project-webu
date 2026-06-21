<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Psr7\Response as SlimResponse;
use App\Middleware\CorsMiddleware;
use App\Middleware\JwtAuthMiddleware;
use App\Controllers\AuthController;
use App\Controllers\CarController;
use App\Controllers\BookingController;
use App\Controllers\RentalController;
use App\Controllers\PaymentController;
use App\Controllers\FeedbackController;
use App\Controllers\CustomerController;

// Create Slim App
$app = AppFactory::create();

// Parse JSON body
$app->addBodyParsingMiddleware();

// Add Error Middleware
$app->addErrorMiddleware(true, true, true);

// Add CORS Middleware (must be added last so it runs first)
$app->add(new CorsMiddleware());

// ────────────────────────────────────────
//  PUBLIC ROUTES (No Authentication)
// ────────────────────────────────────────

// Health check
$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode([
        "status" => "ok",
        "message" => "DriveEase Car Rental API v1.0",
        "timestamp" => date('Y-m-d H:i:s')
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Authentication endpoints
$app->post('/api/register', function ($request, $response) {
    $controller = new AuthController();
    return $controller->register($request, $response);
});

$app->post('/api/login', function ($request, $response) {
    $controller = new AuthController();
    return $controller->login($request, $response);
});

// Public car browsing (no login required)
$app->get('/api/cars', function ($request, $response) {
    $controller = new CarController();
    return $controller->getAll($request, $response);
});

$app->get('/api/cars/{id}', function ($request, $response, $args) {
    $controller = new CarController();
    return $controller->getById($request, $response, $args);
});

// ────────────────────────────────────────
//  PROTECTED ROUTES (JWT Required)
// ────────────────────────────────────────

// Group: Any authenticated user
$app->group('/api', function ($group) {

    // Profile
    $group->get('/profile', function ($request, $response) {
        $controller = new AuthController();
        return $controller->getProfile($request, $response);
    });

    $group->put('/profile', function ($request, $response) {
        $controller = new AuthController();
        return $controller->updateProfile($request, $response);
    });

    // Bookings
    $group->get('/bookings', function ($request, $response) {
        $controller = new BookingController();
        return $controller->getAll($request, $response);
    });

    $group->get('/bookings/{id}', function ($request, $response, $args) {
        $controller = new BookingController();
        return $controller->getById($request, $response, $args);
    });

    $group->post('/bookings', function ($request, $response) {
        $controller = new BookingController();
        return $controller->create($request, $response);
    });

    $group->put('/bookings/{id}', function ($request, $response, $args) {
        $controller = new BookingController();
        return $controller->updateStatus($request, $response, $args);
    });

    $group->delete('/bookings/{id}', function ($request, $response, $args) {
        $controller = new BookingController();
        return $controller->delete($request, $response, $args);
    });

    // Payments
    $group->get('/payments', function ($request, $response) {
        $controller = new PaymentController();
        return $controller->getAll($request, $response);
    });

    $group->post('/payments', function ($request, $response) {
        $controller = new PaymentController();
        return $controller->create($request, $response);
    });

    $group->put('/payments/{id}', function ($request, $response, $args) {
        $controller = new PaymentController();
        return $controller->updateStatus($request, $response, $args);
    });

    // Feedback
    $group->get('/feedback', function ($request, $response) {
        $controller = new FeedbackController();
        return $controller->getAll($request, $response);
    });

    $group->post('/feedback', function ($request, $response) {
        $controller = new FeedbackController();
        return $controller->create($request, $response);
    });

    $group->put('/feedback/{id}', function ($request, $response, $args) {
        $controller = new FeedbackController();
        return $controller->update($request, $response, $args);
    });

    $group->delete('/feedback/{id}', function ($request, $response, $args) {
        $controller = new FeedbackController();
        return $controller->delete($request, $response, $args);
    });

})->add(new JwtAuthMiddleware());

// Group: Admin-only routes
$app->group('/api/admin', function ($group) {

    // Car management (CRUD)
    $group->post('/cars', function ($request, $response) {
        $controller = new CarController();
        return $controller->create($request, $response);
    });

    $group->put('/cars/{id}', function ($request, $response, $args) {
        $controller = new CarController();
        return $controller->update($request, $response, $args);
    });

    $group->delete('/cars/{id}', function ($request, $response, $args) {
        $controller = new CarController();
        return $controller->delete($request, $response, $args);
    });

    $group->put('/cars/{id}/toggle', function ($request, $response, $args) {
        $controller = new CarController();
        return $controller->toggleAvailability($request, $response, $args);
    });

    // Rentals
    $group->get('/rentals', function ($request, $response) {
        $controller = new RentalController();
        return $controller->getAll($request, $response);
    });

    $group->put('/rentals/{id}', function ($request, $response, $args) {
        $controller = new RentalController();
        return $controller->updateStatus($request, $response, $args);
    });

    // Customer management
    $group->get('/customers', function ($request, $response) {
        $controller = new CustomerController();
        return $controller->getAll($request, $response);
    });

    $group->post('/customers', function ($request, $response) {
        $controller = new CustomerController();
        return $controller->create($request, $response);
    });

    $group->put('/customers/{id}', function ($request, $response, $args) {
        $controller = new CustomerController();
        return $controller->update($request, $response, $args);
    });

    $group->delete('/customers/{id}', function ($request, $response, $args) {
        $controller = new CustomerController();
        return $controller->delete($request, $response, $args);
    });

})->add(new JwtAuthMiddleware(['admin']));

// Handle CORS preflight for all routes
$app->options('/{routes:.+}', function ($request, $response) {
    return $response->withStatus(200);
});

// Run Slim
$app->run();
