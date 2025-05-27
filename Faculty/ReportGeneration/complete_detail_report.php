<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require './fpdf186/fpdf.php';
require './FPDI-2.6.3/src/autoload.php'; // Include FPDI
include '../../config.php';

use setasign\Fpdi\Fpdi;

// Function to convert PDF to compatible format using GhostScript
function convertPdfToCompatibleFormat($inputPath, $outputPath) {
    // Create a command that converts PDF to version 1.4 (compatible with FPDI free parser)
    $command = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/default -dNOPAUSE -dQUIET -dBATCH -sOutputFile=\"$outputPath\" \"$inputPath\"";
    
    // Execute the command
    exec($command, $output, $returnVar);
    
    // Return true if successful (exit code 0), false otherwise
    return ($returnVar === 0 && file_exists($outputPath));
}

class PDF extends Fpdi
{
    public $suppressFooter = false; // Flag to control footer rendering

    function Header()
    {
        if ($this->suppressFooter) return; // Skip header when appending PDF pages
        $this->Image('../ptu-logo.png', 10, 8, 20);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'PUDUCHERRY TECHNOLOGICAL UNIVERSITY', 0, 1, 'C');
        $this->Ln(1);

        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 10, '[GOVT. OF PUDUCHERRY INSTITUTION]', 0, 1, 'C');

        $this->Ln(5);

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Complete Course Details', 0, 1, 'C');

        $this->Ln(10);
    }

    function hasAnyValidMarks($studentData)
    {
        foreach ($studentData['tests'] as $mark) {
            if ($mark !== 'a')
                return true;
        }
        foreach ($studentData['assignments'] as $mark) {
            if ($mark !== 'a')
                return true;
        }
        return false;
    }

    function Footer()
    {
        if ($this->suppressFooter) return; // Skip footer when appending PDF pages

        date_default_timezone_set('Asia/Kolkata');
        $this->SetY(-17);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(0, 10, 'Faculty Signature: ________________________', 0, 1, 'R');

        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');

        $this->SetY(-10);
        $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d h:i:s A'), 0, 0, 'R');
    }
}

if (!isset($_GET['fc_id']) ) {
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
$sql = "SELECT semester FROM course WHERE code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$semesterRow = $result->fetch_assoc();
$semester = $semesterRow['semester'];
$year = ceil($semester / 2);

// Fetch faculty name
$sql = "SELECT name FROM faculty WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$faculty_name = $row['name'] ?? 'Unknown Faculty';

// Fetch course name
$sql = "SELECT name FROM course WHERE code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$course_name = $row['name'] ?? 'Unknown Course';

// Fetch tests and assignments
$sql = "SELECT id, test_no, total_mark, test_date, question_paper_image FROM test WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$tests = $result->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT id, assignment_no, total_mark, assignment_date, assignment_file as question_paper_image FROM assignment WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$assignments = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all students in the course
$sql = "SELECT s.register_no, s.name
        FROM course c
        JOIN faculty_course fc
        ON fc.course_id = c.code AND fc.type = c.type 
        JOIN student s
        ON fc.batch = s.batch AND (fc.section = s.section OR s.section IS NULL OR s.section = '') 
        AND fc.type = s.type
        WHERE fc.course_id= ? 
        AND fc.faculty_id= ? 
        AND s.type = ?";
if ($section) {
    $sql .= " AND s.section = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $course_id, $faculty_id, $type, $section);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $course_id, $faculty_id, $type);
}
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

// Fetch test marks for all students
$testMarks = [];
foreach ($tests as $test) {
    $sql = "SELECT m.student_id, m.question_id, m.obtained_mark 
            FROM mark m 
            WHERE m.test_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $test['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $testMarks[$test['id']][$row['student_id']][$row['question_id']] = $row['obtained_mark'];
    }
}

// Fetch assignment marks for all students
$assignmentMarks = [];
foreach ($assignments as $assignment) {
    $sql = "SELECT m.student_id, m.question_id, m.obtained_mark 
            FROM assignment_mark m 
            WHERE m.assignment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignmentMarks[$assignment['id']][$row['student_id']][$row['question_id']] = $row['obtained_mark'];
    }
}

// Fetch questions for all tests
$testQuestions = [];
foreach ($tests as $test) {
    $sql = "SELECT id, question_no FROM question WHERE test_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $test['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $testQuestions[$test['id']] = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch questions for all assignments
$assignmentQuestions = [];
foreach ($assignments as $assignment) {
    $sql = "SELECT id, question_no FROM assignment_question WHERE assignment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignmentQuestions[$assignment['id']] = $result->fetch_all(MYSQLI_ASSOC);
}

$studentTotals = [];
foreach ($students as $student) {
    $regNo = $student['register_no'];
    $studentTotals[$regNo] = ['name' => $student['name'], 'tests' => [], 'assignments' => []];

    foreach ($tests as $test) {
        $total = 'a'; // Default to absent
        if (isset($testMarks[$test['id']][$regNo])) {
            $total = 0;
            $hasValidMarks = false;
            foreach ($testMarks[$test['id']][$regNo] as $mark) {
                if ($mark != -1) {
                    $total += $mark;
                    $hasValidMarks = true;
                }
            }
            $total = $hasValidMarks ? $total : 'a';
        }
        $studentTotals[$regNo]['tests'][$test['id']] = $total;
    }

    foreach ($assignments as $assignment) {
        $total = 'a'; // Default to absent
        if (isset($assignmentMarks[$assignment['id']][$regNo])) {
            $total = 0;
            $hasValidMarks = false;
            foreach ($assignmentMarks[$assignment['id']][$regNo] as $mark) {
                if ($mark != -1) {
                    $total += $mark;
                    $hasValidMarks = true;
                }
            }
            $total = $hasValidMarks ? $total : 'a';
        }
        $studentTotals[$regNo]['assignments'][$assignment['id']] = $total;
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 10);

// Display course details
$pdf->Cell(60, 10, 'Course Code : ' . $course_id, 0, 0);
$pdf->Cell(10, 10, '', 0, 0);
$pdf->Cell(60, 10, 'Batch : ' . $batch, 0, 1);
$pdf->Cell(60, 10, 'Course Name : ' . $course_name, 0, 0);
$pdf->Cell(10, 10, '', 0, 0);
$pdf->Cell(60, 10, 'Semester: ' . $semester, 0, 1);
$pdf->Cell(60, 10, 'Faculty : ' . $faculty_name, 0, 0);
$pdf->Cell(10, 10, '', 0, 0);
$pdf->Cell(60, 10, 'Year : ' . $year, 0, 1);

if (isset($section) && $section !== null) {
    $pdf->Cell(60, 10, "Class : CSE - " . $section, 0, 0);
} else {
    $pdf->Cell(60, 10, "Type : " . $type, 0, 0);
}

$pdf->Ln(10);

// Display tests and assignments summary
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(40, 10, 'Test/Assignment', 1, 0, 'C');
$pdf->Cell(30, 10, 'Number', 1, 0, 'C');
$pdf->Cell(30, 10, 'Total Mark', 1, 0, 'C');
$pdf->Cell(30, 10, 'Date', 1, 1, 'C');

$pdf->SetFont('Arial', '', 8);
foreach ($tests as $test) {
    $pdf->Cell(40, 10, 'Test', 1, 0, 'C');
    $pdf->Cell(30, 10, $test['test_no'], 1, 0, 'C');
    $pdf->Cell(30, 10, $test['total_mark'], 1, 0, 'C');
    $pdf->Cell(30, 10, $test['test_date'], 1, 1, 'C');
}

foreach ($assignments as $assignment) {
    $pdf->Cell(40, 10, 'Assignment', 1, 0, 'C');
    $pdf->Cell(30, 10, $assignment['assignment_no'], 1, 0, 'C');
    $pdf->Cell(30, 10, $assignment['total_mark'], 1, 0, 'C');
    $pdf->Cell(30, 10, $assignment['assignment_date'], 1, 1, 'C');
}

$pdf->Ln(10);

// Display test marks in detail
foreach ($tests as $test) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, 'Test ' . $test['test_no'] . ' Marks', 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(10, 10, 'SI/No', 1, 0, 'C');
    $pdf->Cell(20, 10, 'Register No', 1, 0, 'C');
    $pdf->Cell(40, 10, 'Name', 1, 0, 'C');

    // Add question columns
    foreach ($testQuestions[$test['id']] as $question) {
        $pdf->Cell(15, 10, 'Q' . $question['question_no'], 1, 0, 'C');
    }
    $pdf->Cell(15, 10, 'Total', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 8);
    $serialNo = 1;
    foreach ($students as $student) {
        $regNo = $student['register_no'];
        $name = $student['name'];
        $nameWidth = 40;
        $lineHeight = 10;
        $nameLines = ceil($pdf->GetStringWidth($name) / $nameWidth);
        $rowHeight = max(10, $nameLines * $lineHeight);

        $pdf->Cell(10, $rowHeight, $serialNo++, 1, 0, 'C');
        $pdf->Cell(20, $rowHeight, $regNo, 1, 0, 'C');

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->MultiCell($nameWidth, $lineHeight, $name, 1, 'L');

        $currentY = $pdf->GetY();
        $pdf->SetXY($x + $nameWidth, $y);

        $adjustedHeight = $currentY - $y;
        $rowHeight = max($rowHeight, $adjustedHeight);

        $total = 0;
        foreach ($testQuestions[$test['id']] as $question) {
            $mark = isset($testMarks[$test['id']][$regNo][$question['id']]) ?
                ($testMarks[$test['id']][$regNo][$question['id']] == -1 ? 'a' : $testMarks[$test['id']][$regNo][$question['id']]) : 'a';
            $pdf->Cell(15, $rowHeight, $mark, 1, 0, 'C');
            if ($mark !== 'a') {
                $total += $mark;
            }
        }
        $pdf->Cell(15, $rowHeight, $total, 1, 1, 'C');
    }
    $pdf->Ln(10);
}

// Display assignment marks in detail
foreach ($assignments as $assignment) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, 'Assignment ' . $assignment['assignment_no'] . ' Marks', 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(10, 10, 'SI/No', 1, 0, 'C');
    $pdf->Cell(20, 10, 'Register No', 1, 0, 'C');
    $pdf->Cell(40, 10, 'Name', 1, 0, 'C');

    // Add question columns
    foreach ($assignmentQuestions[$assignment['id']] as $question) {
        $pdf->Cell(15, 10, 'Q' . $question['question_no'], 1, 0, 'C');
    }
    $pdf->Cell(15, 10, 'Total', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 8);
    $serialNo = 1;
    foreach ($students as $student) {
        $regNo = $student['register_no'];
        $name = $student['name'];
        $nameWidth = 40;
        $lineHeight = 10;
        $nameLines = ceil($pdf->GetStringWidth($name) / $nameWidth);
        $rowHeight = max(10, $nameLines * $lineHeight);

        $pdf->Cell(10, $rowHeight, $serialNo++, 1, 0, 'C');
        $pdf->Cell(20, $rowHeight, $regNo, 1, 0, 'C');

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->MultiCell($nameWidth, $lineHeight, $name, 1, 'L');

        $currentY = $pdf->GetY();
        $pdf->SetXY($x + $nameWidth, $y);

        $adjustedHeight = $currentY - $y;
        $rowHeight = max($rowHeight, $adjustedHeight);

        $total = 0;
        foreach ($assignmentQuestions[$assignment['id']] as $question) {
            $mark = isset($assignmentMarks[$assignment['id']][$regNo][$question['id']]) ?
                ($assignmentMarks[$assignment['id']][$regNo][$question['id']] == -1 ? 'a' : $assignmentMarks[$assignment['id']][$regNo][$question['id']]) : 'a';
            $pdf->Cell(15, $rowHeight, $mark, 1, 0, 'C');
            if ($mark !== 'a') {
                $total += $mark;
            }
        }
        $pdf->Cell(15, $rowHeight, $total, 1, 1, 'C');
    }
    $pdf->Ln(10);
}

// Display overall summary
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 10, 'Overall Marks Summary', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(10, 10, 'SI/No', 1, 0, 'C');
$pdf->Cell(20, 10, 'Register No', 1, 0, 'C');
$pdf->Cell(40, 10, 'Name', 1, 0, 'C');

// Add test columns
foreach ($tests as $test) {
    $pdf->Cell(15, 10, 'T' . $test['test_no'], 1, 0, 'C');
}
// Add assignment columns
foreach ($assignments as $assignment) {
    $pdf->Cell(15, 10, 'A' . $assignment['assignment_no'], 1, 0, 'C');
}
$pdf->Cell(15, 10, 'Total', 1, 1, 'C');

$pdf->SetFont('Arial', '', 8);
$serialNo = 1;
foreach ($students as $student) {
    $regNo = $student['register_no'];
    $name = $student['name'];
    $nameWidth = 40;
    $lineHeight = 10;
    $nameLines = ceil($pdf->GetStringWidth($name) / $nameWidth);
    $rowHeight = max(10, $nameLines * $lineHeight);

    $pdf->Cell(10, $rowHeight, $serialNo++, 1, 0, 'C');
    $pdf->Cell(20, $rowHeight, $regNo, 1, 0, 'C');

    $x = $pdf->getX();
    $y = $pdf->getY();

    $pdf->MultiCell($nameWidth, $lineHeight, $name, 1, 'L');

    $currentY = $pdf->getY();
    $pdf->setXY($x + $nameWidth, $y);

    $adjustedHeight = $currentY - $y;
    $rowHeight = max($rowHeight, $adjustedHeight);

    $total = 0;
    // Test marks
    foreach ($tests as $test) {
        $testTotal = isset($studentTotals[$regNo]['tests'][$test['id']]) ? $studentTotals[$regNo]['tests'][$test['id']] : 'a';
        $pdf->Cell(15, $rowHeight, $testTotal, 1, 0, 'C');
        if ($testTotal !== 'a') {
            $total += $testTotal;
        }
    }
    // Assignment marks
    foreach ($assignments as $assignment) {
        $assignmentTotal = isset($studentTotals[$regNo]['assignments'][$assignment['id']]) ? $studentTotals[$regNo]['assignments'][$assignment['id']] : 'a';
        $pdf->Cell(15, $rowHeight, $assignmentTotal, 1, 0, 'C');
        if ($assignmentTotal !== 'a') {
            $total += $assignmentTotal;
        }
    }
    // For the total cell, show 'a' if all were absent
    $finalTotal = ($total === 0 && !$pdf->hasAnyValidMarks($studentTotals[$regNo])) ? 'a' : $total;
    $pdf->Cell(15, $rowHeight, $finalTotal, 1, 1, 'C');
}
$pdf->Ln(10);

$sql = "SELECT id, co_number FROM course_outcome WHERE course_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$cos = $result->fetch_all(MYSQLI_ASSOC);

// Fetch CO test results
$sql = "SELECT co_id, test_id, co_level FROM co_test_results WHERE test_id IN (SELECT id FROM test WHERE fc_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$co_test_results = $result->fetch_all(MYSQLI_ASSOC);

// Fetch CO assignment results
$sql = "SELECT co_id, assignment_id, co_level FROM co_assignment_results WHERE assignment_id IN (SELECT id FROM assignment WHERE fc_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$co_assignment_results = $result->fetch_all(MYSQLI_ASSOC);

// Fetch CO attainment data
$sql = "SELECT co_id, cia, se, da, ia, ca FROM co_overall WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$co_overall_data = $result->fetch_all(MYSQLI_ASSOC);

// Check if $co_overall_data is null or empty
if (empty($co_overall_data)) {
    $co_overall_data = []; // Ensure it is an empty array if no data is fetched
}

// Display CO attainment with full table
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 10, 'Course Outcome Attainment', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(10, 10, '', 1, 0, 'C');
foreach ($cos as $co) {
    $pdf->Cell(20, 10, 'CO' . $co['co_number'], 1, 0, 'C');
}
$pdf->Ln();

foreach ($tests as $test) {
    $pdf->Cell(10, 10, 'T' . $test['test_no'], 1, 0, 'C');
    foreach ($cos as $co) {
        $result = array_filter($co_test_results, fn($item) => $item['co_id'] == $co['id'] && $item['test_id'] == $test['id']);
        $pdf->Cell(20, 10, $result ? reset($result)['co_level'] : '0', 1, 0, 'C');
    }
    $pdf->Ln();
}

foreach ($assignments as $assignment) {
    $pdf->Cell(10, 10, 'A' . $assignment['assignment_no'], 1, 0, 'C');
    foreach ($cos as $co) {
        $result = array_filter($co_assignment_results, fn($item) => $item['co_id'] == $co['id'] && $item['assignment_id'] == $assignment['id']);
        $pdf->Cell(20, 10, $result ? reset($result)['co_level'] : '0', 1, 0, 'C');
    }
    $pdf->Ln();
}

$additionalHeaders = ['CIA', 'SE', 'DA', 'IA', 'CA'];
foreach ($additionalHeaders as $header) {
    $pdf->Cell(10, 10, $header, 1, 0, 'C');
    foreach ($cos as $co) {
        $overallData = array_filter($co_overall_data, fn($item) => $item['co_id'] == $co['id']);
        $value = $overallData ? reset($overallData)[strtolower($header)] : '0';
        $pdf->Cell(20, 10, $value, 1, 0, 'C');
    }
    $pdf->Ln();
}

// Append question paper PDFs for each test and assignment
$pdf->suppressFooter = true;

foreach ($tests as $test) {
    if (!empty($test['question_paper_image'])) {
        $pdfFilePath = "../" . $test['question_paper_image'];

        // Create a temporary file path for the converted PDF
        $tempDir = sys_get_temp_dir();
        $convertedPdfPath = $tempDir . '/' . uniqid('converted_') . '.pdf';

        // Try to convert the PDF to a compatible format
        $conversionSuccess = convertPdfToCompatibleFormat($pdfFilePath, $convertedPdfPath);

        if ($conversionSuccess) {
            try {
                // Attempt to import the converted PDF using FPDI
                $pageCount = $pdf->setSourceFile($convertedPdfPath);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $pdf->AddPage();
                    $templateId = $pdf->importPage($i);
                    $pdf->useTemplate($templateId);
                }
                
                // Clean up the temporary converted file
                @unlink($convertedPdfPath);
            } catch (Exception $e) {
                // If an error occurs (e.g., unsupported compression), add a fallback page
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'I', 12);
                $pdf->Cell(0, 10, "Test {$test['test_no']} question paper could not be appended due to unsupported PDF format.", 0, 1, 'C');
                $pdf->Cell(0, 10, 'Please refer to the original file: ' . basename($pdfFilePath), 0, 1, 'C');
                
                // Clean up the temporary converted file
                @unlink($convertedPdfPath);
            }
        } else {
            // If conversion failed
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'I', 12);
            $pdf->Cell(0, 10, "Failed to convert PDF to a compatible format for Test {$test['test_no']}.", 0, 1, 'C');
            $pdf->Cell(0, 10, 'Please refer to the original file: ' . basename($pdfFilePath), 0, 1, 'C');
        }
    }
}

foreach ($assignments as $assignment) {
    if (!empty($assignment['question_paper_image'])) {
        $pdfFilePath = "../" . $assignment['question_paper_image'];

        // Create a temporary file path for the converted PDF
        $tempDir = sys_get_temp_dir();
        $convertedPdfPath = $tempDir . '/' . uniqid('converted_') . '.pdf';

        // Try to convert the PDF to a compatible format
        $conversionSuccess = convertPdfToCompatibleFormat($pdfFilePath, $convertedPdfPath);

        if ($conversionSuccess) {
            try {
                // Attempt to import the converted PDF using FPDI
                $pageCount = $pdf->setSourceFile($convertedPdfPath);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $pdf->AddPage();
                    $templateId = $pdf->importPage($i);
                    $pdf->useTemplate($templateId);
                }
                
                // Clean up the temporary converted file
                @unlink($convertedPdfPath);
            } catch (Exception $e) {
                // If an error occurs (e.g., unsupported compression), add a fallback page
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'I', 12);
                $pdf->Cell(0, 10, "Assignment {$assignment['assignment_no']} question paper could not be appended due to unsupported PDF format.", 0, 1, 'C');
                $pdf->Cell(0, 10, 'Please refer to the original file: ' . basename($pdfFilePath), 0, 1, 'C');
                
                // Clean up the temporary converted file
                @unlink($convertedPdfPath);
            }
        } else {
            // If conversion failed
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'I', 12);
            $pdf->Cell(0, 10, "Failed to convert PDF to a compatible format for Assignment {$assignment['assignment_no']}.", 0, 1, 'C');
            $pdf->Cell(0, 10, 'Please refer to the original file: ' . basename($pdfFilePath), 0, 1, 'C');
        }
    }
}

$pdf->suppressFooter = false;

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="complete_course_detail_report.pdf"');
$pdf->Output('D', 'complete_course_detail_report.pdf');
?>