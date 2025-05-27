<?php
session_start();
include '../config.php';

if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.php");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];
$faculty_name = $_SESSION['faculty_name'];

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
    <link rel="stylesheet" href="hod_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../chatbot.css">
    <script src="../chatbot.js"></script>
</head>

<body>
    <?php include '../navbar.php'; ?>

    <!-- Hod Dashboard -->
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
                    <li data-section="co-attainment" class="active">
                        <i class="fas fa-file-alt"></i>
                        <span>CO Analysis</span>
                    </li>
                    <li data-section="report-generation">
                        <i class="fas fa-file-alt"></i>
                        <span>Report generation</span>
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

            <!-- CO Attainment -->
            <section id="co-attainment" class="section active">
                <h1>Course Outcome Analysis</h1>
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
                                <label for="sem-select-co">Semester</label>
                                <select id="sem-select-co" name="semester" class="dark-select" required>
                                    <option value="">Select Semester</option>
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
                        <br>
                        <div id="faculty-name-display-co" style="margin-bottom: 30px;">
                            <!-- Faculty Name Will come here -->
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
                    <div class="co-attainment-actions" style="display:none;">
                        <button id="send-response" class="btn upload-btn">Send Response</button>
                        <button id="export-co-attainment" class="btn upload-btn">Export to PDF</button><br><br>
                    </div>
                    <!-- Add this div for test/assignment cards -->
                    <div id="test-assignment-cards" class="cards-container"></div>
                </div>
            </section>

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
                                <label for="sem-select-r">Semester</label>
                                <select id="sem-select-r" name="semester" class="dark-select" required>
                                    <option value="">Select Semester</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="report-course-select">Select Course</label>
                                <select id="report-course-select" class="dark-select" required>
                                    <option value="">Select Course</option>

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="report-actions">
                        <center>
                            <label for="overall-marklist-btn">Download Consolidated Mark List </label>
                            <br><br>
                            <button id="overall-marklist-btn" class="btn upload-btn">
                                Download Consolidated Mark List
                            </button>
                            <br><br>
                            <button id="complete-detail-btn" class="btn upload-btn">
                                Complete Report
                            </button>
                        </center>
                    </div>
                    <div id="test-assignment-cards-report" class="cards-container"></div>
                </div>
            </section>

        </div>
    </div>

    <script>
        document.getElementById('export-co-attainment').addEventListener('click', () => {
            const courseSelect = document.getElementById('co-course-select');
            const fcId = courseSelect.value;

            const data = {
                fc_id: fcId
            };

            fetch('../Faculty/ReportGeneration/generate_co_attainment_pdf.php', {
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

        // Function to handle degree and batch selection
        function handleDegreeBatchSelection(degreeId, batchId, semId, courseId, facultyId) {
            const degreeSelect = document.getElementById(degreeId);
            const batchSelect = document.getElementById(batchId);
            const semSelect = document.getElementById(semId);
            const courseSelect = document.getElementById(courseId);

            if (degreeSelect && batchSelect && semSelect && courseSelect) {
                degreeSelect.addEventListener("change", function () {
                    const degree = this.value;
                    batchSelect.innerHTML = '<option value="">Select Batch</option>';
                    semSelect.innerHTML = '<option value="">Select Semester</option>';
                    courseSelect.innerHTML = '<option value="">Select Course</option>';

                    if (degree === "") return;

                    // Populate semesters based on degree
                    populateSemesters(degree, semId);

                    // Fetch batches for the selected degree
                    fetchBatches(degree, facultyId, batchId);
                });

                batchSelect.addEventListener("change", function () {
                    const batch = this.value;
                    const degree = degreeSelect.value;
                    semSelect.innerHTML = '<option value="">Select Semester</option>';
                    courseSelect.innerHTML = '<option value="">Select Course</option>';

                    if (batch === "" || degree === "") return;

                    // Populate semesters again (in case degree was changed without page refresh)
                    populateSemesters(degree,semId);
                });

                semSelect.addEventListener("change", function () {
                    const semester = this.value;
                    const degree = degreeSelect.value;
                    courseSelect.innerHTML = '<option value="">Select Course</option>';

                    if (semester === "" || degree === "") return;

                    // Fetch courses for the selected degree and semester
                    fetchCourses(degree, facultyId, semester, courseId);
                });
            }
        }

        function populateSemesters(degree, id) {
            // Add proper parameter checks
            if (!id) {
                console.error("No semester select ID provided");
                return;
            }

            const semSelect = document.getElementById(id);
            if (!semSelect) {
                console.error(`Semester select element with ID '${id}' not found`);
                return;
            }

            semSelect.innerHTML = '<option value="">Select Semester</option>';

            if (!degree) {
                console.warn("No degree selected");
                return;
            }

            let maxSemester = 4; // Default for non-B.Tech (M.Tech, etc.)
            if (degree === "B.Tech") {
                maxSemester = 8;
            }

            for (let i = 1; i <= maxSemester; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `Semester ${i}`;
                semSelect.appendChild(option);
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
        function fetchCourses(degree, facultyId, semester, courseId) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "get_courses_by_semester.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const courseSelect = document.getElementById(courseId);
                    if (courseSelect) {  // Add null check
                        courseSelect.innerHTML = xhr.responseText;

                        courseSelect.addEventListener("change", function () {
                            const selectedOption = courseSelect.options[courseSelect.selectedIndex];
                            const facultyName = selectedOption.getAttribute("data-faculty-name");

                            if (courseId === "co-course-select") {
                                const coAttainmentActions = document.querySelectorAll(".co-attainment-actions");
                                document.getElementById("faculty-name-display-co").textContent = "Faculty: " + facultyName;
                                coAttainmentActions.forEach((action) => {
                                    action.style.display = "block";
                                });
                            }
                            else if (courseId === "view-course-select") {
                                document.getElementById("faculty-name-display-view").textContent = "Faculty: " + facultyName;
                            }
                        });
                    }
                }
            };

            xhr.send("degree=" + encodeURIComponent(degree) +
                "&semester=" + encodeURIComponent(semester) +
                "&faculty=" + encodeURIComponent(facultyId));
        }

        // Initialize degree and batch selection for each section
        document.addEventListener("DOMContentLoaded", function () {
            const facultyId = <?php echo $faculty_id; ?>;
            handleDegreeBatchSelection("degree-select-co", "batch-select-co", "sem-select-co", "co-course-select", facultyId);
            handleDegreeBatchSelection("degree-select-r", "batch-select-r", "sem-select-r", "report-course-select", facultyId);

            // Add degree change listener to populate semesters initially if degree is pre-selected
            const degreeSelectCo = document.getElementById("degree-select-co");
            if (degreeSelectCo && degreeSelectCo.value) {
                populateSemesters(degreeSelectCo.value, "sem-select-co");
            }

            const degreeSelectR = document.getElementById("degree-select-r");
            if (degreeSelectR && degreeSelectR.value) {
                populateSemesters(degreeSelectR.value, "sem-select-r");
            }
        });

    </script>

    <script src="co_calculation.js"></script>
    <script src="hod_dashboard.js"></script>
</body>

</html>