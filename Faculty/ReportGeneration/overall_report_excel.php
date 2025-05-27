<?php
include '../../config.php';

// Set the default timezone to Asia/Kolkata
date_default_timezone_set('Asia/Kolkata');

// Fetch parameters from URL
if (!isset($_GET['fc_id'])) {
    die('Error: Missing parameters.');
}

$fc_id = $_GET['fc_id'];

// Fetch section from faculty_course table
$sql = "SELECT course_id, faculty_id, section, batch, type FROM faculty_course WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$section = $row['section'];
$faculty_id = $row['faculty_id'];
$course_id = $row['course_id'];
$batch = $row['batch'];
$type = $row['type'];

// Fetch semester
$sql = "SELECT c.semester FROM course c JOIN faculty_course fc ON fc.course_id = c.code AND fc.type = c.type WHERE fc.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$semester = $row['semester'];

// Fetch faculty name
$sql = "SELECT name FROM faculty WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$faculty = $result->fetch_assoc();
$faculty_name = $faculty['name'] ?? 'Unknown Faculty';

// Fetch course name
$sql = "SELECT name FROM course WHERE code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
$course_name = $course['name'] ?? 'Unknown Course';

// Fetch all tests for the course
$sql = "SELECT id, test_no, total_mark FROM test WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$tests = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all assignments for the course
$sql = "SELECT id, assignment_no, total_mark FROM assignment WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$assignments = $result->fetch_all(MYSQLI_ASSOC);

// Fetch students and their total marks for all tests and assignments
$sql = "SELECT s.register_no, s.name, 
              SUM(m.obtained_mark) AS total_test_marks, 
              SUM(am.obtained_mark) AS total_assignment_marks
       FROM student s
       LEFT JOIN mark m ON s.register_no = m.student_id
       LEFT JOIN assignment_mark am ON s.register_no = am.student_id
       WHERE m.test_id IN (SELECT id FROM test WHERE fc_id = ?)
         OR am.assignment_id IN (SELECT id FROM assignment WHERE fc_id = ?)
       GROUP BY s.register_no";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $fc_id, $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$studentMarks = $result->fetch_all(MYSQLI_ASSOC);

// Fetch total marks for each test and assignment for each student
$studentTestMarks = [];
$studentAssignmentMarks = [];

foreach ($tests as $test) {
    $sql = "SELECT m.student_id, SUM(m.obtained_mark) AS total_mark 
            FROM mark m 
            WHERE m.test_id = ?
            GROUP BY m.student_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $test['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $studentTestMarks[$row['student_id']][$test['id']] = $row['total_mark'];
    }
}

foreach ($assignments as $assignment) {
    $sql = "SELECT am.student_id, SUM(am.obtained_mark) AS total_mark 
            FROM assignment_mark am 
            WHERE am.assignment_id = ?
            GROUP BY am.student_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $studentAssignmentMarks[$row['student_id']][$assignment['id']] = $row['total_mark'];
    }
}

// Set headers for Excel file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="student_overall_marks_report_' . date('Y-m-d') . '.xls"');

// Open output buffer and create a file pointer
ob_start();
$filePointer = fopen('php://output', 'w');

// Add headers to the Excel file
$headers = ['SI/No', 'Register No', 'Name'];
if (!empty($tests)) {
    foreach ($tests as $test) {
        $headers[] = 'Test ' . $test['test_no'] . ' (' . $test['total_mark'] . ')';
    }
    $headers[] = 'Avg';
}
if (!empty($assignments)) {
    foreach ($assignments as $assignment) {
        $headers[] = 'Assignment ' . $assignment['assignment_no'] . ' (' . $assignment['total_mark'] . ')';
    }
    $headers[] = 'Avg';
}
fputcsv($filePointer, $headers, "\t");

// Add data rows to the Excel file
$serialNo = 1;
foreach ($studentMarks as $student) {
    $row = [$serialNo++, $student['register_no'], $student['name']];
    $totalTestMarks = 0;
    $testCount = 0;
    if (!empty($tests)) {
        foreach ($tests as $test) {
            $mark = $studentTestMarks[$student['register_no']][$test['id']] ?? 0;
            $mark = $mark < 0 ? 0 : $mark;
            $row[] = $mark;
            $totalTestMarks += $mark;
            $testCount++;
        }
        $avgTestMarks = $testCount > 0 ? $totalTestMarks / $testCount : 0;
        $row[] = number_format($avgTestMarks, 2);
    }
    $totalAssignmentMarks = 0;
    $assignmentCount = 0;
    if (!empty($assignments)) {
        foreach ($assignments as $assignment) {
            $mark = $studentAssignmentMarks[$student['register_no']][$assignment['id']] ?? 0;
            $mark = $mark < 0 ? 0 : $mark;
            $row[] = $mark;
            $totalAssignmentMarks += $mark;
            $assignmentCount++;
        }
        $avgAssignmentMarks = $assignmentCount > 0 ? $totalAssignmentMarks / $assignmentCount : 0;
        $row[] = number_format($avgAssignmentMarks, 2);
    }
    fputcsv($filePointer, $row, "\t");
}

// Close the file pointer and output the buffer
fclose($filePointer);
$output = ob_get_clean();
echo $output;
exit;