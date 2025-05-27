<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__. '/fpdf186/fpdf.php'; // Include FPDF
require __DIR__. '/FPDI-2.6.3/src/autoload.php'; // Include FPDI
require '../../config.php';

use setasign\Fpdi\Fpdi;

class PDF extends Fpdi {
    public $suppressFooter = false; // Flag to control footer rendering

    function Header() {
        if ($this->suppressFooter) return; // Skip header when appending PDF pages
        $this->Image('../ptu-logo.png', 10, 8, 20);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'PUDUCHERRY TECHNOLOGICAL UNIVERSITY', 0, 1, 'C');
        $this->Ln(1);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 10, '[GOVT. OF PUDUCHERRY INSTITUTION]', 0, 1, 'C');
        $this->Ln(5);

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Student Mark List', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        if ($this->suppressFooter) return; // Skip footer when appending PDF pages

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

// Function to convert PDF to compatible format using GhostScript
function convertPdfToCompatibleFormat($inputPath, $outputPath) {
    // Create a command that converts PDF to version 1.4 (compatible with FPDI free parser)
    $command = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/default -dNOPAUSE -dQUIET -dBATCH -sOutputFile=\"$outputPath\" \"$inputPath\"";
    
    // Execute the command
    exec($command, $output, $returnVar);
    
    // Return true if successful (exit code 0), false otherwise
    return ($returnVar === 0 && file_exists($outputPath));
}

if (!isset($_GET['fc_id']) || !isset($_GET['test_id'])) {
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
$sql = "SELECT c.semester FROM course c
        JOIN faculty_course fc
        ON fc.course_id = c.code AND fc.type = c.type
        WHERE fc.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$semester = $row['semester'];
$year = ceil($semester / 2);

// Fetch test/assignment details
$sql = "SELECT test_no, total_mark, test_date, question_paper_image FROM test WHERE id = ?";
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
$test_date = $row['test_date'];
$question_paper_image = "../".$row['question_paper_image'];

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

// Fetch questions
$sql = "SELECT id, question_no FROM question WHERE test_id = ?";
if ($test_type === 'assignment') {
    $sql = "SELECT id, question_no FROM assignment_question WHERE assignment_id = ?";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();
$questions = $result->fetch_all(MYSQLI_ASSOC);

// Fetch student marks
$sql = "SELECT s.register_no, s.name, m.question_id, m.obtained_mark FROM student s JOIN mark m ON s.register_no = m.student_id WHERE m.test_id = ? ORDER BY s.register_no";
if ($test_type === 'assignment') {
    $sql = "SELECT s.register_no, s.name, m.question_id, m.obtained_mark FROM student s JOIN assignment_mark m ON s.register_no = m.student_id WHERE m.assignment_id = ?";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();
$studentMarks = $result->fetch_all(MYSQLI_ASSOC);

$studentData = [];
foreach ($studentMarks as $mark) {
    $studentData[$mark['register_no']]['name'] = $mark['name'];
    $studentData[$mark['register_no']]['marks'][$mark['question_id']] = $mark['obtained_mark'];
}

// Calculate statistics
$absentees = array_filter($studentData, function ($student) {
    return in_array(-1, $student['marks']);
});
$numberOfAbsentees = count($absentees);

$allTotalMarks = [];
foreach ($studentData as $registerNo => &$student) {
    $totalMarks = 0;
    foreach ($student['marks'] as $mark) {
        if ($mark != -1) {
            $totalMarks += $mark;
        }
    }
    $student['totalMarks'] = $totalMarks;
    $allTotalMarks[] = $totalMarks;
}

$averageMark = count($allTotalMarks) ? array_sum($allTotalMarks) / count($allTotalMarks) : 0;

$squaredDifferences = array_map(function ($mark) use ($averageMark) {
    return pow($mark - $averageMark, 2);
}, $allTotalMarks);
$variance = count($allTotalMarks) ? array_sum($squaredDifferences) / count($squaredDifferences) : 0;
$standardDeviation = sqrt($variance);

// Create PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 10);

// Display details in three columns
$pdf->Cell(60, 10, 'Course Code : ' . $course_id, 0, 0);
$pdf->Cell(60, 10, 'Batch : ' . $batch, 0, 0);
$pdf->Cell(60, 10, $test_type . ' No: ' . $test_no, 0, 1);

$pdf->Cell(60, 10, 'Course Name : ' . $course_name, 0, 0);
$pdf->Cell(60, 10, 'Semester: ' . $semester, 0, 0);
$pdf->Cell(60, 10, $test_type . ' Date: ' . date('d-m-Y', strtotime($test_date)), 0, 1);

$pdf->Cell(60, 10, 'Faculty : ' . $faculty_name, 0, 0);
$pdf->Cell(60, 10, 'Year : ' . $year, 0, 0);
$pdf->Cell(60, 10, 'Total Mark: ' . $total_mark, 0, 1);

if (isset($section) && $section !== null) {
    $pdf->Cell(60, 10, "Class : CSE - " . $section, 0, 1);
} else {
    $pdf->Cell(60, 10, "Type : " . $type, 0, 1);
}

$pdf->Ln(10);

// Display student marks table
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(10, 10, 'SI/No', 1, 0, 'C');
$pdf->Cell(20, 10, 'Register No', 1, 0, 'C');
$pdf->Cell(40, 10, 'Name', 1, 0, 'C');
foreach ($questions as $question) {
    $pdf->Cell(10, 10, 'Q' . $question['question_no'], 1, 0, 'C');
}
$pdf->Cell(10, 10, 'Total', 1, 1, 'C');
$pdf->SetFont('Arial', '', 8);

$serialNo = 1;
foreach ($studentData as $registerNo => $data) {
    $totalMarks = 0;
    $name = $data['name'];
    $nameWidth = 40;
    $lineHeight = 10;
    $nameLines = ceil($pdf->GetStringWidth($name) / $nameWidth);
    $rowHeight = max(10, $nameLines * $lineHeight);
    
    $pdf->Cell(10, $rowHeight, $serialNo++, 1, 0, 'C');
    $pdf->Cell(20, $rowHeight, $registerNo, 1, 0, 'C');
    
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    
    $pdf->MultiCell($nameWidth, $lineHeight, $name, 1, 'L');
    
    $currentY = $pdf->GetY();
    $pdf->SetXY($x + $nameWidth, $y);
    
    $adjustedHeight = $currentY - $y;
    $rowHeight = max($rowHeight, $adjustedHeight);
    
    foreach ($questions as $question) {
        $mark = isset($data['marks'][$question['id']]) && $data['marks'][$question['id']] != -1
                ? $data['marks'][$question['id']]
                : 'a';
        $pdf->Cell(10, $rowHeight, $mark, 1, 0, 'C');
        $totalMarks = ($totalMarks === 'a' || $mark === 'a') ? 'a' : $totalMarks + $mark;
    }
    
    $pdf->Cell(10, $rowHeight, $totalMarks, 1, 1, 'C');
}

// Display statistics
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Statistics', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 10, 'Number of Absentees: ' . $numberOfAbsentees, 0, 1);
$pdf->Cell(0, 10, 'Average Mark: ' . number_format($averageMark, 2), 0, 1);
$pdf->Cell(0, 10, 'Standard Deviation: ' . number_format($standardDeviation, 2), 0, 1);

// Process the question paper PDF if it exists
if (!empty($question_paper_image) && file_exists($question_paper_image)) {
    // Create a temporary file path for the converted PDF
    $tempDir = sys_get_temp_dir();
    $convertedPdfPath = $tempDir . '/' . uniqid('converted_') . '.pdf';
    
    // Try to convert the PDF to a compatible format
    $conversionSuccess = convertPdfToCompatibleFormat($question_paper_image, $convertedPdfPath);
    
    // Suppress footer before appending pages
    $pdf->suppressFooter = true;
    
    if ($conversionSuccess) {
        try {
            // Try to import the converted PDF
            $pageCount = $pdf->setSourceFile($convertedPdfPath);
            for ($i = 1; $i <= $pageCount; $i++) {
                $pdf->AddPage();
                $templateId = $pdf->importPage($i);
                $pdf->useTemplate($templateId);
            }
            
            // Clean up the temporary converted file
            @unlink($convertedPdfPath);
            
        } catch (Exception $e) {
            // If there's still an error with the converted PDF
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'I', 12);
            $pdf->Cell(0, 10, 'Error importing converted PDF: ' . $e->getMessage(), 0, 1, 'C');
            $pdf->Cell(0, 10, 'Please refer to the original file: ' . basename($question_paper_image), 0, 1, 'C');
            
            // Clean up the temporary converted file
            @unlink($convertedPdfPath);
        }
    } else {
        // If conversion failed
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'I', 12);
        $pdf->Cell(0, 10, 'Failed to convert PDF to a compatible format.', 0, 1, 'C');
        $pdf->Cell(0, 10, 'Please refer to the original file: ' . basename($question_paper_image), 0, 1, 'C');
    }
    
    // Restore footer rendering for subsequent pages
    $pdf->suppressFooter = false;
} else if (!empty($question_paper_image)) {
    // If question paper file doesn't exist
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, 'Question paper file not found at path: ' . $question_paper_image, 0, 1, 'C');
}

// Output the final PDF
$pdf->Output('D', 'student_marks_report.pdf');
?>