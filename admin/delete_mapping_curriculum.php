<?php

include '../config.php'; 


if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    
    $stmt = $conn->prepare("DELETE FROM curriculum_mapping WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<script>alert('Mapping deleted successfully'); window.location.href='admin_main_page.php#curriculum-mapping-section';</script>";
        } else {
            echo "<script>alert('No mapping found with that ID'); window.location.href='admin_main_page.php#curriculum-mapping-section';</script>";
        }
    } else {
        echo "<script>alert('Database error: " . $stmt->error . "'); window.location.href='admin_main_page.php#curriculum-mapping-section';</script>";
    }
    
    $stmt->close();
} else {
    echo "<script>alert('Invalid request: No ID provided'); window.location.href='admin_main_page.php#curriculum-mapping-section';</script>";
}

$conn->close();
?>