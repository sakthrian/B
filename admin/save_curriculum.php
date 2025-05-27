<?php
session_start();
include '../config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $code = $_POST['course_code'];
    $name = $_POST['course_name'];
    $credits = (int)$_POST['credits'];
    $no_of_co = (int)$_POST['no_of_co'];
    $semester = isset($_POST['semester']) ? (int)$_POST['semester'] : null;
    $degree = isset($_POST['types']) ? $_POST['types'] : null; // Program type (B.Tech, M.Tech IS, etc.)
    $regulation = $_POST['regulation'];
    $course_type = $_POST['course_type']; // Course type (PCC, PSE, etc.)

    // Validate required fields
    if (empty($code) || empty($name) || empty($degree) || empty($regulation) || empty($course_type) || 
        $credits <= 0 || $no_of_co <= 0 || empty($semester)) {
        $_SESSION['error'] = "All fields are required and must be valid.";
        header("Location: admin_main_page.php#curriculum-section");
        exit();
    }

    // Check if course already exists with same code, type (allowing different regulations)
    // $check_stmt = $conn->prepare("SELECT * FROM course WHERE code = ? AND type = ? AND regulation = ?");
    // $check_stmt->bind_param("sss", $code, $type, $regulation);
    // $check_stmt->execute();
    // $check_result = $check_stmt->get_result();

    // // If course already exists with same code, type, and regulation, show error
    // if ($check_result->num_rows > 0) {
    //     $_SESSION['error'] = "Course with this code, type, and regulation already exists.";
    //     $check_stmt->close();
    //     header("Location: admin_main_page.php#curriculum-section");
    //     exit();
    // }
    // $check_stmt->close();

    // Prepare SQL to insert data
    $stmt = $conn->prepare("INSERT INTO course (code, name, credits, no_of_co, semester, type, regulation, course_type) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        $_SESSION['error'] = "Database error: " . htmlspecialchars($conn->error);
        header("Location: admin_main_page.php#curriculum-section");
        exit();
    }

    // Bind parameters and execute query
    if (!$stmt->bind_param("ssiiisss", $code, $name, $credits, $no_of_co, $semester, $degree, $regulation, $course_type)) {
        error_log("bind_param failed: " . $stmt->error);
    }

    // Execute and check result
    if ($stmt->execute()) {
        $_SESSION['success'] = "Curriculum added successfully.";
    } else {
        error_log("Execute failed: " . $stmt->error);
        $_SESSION['error'] = "Error adding curriculum: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();

    header("Location: admin_main_page.php#curriculum-section");
    exit();
} else {
    // Redirect if not POST
    header("Location: admin_main_page.php#curriculum-section");
    exit();
}

$conn->close();
?>
