<?php
include '../config.php';

if (!isset($_GET['fc_id'])) {
    error_log("Invalid access: Missing faculty-course id" . $_GET["fc_id"]);
    echo json_encode(['error' => 'Invalid access!']);
    exit();
}
$fc_id = $_GET['fc_id'];
$type = $_GET['type'];
$course_type = null;

$stmt = $conn->prepare("Select c.course_type
                        from course c
                        join faculty_course fc
                        on fc.course_id = c.code
                        WHERE fc.id = ?;");
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$stmt->bind_result($course_type);
$stmt->fetch();
$stmt->close();


$stmt = $conn->prepare("SELECT section, batch FROM faculty_course WHERE id = ?");
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$stmt->bind_result($sec, $batch);

if (!$stmt->fetch()) {
    error_log("No matching record found for fc_id: $fc_id");
    echo json_encode(['error' => 'Invalid faculty-course ID!']);
    exit();
}
$stmt->close();
error_log("Fetching students for batch: $batch and section : $sec and course type : $course_type");

// Fetch semester from the selected course
$sql = "SELECT c.semester FROM course c
        JOIN faculty_course fc on fc.course_id = c.code
        WHERE fc.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$semesterRow = $result->fetch_assoc();
$semester = $semesterRow['semester'];
error_log('Sem ' . $semester);

// Fetch students for the selected semester
if ($sec) {
    if ($course_type != "PSE") {
        $sql = "SELECT register_no, name
        FROM student WHERE batch = ? and section = ? and type = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $batch, $sec, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['semester' => $semester, 'students' => $students]);
    } else {
        $sql = "SELECT s.register_no, s.name
        FROM student_course_assignment sa
        JOIN student s ON sa.student_id = s.register_no
        WHERE sa.faculty_course_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $fc_id); // Only one parameter: fc_id
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['semester' => $semester, 'students' => $students]);
    }

} else {
    if ($course_type != "PEC") {
        $sql = "SELECT register_no, name
            FROM student WHERE batch = ? and type = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $batch, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['semester' => $semester, 'students' => $students]);
    }else{
        $sql = "SELECT s.register_no, s.name
        FROM student_course_assignment sa
        JOIN student s ON sa.student_id = s.register_no
        WHERE sa.faculty_course_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $fc_id); // Only one parameter: fc_id
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['semester' => $semester, 'students' => $students]);
    }

}

?>