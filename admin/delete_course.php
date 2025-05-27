<?php

include '../config.php';
session_start();

if (isset($_GET['code']) && isset($_GET['regulation'])) {
    $code = $_GET['code'];
    $regulation = (int)$_GET['regulation'];

    $stmt = $conn->prepare("DELETE FROM course WHERE code = ? AND regulation = ?");
    $stmt->bind_param("si", $code, $regulation);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Course deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting course: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid course identifier.";
}

header("Location: admin_main_page.php#curriculum-section");
exit();

$conn->close();
?>
