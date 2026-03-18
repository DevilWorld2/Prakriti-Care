<?php
// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Include required classes
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/JWT.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Admin.php';
require_once __DIR__ . '/classes/Herb.php';
require_once __DIR__ . '/classes/Recommendation.php';
require_once __DIR__ . '/classes/Assessment.php';
require_once __DIR__ . '/classes/Contact.php';
require_once __DIR__ . '/classes/Disease.php';
require_once __DIR__ . '/classes/Formulation.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];

// Remove query string and base path
$request = parse_url($request, PHP_URL_PATH);
$request = rawurldecode($request);

// Remove base path (support spaces and URL-encoded spaces)
$basePaths = ['/Prakriti Care/api', '/Prakriti%20Care/api', '/prakriti care/api', '/prakriti%20care/api'];
foreach ($basePaths as $basePath) {
    if (strpos($request, $basePath) === 0) {
        $request = substr($request, strlen($basePath));
        break;
    }
}

$request = trim($request, '/');

// Split path into segments
$pathSegments = explode('/', $request);
$endpoint = $pathSegments[0] ?? '';
$subEndpoint = $pathSegments[1] ?? '';
$subSubEndpoint = $pathSegments[2] ?? '';

// Get request data
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// Route requests
try {
    switch ($endpoint) {
        case 'auth':
            handleAuth($method, $subEndpoint, $data);
            break;

        case 'admin':
            handleAdmin($method, $subEndpoint, $subSubEndpoint, $data);
            break;

        case 'herbs':
            handleHerbs($method, $subEndpoint, $data);
            break;

        case 'diseases':
            handleDiseases($method, $subEndpoint, $data);
            break;

        case 'formulations':
            handleFormulations($method, $subEndpoint, $data);
            break;

        case 'recommendations':
            handleRecommendations($method, $subEndpoint, $data);
            break;

        case 'assessments':
            handleAssessments($method, $subEndpoint, $data);
            break;

        case 'contact':
            handleContact($method, $subEndpoint, $data);
            break;

        case 'search':
            handleSearch($method, $data);
            break;

        case 'health':
            handleHealth($method);
            break;

        default:
            sendResponse(404, ['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    sendResponse(500, ['error' => 'Internal server error', 'message' => $e->getMessage()]);
}

function handleAuth($method, $subEndpoint, $data) {
    $user = new User();

    switch ($subEndpoint) {
        case 'login':
            if ($method !== 'POST') {
                sendResponse(405, ['error' => 'Method not allowed']);
            }
            $result = $user->login($data['email'] ?? '', $data['password'] ?? '');
            sendResponse($result['success'] ? 200 : 401, $result);
            break;

        case 'register':
            if ($method !== 'POST') {
                sendResponse(405, ['error' => 'Method not allowed']);
            }
            $result = $user->register($data);
            sendResponse($result['success'] ? 201 : 400, $result);
            break;

        case 'admin-login':
            if ($method !== 'POST') {
                sendResponse(405, ['error' => 'Method not allowed']);
            }
            $admin = new Admin();
            $result = $admin->login($data['email'] ?? '', $data['password'] ?? '');
            sendResponse($result['success'] ? 200 : 401, $result);
            break;

        case 'me':
            if ($method !== 'GET') {
                sendResponse(405, ['error' => 'Method not allowed']);
            }
            $payload = JWT::validateToken();
            if (!$payload) {
                sendResponse(401, ['error' => 'Unauthorized']);
            }
            $result = $user->getProfile($payload['user_id']);
            sendResponse($result['success'] ? 200 : 404, $result);
            break;

        default:
            sendResponse(404, ['error' => 'Auth endpoint not found']);
    }
}

function handleAdmin($method, $subEndpoint, $subSubEndpoint, $data) {
    // Validate admin token
    $payload = JWT::validateToken();
    if (!$payload || !isset($payload['type']) || $payload['type'] !== 'admin') {
        sendResponse(401, ['error' => 'Admin access required']);
    }

    $admin = new Admin();

    switch ($subEndpoint) {
        case 'users':
            if ($method === 'GET' && empty($subSubEndpoint)) {
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 25;
                $result = $admin->getUsers($page, $limit);
                sendResponse(200, $result);
                return;
            }

            if ($method === 'GET' && is_numeric($subSubEndpoint)) {
                $result = $admin->getUserDetails($subSubEndpoint);
                sendResponse($result['success'] ? 200 : 404, $result);
                return;
            }

            if ($method === 'PUT' && is_numeric($subSubEndpoint)) {
                $result = $admin->updateUserStatus($subSubEndpoint, $data['is_active'] ?? 1);
                sendResponse($result['success'] ? 200 : 400, $result);
                return;
            }

            if ($method === 'DELETE' && is_numeric($subSubEndpoint)) {
                $result = $admin->deleteUser($subSubEndpoint);
                sendResponse($result['success'] ? 200 : 400, $result);
                return;
            }

            sendResponse(405, ['error' => 'Method not allowed']);
            return;

        case 'dashboard-stats':
            if ($method !== 'GET') {
                sendResponse(405, ['error' => 'Method not allowed']);
            }
            $result = $admin->getDashboardStats();
            sendResponse(200, $result);
            break;

        case 'recent-activity':
            if ($method !== 'GET') {
                sendResponse(405, ['error' => 'Method not allowed']);
            }
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $result = $admin->getRecentActivity($limit);
            sendResponse(200, $result);
            break;

        default:
            sendResponse(404, ['error' => 'Admin endpoint not found']);
    }
}

function handleHerbs($method, $subEndpoint, $data) {
    // Admin required for recipe CRUD operations
    $payload = JWT::validateToken();
    if (!$payload || !isset($payload['type']) || $payload['type'] !== 'admin') {
        sendResponse(401, ['error' => 'Admin access required']);
    }

    $herb = new Herb();

    switch ($method) {
        case 'GET':
            if ($subEndpoint === 'search') {
                $query = $_GET['query'] ?? '';
                $dosha = $_GET['dosha'] ?? null;
                $result = $herb->searchHerbs($query, $dosha);
            } elseif (is_numeric($subEndpoint)) {
                $result = $herb->getHerbById($subEndpoint);
            } else {
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 50;
                $result = $herb->getAllHerbs($page, $limit);
            }
            sendResponse(200, $result);
            break;

        case 'POST':
            $result = $herb->addHerb($data);
            sendResponse($result['success'] ? 201 : 400, $result);
            break;

        case 'PUT':
            if (!is_numeric($subEndpoint)) {
                sendResponse(400, ['error' => 'Herb ID required for update']);
            }
            $result = $herb->updateHerb($subEndpoint, $data);
            sendResponse($result['success'] ? 200 : 400, $result);
            break;

        case 'DELETE':
            if (!is_numeric($subEndpoint)) {
                sendResponse(400, ['error' => 'Herb ID required for delete']);
            }
            $result = $herb->deleteHerb($subEndpoint);
            sendResponse($result['success'] ? 200 : 400, $result);
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

function handleDiseases($method, $subEndpoint, $data) {
    $payload = JWT::validateToken();
    if (!$payload || !isset($payload['type']) || $payload['type'] !== 'admin') {
        sendResponse(401, ['error' => 'Admin access required']);
    }

    $disease = new Disease();

    switch ($method) {
        case 'GET':
            if (is_numeric($subEndpoint)) {
                $result = $disease->getDiseaseById($subEndpoint);
            } elseif (!empty($_GET['query'])) {
                $result = $disease->searchDiseases($_GET['query']);
            } else {
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 50;
                $result = $disease->getAllDiseases($page, $limit);
            }
            sendResponse(200, $result);
            break;

        case 'POST':
            $result = $disease->addDisease($data);
            sendResponse($result['success'] ? 201 : 400, $result);
            break;

        case 'PUT':
            if (!is_numeric($subEndpoint)) {
                sendResponse(400, ['error' => 'Disease ID required for update']);
            }
            $result = $disease->updateDisease($subEndpoint, $data);
            sendResponse($result['success'] ? 200 : 400, $result);
            break;

        case 'DELETE':
            if (!is_numeric($subEndpoint)) {
                sendResponse(400, ['error' => 'Disease ID required for delete']);
            }
            $result = $disease->deleteDisease($subEndpoint);
            sendResponse($result['success'] ? 200 : 400, $result);
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

function handleFormulations($method, $subEndpoint, $data) {
    $payload = JWT::validateToken();
    if (!$payload || !isset($payload['type']) || $payload['type'] !== 'admin') {
        sendResponse(401, ['error' => 'Admin access required']);
    }

    $formulation = new Formulation();

    switch ($method) {
        case 'GET':
            if (is_numeric($subEndpoint)) {
                $result = $formulation->getFormulationById($subEndpoint);
            } else {
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 50;
                $result = $formulation->getAllFormulations($page, $limit);
            }
            sendResponse(200, $result);
            break;

        case 'POST':
            $result = $formulation->addFormulation($data);
            sendResponse($result['success'] ? 201 : 400, $result);
            break;

        case 'PUT':
            if (!is_numeric($subEndpoint)) {
                sendResponse(400, ['error' => 'Formulation ID required for update']);
            }
            $result = $formulation->updateFormulation($subEndpoint, $data);
            sendResponse($result['success'] ? 200 : 400, $result);
            break;

        case 'DELETE':
            if (!is_numeric($subEndpoint)) {
                sendResponse(400, ['error' => 'Formulation ID required for delete']);
            }
            $result = $formulation->deleteFormulation($subEndpoint);
            sendResponse($result['success'] ? 200 : 400, $result);
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}


function handleRecommendations($method, $subEndpoint, $data) {
    // Validate user token
    $payload = JWT::validateToken();
    if (!$payload || !isset($payload['user_id'])) {
        sendResponse(401, ['error' => 'Authentication required']);
    }

    $recommendation = new Recommendation();

    switch ($method) {
        case 'GET':
            if (is_numeric($subEndpoint)) {
                // Get specific recommendation - not implemented yet
                sendResponse(404, ['error' => 'Endpoint not implemented']);
            } else {
                $status = $_GET['status'] ?? null;
                $result = $recommendation->getUserRecommendations($payload['user_id'], $status);
                sendResponse(200, $result);
            }
            break;

        case 'POST':
            $herbId = $data['herb_id'] ?? null;
            if (!$herbId) {
                sendResponse(400, ['error' => 'Herb ID is required']);
            }
            $recResult = $recommendation->createRecommendation($payload['user_id'], $herbId, [
                'dosage' => $data['dosage'] ?? null,
                'frequency' => $data['frequency'] ?? 'twice daily',
                'duration' => $data['duration'] ?? '4 weeks',
                'instructions' => $data['instructions'] ?? null,
                'expected_benefits' => $data['expected_benefits'] ?? null,
                'precautions' => $data['precautions'] ?? null,
                'priority' => $data['priority'] ?? 'medium'
            ]);
            sendResponse($recResult['success'] ? 201 : 400, $recResult);
            break;

        case 'PUT':
            if (is_numeric($subEndpoint)) {
                $result = $recommendation->updateRecommendationStatus($subEndpoint, $payload['user_id'], $data['status'] ?? 'completed');
                sendResponse($result['success'] ? 200 : 400, $result);
            } else {
                sendResponse(400, ['error' => 'Invalid recommendation ID']);
            }
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

function handleAssessments($method, $subEndpoint, $data) {
    // Validate user token
    $payload = JWT::validateToken();
    if (!$payload || !isset($payload['user_id'])) {
        sendResponse(401, ['error' => 'Authentication required']);
    }

    $assessment = new Assessment();

    switch ($method) {
        case 'GET':
            if (is_numeric($subEndpoint)) {
                $result = $assessment->getAssessmentById($subEndpoint, $payload['user_id']);
                sendResponse($result['success'] ? 200 : 404, $result);
            } else {
                $result = $assessment->getUserAssessments($payload['user_id']);
                sendResponse(200, $result);
            }
            break;

        case 'POST':
            $result = $assessment->createAssessment($payload['user_id'], $data);
            sendResponse($result['success'] ? 201 : 400, $result);
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

function handleContact($method, $subEndpoint, $data) {
    $contact = new Contact();

    switch ($method) {
        case 'POST':
            $result = $contact->submitMessage($data);
            sendResponse($result['success'] ? 201 : 400, $result);
            break;

        default:
            sendResponse(405, ['error' => 'Method not allowed']);
    }
}

function handleSearch($method, $data) {
    // Allow both GET and POST for search
    if (!in_array($method, ['GET', 'POST'])) {
        sendResponse(405, ['error' => 'Method not allowed']);
    }

    // Validate user token
    $payload = JWT::validateToken();
    if (!$payload || !isset($payload['user_id'])) {
        sendResponse(401, ['error' => 'Authentication required']);
    }

    $query = '';
    if ($method === 'GET') {
        $query = $_GET['query'] ?? '';
    } else {
        $query = $data['query'] ?? '';
    }

    if (empty(trim($query))) {
        sendResponse(400, ['error' => 'Search query is required']);
    }

    $recommendation = new Recommendation();
    $result = $recommendation->searchHerbsBySymptoms(trim($query));
    sendResponse(200, $result);
}

function handleHealth($method) {
    if ($method !== 'GET') {
        sendResponse(405, ['error' => 'Method not allowed']);
    }

    try {
        $db = Database::getInstance();
        $db->query("SELECT 1");

        sendResponse(200, [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'database' => 'connected'
        ]);
    } catch (Exception $e) {
        sendResponse(500, [
            'status' => 'unhealthy',
            'error' => 'Database connection failed',
            'timestamp' => date('c')
        ]);
    }
}

function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}
?>