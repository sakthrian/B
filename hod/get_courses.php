<?php
require '../config.php'; // Include database connection

if (isset($_POST['degree']) && isset($_POST['batch'])) {
    $degree = $_POST['degree'];
    $batch = $_POST['batch'];

    // Fetch batches based on degree
    $stmt = $conn->prepare("SELECT fc.id as fc_id, c.name as course_name, fc.section, fc.batch, fc.type, f.name as faculty_name
        FROM course c
        JOIN faculty_course fc ON fc.course_id = c.code AND c.type = fc.type
        JOIN faculty f ON fc.faculty_id = f.id
        WHERE fc.type = ? AND fc.batch = ?
        ORDER BY c.name, fc.section");

    $stmt->bind_param("ss", $degree, $batch);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">Select Course</option>'; // Default option

    while ($row = $result->fetch_assoc()) {
        echo '<option value="'. htmlspecialchars($row['fc_id']) . '" data-faculty-name="'. htmlspecialchars($row['faculty_name']) .'">';
        if ($row['type'] === "B.Tech") {
            echo htmlspecialchars($row['course_name']) . " CSE-" . htmlspecialchars($row['section']);
        } else {
            echo htmlspecialchars($row['course_name']);
        }
        echo '</option>';
    }
    echo '<br><h3></h3>';

    $stmt->close();
    $conn->close();
}
?>