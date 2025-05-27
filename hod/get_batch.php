<?php
require '../config.php'; // Include database connection

if (isset($_POST['degree']) && isset($_POST['faculty'])) {
    $degree = $_POST['degree'];
    $faculty = $_POST['faculty'];

    // Fetch batches based on degree
    $stmt = $conn->prepare("SELECT DISTINCT batch FROM faculty_course WHERE type = ?");
    $stmt->bind_param("s", $degree);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">Select Batch</option>'; // Default option

    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . htmlspecialchars($row['batch']) . '">' . htmlspecialchars($row['batch']) . '</option>';
    }

    $stmt->close();
    $conn->close();
}
?>
