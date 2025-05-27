<?php
include '../config.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $type = $_POST['type'];
    $regulation = $_POST['regulation'];
    $batch = $_POST['batch'];
    
    
    if (empty($type) || empty($regulation) || empty($batch)) {
        echo "<script>alert('All fields are required!'); window.location.href='admin_main_page.php#save_curriculum_mapping';</script>";
        exit;
    }
    
    
    $sql = "INSERT INTO curriculum_mapping (type, regulation, batch) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE type = VALUES(type), regulation = VALUES(regulation), batch = VALUES(batch)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $type, $regulation, $batch);
    
    if ($stmt->execute()) {
        echo "<script>alert('Curriculum mapping saved successfully!'); window.location.href='admin_main_page.php#save_curriculum_mapping';</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "'); window.location.href='admin_main_page.php#save_curriculum_mapping';</script>";
    }
    
    $stmt->close();
    $conn->close();
}
?>