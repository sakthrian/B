<?php
session_start();
include '../config.php';


if (!isset($_SESSION['faculty_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $faculty_id = $_POST['faculty_id'];
    $course_id = $_POST['course_id'];
    $section = isset($_POST['section']) ? $_POST['section'] : null;
    $batch = $_POST['batch'];
    $type = $_POST['type']; // 'elective'
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];

    if (empty($faculty_id) || empty($course_id) || empty($batch) || empty($student_ids)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

   
    $conn->begin_transaction();

    try {
        
        $insert_fc_query = "INSERT INTO faculty_course (faculty_id, course_id, section, type, batch) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_fc_query);
        $stmt->bind_param("issss", $faculty_id, $course_id, $section, $type, $batch);
        $stmt->execute();
        $faculty_course_id = $stmt->insert_id;
        $stmt->close();

        $insert_student_query = "INSERT INTO student_course_assignment (faculty_course_id, student_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_student_query);
        
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        foreach ($student_ids as $student_id) {
            $stmt->bind_param("ii", $faculty_course_id, $student_id);
            $stmt->execute();
            
            if ($stmt->error) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Professional elective assigned successfully!']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>