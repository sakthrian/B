<?php
include '../config.php';

if (!isset($_GET['test_id'])) {
    echo json_encode(['error' => 'Invalid access!']);
    exit();
}

$test_id = $_GET['test_id'];

// Fetch questions for the selected test
$sql = "SELECT id, question_no, max_mark FROM assignment_question WHERE assignment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();
$questions = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['questions' => $questions]);
?>