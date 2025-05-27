<?php
include '../config.php';


if (!isset($_GET['course_id']) || !isset($_GET['program']) || !isset($_GET['semester'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}


$course_id = $_GET['course_id'];
$program = $_GET['program'];
$semester = $_GET['semester'];
$section = isset($_GET['section']) ? $_GET['section'] : null;


error_log("Fetching students for course_id: $course_id, program: $program, semester: $semester, section: $section");

try {
   
    $query = "SELECT s.register_no, s.name, s.section 
              FROM student s 
              WHERE s.type = ? AND s.semester = ?";
    
    
    $query .= " AND s.register_no NOT IN (
                SELECT DISTINCT es.student_id 
                FROM student_course_assignment es 
                JOIN faculty_course pe ON es.faculty_course_id = pe.id 
                JOIN course c ON pe.course_id = c.code
                WHERE c.semester = ? AND c.type = ? AND c.course_type = 'PEC'
              )";
    
    
    if ($program === 'B.Tech' && $section && $section !== 'Mixed') {
        $query .= " AND s.section = ?";
        $params = [$program, $semester, $semester, $program, $section];
        $types = "sisss";
    } else {
        $params = [$program, $semester, $semester, $program];
        $types = "siss";
    }
    
    
    error_log("Query: $query");
    error_log("Params: " . implode(", ", $params));
    
    
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    
    $stmt->bind_param($types, ...$params);
    
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
   
    $result = $stmt->get_result();
    $students = [];
    
    
    if ($result && $result->num_rows > 0) {
        while ($student = $result->fetch_assoc()) {
            $students[] = $student;
        }
    }
    
    
    header('Content-Type: application/json');
    echo json_encode($students);
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in fetch_students_for_course.php: " . $e->getMessage());
    
    // Return error as JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>