<?php
require_once '../config.php';

$program = $_POST['program'] ?? '';
$semester = $_POST['semester'] ?? '';

if (!$program || !$semester) {
    http_response_code(400);
    die(json_encode(['error' => 'Program and semester are required']));
}

$stmt = $conn->prepare("SELECT code, name FROM course WHERE program = ? AND semester = ?");
$stmt->bind_param("si", $program, $semester);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

echo json_encode($courses);
?>