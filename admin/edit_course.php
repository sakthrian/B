<?php

include '../config.php';
session_start();

if (!isset($_GET['code']) || !isset($_GET['regulation'])) {
    $_SESSION['error'] = "Invalid course identifier.";
    header("Location: admin_main_page.php#curriculum-section");
    exit();
}

$code = $_GET['code'];
$regulation = (int)$_GET['regulation'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $credits = $_POST['credits'];
    $no_of_co = $_POST['no_of_co'];
    $semester = $_POST['semester'];
    $degree = $_POST['type'];
    $course_type = $_POST['course_type'];

    $update_stmt = $conn->prepare("UPDATE course SET name = ?, credits = ?, no_of_co = ?, semester = ?, type = ?, course_type = ? WHERE code = ? AND regulation = ?");
    $update_stmt->bind_param("siissssi", $name, $credits, $no_of_co, $semester, $degree, $course_type, $code, $regulation);

    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Course updated successfully.";
        header("Location: admin_main_page.php#curriculum-section");
        exit();
    } else {
        $_SESSION['error'] = "Error updating course: " . $update_stmt->error;
    }

    $update_stmt->close();
}

$stmt = $conn->prepare("SELECT * FROM course WHERE code = ? AND regulation = ?");
$stmt->bind_param("si", $code, $regulation);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) {
    $_SESSION['error'] = "Course not found.";
    header("Location: admin_main_page.php#curriculum-section");
    exit();
}

$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Course</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        form { max-width: 500px; margin: auto; }
        label { display: block; margin-top: 10px; }
        input, select { width: 100%; padding: 8px; }
        button { margin-top: 20px; padding: 10px 15px; }
    </style>
</head>
<body>
    <h2>Edit Course</h2>
    <form method="POST">
        <label>Course Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($course['name']) ?>" required>

        <label>Credits:</label>
        <input type="number" name="credits" value="<?= $course['credits'] ?>" required>

        <label>Number of COs:</label>
        <input type="number" name="no_of_co" value="<?= $course['no_of_co'] ?>" required>

        <label>Semester:</label>
        <input type="number" name="semester" value="<?= $course['semester'] ?>" required>

        <label>Type:</label>
<select name="type" required>
    <option value="B.TECH" <?= $course['type'] == 'B.TECH' ? 'selected' : '' ?>>B.TECH</option>
    <option value="MCA" <?= $course['type'] == 'MCA' ? 'selected' : '' ?>>MCA</option>
    <option value="M.TECH IS" <?= $course['type'] == 'M.TECH IS' ? 'selected' : '' ?>>M.TECH IS</option>
    <option value="M.TECH DS" <?= $course['type'] == 'M.TECH DS' ? 'selected' : '' ?>>M.TECH DS</option>
</select>

<label>Course Type:</label>
<select name="course_type" required>
    <option value="PCC" <?= $course['course_type'] == 'PCC' ? 'selected' : '' ?>>PCC</option>
    <option value="PSE" <?= $course['course_type'] == 'PSE' ? 'selected' : '' ?>>PSE</option>
    <option value="PEC" <?= $course['course_type'] == 'PEC' ? 'selected' : '' ?>>PEC</option>
    <option value="PAC" <?= $course['course_type'] == 'PAC' ? 'selected' : '' ?>>PAC</option>
</select>

        <button type="submit">Update Course</button>
    </form>
</body>
</html>
