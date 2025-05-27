<?php
include '../../config.php';

$actionsData = json_decode(file_get_contents('php://input'), true);

foreach ($actionsData as $action) {
    $fc_id = $action['fc_id'];
    $category = $action['category'];
    $category_id = $action['category_id'];
    $action_taken = $action['action_taken'];
    $actionDate = $action['action_taken_date'];

    // Log the received date for debugging
    error_log("Received Date: " . $actionDate);

    // Ensure the date is in the correct format (YYYY-MM-DD)
    if ($actionDate && strtotime($actionDate)) {
        $actionDate = date('Y-m-d', strtotime($actionDate));
    } else {
        $actionDate = '0000-00-00';
    }

    // Log the formatted date for debugging
    error_log("Formatted Date: " . $actionDate);

    $stmt = $conn->prepare("INSERT INTO actions (fc_id, category, category_id, action_taken, action_taken_date) VALUES (?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE action_taken = VALUES(action_taken), action_taken_date = VALUES(action_taken_date)");
    $stmt->bind_param("isiss", $fc_id, $category, $category_id, $action_taken, $actionDate);
    $stmt->execute();
}

header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
?>