<?php
require './fpdf186/fpdf.php';
include '../../config.php';

class PDF extends FPDF {
    function Header() {
        $this->Image('../ptu-logo.png', 10, 8, 20);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'PUDUCHERRY TECHNOLOGICAL UNIVERSITY', 0, 1, 'C');
        $this->Ln(1);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 10,'[GOVT. OF PUDUCHERRY INSTITUTION]',0,1,'C');

        $this->Ln(5);

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Student Overall Marks Report', 0, 1, 'C');

        $this->Ln(10);
    }

    function Footer() {
        date_default_timezone_set('Asia/Kolkata'); // Adjust based on your location
        $this->SetY(-17); // Move up to create space for the signature
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(0, 10, 'Faculty Signature: ________________________', 0, 1, 'R');
        
        $this->SetY(-20); // Adjust position for the page number and timestamp
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        
        $this->SetY(-10);
        $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d h:i:s A'), 0, 0, 'R');
    }
}

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
$year = ceil($semester /2);

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

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('P'); // Portrait orientation
$pdf->SetFont('Arial', 'B', 11);

// Display details
$pdf->Cell(60, 10, 'Course Code : ' . $course_id, 0, 0);
$pdf->Cell(10, 10, '', 0, 0); // Gap between columns
$pdf->Cell(60, 10, 'Batch : ' . $batch, 0, 1);
$pdf->Cell(60, 10, 'Course Name : ' . $course_name, 0, 0);
$pdf->Cell(10, 10, '', 0, 0); // Gap between columns
$pdf->Cell(60, 10, 'Semester: ' . $semester,  0, 1);
$pdf->Cell(60, 10, 'Faculty : ' . $faculty_name, 0, 0);
$pdf->Cell(10, 10, '', 0, 0); // Gap between columns
$pdf->Cell(60, 10, 'Year : ' . $year, 0, 1);

if (isset($section) && $section !== null) {
    $pdf->Cell(60, 10, "Class : CSE - " . $section, 0, 0);
} else {
    $pdf->Cell(60, 10, "Type : " . $type, 0, 0);
}

$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(7, 10, 'SI/No', 1, 0, 'C');
$pdf->Cell(20, 10, 'Register No', 1, 0, 'C');
$pdf->Cell(40, 10, 'Name', 1, 0, 'C');

if(!empty($tests)){
    foreach ($tests as $test) {
        $pdf->Cell(15, 10, 'T' . $test['test_no'] . ' (' . $test['total_mark'] . ')', 1, 0, 'C');
    }
    $pdf->Cell(15, 10, 'Avg', 1, 0, 'C');
}

if(!empty($assignments)){
    foreach ($assignments as $assignment) {
        $pdf->Cell(15, 10, 'A' . $assignment['assignment_no'] . ' (' . $assignment['total_mark'] . ')', 1, 0, 'C');
    }
    $pdf->Cell(15, 10, 'Avg', 1, 0, 'C');
}

$pdf->Ln(); // Move to the next line after headers

$pdf->SetFont('Arial', '', 8);

$serialNo = 1;
foreach ($studentMarks as $student) {
    $name = $student['name'];
    $nameWidth = 40;
    $lineHeight = 10;
    $nameLines = ceil($pdf->GetStringWidth($name) / $nameWidth);
    $rowHeight = max(10, $nameLines * $lineHeight);

    $pdf->Cell(7, $rowHeight, $serialNo++, 1, 0, 'C');
    $pdf->Cell(20, $rowHeight, $student['register_no'], 1, 0, 'C');

    // Save the current position
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Write the name using MultiCell
    $pdf->MultiCell($nameWidth, $lineHeight, $name, 1, 'L');

    // Restore the position for the next cells
    $currentY = $pdf->GetY();
    $pdf->SetXY($x + $nameWidth, $y);

    // Adjust the row height if the MultiCell took more space
    $adjustedHeight = $currentY - $y;
    $rowHeight = max($rowHeight, $adjustedHeight);

    $totalTestMarks = 0;
    $testCount = 0;
    if(!empty($tests))
    {
        foreach ($tests as $test) {
            $mark = $studentTestMarks[$student['register_no']][$test['id']] ?? -1;
            $mark = $mark < 0 ? 'a' : $mark; // Changed from 0 to 'a'
            $pdf->Cell(15, $rowHeight, $mark, 1, 0, 'C');
            if ($mark !== 'a') { // Only add to total if not absent
                $totalTestMarks += $mark;
                $testCount++;
            }
        }
        $avgTestMarks = $testCount > 0 ? number_format($totalTestMarks / $testCount, 2) : 0;
        $pdf->Cell(15, $rowHeight, $avgTestMarks, 1, 0, 'C');    
    }

    $totalAssignmentMarks = 0;
    $assignmentCount = 0;
    if(!empty($assignments)){
        foreach ($assignments as $assignment) {
            $mark = $studentAssignmentMarks[$student['register_no']][$assignment['id']] ?? -1;
            $mark = $mark < 0 ? 'a' : $mark; // Changed from 0 to 'a'
            $pdf->Cell(15, $rowHeight, $mark, 1, 0, 'C');
            if ($mark !== 'a') { // Only add to total if not absent
                $totalAssignmentMarks += $mark;
                $assignmentCount++;
            }
        }
        $avgAssignmentMarks = $assignmentCount > 0 ? number_format($totalAssignmentMarks / $assignmentCount, 2) : 0;
        $pdf->Cell(15, $rowHeight, $avgAssignmentMarks, 1, 0, 'C');    
    }
    
    $pdf->Ln(); // Move to the next line after each student's data
}

$pdf->Output('D', 'student_overall_marks_report.pdf');