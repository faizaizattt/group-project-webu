<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtAuthMiddleware {
    private $allowedRoles;
    private $secret;

    public function __construct(array $allowedRoles = []) {
        $this->allowedRoles = $allowedRoles;
        $this->secret = getenv('JWT_SECRET') ?: 'driveease_jwt_secret_key_12345';
    }

    public function __invoke(Request $request, Handler $handler): Response {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (!$authHeader) {
            return $this->jsonErrorResponse("Authorization header missing", 401);
        }

        // Bearer <token>
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->jsonErrorResponse("Token format invalid (must be Bearer <token>)", 401);
        }

        $token = $matches[1];

        try {
            // Decode the token using Firebase JWT Key object
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            
            // Validate expiration time just in case (php-jwt does this automatically, but good to be explicit)
            if (isset($decoded->exp) && $decoded->exp < time()) {
                return $this->jsonErrorResponse("Token has expired", 401);
            }

            // Check roles if required
            if (!empty($this->allowedRoles)) {
                $userRole = $decoded->role ?? '';
                if (!in_array($userRole, $this->allowedRoles)) {
                    return $this->jsonErrorResponse("Forbidden: Insufficient privileges", 403);
                }
            }

            // Attach user data to request context
            $request = $request->withAttribute('user', $decoded);

        } catch (Exception $e) {
            return $this->jsonErrorResponse("Unauthorized: " . $e->getMessage(), 401);
        }

        return $handler->handle($request);
    }

    private function jsonErrorResponse(string $message, int $statusCode): Response {
        $response = new Response();
        $response->getBody()->write(json_encode([
            "status" => "error",
            "message" => $message
        ], JSON_PRETTY_PRINT));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
