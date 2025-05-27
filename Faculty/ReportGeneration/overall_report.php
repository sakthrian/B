<?php
include '../../config.php';

// Set the default timezone to Asia/Kolkata
date_default_timezone_set('Asia/Kolkata');

// Fetch parameters from URL
if (!isset($_GET['fc_id'])) {
    die('Error: Missing parameters.');
}

$fc_id = $_GET['fc_id'];

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

// Fetch faculty name
$sql = "SELECT name FROM faculty WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$faculty = $result->fetch_assoc();
$faculty_name = $faculty['name']; // Default to 'Unknown Faculty' if name is not found

// Fetch course name
$sql = "SELECT name FROM course WHERE code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
$course_name = $course['name'] ?? 'Unknown Course'; // Default to 'Unknown Course' if name is not found

// Fetch all tests for the course
$sql = "SELECT id, test_no, total_mark FROM test WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$tests = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all assignments for the course
$sql = "SELECT id, assignment_no, total_mark FROM assignment WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$assignments = $result->fetch_all(MYSQLI_ASSOC);

// Fetch students and their total marks for all tests and assignments
$sql = "SELECT s.register_no, s.name, 
               SUM(m.obtained_mark) AS total_test_marks, 
               SUM(am.obtained_mark) AS total_assignment_marks
        FROM student s
        LEFT JOIN mark m ON s.register_no = m.student_id
        LEFT JOIN assignment_mark am ON s.register_no = am.student_id
        WHERE m.test_id IN (SELECT id FROM test WHERE fc_id = ?)
          OR am.assignment_id IN (SELECT id FROM assignment WHERE fc_id = ?)
        GROUP BY s.register_no";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $fc_id, $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$studentMarks = $result->fetch_all(MYSQLI_ASSOC);

// Fetch total marks for each test and assignment for each student
$studentTestMarks = [];
$studentAssignmentMarks = [];

foreach ($tests as $test) {
    $sql = "SELECT m.student_id, SUM(m.obtained_mark) AS total_mark 
            FROM mark m 
            WHERE m.test_id = ?
            GROUP BY m.student_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $test['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $studentTestMarks[$row['student_id']][$test['id']] = $row['total_mark'];
    }
}

foreach ($assignments as $assignment) {
    $sql = "SELECT am.student_id, SUM(am.obtained_mark) AS total_mark 
            FROM assignment_mark am 
            WHERE am.assignment_id = ?
            GROUP BY am.student_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $studentAssignmentMarks[$row['student_id']][$assignment['id']] = $row['total_mark'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Overall Marks Report</title>
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
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            white-space: normal;
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
            padding: 10px 20px;
            color: #fff;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
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
        <h2 style="text-align: center; font-size: 18px; margin-bottom: 20px;">Student Overall Marks Report</h2>

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
            <p><strong>Semester:</strong> <?php echo $semester; ?></p>
            <?php
            $year = ceil($semester / 2);

            echo "<p><strong>Year:</strong> " . htmlspecialchars($year) . "</p>";
            ?>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th>SI/No</th>
                    <th>Register No</th>
                    <th>Name</th>
                    <?php if (!empty($tests)): ?> <!-- Check if tests exist -->
                        <?php foreach ($tests as $test): ?>
                            <th>Test <?php echo $test['test_no']; ?> (<?php echo $test['total_mark']; ?>)</th>
                        <?php endforeach; ?>
                        <th>Avg</th> <!-- Add Avg only if tests exist -->
                    <?php endif; ?>
                    <?php if (!empty($assignments)): ?> <!-- Check if assignments exist -->
                        <?php foreach ($assignments as $assignment): ?>
                            <th>Assignment <?php echo $assignment['assignment_no']; ?>
                                (<?php echo $assignment['total_mark']; ?>)</th>
                        <?php endforeach; ?>
                        <th>Avg</th> <!-- Add Avg only if assignments exist -->
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $serialNo = 1;
                foreach ($studentMarks as $student):
                    ?>
                    <tr>
                        <td><?php echo $serialNo++; ?></td>
                        <td><?php echo $student['register_no']; ?></td>
                        <td><?php echo $student['name']; ?></td>
                        <?php
                        $totalTestMarks = 0;
                        $testCount = 0;
                        if (!empty($tests)) {
                            foreach ($tests as $test):
                                $mark = $studentTestMarks[$student['register_no']][$test['id']] ?? 0;
                                $mark = $mark < 0 ? 'a' : $mark;
                                echo '<td>' . $mark . '</td>';
                                if ($mark !== 'a') { // Only add to total if not absent
                                    $totalTestMarks += $mark;
                                    $testCount++;
                                }
                            endforeach;
                            $avgTestMarks = $testCount > 0 ? $totalTestMarks / $testCount : 0;
                            echo '<td>' . number_format($avgTestMarks, 2) . '</td>';
                        }
                        ?>
                        <?php
                        $totalAssignmentMarks = 0;
                        $assignmentCount = 0;
                        if (!empty($assignments)) {
                            foreach ($assignments as $assignment):
                                $mark = $studentAssignmentMarks[$student['register_no']][$assignment['id']] ?? 0;
                                $mark = $mark < 0 ? 'a' : $mark;
                                echo '<td>' . $mark . '</td>';
                                if($mark !== 'a'){
                                    $totalAssignmentMarks += $mark;
                                    $assignmentCount++;
                                }
                            endforeach;
                            $avgAssignmentMarks = $assignmentCount > 0 ? $totalAssignmentMarks / $assignmentCount : 0;
                            echo '<td>' . number_format($avgAssignmentMarks, 2) . '</td>';
                        }
                        ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>Generated on: <?php echo date('d-m-Y h:i:s A'); ?></p>
        </div>
    </div>

    <!-- Download Buttons -->
    <div
        style="position: fixed; bottom: 20px; right: 20px; display: flex; flex-direction: column; gap: 10px; z-index: 1000;">
        <a href="overall_report_pdf.php?fc_id=<?php echo $fc_id; ?>"
            class="download-btn" style="background-color: #007bff;">Download as PDF</a>
        <a href="overall_report_excel.php?fc_id=<?php echo $fc_id; ?>"
            class="download-btn" style="background-color: #28a745;">Download as Excel</a>
    </div>
</body>

</html>