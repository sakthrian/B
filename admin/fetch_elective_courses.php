<?php

error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1); 


header('Content-Type: application/json');

try {
    
    include '../config.php';
    
    
    $program = isset($_GET['program']) ? $_GET['program'] : '';
    $batch = isset($_GET['batch']) ? $_GET['batch'] : '';
    $semester = isset($_GET['semester']) ? $_GET['semester'] : '';
    $course_type = isset($_GET['course_type']) ? $_GET['course_type'] : '';
    
    
    if (empty($program) || empty($batch) || empty($semester) || empty($course_type)) {
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    
    
    error_log("Elective lookup - Program: $program, Batch: $batch, Semester: $semester, Course Type: $course_type");
    
    
    $mapping_query = "SELECT id, regulation FROM curriculum_mapping 
                     WHERE type = ? AND batch = ?";
    
    $stmt = $conn->prepare($mapping_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $program, $batch);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $mapping_result = $stmt->get_result();
    
    if ($mapping_result->num_rows === 0) {
        echo json_encode(['error' => 'No curriculum mapping found for this selection']);
        exit;
    }
    
    $mapping_row = $mapping_result->fetch_assoc();
    $regulation = $mapping_row['regulation'];
    
   
    $course_query = "SELECT code, name, credits, regulation 
                    FROM course 
                    WHERE regulation = ? 
                    AND semester = ? 
                    AND course_type = ?";
    
    $stmt = $conn->prepare($course_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("sis", $regulation, $semester, $course_type);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $course_result = $stmt->get_result();
    
    $courses = [];
    while ($row = $course_result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    echo json_encode($courses);
    
} catch (Exception $e) {
    error_log("Error in fetch_elective_courses.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>