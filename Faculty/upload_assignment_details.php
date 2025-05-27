<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignment_no = $_POST['assignment_no'];
    $fc_id = $_POST['fc_id'];
    $total_mark = $_POST['total_mark'];
    $assignment_date = $_POST['assignment_date'];
    $image = isset($_FILES['assignment_file']) ? $_FILES['assignment_file'] : null;
    $existingFilePath = isset($_POST['existing_question_paper']) ? $_POST['existing_question_paper'] : '';

    if(!$image && !$existingFilePath) {
        echo json_encode(['status' => 'error', 'message' => 'Please upload a question paper.']);
        exit();
    }

    $stmt = $conn->prepare("SELECT id, status FROM assignment WHERE fc_id = ? AND assignment_no = ?");
    $stmt->bind_param("ii", $fc_id, $assignment_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        //If test already exists, get `assignment_id`
        $row = $result->fetch_assoc();
        $assignment_id = $row['id'];
        $status = $row['status'];

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM assignment_mark WHERE assignment_id = ?");
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            // Check if the test is frozen
            if ($status === 'Freeze') {
            echo json_encode(['status' => 'error', 'message' => 'This test marks are already entered and freezed. You cannot further edit this test metadata.']);
            exit();
            }
    
            echo json_encode(['status' => 'error', 'message' => 'Marks have already been entered for this test. You cannot edit this test.']);
            exit();
        }

        //Delete existing questions for this test
        $stmt = $conn->prepare("DELETE FROM assignment_question WHERE assignment_id = ?");
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        
        //✅ Delete question-co mapping (to avoid orphaned records)
        $stmt = $conn->prepare("DELETE FROM assignment_question_co WHERE question_id IN (SELECT id FROM assignment_question WHERE assignment_id = ?)");
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();

        //Update the existing test details
        $stmt = $conn->prepare("UPDATE assignment SET total_mark = ?, assignment_date = ? WHERE id = ?");
        $stmt->bind_param("dsi", $total_mark, $assignment_date, $assignment_id);
        $stmt->execute();
        
    } else {
        $stmt = $conn->prepare("INSERT INTO assignment (fc_id, assignment_no, total_mark, assignment_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iids", $fc_id, $assignment_no, $total_mark, $assignment_date);
        $stmt->execute();
        $assignment_id = $stmt->insert_id;
    }
    
    $targetDir = "../AssignmentFiles/";
    $fileExtension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    $newFileName = $fc_id . "_" . $assignment_no . "." . $fileExtension;
    $targetFilePath = $targetDir . $newFileName;
    $allowedTypes = ['pdf', 'doc', 'docx'];

    if ($image['size'] > (2 * 1024 * 1024)) {
        echo json_encode(['status' => 'error', 'message' => 'File size is larger than the allowed limit.']);
        exit();
    }

    if ($image && in_array($fileExtension, $allowedTypes)) {
        if (move_uploaded_file($image['tmp_name'], $targetFilePath)) {
            $stmt = $conn->prepare("UPDATE assignment SET assignment_file = ? WHERE id = ?");
            $stmt->bind_param("si", $targetFilePath, $assignment_id);
            $stmt->execute();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Question paper upload failed!']);
            exit();
        }
    } 
    else if($existingFilePath){
        $stmt = $conn->prepare("UPDATE assignment SET assignment_file = ? WHERE id = ?");
        $stmt->bind_param("si", $existingFilePath, $test_id);
        $stmt->execute();
    } 
    else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Question paper file type!']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $questions = $_POST["question_marks"];
        $target_marks = $_POST['target_marks'];
        $knowledge_levels = $_POST['knowledge_levels'];
        $co_levels = $_POST['co_levels'];
        
        foreach ($questions as $index => $question_mark) {
            $target_mark = $target_marks[$index];
            $knowledge_level = $knowledge_levels[$index];
            $co_level = $co_levels[$index];
            $question_number = $index + 1;

            $stmt = $conn->prepare("INSERT INTO assignment_question (assignment_id, question_no, max_mark, target_mark, knowledge_level) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iidds", $assignment_id, $question_number, $question_mark, $target_mark, $knowledge_level);
            $stmt->execute();
            $question_id = $stmt->insert_id;

            $stmt = $conn->prepare("SELECT course_id FROM faculty_course WHERE id = ?");
            $stmt->bind_param("i", $fc_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $course_id = $row['course_id'];

            $stmt = $conn->prepare("SELECT id FROM course_outcome WHERE course_id=? AND co_number=?");
            $stmt->bind_param("si", $course_id, $co_level);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row) {
                $co_id = $row['id'];
                $stmt = $conn->prepare('INSERT INTO assignment_question_co(question_id, co_id) VALUES (?, ?)');
                $stmt->bind_param('ii', $question_id, $co_id);
                $stmt->execute();
            } else {
                throw new Exception("Error: Course outcome not found");
            }
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Assignment updated successfully, and new questions added!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

$conn->close();
?>