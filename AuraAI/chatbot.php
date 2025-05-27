<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Set longer timeout for generating visualizations
ini_set('max_execution_time', 300); // 5 minutes
set_time_limit(300);

$data = json_decode(file_get_contents("php://input"), true);
$user_message = $data["message"] ?? "";


$url = "http://localhost:5005/webhooks/rest/webhook";

$options = [
    "http" => [
        "header"  => "Content-Type: application/json",
        "method"  => "POST",
        "content" => json_encode([
            "sender" => "user",
            "message" => $user_message
        ]),
        // Increase timeout for longer processing
        "timeout" => 180.0, // 3 minutes timeout
    ],
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    // Log the error
    error_log("Error connecting to Rasa: " . error_get_last()['message']);
    
    echo json_encode([
        "success" => false,
        "message" => "Error connecting to the chatbot API"
    ]);
} else {
    // Success
    echo $result;
}
?>
