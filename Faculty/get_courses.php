<?php
require '../config.php'; // Include database connection

if (isset($_POST['degree']) && isset($_POST['faculty']) && isset($_POST['batch'])) {
    $degree = $_POST['degree'];
    $faculty = $_POST['faculty'];
    $batch = $_POST['batch'];

    // Fetch batches based on degree
    $stmt = $conn->prepare("SELECT fc.id as fc_id, c.name, fc.section, fc.batch, fc.type
                                    FROM course c
                                    JOIN faculty_course fc
                                    ON fc.course_id = c.code AND c.type = fc.type
                                    WHERE fc.type = ? and fc.faculty_id = ? and fc.batch = ?");

    $stmt->bind_param("sss", $degree,$faculty, $batch);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">Select Course</option>'; // Default option

    while ($row = $result->fetch_assoc()) {
        echo '<option value="'. htmlspecialchars($row['fc_id']) . '">';
        if ($row['type'] === "B.Tech") {
            echo htmlspecialchars($row['name']) . " CSE-" . htmlspecialchars($row['section']);
        } else {
            echo htmlspecialchars($row['name']) . " " . htmlspecialchars($row['type']);
        }
        echo '</option>';
    }

    $stmt->close();
    $conn->close();
}
?>
