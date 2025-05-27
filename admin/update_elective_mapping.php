<?php

include '../config.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $mapping_id = isset($_POST['mapping_id']) ? intval($_POST['mapping_id']) : 0;
    $faculty_id = isset($_POST['faculty_id']) ? $_POST['faculty_id'] : '';
    $course_id = isset($_POST['course_id']) ? $_POST['course_id'] : '';
   
    if (empty($mapping_id) || empty($faculty_id) || empty($course_id)) {
        $_SESSION['error'] = "All fields are required for updating the elective mapping.";
        header("Location: index.php#professional-elective-mappings");
        exit();
    }
    
    
    $update_query = "UPDATE faculty_course 
                    SET faculty_id = ? 
                    WHERE id = ? AND type = 'elective'";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $faculty_id, $mapping_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Professional elective mapping updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating elective mapping: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
   
    header("Location: admin_main_page.php#professional-elective-mappings");
    exit();
} else {
    
    header("Location: admin_main_page.php#professional-elective-mappings");
    exit();
}
?>