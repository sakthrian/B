<?php
include '../config.php';

// Ensure proper JSON response
header("Content-Type: application/json");

// Clear any previous output
ob_clean(); 

if (!isset($_GET['id'])) {
    echo json_encode(["error" => "Missing test ID"]);
    exit;
}

$testId = intval($_GET['id']); // Ensure ID is an integer

// Fetch test details
$stmt = $conn->prepare("SELECT t.test_no, t.fc_id, t.test_date, t.total_mark, t.question_paper_image, fc.type, fc.batch,t.status FROM test t
                                JOIN faculty_course fc on fc.id = t.fc_id
                                WHERE t.id = ?");
$stmt->bind_param("i", $testId);
$stmt->execute();
$testResult = $stmt->get_result();
$testData = $testResult->fetch_assoc();

if (!$testData) {
    echo json_encode(["error" => "No test found for the given ID"]);
    exit;
}

// Fetch questions for the test
$stmt = $conn->prepare("SELECT q.question_no, q.max_mark, q.target_mark, q.knowledge_level, co.co_number as co_level, co.id
                        FROM question q
                        JOIN question_co qc ON qc.question_id = q.id
                        JOIN course_outcome co ON qc.co_id = co.id
                        WHERE test_id = ?");
$stmt->bind_param("i", $testId);
$stmt->execute();
$questionsResult = $stmt->get_result();
$questionsData = [];
while ($row = $questionsResult->fetch_assoc()) {
    $questionsData[] = $row;
}

// Output JSON response
$response = [
    'test_no' => $testData['test_no'],
    'fc_id' => $testData['fc_id'],
    'test_date' => $testData['test_date'],
    'total_mark' => $testData['total_mark'],
    'degree' => $testData['type'],
    'batch' => $testData['batch'],
    'question_paper_image'=> $testData['question_paper_image'],
    'questions' => $questionsData,
    'status' => $testData['status'] ?? null
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
