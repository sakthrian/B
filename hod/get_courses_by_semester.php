<?php
require '../config.php';

if (isset($_POST['degree']) && isset($_POST['semester'])) {
    $degree = $_POST['degree'];
    $semester = $_POST['semester'];
    $faculty_id = $_POST['faculty']; // Add this line to get faculty_id

    $stmt = $conn->prepare("SELECT fc.id as fc_id, c.name as course_name, fc.section, fc.type, f.name as faculty_name
        FROM course c
        JOIN faculty_course fc ON fc.course_id = c.code AND c.type = fc.type
        JOIN faculty f ON fc.faculty_id = f.id
        WHERE fc.type = ? AND c.semester = ? 
        ORDER BY c.name, fc.section");

    $stmt->bind_param("si", $degree, $semester);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">Select Course</option>';

    while ($row = $result->fetch_assoc()) {
        echo '<option value="'. htmlspecialchars($row['fc_id']) . '" data-faculty-name="'. htmlspecialchars($row['faculty_name']) .'">';
        if ($row['type'] === "B.Tech") {
            echo htmlspecialchars($row['course_name']) . " CSE-" . htmlspecialchars($row['section']);
        } else {
            echo htmlspecialchars($row['course_name']);
        }
        echo '</option>';
    }

    $stmt->close();
    $conn->close();
}
?>