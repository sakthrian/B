<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $assignmentId = $data['id'];
    $fcId = $data['fc_id'];

    $sql = "DELETE FROM assignment WHERE id = ? AND fc_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $assignmentId, $fcId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Assignment deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete assignment']);
    }
}
?>