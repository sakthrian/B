<?php
include '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['test_id']) || empty($data['status'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data received.']);
        exit();
    }

    $test_id = $data['test_id'];
    $status = $data['status'];

    $stmt = $conn->prepare("UPDATE assignment SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $test_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Assignment status updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update assignment status.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>