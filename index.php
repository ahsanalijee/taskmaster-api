<?php
// Set headers to strictly return JSON
header('Content-Type: application/json');

// Get the requested URI
$request_uri = $_SERVER['REQUEST_URI'];

// 1. The Health Check Endpoint (Crucial for Kubernetes later!)
if ($request_uri === '/health') {
    http_response_code(200);
    echo json_encode(["status" => "healthy", "timestamp" => time()]);
    exit;
}

// 2. The Tasks Endpoint
// 2. The Tasks Endpoint (Now with real Database connection!)
if ($request_uri === '/tasks') {
    // Grabbing the secure environment variables Docker injected
    $db_host = getenv('DB_HOST');
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');
    $db_name = getenv('DB_NAME');
    
    try {
        // Attempting to connect to the MySQL container
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        http_response_code(200);
        echo json_encode([
            "message" => "Successfully connected to MySQL database at $db_host! 🚀",
            "status" => "Microservices are talking to each other!"
        ]);
    } catch (PDOException $e) {
        // If it fails, catch the error so we can debug it
        http_response_code(500);
        echo json_encode([
            "error" => "Database connection failed ❌",
            "details" => $e->getMessage()
        ]);
    }
    exit;
}

// 404 Fallback
http_response_code(404);
echo json_encode(["error" => "Endpoint not found. Try /health or /tasks"]);
?>
