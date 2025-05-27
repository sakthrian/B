<?php
require_once '../config.php'; 


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mapping_id'])) {
   
    $mapping_id = $_POST['mapping_id'];
    
    
    $delete_query = "DELETE FROM faculty_course WHERE id = ?";
    
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $mapping_id);
    
   
    if ($stmt->execute()) {
        
        header("Location: admin_main_page.php?delete=success");
        exit();
    } else {
        
        header("Location: admin_main_page.php?delete=error&message=" . urlencode($conn->error));
        exit();
    }
    
    $stmt->close();
} else {
    
    header("Location: admin_main_page.php?delete=invalid");
    exit();
}

$conn->close();
?>