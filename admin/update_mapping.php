<?php
session_start();
include '../config.php';


if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mapping_id = $_POST['mapping_id'];
    $faculty_id = $_POST['faculty_id']; 
    // $course_id = $_POST['course_id']; 
    // $course_name = $_POST['course_name'];
    $section = $_POST['section']; 

   
    $update_query = "UPDATE faculty_course SET faculty_id = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $faculty_id, $mapping_id);

    if ($stmt->execute()) {
        $_SESSION['mapping_success'] = "Mapping updated successfully!";
    } else {
        $_SESSION['mapping_error'] = "Error updating mapping: " . $conn->error;
    }

    $stmt->close();
    header("Location: admin_main_page.php#current-mappings"); 
    exit();
}
?>