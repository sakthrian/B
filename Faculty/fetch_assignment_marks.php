<?php
include '../config.php';

if (!isset($_GET['test_id'])) {
    error_log("Invalid access: Missing test_id");
    echo json_encode(['error' => 'Invalid access!']);
    exit();
}

$test_id = $_GET['test_id'];

error_log("Fetching marks for test_id: $test_id");
$sql = "SELECT status FROM assignment WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $test_id);
$stmt->execute();
$stmt->bind_result($status);

if ($stmt->fetch()) {
    // If status is NULL in the database, $status will be NULL in PHP
    $status = isset($status) ? $status : null;
} else {
    // If no rows are returned, explicitly set status to NULL
    $status = null;
}
$stmt->close();

$sql = "SELECT student_id, question_id, obtained_mark FROM assignment_mark WHERE assignment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();
$marks = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['marks' => $marks,'status'=> $status]);
?>