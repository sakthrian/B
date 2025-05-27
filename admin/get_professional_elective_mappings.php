<?php
session_start();
include '../config.php';

// Ensure faculty is logged in
if (!isset($_SESSION['faculty_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$program = isset($_GET['program']) ? $_GET['program'] : '';
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';


$query = "
    SELECT pe.id, f.id as faculty_id, f.name as faculty_name, 
           pe.course_id as course_code, c.name as course_name,
           c.program, c.semester,
           (SELECT COUNT(*) FROM elective_student es WHERE es.professional_elective_id = pe.id) as student_count
    FROM professional_elective pe
    INNER JOIN faculty f ON pe.faculty_id = f.id
    INNER JOIN course c ON pe.course_id = c.code
";


if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " WHERE (f.name LIKE '%$search%' OR c.name LIKE '%$search%' OR pe.course_id LIKE '%$search%')";
}


if (!empty($program)) {
    $program = $conn->real_escape_string($program);
    $query .= " AND c.program = '$program'";
}


if (!empty($semester)) {
    $semester = $conn->real_escape_string($semester);
    $query .= " AND c.semester = '$semester'";
}


$count_query = "SELECT COUNT(*) as total FROM ($query) as sub";
$count_result = $conn->query($count_query);
$total_records = 0;

if ($count_result && $count_result->num_rows > 0) {
    $row = $count_result->fetch_assoc();
    $total_records = $row['total'];
}


$query .= " ORDER BY pe.id DESC LIMIT $offset, $limit";


$result = $conn->query($query);


$records = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}


header('Content-Type: application/json');
echo json_encode([
    'total_records' => $total_records,
    'records' => $records,
]);