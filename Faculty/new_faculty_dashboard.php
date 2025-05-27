<?php
session_start();
include '../config.php';

if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.php");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];

// Fetch faculty name
$sql = "SELECT name FROM faculty WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$faculty_name = $row['name'];

// Fetch courses assigned to the faculty
$sql = "SELECT c.code, c.name, c.semester, fc.section, fc.id AS fc_id, c.type, fc.batch
        FROM faculty_course fc
        JOIN course c ON fc.course_id = c.code and fc.type = c.type
        WHERE fc.faculty_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$courses = $stmt->get_result();

$courses_data = [];
while ($row = $courses->fetch_assoc()) {
    $courses_data[] = $row;
}

// Fetch test details
$sql = "SELECT t.id, t.test_no, t.total_mark, t.test_date, t.fc_id, t.status
        FROM test t 
        JOIN faculty_course fc ON t.fc_id = fc.id
        WHERE fc.faculty_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$tests = $stmt->get_result();
$tests_data = [];
while ($row = $tests->fetch_assoc()) {
    $tests_data[$row['fc_id']][] = $row;
}

// Fetch assignment details
$sql = "SELECT a.id, a.assignment_no, a.total_mark, a.assignment_date, a.fc_id 
        FROM assignment a 
        JOIN faculty_course fc ON a.fc_id = fc.id
        WHERE fc.faculty_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$assignments = $stmt->get_result();
$assignments_data = [];
while ($row = $assignments->fetch_assoc()) {
    $assignments_data[$row['fc_id']][] = $row;
}

// Fetch question details for each test
$sql = "SELECT q.id, q.test_id, q.question_no, q.max_mark, q.target_mark, q.knowledge_level, co.co_number
        FROM question q 
        JOIN test t ON q.test_id = t.id
        JOIN faculty_course fc ON t.fc_id = fc.id
        JOIN question_co qc ON qc.question_id = q.id
        JOIN course_outcome co ON co.id = qc.co_id
        WHERE fc.faculty_id = ?;";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$questions = $stmt->get_result();
$questions_data = [];
while ($row = $questions->fetch_assoc()) {
    $questions_data[$row['test_id']][] = $row;
}

// Fetch assignment question details for each assignment
$sql = "SELECT q.id, q.assignment_id, q.question_no, q.max_mark, q.target_mark, q.knowledge_level, co.co_number
        FROM assignment_question q 
        JOIN assignment a ON q.assignment_id = a.id
        JOIN faculty_course fc ON a.fc_id = fc.id
        JOIN assignment_question_co qc ON qc.question_id = q.id
        JOIN course_outcome co ON co.id = qc.co_id
        WHERE fc.faculty_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$assignment_questions = $stmt->get_result();
$assignment_questions_data = [];
while ($row = $assignment_questions->fetch_assoc()) {
    $assignment_questions_data[$row['assignment_id']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../navbar.css">
    <link rel="stylesheet" href="new_faculty_dashboard.css">
    <link rel="stylesheet" href="../chatbot.css">
    <script src="../chatbot.js"></script>
</head>

<body>
    <?php include '../navbar.php'; ?>

    <!-- Faculty Dashboard -->
    <div class="faculty-dashboard">

        <!-- Side Bar -->
        <div class="dashboard-sidebar">
            <div class="faculty-profile">
                <div class="faculty-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="faculty-info">
                    <span class="faculty-name"><?php echo htmlspecialchars($faculty_name); ?></span>
                    <span class="faculty-role">Faculty</span>
                </div>
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li data-section="mapped-courses" class="active">
                        <i class="fas fa-book"></i>
                        <span>Dashboard Home</span>
                    </li>
                    <li data-section="test-upload">
                        <i class="fas fa-file-alt"></i>
                        <span>Configure Test</span>
                    </li>
                    <li data-section="assignment-upload">
                        <i class="fas fa-file-alt"></i>
                        <span>Configure Assignment</span>
                    </li>
                    <li data-section="student-marks">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Enter Marks</span>
                    </li>
                    <li data-section="view-progress">
                        <i class="fas fa-clipboard-list"></i>
                        <span>View Test Data</span>
                    </li>
                    <li data-section="co-attainment">
                        <i class="fas fa-file-alt"></i>
                        <span>CO Attainment</span>
                    </li>
                    <li data-section="report-generation">
                        <i class="fas fa-file-alt"></i>
                        <span>Report Generation</span>
                    </li>
                    <li id="logout-menu-item" class="sidebar-menu-item">
                        <a href="#" style="color: inherit;">
                            <i class="fas fa-sign-out-alt" style="margin-right: 10px;"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Logout Modal -->
        <div id="logout-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <h2>Confirm Logout</h2>
                <p>Are you sure you want to log out?</p>
                <div class="modal-buttons">
                    <button id="confirm-logout" class="btn btn-danger">Logout</button>
                    <button id="cancel-logout" class="btn btn-secondary">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Dashboard content -->
        <div class="dashboard-content">

            <!-- Mapped Courses -->
            <section id="mapped-courses" class="section active">
                <h1>My Courses</h1>
                <div class="courses-container">
                    <table id="courses-table">
                        <thead>
                            <tr>
                                <th>Course ID</th>
                                <th>Course Name</th>
                                <th>Semester</th>
                                <th>Batch</th>
                                <th>Class</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses_data as $row) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['code']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($row['batch']); ?></td>
                                    <td>
                                        <?php
                                        if ($row['type'] === "B.Tech") {
                                            echo "CSE-" . htmlspecialchars($row['section']);
                                        } else {
                                            echo htmlspecialchars($row['type']);
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Upload test -->
            <section id="test-upload" class="section">
                <h1>Configure Test</h1>
                <form id="test-upload-form" method="post" enctype="multipart/form-data">
                    <div class="test-cat-container">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="degree-select-test">Degree</label>
                                <select id="degree-select-test" name="degree" class="dark-select" required>
                                    <option value="">Select Degree</option>
                                    <option value="B.Tech">B.Tech</option>
                                    <option value="M.Tech DS">M.Tech DS</option>
                                    <option value="M.Tech IS">M.Tech IS</option>
                                    <option value="MCA">MCA</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="batch-select-test">Batch</label>
                                <select id="batch-select-test" name="batch" class="dark-select" required>
                                    <option value="">Select Batch</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="course-select">Course</label>
                                <select id="course-select" name="fc_id" class="dark-select" required>
                                    <option value="">Select Course</option>

                                </select>
                            </div>
                            <div class="form-group">
                                <label for="test-type">Test</label>
                                <select id="test-type" name="test_no" class="dark-select" required>
                                    <option value="">Select Test Number</option>
                                    <option value="1">Test 1</option>
                                    <option value="2">Test 2</option>
                                    <option value="3">Test 3</option>
                                    <option value="4">Test 4</option>
                                    <option value="5">Test 5</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="test-date">Test Date</label>
                                <input type="date" id="test-date" name="test_date" required>
                            </div>
                            <div class="form-group">
                                <label for="max-mark">Maximum Mark</label>
                                <input type="number" id="max-mark" name="total_mark" placeholder="Enter max mark"
                                    required>
                            </div>
                        </div>

                        <p class="file-size-limit">Please upload a file size lesser than 500KB.</p>
                        <!-- Question paper -->
                        <div class="question-paper-upload">
                            <input type="file" id="question-paper" name="question_paper" accept=".pdf"
                                onchange="validateFileSize(this)" style="display:none;">
                            <button class="btn upload-btn" id="choose-question-paper" type="button">Choose File</button>
                            <span class="file-name">No file chosen</span>
                            <span id="file-size-error" class="error-message" style="color: red; display: none;">File
                                size exceeds 100 KB.</span>
                        </div>
                        <!-- Question field -->
                        <div class="questions-container">
                            <div class="question-details" id="question-1">
                                <div class="question-header">
                                    <h3>Question 1</h3>
                                    <div class="question-buttons">
                                        <button class="add-question-btn">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-row marks-row">
                                    <div class="form-group">
                                        <label>Maximum Mark</label>
                                        <input type="number" class="max-mark" name="question_marks[]" step="any"
                                            placeholder="Enter max mark" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Targeted Mark</label>
                                        <input type="number" class="targeted-mark" name="target_marks[]" step="any"
                                            placeholder="Enter targeted mark" required>
                                    </div>
                                </div>
                                <div class="form-row levels-row">
                                    <div class="form-group">
                                        <label>Knowledge Level</label>
                                        <select class="knowledge-level dark-select" name="knowledge_levels[]" required>
                                            <option value="">Select Level</option>
                                            <option value="1">Remembering (1)</option>
                                            <option value="2">Understanding (2)</option>
                                            <option value="3">Applying (3)</option>
                                            <option value="4">Analyzing (4)</option>
                                            <option value="5">Evaluating (5)</option>
                                            <option value="6">Creating (6)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>CO Level</label>
                                        <select class="co-level dark-select" name="co_levels[]" required>
                                            <option value="">Select CO</option>
                                            <option value=1>CO1</option>
                                            <option value=2>CO2</option>
                                            <option value=3>CO3</option>
                                            <option value=4>CO4</option>
                                            <option value=5>CO5</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn upload-btn" type="submit" id="save-details">Save Details</button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Assignment upload -->
            <section id="assignment-upload" class="section">
                <h1>Configure Assignment</h1>
                <form id="assignment-upload-form" method="post" enctype="multipart/form-data">
                    <div class="test-cat-container">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="degree-select-assignment">Degree</label>
                                <select id="degree-select-assignment" name="degree" class="dark-select" required>
                                    <option value="">Select Degree</option>
                                    <option value="B.Tech">B.Tech</option>
                                    <option value="M.Tech DS">M.Tech DS</option>
                                    <option value="M.Tech IS">M.Tech IS</option>
                                    <option value="MCA">MCA</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="batch-select-assignment">Batch</label>
                                <select id="batch-select-assignment" name="batch" class="dark-select" required>
                                    <option value="">Select Batch</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="assignment-course-select">Course</label>
                                <select id="assignment-course-select" name="fc_id" class="dark-select" required>
                                    <option value="">Select Course</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="assignment-type">Assignment</label>
                                <select id="assignment-type" name="assignment_no" class="dark-select" required>
                                    <option value="">Select Assignment Number</option>
                                    <option value="1">Assignment 1</option>
                                    <option value="2">Assignment 2</option>
                                    <option value="3">Assignment 3</option>
                                    <option value="4">Assignment 4</option>
                                    <option value="5">Assignment 5</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="assignment-date">Assignment Date</label>
                                <input type="date" id="assignment-date" name="assignment_date" required>
                            </div>
                            <div class="form-group">
                                <label for="assignment-max-mark">Maximum Mark</label>
                                <input type="number" id="assignment-max-mark" name="total_mark"
                                    placeholder="Enter max mark" required>
                            </div>
                        </div>

                        <p class="file-size-limit">Please upload a file size lesser than 500KB.</p>
                        <div class="question-paper-upload">
                            <input type="file" id="assignment-file" name="assignment_file" accept=".pdf"
                                onchange="validateFileSize(this)" style="display:none;">
                            <button class="btn upload-btn" id="choose-assignment-file" type="button">Choose
                                File</button>
                            <span class="file-name">No file chosen</span>
                            <span id="file-size-error" class="error-message" style="color: red; display: none;">File
                                size exceeds 100 KB.</span>
                        </div>
                        <div class="questions-container">
                            <div class="question-details" id="question-1">
                                <div class="question-header">
                                    <h3>Question 1</h3>
                                    <div class="question-buttons">
                                        <button class="add-question-btn">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-row marks-row">
                                    <div class="form-group">
                                        <label>Maximum Mark</label>
                                        <input type="number" class="max-mark" name="question_marks[]" step="any"
                                            placeholder="Enter max mark" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Targeted Mark</label>
                                        <input type="number" class="targeted-mark" name="target_marks[]" step="any"
                                            placeholder="Enter targeted mark" required>
                                    </div>
                                </div>
                                <div class="form-row levels-row">
                                    <div class="form-group">
                                        <label>Knowledge Level</label>
                                        <select class="knowledge-level dark-select" name="knowledge_levels[]" required>
                                            <option value="">Select Level</option>
                                            <option value="1">Remembering (1)</option>
                                            <option value="2">Understanding (2)</option>
                                            <option value="3">Applying (3)</option>
                                            <option value="4">Analyzing (4)</option>
                                            <option value="5">Evaluating (5)</option>
                                            <option value="6">Creating (6)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>CO Level</label>
                                        <select class="co-level dark-select" name="co_levels[]" required>
                                            <option value="">Select CO</option>
                                            <option value="1">CO1</option>
                                            <option value="2">CO2</option>
                                            <option value="3">CO3</option>
                                            <option value="4">CO4</option>
                                            <option value="5">CO5</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn upload-btn" type="submit" id="save-assignment-details">Save
                                Details</button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Mark enter -->
            <section id="student-marks" class="section">
                <h1>Enter Students Mark</h1>
                <div class="marks-entry-container">
                    <div class="marks-header">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="degree-select-mark">Degree</label>
                                <select id="degree-select-mark" name="degree" class="dark-select" required>
                                    <option value="">Select Degree</option>
                                    <option value="B.Tech">B.Tech</option>
                                    <option value="M.Tech DS">M.Tech DS</option>
                                    <option value="M.Tech IS">M.Tech IS</option>
                                    <option value="MCA">MCA</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="batch-select-mark">Batch</label>
                                <select id="batch-select-mark" name="batch" class="dark-select" required>
                                    <option value="">Select Batch</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="marks-course-select">Select Course</label>
                                <select id="marks-course-select" class="dark-select" required>
                                    <option value="">Select Course</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="test-select">Select Test/Assignment</label>
                                <select id="test-select" class="dark-select" required>
                                    <option value="">Select Test/Assignment</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="instruction-card">
                        <h3>Instructions</h3>
                        <ul>
                            <li>Enter -1 when a student is absent.</li>
                            <li>
                                It is recommended to freeze the mark once it has been correctly entered to prevent data
                                loss.
                            </li>
                        </ul>
                    </div>

                    <div class="student-marks-table-container">
                        <table id="student-marks-table">
                            <thead>
                                <tr>
                                    <th>SI/No</th>
                                    <th>Reg No</th>
                                    <th>Student Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Student details along with questions -->
                            </tbody>
                        </table>
                    </div>
                    <div class="marks-entry-actions">
                        <button id="save-student-marks" class="btn upload-btn">Save Marks</button>
                        <!-- <button id="freeze-marks" class="btn upload-btn" disabled>Freeze</button> -->
                        <button id="freeze-marks" class="btn upload-btn">Freeze</button>
                        <div id="frozen-message" class="frozen-message" style="display: none;">
                            This test and mark is frozen. Ask admin to unfreeze it if you want to edit.
                        </div>
                    </div>
                </div>
            </section>

            <!-- View Progress Section -->
            <section id="view-progress" class="section">
                <h1>View Progress</h1>
                <div class="view-progress-container">
                    <div class="view-progress-header">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="degree-select-view">Degree</label>
                                <select id="degree-select-view" name="degree" class="dark-select" required>
                                    <option value="">Select Degree</option>
                                    <option value="B.Tech">B.Tech</option>
                                    <option value="M.Tech DS">M.Tech DS</option>
                                    <option value="M.Tech IS">M.Tech IS</option>
                                    <option value="MCA">MCA</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="batch-select-view">Batch</label>
                                <select id="batch-select-view" name="batch" class="dark-select" required>
                                    <option value="">Select Batch</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="view-course-select">Select Course</label>
                                <select id="view-course-select" class="dark-select" required>
                                    <option value="">Select Course</option>
                                    <!--  -->
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="view-progress-content">
                        <!-- Dynamic content will be added here -->
                    </div>
                </div>
            </section>

            <!-- CO Attainment -->
            <section id="co-attainment" class="section">
                <h1>Course Outcome Attainment</h1>
                <div class="co-attainment-container">
                    <div class="co-attainment-header">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="degree-select-co">Degree</label>
                                <select id="degree-select-co" name="degree" class="dark-select" required>
                                    <option value="">Select Degree</option>
                                    <option value="B.Tech">B.Tech</option>
                                    <option value="M.Tech DS">M.Tech DS</option>
                                    <option value="M.Tech IS">M.Tech IS</option>
                                    <option value="MCA">MCA</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="batch-select-co">Batch</label>
                                <select id="batch-select-co" name="batch" class="dark-select" required>
                                    <option value="">Select Batch</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="co-course-select">Select Course</label>
                                <select id="co-course-select" class="dark-select" required>
                                    <option value="">Select Course</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="co-attainment-table-container">
                        <table id="co-attainment-table">
                            <thead>
                                <tr>
                                    <!-- Dynamic headers will be added here -->
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic rows will be added here -->
                            </tbody>
                        </table>
                    </div>
                    <div class="co-attainment-actions">
                        <button id="save-co-attainment" class="btn upload-btn">Save Attainment</button>
                        <button id="send-response" class="btn upload-btn">Send Response</button>
                        <button id="export-co-attainment" class="btn upload-btn">Export to PDF</button><br><br>
                        <p>Once you save the correct value, you may proceed to freeze it. Please note that once frozen, this action cannot be undone.</p>
                        <button id="freeze-co-attainment" class="btn upload-btn">Freeze</button>
                    </div>
                </div>
            </section>

            <!-- Report Generation -->
            <section id="report-generation" class="section">
                <h1>Student Marks Entry</h1>
                <div class="marks-entry-container">
                    <div class="marks-header">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="degree-select-r">Degree</label>
                                <select id="degree-select-r" name="degree" class="dark-select" required>
                                    <option value="">Select Degree</option>
                                    <option value="B.Tech">B.Tech</option>
                                    <option value="M.Tech DS">M.Tech DS</option>
                                    <option value="M.Tech IS">M.Tech IS</option>
                                    <option value="MCA">MCA</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="batch-select-r">Batch</label>
                                <select id="batch-select-r" name="batch" class="dark-select" required>
                                    <option value="">Select Batch</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="report-course-select">Select Course</label>
                                <select id="report-course-select" class="dark-select" required>
                                    <option value="">Select Course</option>

                                </select>
                            </div>

                            <div class="form-group">
                                <label for="report-test-select">Select Test/Assignment</label>
                                <select id="report-test-select" class="dark-select" required>
                                    <option value="">Select Test/Assignment</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="report-actions">
                        <center>
                            <button id="generate-marklist-btn" class="btn upload-btn">Generate Mark List</button>
                            <br><br>
                            <label for="overall-marklist-btn">Download Consolidated Mark List for this Course</label>
                            <br><br>
                            <button id="overall-marklist-btn" class="btn upload-btn">
                                Download Consolidated Mark addEventListener
                            </button>
                            <br><br>
                            <button id="complete-detail-btn" class="btn upload-btn">
                                Complete Detail
                            </button>
                        </center>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        function validateFileSize(input) {
            if (input.files.length > 0) {
                let file = input.files[0];
                let maxSize = 500 * 1024;

                if (file.size > maxSize) {
                    alert("File size exceeds 500KB. Please upload a smaller file.");
                    input.value = "";
                }
            }
        }
        document.getElementById('export-co-attainment').addEventListener('click', () => {
            const courseSelect = document.getElementById('co-course-select');
            const fcId = courseSelect.value;

            const data = {
                fc_id: fcId
            };

            fetch(' ./ReportGeneration/generate_co_attainment_pdf.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            }).then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.blob();
            }).then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'co_attainment_report.pdf';
                document.body.appendChild(a);
                a.click();
                a.remove();
            }).catch(error => {
                console.error('Error:', error);
                alert('Failed to generate PDF: ' + error.message);
            });
        });

        //here   ./ReportGeneration/generate_co_attainment_pdf.php
        document.addEventListener('DOMContentLoaded', () => {
            const marksCourseSelect = document.getElementById('marks-course-select');
            const reportCourseSelect = document.getElementById('report-course-select');
            const reportTestSelect = document.getElementById('report-test-select');

            const generateMarklistBtn = document.getElementById('generate-marklist-btn');
            const overallMarklistBtn = document.getElementById('overall-marklist-btn');

            // Logout Modal Functionality
            const logoutMenuItem = document.getElementById('logout-menu-item');
            const logoutModal = document.getElementById('logout-modal');
            const confirmLogoutBtn = document.getElementById('confirm-logout');
            const cancelLogoutBtn = document.getElementById('cancel-logout');

            if (logoutMenuItem) {
                logoutMenuItem.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (logoutModal) {
                        logoutModal.style.display = 'flex';
                    }
                });
            }

            if (confirmLogoutBtn) {
                confirmLogoutBtn.addEventListener('click', () => {
                    window.location.href = '../logout.php';
                });
            }

            if (cancelLogoutBtn) {
                cancelLogoutBtn.addEventListener('click', () => {
                    if (logoutModal) {
                        logoutModal.style.display = 'none';
                    }
                });
            }

            if (logoutModal) {
                logoutModal.addEventListener('click', (event) => {
                    if (event.target === logoutModal) {
                        logoutModal.style.display = 'none';
                    }
                });
            }

            marksCourseSelect.addEventListener('change', (e) => {
                const id = "test-select";
                showTests(e, id);
            });
            reportCourseSelect.addEventListener('change', (e) => {
                const id = "report-test-select";
                showTests(e, id);
            });

            function showTests(e, id) {
                const courseId = e.target.value.split(" ")[0];
                console.log(courseId);
                const testsData = <?php echo json_encode($tests_data); ?>;
                console.log(testsData);
                const assignmentsData = <?php echo json_encode($assignments_data); ?>;


                // Clear existing options
                const testSelect = document.getElementById(id);
                testSelect.innerHTML = '<option value="">Select Test/Assignment</option>';

                // Add test options
                if (testsData[courseId]) {
                    testsData[courseId].forEach(test => {
                        const option = document.createElement('option');
                        option.value = `${test.id} ${test.total_mark}`;
                        option.textContent = `Test ${test.test_no}`;
                        testSelect.appendChild(option);
                    });
                }

                // Add assignment options
                if (assignmentsData[courseId]) {
                    assignmentsData[courseId].forEach(assignment => {
                        const option = document.createElement('option');
                        option.value = `${assignment.id} ${assignment.total_mark}`;
                        option.textContent = `Assignment ${assignment.assignment_no}`;
                        testSelect.appendChild(option);
                    });
                }
            }

            generateMarklistBtn.addEventListener('click', () => {
                const fcId = reportCourseSelect.value;
                const testId = reportTestSelect.value.split(' ')[0];
                const testType = reportTestSelect.options[reportTestSelect.selectedIndex].textContent.toLowerCase().split(' ')[0];

                console.log(testType);

                if (!fcId || !testId) {
                    alert('Please select a course and a test/assignment.');
                    return;
                }

                const reportUrl = `./ReportGeneration/generate_report.php?fc_id=${fcId}&test_id=${testId}&test_type=${testType}`;
                window.open(reportUrl, '_blank');
            });

            overallMarklistBtn.addEventListener('click', () => {
                const fcId = reportCourseSelect.value;
                const testType = reportTestSelect.options[reportTestSelect.selectedIndex].textContent.toLowerCase().split(' ')[0];

                if (!fcId) {
                    alert('Please select a course.');
                    return;
                }

                // window.location.href = `./ReportGeneration/overall_report.php?faculty_id=${facultyId}&course_id=${courseId}`;
                const reportUrl = `./ReportGeneration/overall_report.php?fc_id=${fcId}&test_type=${testType}`;
                window.open(reportUrl, '_blank');
            });

            const viewCourseSelect = document.getElementById('view-course-select');
            const viewProgressContent = document.getElementById('view-progress-content');

            viewCourseSelect.addEventListener('change', async (e) => {
                const fcId = e.target.value;
                const testsData = <?php echo json_encode($tests_data); ?>;
                const assignmentsData = <?php echo json_encode($assignments_data); ?>;
                const questionsData = <?php echo json_encode($questions_data); ?>;
                const assignmentQuestionsData = <?php echo json_encode($assignment_questions_data); ?>;

                viewProgressContent.innerHTML = '';

                let testCards = [];
                let assignmentCards = [];

                // Fetch test marks
                if (testsData[fcId]) {
                    const testFetchPromises = testsData[fcId].map(test =>
                        fetch(`fetch_test_marks.php?test_id=${test.id}`)
                            .then(response => response.json())
                            .then(data => {
                                const marksAssigned = data.marks.length > 0;
                                const [year, month, day] = test.test_date.split('-');
                                const reversedTestDate = `${day}-${month}-${year}`;

                                // Check if questionsData[test.id] is defined
                                const questions = questionsData[test.id] || [];
                                const testCard = `
                        <div class="test-card">
                            <h2>Test ${test.test_no}</h2>
                            <p>Total Mark: ${test.total_mark}</p>
                            <p>Test Date: ${reversedTestDate}</p>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Question No</th>
                                        <th>Max Mark</th>
                                        <th>Target Mark</th>
                                        <th>CO level</th>
                                        <th>Knowledge Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${questions.map(q => `
                                        <tr>
                                            <td>${q.question_no}</td>
                                            <td>${q.max_mark}</td>
                                            <td>${q.target_mark}</td>
                                            <td>${q.co_number}</td>
                                            <td>${q.knowledge_level}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                            <div class="action-buttons">
                                ${marksAssigned ? `<button class="btn edit-btn" data-id="${test.id}" data-type="test" data-fc-id="${fcId}" data-total="${test.total_mark}">Edit Mark</button>` : `
                                    <button class="btn edit-btn" data-id="${test.id}" data-type="test" data-fc-id="${fcId}" data-total="${test.total_mark}">Edit Test</button>
                                    <button class="btn edit-btn" data-id="${test.id}" data-type="test" data-fc-id="${fcId}" data-total="${test.total_mark}">Edit Mark</button>
                                    <button class="btn delete-btn" data-id="${test.id}" data-type="test" data-fc-id="${fcId}">Delete Test</button>
                                `}
                            </div>
                        </div>`;
                                testCards.push({ testNo: test.test_no, html: testCard });
                            })
                    );
                    await Promise.all(testFetchPromises);
                }

                // Fetch assignment marks
                if (assignmentsData[fcId]) {
                    const assignmentFetchPromises = assignmentsData[fcId].map(assignment =>
                        fetch(`fetch_assignment_marks.php?test_id=${assignment.id}`)
                            .then(response => response.json())
                            .then(data => {
                                const marksAssigned = data.marks.length > 0;
                                const [year, month, day] = assignment.assignment_date.split('-');
                                const reversedAssignmentDate = `${day}-${month}-${year}`;

                                // Check if assignmentQuestionsData[assignment.id] is defined
                                const questions = assignmentQuestionsData[assignment.id] || [];
                                const assignmentCard = `
                        <div class="assignment-card">
                            <h2>Assignment ${assignment.assignment_no}</h2>
                            <p>Total Mark: ${assignment.total_mark}</p>
                            <p>Assignment Date: ${reversedAssignmentDate}</p>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Question No</th>
                                        <th>Max Mark</th>
                                        <th>Target Mark</th>
                                        <th>CO level</th>
                                        <th>Knowledge Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${questions.map(q => `
                                        <tr>
                                            <td>${q.question_no}</td>
                                            <td>${q.max_mark}</td>
                                            <td>${q.target_mark}</td>
                                            <td>${q.co_number}</td>
                                            <td>${q.knowledge_level}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                            <div class="action-buttons">
                                ${marksAssigned ? `<button class="btn edit-btn" data-id="${assignment.id}" data-type="assignment" data-fc-id="${fcId}" data-total="${assignment.total_mark}">Edit Mark</button>` : `
                                    <button class="btn edit-btn" data-id="${assignment.id}" data-type="assignment" data-fc-id="${fcId}" data-total="${assignment.total_mark}">Edit Assignment</button>
                                    <button class="btn edit-btn" data-id="${assignment.id}" data-type="assignment" data-fc-id="${fcId}" data-total="${assignment.total_mark}">Edit Mark</button>
                                    <button class="btn delete-btn" data-id="${assignment.id}" data-type="assignment" data-fc-id="${fcId}">Delete Assignment</button>
                                `}
                            </div>
                        </div>`;
                                assignmentCards.push({ assignmentNo: assignment.assignment_no, html: assignmentCard });
                            })
                    );
                    await Promise.all(assignmentFetchPromises); // Wait for all assignment fetches to complete
                }

                // Sort test cards by test number
                testCards.sort((a, b) => a.testNo - b.testNo);

                // Sort assignment cards by assignment number
                assignmentCards.sort((a, b) => a.assignmentNo - b.assignmentNo);

                // Append test cards first, then assignment cards
                testCards.forEach(card => viewProgressContent.innerHTML += card.html);
                assignmentCards.forEach(card => viewProgressContent.innerHTML += card.html);
            });
        });

        // Function to handle degree and batch selection
        function handleDegreeBatchSelection(degreeId, batchId, courseId, facultyId) {
            const degreeSelect = document.getElementById(degreeId);
            const batchSelect = document.getElementById(batchId);
            const courseSelect = document.getElementById(courseId);

            if (degreeSelect && batchSelect && courseSelect) {
                degreeSelect.addEventListener("change", function () {
                    const degree = this.value;
                    batchSelect.innerHTML = '<option value="">Select Batch</option>';
                    courseSelect.innerHTML = '<option value="">Select Course</option>';

                    if (degree === "") return;

                    // Fetch batches for the selected degree
                    fetchBatches(degree, facultyId, batchId);
                });

                batchSelect.addEventListener("change", function () {
                    const batch = this.value;
                    const degree = degreeSelect.value;
                    courseSelect.innerHTML = '<option value="">Select Course</option>';

                    if (batch === "" || degree === "") return;

                    // Fetch courses for the selected degree and batch
                    fetchCourses(degree, facultyId, batch, courseId);
                });
            }
        }

        // Function to fetch batches
        function fetchBatches(degree, facultyId, batchId) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "get_batch.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById(batchId).innerHTML = xhr.responseText;
                }
            };

            xhr.send("degree=" + encodeURIComponent(degree) + "&faculty=" + encodeURIComponent(facultyId));
        }

        // Function to fetch courses
        function fetchCourses(degree, facultyId, batch, courseId) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "get_courses.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById(courseId).innerHTML = xhr.responseText;
                }
            };

            xhr.send("degree=" + encodeURIComponent(degree) + "&faculty=" + encodeURIComponent(facultyId) + "&batch=" + encodeURIComponent(batch));
        }
        // Initialize degree and batch selection for each section
        document.addEventListener("DOMContentLoaded", function () {
            const facultyId = <?php echo $faculty_id; ?>;

            handleDegreeBatchSelection("degree-select-test", "batch-select-test", "course-select", facultyId);
            handleDegreeBatchSelection("degree-select-assignment", "batch-select-assignment", "assignment-course-select", facultyId);
            handleDegreeBatchSelection("degree-select-view", "batch-select-view", "view-course-select", facultyId);
            handleDegreeBatchSelection("degree-select-co", "batch-select-co", "co-course-select", facultyId);
            handleDegreeBatchSelection("degree-select-r", "batch-select-r", "report-course-select", facultyId);
            handleDegreeBatchSelection("degree-select-mark", "batch-select-mark", "marks-course-select", facultyId);
            
        });
    </script>

    <script src="./CoCalculation/co_calculation.js"></script>
    <script src="student_marks.js"></script>
    <script src="../script.js"></script>
    <script src="new_faculty_dashboard.js"></script>
</body>

</html>