<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

include '../config.php';

$conn->begin_transaction();

try {
    $data = file_get_contents('php://input');
    $marksData = json_decode($data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    if (empty($marksData)) {
        throw new Exception('No marks data received.');
    }

    // Prepare the insert or update statement for marks
    $stmt = $conn->prepare("
        INSERT INTO mark (student_id, test_id, question_id, obtained_mark)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE obtained_mark = VALUES(obtained_mark)
    ");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    // Prepare the insert or update statement for co_results
    $coStmt = $conn->prepare("
        INSERT INTO co_test_results (co_id, test_id, co_percentage, co_level)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE co_percentage = VALUES(co_percentage), co_level = VALUES(co_level)
    ");
    if (!$coStmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    // Prepare the select statement to fetch questions and COs for the test
    $testQuestionsStmt = $conn->prepare("SELECT q.id, q.target_mark, qc.co_id, co.co_number
                                         FROM question q 
                                         JOIN question_co qc ON q.id = qc.question_id
                                         JOIN course_outcome co ON qc.co_id = co.id
                                         WHERE q.test_id = ?");
    if (!$testQuestionsStmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    foreach ($marksData as $mark) {
        $student_id = $mark['student_id'];
        $test_id = $mark['test_id'];
        $question_id = $mark['question_id'];
        $obtained_mark = $mark['obtained_mark'];

        $stmt->bind_param("siid", $student_id, $test_id, $question_id, $obtained_mark);
        if (!$stmt->execute()) {
            throw new Exception('Error saving marks: ' . $stmt->error);
        }
    }

    // Fetch questions and COs for the test
    $testQuestionsStmt->bind_param("i", $test_id);
    $testQuestionsStmt->execute();
    $testQuestionsResult = $testQuestionsStmt->get_result();
    $questions = [];
    while ($row = $testQuestionsResult->fetch_assoc()) {
        $questions[] = [
            'q_id' => $row['id'],
            'target_mark' => $row['target_mark'],
            'co_id' => $row['co_id'],
            'co_no' => $row['co_number']
        ];
    }
    error_log('' . json_encode($questions));

    $studentIds = array_column($marksData, 'student_id');
    $uniqueStudentIds = array_unique($studentIds);
    $students_count = count($uniqueStudentIds);

    // Calculate CO percentages and levels
    $coGroups = [];
    foreach ($questions as $question) {
        $co_id = $question['co_id'];
        $co_no = $question['co_no'];
        $target_mark = $question['target_mark'];
        $j = 0;
        $attended_count = 0;

        foreach ($marksData as $mark) {
            if ($mark['question_id'] == $question['q_id']) {
                if ($mark['obtained_mark'] != -1) { 
                    $attended_count++;
                    if ($mark['obtained_mark'] >= $target_mark) {
                        $j++;
                    }
                }
            }
        }

        if ($attended_count > 0) {
            $percentage = ($j / $attended_count) * 100;
        } else {
            $percentage = 0;
        }
        
        $co_level = 0;
        if ($percentage >= 60) {
            $co_level = 3;
        } elseif ($percentage >= 50 && $percentage < 60) {
            $co_level = 2;
        } elseif ($percentage >= 40 && $percentage < 50) {
            $co_level = 1;
        }

        if (!isset($coGroups[$co_id])) {
            $coGroups[$co_id] = [
                'co_no' => $co_no,
                'percentages' => []
            ];
        }

        $coGroups[$co_id]['percentages'][] = $percentage;
    }
    error_log("CO Groups" . print_r($coGroups, true));

    foreach ($coGroups as $co_id => $group_data) {
        $avgPercentage = array_sum($group_data['percentages']) / count($group_data['percentages']);
        $co_level = 0;
        if ($avgPercentage >= 60) {
            $co_level = 3;
        } elseif ($avgPercentage >= 50 && $avgPercentage < 60) {
            $co_level = 2;
        } elseif ($avgPercentage >= 40 && $avgPercentage < 50) {
            $co_level = 1;
        }

        $coStmt->bind_param("iidd", $co_id, $test_id, $avgPercentage, $co_level);
        if (!$coStmt->execute()) {
            throw new Exception('Error saving CO results: ' . $coStmt->error);
        }
    }

    $stmt->close();
    $testQuestionsStmt->close();
    $coStmt->close();

    // Commit the transaction
    $conn->commit();
    error_log("Transaction committed successfully.");

    echo json_encode(['success' => true, 'message' => 'Marks and CO results saved successfully!']);
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    error_log("Transaction rolled back due to error: " . $e->getMessage());

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
    error_log("Database connection closed.");
}
?>