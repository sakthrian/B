<?php
require_once '../config.php'; 

if (!isset($_GET['fc_id'])) {
    echo json_encode(['error' => 'fc_id is required']);
    exit;
}

$fc_id = intval($_GET['fc_id']);
$tests_data = [];
$assignments_data = [];
$questions_data = [];
$assignment_questions_data = [];

// Fetch test details for the fc_id
$sql = "SELECT id, test_no, total_mark, test_date, fc_id, status FROM test WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tests_data[] = $row;
}
$stmt->close();

// Fetch assignment details for the fc_id
$sql = "SELECT id, assignment_no, total_mark, assignment_date, fc_id FROM assignment WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $assignments_data[] = $row;
}
$stmt->close();

// Fetch question details for the tests in this fc_id
$sql = "SELECT q.id, q.test_id, q.question_no, q.max_mark, q.target_mark, q.knowledge_level, co.co_number
        FROM question q
        JOIN test t ON q.test_id = t.id
        JOIN question_co qc ON qc.question_id = q.id
        JOIN course_outcome co ON co.id = qc.co_id
        WHERE t.fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $questions_data[$row['test_id']][] = $row;
}
$stmt->close();

// Fetch assignment question details
$sql = "SELECT q.id, q.assignment_id, q.question_no, q.max_mark, q.target_mark, q.knowledge_level, co.co_number
        FROM assignment_question q
        JOIN assignment a ON q.assignment_id = a.id
        JOIN assignment_question_co qc ON qc.question_id = q.id
        JOIN course_outcome co ON co.id = qc.co_id
        WHERE a.fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $assignment_questions_data[$row['assignment_id']][] = $row;
}
$stmt->close();

echo json_encode([
    'testsData' => $tests_data,
    'assignmentsData' => $assignments_data,
    'questionsData' => $questions_data,
    'assignmentQuestionsData' => $assignment_questions_data
]);
?>
