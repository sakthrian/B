<?php

include '../config.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $mapping_id = isset($_POST['mapping_id']) ? intval($_POST['mapping_id']) : 0;
    
    
    if (empty($mapping_id)) {
        $_SESSION['error'] = "Missing mapping ID for deletion.";
        header("Location: admin_main_page.php#professional-elective-mappings");
        exit();
    }
    
    
    $delete_query = "DELETE FROM faculty_course WHERE id = ? AND type = 'elective'";
    
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $mapping_id);
    
    if ($stmt->execute()) {
        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Professional elective mapping deleted successfully.";
        } else {
            $_SESSION['warning'] = "No elective mapping found with the specified ID.";
        }
    } else {
        $_SESSION['error'] = "Error deleting elective mapping: " . $conn->error;
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