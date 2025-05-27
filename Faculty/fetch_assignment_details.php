<?php
include '../config.php';

header('Content-Type: application/json');
ob_clean();

if(!isset($_GET['id'])){
    echo json_encode(["error" => "Missing assignment ID"]);
    exit;
}

$assignmentId = $_GET['id'];


$stmt = $conn->prepare("SELECT a.assignment_no, a.fc_id, a.assignment_date, a.total_mark, a.assignment_file, fc.type, fc.batch,a.status FROM assignment a
                                JOIN faculty_course fc on fc.id = a.fc_id
                                WHERE a.id = ?");
$stmt->bind_param("i", $assignmentId);
$stmt->execute();
$assignmentResult = $stmt->get_result();
$assignmentData = $assignmentResult->fetch_assoc();

if (!$assignmentData) {
    echo json_encode(["error" => "No assignment found for the given ID"]);
    exit;
}

// Fetch questions for the assignment
$stmt = $conn->prepare("SELECT q.question_no, q.max_mark, q.target_mark, q.knowledge_level, co.co_number as co_level, co.id
                        FROM assignment_question q
                        JOIN assignment_question_co qc ON qc.question_id = q.id
                        JOIN course_outcome co ON qc.co_id = co.id
                        WHERE assignment_id = ?");
$stmt->bind_param("i", $assignmentId);
$stmt->execute();
$questionsResult = $stmt->get_result();
$questionsData = [];
while ($row = $questionsResult->fetch_assoc()) {
    $questionsData[] = $row;
}

// Output JSON response
$response = [
    'assignment_no' => $assignmentData['assignment_no'],
    'fc_id' => $assignmentData['fc_id'],
    'assignment_date' => $assignmentData['assignment_date'],
    'degree' => $assignmentData['type'],
    'batch' => $assignmentData['batch'],
    'total_mark' => $assignmentData['total_mark'],
    'question_paper_image'=> $assignmentData['assignment_file'],
    'questions' => $questionsData,
    'status' => $assignmentData['status']
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>