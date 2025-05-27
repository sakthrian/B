<?php
include '../../config.php';

$fc_id = $_GET['fc_id'];

$stmt = $conn->prepare("SELECT course_id, faculty_id, section FROM faculty_course WHERE id = ?");
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$stmt->bind_result($course_id, $faculty_id, $sec);

if (!$stmt->fetch()) {
    error_log("No matching record found for fc_id: $fc_id");
    echo json_encode(['error' => 'Invalid faculty-course ID!']);
    exit();
}
$stmt->close();

// Fetch COs for the selected course
$sql = "SELECT id, co_number FROM course_outcome WHERE course_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$cos = [];
while ($row = $result->fetch_assoc()) {
    $cos[] = [
        'id' => $row['id'],
        'co_number' => $row['co_number']
    ];
}
error_log('COs: ' . print_r($cos, true));

// Fetch tests for the selected course and faculty
$sql = "SELECT id, test_no FROM test WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$tests = [];
while ($row = $result->fetch_assoc()) {
    $tests[] = [
        'id' => $row['id'],
        'test_no' => $row['test_no']
    ];
}
error_log('Tests: ' . print_r($tests, true));

// Fetch assignments for the selected course and faculty
$sql = "SELECT id, assignment_no FROM assignment WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$assignments = [];
while ($row = $result->fetch_assoc()) {
    $assignments[] = [
        'id' => $row['id'],
        'assignment_no' => $row['assignment_no']
    ];
}
error_log('Assignments: ' . print_r($assignments, true));

// Fetch CO results for tests
$sql = "SELECT co_id, test_id, co_level FROM co_test_results WHERE test_id IN (SELECT id FROM test WHERE fc_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$co_test_results = [];
while ($row = $result->fetch_assoc()) {
    $co_test_results[] = [
        'co_id' => $row['co_id'],
        'test_id' => $row['test_id'],
        'co_level' => $row['co_level']
    ];
}
error_log('Test COs: ' . print_r($co_test_results, true));

// Fetch CO results for assignments
$sql = "SELECT co_id, assignment_id, co_level FROM co_assignment_results WHERE assignment_id IN (SELECT id FROM assignment WHERE fc_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$co_assignment_results = [];
while ($row = $result->fetch_assoc()) {
    $co_assignment_results[] = [
        'co_id' => $row['co_id'],
        'assignment_id' => $row['assignment_id'],
        'co_level' => $row['co_level']
    ];
}
error_log('Assignment COs: ' . print_r($co_assignment_results, true));

// Combine test and assignment results
$co_results = array_merge($co_test_results, $co_assignment_results);
error_log('Combined CO Results: ' . print_r($co_results, true));

// Fetch existing CO overall data
$sql = "SELECT co_id, cia, se, da, ia, ca FROM co_overall WHERE fc_id IN (SELECT id FROM faculty_course WHERE fc_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$co_overall_data = [];
while ($row = $result->fetch_assoc()) {
    $co_overall_data[] = [
        'co_id' => $row['co_id'],
        'cia' => $row['cia'],
        'se' => $row['se'],
        'da' => $row['da'],
        'ia' => $row['ia'],
        'ca' => $row['ca']
    ];
}
error_log('CO Overall Data: ' . print_r($co_overall_data, true));

// Output JSON
header('Content-Type: application/json');
echo json_encode([
    'cos' => $cos,
    'tests' => $tests,
    'assignments' => $assignments,
    'co_results' => $co_results,
    'co_overall_data' => $co_overall_data
]);
?>