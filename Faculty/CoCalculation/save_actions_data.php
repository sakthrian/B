<?php
include '../../config.php';

$actionsData = json_decode(file_get_contents('php://input'), true);

foreach ($actionsData as $action) {
    $fc_id = $action['fc_id'];
    $category = $action['category'];
    $category_id = $action['category_id'];
    $action_required = $action['action_required'];
    $actionDate = $action['action_required_date'];

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

    $stmt = $conn->prepare("INSERT INTO actions (fc_id, category, category_id, action_required, action_required_date) VALUES (?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE action_required = VALUES(action_required), action_required_date = VALUES(action_required_date)");
    $stmt->bind_param("isiss", $fc_id, $category, $category_id, $action_required, $actionDate);
    $stmt->execute();
}

header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
?>