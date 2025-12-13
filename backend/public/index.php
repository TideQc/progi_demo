<?php
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use App\BidCalculator;
use App\FeeRepository;
use App\Database;
use App\DatabaseConfig;
use App\VehicleRepository;

// Setup logger to /var/log/app/app.log in JSON
$logger = new Logger('app');
$stream = new StreamHandler(__DIR__ . '/../var/log/app.log', Logger::DEBUG);
$stream->setFormatter(new JsonFormatter());
$logger->pushHandler($stream);

// Initialize database connection
try {
    $dbConfig = new DatabaseConfig();
    $database = new Database($dbConfig);
    $pdo = $database->getConnection();
    $feeRepository = new FeeRepository($pdo);
    $vehicleRepository = new VehicleRepository($pdo);
} catch (PDOException $e) {
    $logger->error('db_connection_error', ['error' => $e->getMessage()]);
    // Create empty response if DB fails
    $response = new Response(json_encode(['error' => 'Database connection failed']), 500, ['Content-Type' => 'application/json']);
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->send();
    exit;
}

$request = Request::createFromGlobals();

// Handle CORS preflight
if ($request->getMethod() === 'OPTIONS') {
    $resp = new Response('', 204, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'POST, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type',
    ]);
    $logger->info('preflight', ['path' => $request->getPathInfo()]);
    $resp->send();
    exit;
}

// GET /api/vehicles - return list of vehicles with calculation breakdown
if ($request->getMethod() === 'GET' && $request->getPathInfo() === '/api/vehicles') {
    try {
        $vehicles = $vehicleRepository->getAllVehicles();
        $calc = new BidCalculator($feeRepository);
        $out = [];
        foreach ($vehicles as $v) {
            $price = isset($v['price']) ? (float)$v['price'] : 0.0;
            $type = isset($v['type']) ? $v['type'] : 'common';
            try {
                $calculation = $calc->calculate($price, $type);
            } catch (Exception $e) {
                $calculation = ['error' => $e->getMessage()];
            }
            $v['calculation'] = $calculation;
            $out[] = $v;
        }

        $response = new Response(json_encode($out), 200, ['Content-Type' => 'application/json']);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
    } catch (Exception $e) {
        $logger->error('vehicles_error', ['exception' => $e->getMessage()]);
        $response = new Response(json_encode(['error' => $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
        $response->headers->set('Access-Control-Allow-Origin', '*');
    }

    $response->send();
    exit;
}

// GET /api/vehicles/{id} - return single vehicle with calculation
if ($request->getMethod() === 'GET' && preg_match('#^/api/vehicles/(\d+)$#', $request->getPathInfo(), $m)) {
    $id = (int)$m[1];
    try {
        $v = $vehicleRepository->getVehicleById($id);
        if (!$v) {
            $response = new Response(json_encode(['error' => 'Vehicle not found']), 404, ['Content-Type' => 'application/json']);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->send();
            exit;
        }

        $calc = new BidCalculator($feeRepository);
        try {
            $v['calculation'] = $calc->calculate((float)$v['price'], $v['type']);
        } catch (Exception $e) {
            $v['calculation'] = ['error' => $e->getMessage()];
        }

        $response = new Response(json_encode($v), 200, ['Content-Type' => 'application/json']);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->send();
    } catch (Exception $e) {
        $logger->error('vehicle_error', ['exception' => $e->getMessage()]);
        $response = new Response(json_encode(['error' => $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->send();
    }

    exit;
}

if ($request->getMethod() === 'POST' && strpos($request->getPathInfo(), '/api/calculate') === 0) {
    $data = json_decode($request->getContent(), true);
    $price = isset($data['price']) ? (float)$data['price'] : 0.0;
    $type = isset($data['type']) ? $data['type'] : 'common';

    try {
        $calc = new BidCalculator($feeRepository);
        $result = $calc->calculate($price, $type);

        $logger->info('calculation', ['request' => $data, 'result' => $result]);

        $response = new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);
        // Add CORS headers for browser clients
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
    } catch (Exception $e) {
        $logger->error('calculation_error', ['exception' => $e->getMessage()]);
        $response = new Response(json_encode(['error' => $e->getMessage()]), 500, ['Content-Type' => 'application/json']);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
    }

    $response->send();
    exit;
}

// Health / basic index
echo "Bid Calculation Backend - running";
