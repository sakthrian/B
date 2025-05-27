<?php
include '../config.php';

$program = $_GET['program'];
$semester = $_GET['semester'];


$query = "SELECT code, name, credits, no_of_co, semester, type, regulation, course_type 
          FROM course 
          WHERE type = ? AND semester = ? AND course_type='PEC'";

$stmt = $conn->prepare($query);
$stmt->bind_param("si", $program, $semester);
$stmt->execute();
$result = $stmt->get_result();
$courses = [];

if ($result && $result->num_rows > 0) {
    while ($course = $result->fetch_assoc()) {
        $courses[] = $course;
    }
}

header('Content-Type: application/json');
echo json_encode($courses);
?>