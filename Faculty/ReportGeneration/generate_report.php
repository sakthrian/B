<?php
include '../../config.php';

// Set the default timezone to Asia/Kolkata
date_default_timezone_set('Asia/Kolkata');

// Fetch parameters from URL
if (!isset($_GET['fc_id']) || !isset($_GET['test_id']) || !isset($_GET['test_type'])) {
    die('Error: Missing parameters.');
}

$fc_id = $_GET['fc_id'];
$test_id = $_GET['test_id'];
$test_type = $_GET['test_type'];

// Fetch section from faculty_course table
$sql = "SELECT course_id, faculty_id, section, batch, type FROM faculty_course WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$section = $row['section'];
$faculty_id = $row['faculty_id'];
$course_id = $row['course_id'];
$batch = $row['batch'];
$type = $row['type'];

// Fetch semester
$sql = "SELECT c.semester FROM course c JOIN faculty_course fc ON fc.course_id = c.code AND fc.type = c.type WHERE fc.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$semester = $row['semester'];

// Fetch Test or Assignment details
$sql = "SELECT test_no, total_mark, question_paper_image, test_date FROM test WHERE id = ?";
if ($test_type === 'assignment') {
    $sql = "SELECT assignment_no AS test_no, total_mark, assignment_file as question_paper_image, assignment_date as test_date FROM assignment WHERE id = ?";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$test_no = $row['test_no'];
$total_mark = $row['total_mark'];
$qp_pdf = $row['question_paper_image'];
$test_date = $row['test_date'];

// Fetch faculty name
$sql = "SELECT name FROM faculty WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$faculty_name = $row['name'];

// Fetch course name
$sql = "SELECT name FROM course WHERE code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$course_name = $row['name'];

// Fetch questions or assignments for the test
$sql = "SELECT id, question_no FROM question WHERE test_id = ?";
if ($test_type === 'assignment') {
    $sql = "SELECT id, question_no FROM assignment_question WHERE assignment_id = ?";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();
$questions = $result->fetch_all(MYSQLI_ASSOC);

// Fetch students and their marks
$sql = "SELECT s.register_no, s.name, m.question_id, m.obtained_mark 
        FROM student s 
        JOIN mark m ON s.register_no = m.student_id 
        WHERE m.test_id = ?";
if ($test_type === 'assignment') {
    $sql = "SELECT s.register_no, s.name, m.question_id, m.obtained_mark FROM student s JOIN assignment_mark m ON s.register_no = m.student_id WHERE m.assignment_id = ?";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();
$studentMarks = $result->fetch_all(MYSQLI_ASSOC);

// Prepare student marks data
$studentData = [];
foreach ($studentMarks as $mark) {
    $studentData[$mark['register_no']]['name'] = $mark['name'];
    $studentData[$mark['register_no']]['marks'][$mark['question_id']] = $mark['obtained_mark'];
}

// Calculate the number of absentees
$absentees = array_filter($studentData, function ($student) {
    return in_array(-1, $student['marks']);
});
$numberOfAbsentees = count($absentees);

// Calculate total marks for each student
foreach ($studentData as $registerNo => &$student) {
    $totalMarks = 0;
    foreach ($student['marks'] as $mark) {
        if ($mark != -1) {
            $totalMarks += $mark;
        }
    }
    $student['totalMarks'] = $totalMarks;
}

error_log("Total marks for each student: ".$student['totalMarks']);
// Extract total marks for all students
$allTotalMarks = array_column($studentData, 'totalMarks');

// Calculate the average mark
$averageMark = count($allTotalMarks) ? array_sum($allTotalMarks) / count($allTotalMarks) : 0;

// Calculate the standard deviation
$squaredDifferences = array_map(function ($mark) use ($averageMark) {
    return pow($mark - $averageMark, 2);
}, $allTotalMarks);
$variance = count($allTotalMarks) ? array_sum($squaredDifferences) / count($squaredDifferences) : 0;
$standardDeviation = sqrt($variance);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Mark List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .report-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #000;
            box-sizing: border-box;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 20px;
        }

        .header img {
            width: 100px;
            height: auto;
            margin-right: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .header p {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }

        .details {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .details p {
            flex: 1 1 45%;
            margin: 5px 0;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-size: 12px;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 20px;
        }

        .download-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            z-index: 1000;
        }

        .statistics {
            margin-top: 20px;
        }

        .statistics h3 {
            text-align: center;
            font-size: 16px;
        }

        .statistics p {
            margin: 5px 0;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="report-container">
        <!-- Header -->
        <div class="header">
            <img src="../ptu-logo.png" alt="PTU Logo">
            <div>
                <h1>PUDUCHERRY TECHNOLOGICAL UNIVERSITY</h1>
                <p>[GOVT. OF PUDUCHERRY INSTITUTION]</p>
            </div>
        </div>

        <!-- Title -->
        <h2 style="text-align: center; font-size: 18px; margin-bottom: 20px;">Student Mark List</h2>

        <!-- Details Section -->
        <div class="details">
            <p><strong>Course Code:</strong> <?php echo $course_id; ?></p>
            <p><strong>Faculty:</strong> <?php echo $faculty_name; ?></p>
            <p><strong>Course Name:</strong> <?php echo $course_name; ?></p>
            <?php
            if (isset($section) && $section !== null) {
                echo "<p><strong>Class:</strong> CSE -" . htmlspecialchars($section) . "</p>";
            } else {
                echo "<p><strong>Type:</strong> " . htmlspecialchars($type) . "</p>";
            }
            ?>
            <p><strong>Batch:</strong> <?php echo $batch; ?></p>
            <p><strong><?php echo ucfirst($test_type); ?> No:</strong> <?php echo $test_no; ?></p>
            <p><strong>Semester:</strong> <?php echo $semester; ?></p>
            <p><strong><?php echo ucfirst($test_type); ?> Date:</strong>
                <?php
                $date = new DateTime($test_date);
                echo $date->format('d-m-Y');
                ?>
            </p>
            <?php
            $year = ceil($semester / 2);

            echo "<p><strong>Year:</strong> " . htmlspecialchars($year) . "</p>";
            ?>
            <p><strong>Total Marks:</strong> <?php echo $total_mark; ?></p>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th>SI/No</th>
                    <th>Register No</th>
                    <th>Name</th>
                    <?php foreach ($questions as $question): ?>
                        <th>Q<?php echo $question['question_no']; ?></th>
                    <?php endforeach; ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $serialNo = 1;
                foreach ($studentData as $registerNo => $data):
                    $totalMarks = 0;
                    ?>
                    <tr>
                        <td><?php echo $serialNo++; ?></td>
                        <td><?php echo $registerNo; ?></td>
                        <td><?php echo $data['name']; ?></td>
                        <?php foreach ($questions as $question): ?>
                            <?php
                            $mark = isset($data['marks'][$question['id']]) && $data['marks'][$question['id']] != -1
                                ? $data['marks'][$question['id']]
                                : 'a';

                            $totalMarks = ($totalMarks === 'a' || $mark === 'a') ? 'a' : $totalMarks + $mark;
                            ?>
                            <td><?php echo $mark; ?></td>
                        <?php endforeach; ?>
                        <td><?php echo $totalMarks; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Statistics Section -->
        <div class="statistics">
            <h3 style="text-align: center; font-size: 16px; margin-top: 20px;">Statistics</h3>
            <p><strong>Number of Absentees:</strong> <?php echo $numberOfAbsentees; ?></p>
            <p><strong>Average Mark:</strong> <?php echo number_format($averageMark, 2); ?></p>
            <p><strong>Standard Deviation:</strong> <?php echo number_format($standardDeviation, 2); ?></p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Generated on: <?php echo date('d-m-Y h:i:s A'); ?></p> <!-- Timestamp in Asia/Kolkata -->
        </div>
    </div>

    <!-- Download Button -->
    <a href="generate_report_pdf.php?fc_id=<?php echo $fc_id; ?>&test_id=<?php echo $test_id; ?>&test_type=<?php echo $test_type; ?>"
        class="download-btn">Download as PDF</a>
</body>

</html>