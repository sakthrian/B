<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $fc_id = $data['fc_id'];

    $sql = "
    SELECT fc.id, fc.faculty_id, f.name AS faculty_name, fc.course_id, c.name AS course_name,
           fc.type, c.semester, fc.section, fc.batch, c.regulation
    FROM faculty_course fc
    JOIN faculty f ON f.id = fc.faculty_id
    JOIN course c ON c.code = fc.course_id AND c.type = fc.type
    WHERE fc.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fc_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = [];

    if ($row = $result->fetch_assoc()) {
        // Store each value into variables
        $faculty_id = $row['faculty_id'];
        $faculty_name = $row['faculty_name'];
        $course_id = $row['course_id'];
        $course_name = $row['course_name'];
        $degree = $row['type'];
        $semester = $row['semester'];
        $section = $row['section'];
        $batch = $row['batch'];
        $regulation = $row['regulation'];

    } else {
        echo json_encode(['success' => false, 'message' => 'Courses not found for this faculty']);
    }

    $sql = "
    SELECT cc.ca, co.co_number, co.id AS co_id
    FROM co_overall cc
    JOIN course_outcome co ON cc.co_id = co.id
    WHERE cc.fc_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fc_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $insert_sql = "
    INSERT INTO co_attainment (
        fc_id, faculty_id, faculty_name, course_id, course_name,
        type, semester, section, batch, regulation, co_number, ca
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $insert_stmt = $conn->prepare($insert_sql);

    $alreadyExists = false;

    while ($co_row = $result->fetch_assoc()) {
        $co_number = $co_row['co_number'];
        $ca = $co_row['ca'];

        // Check if the combination already exists
        $check_sql = "SELECT 1 FROM co_attainment WHERE fc_id = ? AND co_number = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $fc_id, $co_number);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $alreadyExists = true;
            $check_stmt->close();
            break; 
        }

        $check_stmt->close();

        // Proceed to insert if not duplicate
        $insert_stmt->bind_param(
            "iissssissssd",
            $fc_id,
            $faculty_id,
            $faculty_name,
            $course_id,
            $course_name,
            $degree,
            $semester,
            $section,
            $batch,
            $regulation,
            $co_number,
            $ca
        );
        $insert_stmt->execute();
    }

    $insert_stmt->close();

    if ($alreadyExists) {
        echo json_encode(value: ['success' => false, 'message' => 'Marks are already freezed.']);
    }
    else{
        echo json_encode(['success' => true, 'message' => 'Marks frozen successfully']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>