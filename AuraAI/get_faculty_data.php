<?php
session_start();
include '../config.php';

if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.php");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];

// Fetch faculty name
$sql = "SELECT name FROM faculty WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$faculty_name = $row['name'];

// Send all data as a JSON response
echo json_encode([
    'faculty_name' => $faculty_name,
]);
?>
