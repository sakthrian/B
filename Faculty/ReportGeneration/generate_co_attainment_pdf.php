<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
        $this->Cell(0, 10, 'Course Outcome Attainment', 0, 1, 'C');

        $this->Ln(10);
    }

    function Footer() {
        date_default_timezone_set('Asia/Kolkata');
        $this->SetY(-30);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(0, 10, 'Faculty Signature: ________________________', 0, 1, 'R');
        
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        
        $this->SetY(-10);
        $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d h:i:s A'), 0, 0, 'R');
    }
}

header('Content-Type: application/json');
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!isset($data['fc_id'])) {
    die(json_encode(['error' => 'Missing parameters']));
}

$fc_id = $data['fc_id'];

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
$row = $result->fetch_assoc();
$faculty_name = $row['name'] ?? 'Unknown';

// Fetch course name
$sql = "SELECT name FROM course WHERE code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$course_name = $row['name'] ?? 'Unknown';

// Fetch course outcomes
$sql = "SELECT id, co_number FROM course_outcome WHERE course_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$cos = $result->fetch_all(MYSQLI_ASSOC);

// Fetch tests
$sql = "SELECT id, test_no FROM test WHERE fc_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$fc_id);
$stmt->execute();
$result = $stmt->get_result();
$tests = $result->fetch_all(MYSQLI_ASSOC);

// Fetch assignments
$sql = "SELECT id, assignment_no FROM assignment WHERE fc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$assignments = $result->fetch_all(MYSQLI_ASSOC);

// Fetch CO test results
$sql = "SELECT co_id, test_id, co_level FROM co_test_results WHERE test_id IN (SELECT id FROM test WHERE fc_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",  $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$co_test_results = $result->fetch_all(MYSQLI_ASSOC);

// Fetch CO assignment results
$sql = "SELECT co_id, assignment_id, co_level FROM co_assignment_results WHERE assignment_id IN (SELECT id FROM assignment WHERE fc_id=?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$co_assignment_results = $result->fetch_all(MYSQLI_ASSOC);

// Fetch CO overall data
$sql = "SELECT co_id, cia, se, da, ia, ca FROM co_overall WHERE fc_id IN (SELECT id FROM faculty_course WHERE fc_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",  $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$co_overall_data = $result->fetch_all(MYSQLI_ASSOC);

// Fetch actions data
$sql = "SELECT * FROM actions WHERE fc_id = ? order by category desc, category_id asc";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fc_id);
$stmt->execute();
$result = $stmt->get_result();
$actionsData = $result->fetch_all(MYSQLI_ASSOC);

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 10);

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
$pdf->Cell(10, 10, 'CO No', 1, 0, 'C');
foreach ($cos as $co) {
    $pdf->Cell(20, 10, 'CO' . $co['co_number'], 1, 0, 'C');
}
// Add action columns headers
$pdf->Cell(20, 10, 'Act. Req.', 1, 0, 'C');
$pdf->Cell(20, 10, 'Act. Req. Date', 1, 0, 'C');
$pdf->Cell(20, 10, 'Act. Taken', 1, 0, 'C');
$pdf->Cell(20, 10, 'Act. Taken Date', 1, 0, 'C');
$pdf->Ln();

foreach ($tests as $test) {
    $pdf->Cell(10, 10, 'T' . $test['test_no'], 1, 0, 'C');
    foreach ($cos as $co) {
        $result = array_filter($co_test_results, fn($item) => $item['co_id'] == $co['id'] && $item['test_id'] == $test['id']);
        $pdf->Cell(20, 10, $result ? reset($result)['co_level'] : '0', 1, 0, 'C');
    }
    
    // Find action data for this test
    $action = array_filter($actionsData, fn($item) => $item['category'] == 'test' && $item['category_id'] == $test['test_no']);
    $action = $action ? reset($action) : null;
    
    $pdf->Cell(20, 10, $action ? ($action['action_required'] ?: 'Nil') : 'Nil', 1, 0, 'C');
    $pdf->Cell(20, 10, $action ? ($action['action_required_date'] ?: 'Nil') : 'Nil', 1, 0, 'C');
    $pdf->Cell(20, 10, $action ? ($action['action_taken'] ?: 'Nil') : 'Nil', 1, 0, 'C');
    $pdf->Cell(20, 10, $action ? ($action['action_taken_date'] ?: 'Nil') : 'Nil', 1, 0, 'C');
    $pdf->Ln();
}

foreach ($assignments as $assignment) {
    $pdf->Cell(10, 10, 'A' . $assignment['assignment_no'], 1, 0, 'C');
    foreach ($cos as $co) {
        $result = array_filter($co_assignment_results, fn($item) => $item['co_id'] == $co['id'] && $item['assignment_id'] == $assignment['id']);
        $pdf->Cell(20, 10, $result ? reset($result)['co_level'] : '0', 1, 0, 'C');
    }
    
    // Find action data for this assignment
    $action = array_filter($actionsData, fn($item) => $item['category'] == 'assignment' && $item['category_id'] == $assignment['assignment_no']);
    $action = $action ? reset($action) : null;
    
    $pdf->Cell(20, 10, $action ? ($action['action_required'] ?: 'Nil') : 'Nil', 1, 0, 'C');
    $pdf->Cell(20, 10, $action ? ($action['action_required_date'] ?: 'Nil') : 'Nil', 1, 0, 'C');
    $pdf->Cell(20, 10, $action ? ($action['action_taken'] ?: 'Nil') : 'Nil', 1, 0, 'C');
    $pdf->Cell(20, 10, $action ? ($action['action_taken_date'] ?: 'Nil') : 'Nil', 1, 0, 'C');
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
    // Empty cells for action columns in these rows
    $pdf->Cell(20, 10, '', 1, 0, 'C');
    $pdf->Cell(20, 10, '', 1, 0, 'C');
    $pdf->Cell(20, 10, '', 1, 0, 'C');
    $pdf->Cell(20, 10, '', 1, 0, 'C');
    $pdf->Ln();
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="co_attainment_report.pdf"');

$pdf->Output('D', 'co_attainment_report.pdf');
?>