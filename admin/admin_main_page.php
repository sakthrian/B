<?php
session_start();
include '../config.php';

if (!isset($_SESSION['faculty_id'])) {
    header("Location: login.php");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];

// Fetch faculty details
$sql = "SELECT name FROM faculty WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$faculty_name = $row['name'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="../navbar.css">
    <script src="admin_main_page_js.js"></script>
    <link rel="stylesheet" href="../chatbot.css">
    <script src="../chatbot.js"></script>

    <style>
       
        .admin-dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .dashboard-sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            overflow-y: auto;
            z-index: 1000;
            transition: width 0.3s ease;
        }

        /* Main Content Styling */
        .dashboard-content {
            margin-left: 250px;
            flex-grow: 1;
            padding: 20px;
            background-color: #f4f6f9;
            width: calc(100% - 250px);
        }

        /* Sidebar Menu */
        .sidebar-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            padding: 15px 20px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .sidebar-menu li:hover {
            background-color: #34495e;
        }

        .sidebar-menu li.active {
            background-color: #1abc9c;
        }

        /* Faculty Profile in Sidebar */
        .faculty-profile {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #34495e;
        }

        .faculty-icon i {
            font-size: 50px;
        }

        .faculty-info {
            margin-top: 10px;
        }

        .faculty-name {
            font-size: 18px;
            font-weight: 600;
        }

        .faculty-role {
            font-size: 14px;
            color: #bdc3c7;
        }

        /* Section Styling */
        .section {
            display: none;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section.active {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-sidebar {
                width: 200px;
            }

            .dashboard-content {
                margin-left: 200px;
                width: calc(100% - 200px);
            }
        }

        @media (max-width: 576px) {
            .dashboard-sidebar {
                width: 100%;
                height: auto;
                position: static;
            }

            .dashboard-content {
                margin-left: 0;
                width: 100%;
            }
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
        }

        .modal-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* Button Styling */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #1abc9c;
            color: white;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        /* Form Styling */
        .form-row, .filter-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .form-group, .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label, .filter-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group select, .form-group input, .filter-group select, .filter-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        /* Upload Container */
        .upload-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .upload-box {
            flex: 1;
            text-align: center;
            padding: 20px;
            border: 2px dashed #ccc;
            border-radius: 8px;
        }

        .upload-instructions ul {
            list-style: disc;
            padding-left: 20px;
        }

        .upload-preview {
            display: none;
        }

        .upload-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* Alert Styling */
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <?php include '../navbar.php'; ?>

    <!-- Admin Dashboard -->
    <div class="admin-dashboard">
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
                    <li data-section="student-upload" class="active">
                        <i class="fas fa-upload"></i>
                        <span>Student Upload</span>
                    </li>
                    <li data-section="faculty-mapping">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Faculty Mapping</span>
                    </li>
                    <li data-section="professional-elective">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Professional Elective Faculty Mapping</span>
                    </li>
                    <li data-section="current-mappings">
                        <i class="fas fa-table"></i>
                        <span>Current Mappings</span>
                    </li>
                    <li data-section="professional-elective-mappings">
                        <i class="fas fa-table"></i>
                        <span>Professional Elective Current Mapping</span>
                    </li>
                    <li data-section="co-attainment">
                        <i class="fas fa-file-alt"></i>
                        <span>CO Analysis</span>
                    </li>
                    <li data-section="report-generation">
                        <i class="fas fa-file-alt"></i>
                        <span>Report Generation</span>
                    </li>
                    <li data-section="curriculum-section">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Curriculum Management</span>
                    </li>
                    <li data-section="curriculum-mapping-section">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Curriculum Mapping</span>
                    </li>
                    <li data-section="staff-details">
                        <i class="fas fa-users"></i>
                        <span>Faculty Details</span>
                    </li>
                    <li data-section="student-details">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Student Details</span>
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

        <div class="dashboard-content">
            <!-- Student Upload -->
            <section id="student-upload" class="section active">
                <h1>Student Upload</h1>
                <div class="upload-container">
                    <div class="upload-box">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <input type="file" id="student-file-upload" style="display:none" accept=".csv">
                        <button class="upload-btn">Upload CSV</button>
                    </div>
                    <div class="upload-instructions">
                        <h3>CSV Upload Instructions</h3>
                        <ul>
                            <li>File must be in CSV format</li>
                            <li>Columns: Student Reg No, Name, Year, Semester, Section, Batch</li>
                            <li>Maximum file size: 5MB</li>
                            <li>Ensure data is clean and formatted correctly</li>
                        </ul>
                    </div>
                </div>
                <div class="upload-preview">
                    <table id="upload-preview-table">
                        <thead>
                            <tr>
                                <th>Register Number</th>
                                <th>Name</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Section</th>
                                <th>Batch</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <div class="upload-actions">
                        <button class="cancel-upload">
                            <i class="fas fa-times"></i>Cancel
                        </button>
                        <button class="confirm-upload">
                            <i class="fas fa-check"></i>Confirm Upload
                        </button>
                    </div>
                </div>
            </section>

          <!-- Faculty Mapping Section -->
<section id="faculty-mapping" class="section">
    <div class="mapping-container full-width">
        <div class="mapping-preview">
            <h3>Faculty Course Mapping</h3>
            <?php
            // Display success/error messages
            if (isset($_SESSION['mapping_success'])) {
                echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['mapping_success']) . "</div>";
                unset($_SESSION['mapping_success']);
            }
            if (isset($_SESSION['mapping_error'])) {
                echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['mapping_error']) . "</div>";
                unset($_SESSION['mapping_error']);
            }

            // Helper function for semester suffixes
            function getSemesterSuffix($sem)
            {
                switch ($sem) {
                    case 1:
                        return '1st';
                    case 2:
                        return '2nd';
                    case 3:
                        return '3rd';
                    default:
                        return $sem . 'th';
                }
            }

            // Debug function - only for development
            function debug_to_console($data)
            {
                echo "<div style='background:#f8f9fa;border:1px solid #ddd;padding:10px;margin:10px 0;font-family:monospace;'>";
                echo "<strong>Debug:</strong> <pre>" . print_r($data, true) . "</pre>";
                echo "</div>";
            }

            
            if (isset($_POST['cancel'])) {
                
                displayInitialForm();
            }
            
            elseif (isset($_POST['step1_submit'])) {
                $program = $conn->real_escape_string($_POST['program']);
                $semester = $conn->real_escape_string($_POST['semester']);
                $section = isset($_POST['section']) ? $conn->real_escape_string($_POST['section']) : '';
                $batch = $conn->real_escape_string($_POST['batch']);

                
                if ($program == 'M.Tech IS' || $program == 'M.Tech DS' || $program == 'MCA') {
                    $section = 'NA';
                }

                
                $curriculum_sql = "SELECT id, regulation FROM curriculum_mapping 
                                  WHERE type = '$program' AND batch = '$batch'";
                $curriculum_result = $conn->query($curriculum_sql);

                // Debug curriculum query if it fails
                if (!$curriculum_result) {
                    debug_to_console("Curriculum query failed: " . $conn->error);
                    debug_to_console("SQL: $curriculum_sql");
                }

                if ($curriculum_result && $curriculum_result->num_rows > 0) {
                    $curriculum_row = $curriculum_result->fetch_assoc();
                    $regulation = $curriculum_row['regulation'];
                    
                    
                    $sql = "SELECT code, name, credits, no_of_co 
                            FROM course 
                            WHERE type = '$program' 
                            AND semester = '$semester' 
                            AND regulation = '$regulation'
                            AND course_type = 'PCC'";
                    $result = $conn->query($sql);

                    // Debug SQL query if it fails
                    if (!$result) {
                        debug_to_console("Course query failed: " . $conn->error);
                        debug_to_console("SQL: $sql");
                    }

                   
                    $faculty_sql = "SELECT id, name FROM faculty WHERE role = 'faculty'";
                    $faculty_result = $conn->query($faculty_sql);

                    
                    if (!$faculty_result) {
                        debug_to_console("Faculty query failed: " . $conn->error);
                        debug_to_console("SQL: $faculty_sql");
                    }

                    if ($result && $result->num_rows > 0) {
                        echo "<h4>$program - " . getSemesterSuffix($semester) . " Semester - Batch: $batch</h4>";
                        echo "<form method='post' action=''>";
                        echo "<input type='hidden' name='program' value='" . htmlspecialchars($program) . "'>";
                        echo "<input type='hidden' name='semester' value='" . htmlspecialchars($semester) . "'>";
                        echo "<input type='hidden' name='section' value='" . htmlspecialchars($section) . "'>";
                        echo "<input type='hidden' name='batch' value='" . htmlspecialchars($batch) . "'>";

                        echo "<table class='mapping-table'>";
                        echo "<thead><tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>CO Count</th>
                                <th>Faculty</th>
                              </tr></thead><tbody>";

                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row["code"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["credits"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["no_of_co"]) . "</td>";
                            echo "<td>";
                            echo "<input type='hidden' name='course_id[]' value='" . htmlspecialchars($row["code"]) . "'>";

                            echo "<select name='faculty_id[]' class='form-select'>";
                            echo "<option value=''>Select Faculty</option>";

                            if ($faculty_result) {
                                $faculty_result->data_seek(0);
                                while ($faculty_row = $faculty_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($faculty_row["id"]) . "'>"
                                        . htmlspecialchars($faculty_row["name"]) . "</option>";
                                }
                            }
                            echo "</select></td></tr>";
                        }
                        echo "</tbody></table>";
                        echo "<div class='button-group'>";
                        echo "<button type='submit' name='step2_submit' class='btn btn-primary'>Save Mappings</button>";
                        echo "<button type='submit' name='cancel' class='btn btn-secondary'>Cancel</button>";
                        echo "</div>";
                        echo "</form>";
                    } else {
                        echo "<div class='alert alert-danger'>No courses found for the selected criteria.</div>";
                        echo "<div class='button-group'>";
                        echo "<form method='post' action=''>";
                        echo "<button type='submit' name='cancel' class='btn btn-secondary'>Back</button>";
                        echo "</form>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>No curriculum mapping found for the selected program and batch.</div>";
                    echo "<div class='button-group'>";
                    echo "<form method='post' action=''>";
                    echo "<button type='submit' name='cancel' class='btn btn-secondary'>Back</button>";
                    echo "</form>";
                    echo "</div>";
                }
            }
            
            elseif (isset($_POST['step2_submit'])) {
                try {
                    $program = $conn->real_escape_string($_POST['program']);
                    $semester = $conn->real_escape_string($_POST['semester']);
                    $section = $conn->real_escape_string($_POST['section']);
                    $batch = $conn->real_escape_string($_POST['batch']);
                    $course_ids = isset($_POST['course_id']) ? $_POST['course_id'] : [];
                    $faculty_ids = isset($_POST['faculty_id']) ? $_POST['faculty_id'] : [];

                    $success_count = 0;
                    $error_messages = [];

                    
                    if (empty($course_ids) || empty($faculty_ids)) {
                        $_SESSION['mapping_error'] = "No courses or faculty selections found.";
                    } else {
                       
                        for ($i = 0; $i < count($course_ids); $i++) {
                            if (!empty($faculty_ids[$i]) && isset($course_ids[$i])) {
                                $faculty_id = $conn->real_escape_string($faculty_ids[$i]);
                                $course_id = $conn->real_escape_string($course_ids[$i]);

                                
                                $validation_sql = "SELECT code FROM course 
                                                 WHERE code = '$course_id' 
                                                 AND type = '$program'";
                                $validation_result = $conn->query($validation_sql);

                                if (!$validation_result || $validation_result->num_rows == 0) {
                                    $error_messages[] = "Course $course_id does not exist for program $program. Skipping this assignment.";
                                    continue; 
                                }

                                
                                $check_sql = "SELECT id FROM faculty_course 
                                             WHERE faculty_id = '$faculty_id' 
                                             AND course_id = '$course_id' 
                                             AND type = '$program' 
                                             AND section = '$section' 
                                             AND batch = '$batch'";
                                $check_result = $conn->query($check_sql);

                                if ($check_result && $check_result->num_rows > 0) {
                                    
                                    $update_sql = "UPDATE faculty_course 
                                                  SET faculty_id = '$faculty_id' 
                                                  WHERE course_id = '$course_id' 
                                                  AND type = '$program'
                                                  AND section = '$section' 
                                                  AND batch = '$batch'";
                                    if ($conn->query($update_sql) === TRUE) {
                                        $success_count++;
                                    } else {
                                        $error_messages[] = "Error updating mapping for course $course_id: " . $conn->error;
                                    }
                                } else {
                                    
                                    $check_existing_sql = "SELECT id FROM faculty_course 
                                                         WHERE course_id = '$course_id' 
                                                         AND type = '$program'
                                                         AND section = '$section' 
                                                         AND batch = '$batch'";
                                    $existing_result = $conn->query($check_existing_sql);

                                    if ($existing_result && $existing_result->num_rows > 0) {
                                        
                                        $existing_row = $existing_result->fetch_assoc();
                                        $existing_id = $existing_row['id'];
                                        $update_existing_sql = "UPDATE faculty_course 
                                                              SET faculty_id = '$faculty_id' 
                                                              WHERE id = '$existing_id'";
                                        if ($conn->query($update_existing_sql) === TRUE) {
                                            $success_count++;
                                        } else {
                                            $error_messages[] = "Error updating existing mapping for course $course_id: " . $conn->error;
                                        }
                                    } else {
                                        
                                        $insert_sql = "INSERT INTO faculty_course 
                                                      (faculty_id, course_id, type, section, batch) 
                                                      VALUES ('$faculty_id', '$course_id', '$program', '$section', '$batch')";
                                        if ($conn->query($insert_sql) === TRUE) {
                                            $success_count++;
                                        } else {
                                            $error_messages[] = "Error assigning faculty to course $course_id: " . $conn->error;
                                            
                                            // Add detailed debug information for failures
                                            debug_to_console("Failed insertion: $insert_sql");
                                            debug_to_console("Error: " . $conn->error);
                                        }
                                    }
                                }
                            }
                        }

                        if ($success_count > 0) {
                            $_SESSION['mapping_success'] = "Successfully mapped $success_count courses to faculty members for batch $batch!";
                        } else {
                            $_SESSION['mapping_error'] = "No mappings were processed. Please check your selections.";
                        }

                        if (!empty($error_messages)) {
                            $_SESSION['mapping_error'] = implode("<br>", $error_messages);
                        }
                    }

                    
                    displayInitialForm();

                } catch (Exception $e) {
                    $_SESSION['mapping_error'] = "An error occurred: " . $e->getMessage();
                    echo "<div class='alert alert-danger'>Error occurred. Please try again.</div>";
                    displayInitialForm();
                }
            }
           
            else {
                displayInitialForm();
            }

            
            function displayInitialForm()
            {
                global $conn;
                ?>
                <form method="post" action="#faculty-mapping" id="initialForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="program">Program</label>
                            <select id="program" name="program" required onchange="updateSemestersAndBatches()">
                                <option value="">Select Program</option>
                                <option value="B.Tech">B.Tech</option>
                                <option value="M.Tech IS">M.Tech IS</option>
                                <option value="M.Tech DS">M.Tech DS</option>
                                <option value="MCA">MCA</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="batch">Batch</label>
                            <input type="text" id="batch" name="batch" placeholder="e.g., 2023-27" required>
                        </div>

                        <div class="form-group">
                            <label for="semester">Semester</label>
                            <select id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                            </select>
                        </div>

                        <div class="form-group" id="section_div">
                            <label for="section">Section</label>
                            <select id="section" name="section">
                                <option value="A">A</option>
                                <option value="B">B</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="step1_submit" class="btn btn-primary">Find Courses</button>
                </form>
                <script>
                    function updateSemestersAndBatches() {
                        const program = document.getElementById('program').value;
                        const semesterSelect = document.getElementById('semester');
                        const batchSelect = document.getElementById('batch');
                        const sectionDiv = document.getElementById('section_div');

                        
                        semesterSelect.innerHTML = '<option value="">Select Semester</option>';
                        batchSelect.innerHTML = '<option value="">Select Batch</option>';

                        
                        const currentYear = new Date().getFullYear();

                        if (program === 'B.Tech') {
                            
                            for (let i = 3; i <= 8; i++) {
                                const option = document.createElement('option');
                                option.value = i;
                                option.text = getSemesterText(i);
                                semesterSelect.appendChild(option);
                            }

                            
                            for (let i = 0; i < 5; i++) {
                                const startYear = currentYear - i;
                                const endYear = startYear + 4;
                                const option = document.createElement('option');
                                option.value = startYear + '-' + endYear;
                                option.text = startYear + '-' + endYear;
                                batchSelect.appendChild(option);
                            }

                            sectionDiv.style.display = 'block';
                        } else if (program === 'M.Tech IS' || program === 'M.Tech DS') {
                            
                            for (let i = 1; i <= 4; i++) {
                                const option = document.createElement('option');
                                option.value = i;
                                option.text = getSemesterText(i);
                                semesterSelect.appendChild(option);
                            }

                            
                            for (let i = 0; i < 3; i++) {
                                const startYear = currentYear - i;
                                const endYear = startYear + 2;
                                const option = document.createElement('option');
                                option.value = startYear + '-' + endYear;
                                option.text = startYear + '-' + endYear;
                                batchSelect.appendChild(option);
                            }

                            sectionDiv.style.display = 'none';
                        } else if (program === 'MCA') {
                            
                            for (let i = 1; i <= 6; i++) {
                                const option = document.createElement('option');
                                option.value = i;
                                option.text = getSemesterText(i);
                                semesterSelect.appendChild(option);
                            }

                            
                            for (let i = 0; i < 4; i++) {
                                const startYear = currentYear - i;
                                const endYear = startYear + 3;
                                const option = document.createElement('option');
                                option.value = startYear + '-' + endYear;
                                option.text = startYear + '-' + endYear;
                                batchSelect.appendChild(option);
                            }

                            sectionDiv.style.display = 'none';
                        }
                    }

                    function getSemesterText(sem) {
                        switch (sem) {
                            case 1: return '1st';
                            case 2: return '2nd';
                            case 3: return '3rd';
                            default: return sem + 'th';
                        }
                    }

                   
                    document.addEventListener('DOMContentLoaded', function () {
                        const programSelect = document.getElementById('program');
                        if (programSelect.value) {
                            updateSemestersAndBatches();
                        }
                    });
                </script>
                <?php
            }
            ?>
        </div>
    </div>
</section>
            <!-- Current Mappings -->
            <section id="current-mappings" class="section">
                <div class="mapping-container full-width">
                    <div class="mapping-preview">
                        <h3>Current Mappings</h3>
                        <table id="mapping-table">
                            <thead>
                                <tr>
                                    <th>Faculty ID</th>
                                    <th>Faculty Name</th>
                                    <th>Semester</th>
                                    <th>Program</th>
                                    <th>Batch</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $mapping_query = "
                                    SELECT 
                                        fc.id,
                                        f.id AS faculty_id,
                                        f.name AS faculty_name, 
                                        c.name AS course_name, 
                                        c.semester,
                                        c.type,
                                        fc.batch,
                                        fc.section,
                                        c.code AS course_id
                                    FROM 
                                        faculty_course fc
                                    JOIN 
                                        faculty f ON fc.faculty_id = f.id
                                    JOIN 
                                        course c ON fc.course_id = c.code
                                    WHERE 
                                        course_type!='PEC' AND course_type!='PSE'
                                    ORDER BY 
                                        c.semester, f.name
                                ";
                                $mapping_result = $conn->query($mapping_query);

                                if ($mapping_result->num_rows > 0) {
                                    while ($row = $mapping_result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['faculty_id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['faculty_name']) . "</td>";
                                        echo "<td>" . getSemesterSuffix($row['semester']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['batch']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['course_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['section']) . "</td>";
                                        echo "<td>
                                            <button class='btn btn-sm btn-update' onclick='openUpdateModal(" . $row['id'] . ", " . htmlspecialchars(json_encode($row)) . ")'>Update</button>
                                            <button class='btn btn-sm btn-danger' onclick='confirmDelete(" . $row['id'] . ")'>Delete</button>
                                        </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='text-center'>No faculty mappings found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Update Modal -->
            <div id="update-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <h2>Update Mapping</h2>
                    <form id="update-form" method="POST" action="update_mapping.php">
                        <input type="hidden" name="mapping_id" id="mapping_id">
                        <div>
                            <label for="faculty_id">Faculty Name</label>
                            <select name="faculty_id" id="faculty_id" required>
                                <option value="">Select Faculty</option>
                                <?php
                                $faculty_query = "SELECT id, name FROM faculty";
                                $faculty_result = $conn->query($faculty_query);
                                if ($faculty_result && $faculty_result->num_rows > 0) {
                                    while ($faculty_row = $faculty_result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($faculty_row['id']) . "'>"
                                            . htmlspecialchars($faculty_row['name']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label for="course_id">Course ID</label>
                            <input type="text" name="course_id" id="course_id" readonly>
                        </div>
                        <div>
                            <label for="course_name">Course Name</label>
                            <input type="text" name="course_name" id="course_name" readonly>
                        </div>
                        <div>
                            <label for="program">Program</label>
                            <input type="text" name="program" id="program" readonly>
                        </div>
                        <div>
                            <label for="batch">Batch</label>
                            <input type="text" name="batch" id="batch">
                        </div>
                        <div>
                            <label for="section">Section</label>
                            <input type="text" name="section" id="section">
                        </div>
                        <div class="modal-buttons">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="delete-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <h2>Confirm Delete</h2>
                    <p>Are you sure you want to delete this faculty-course mapping?</p>
                    <form id="delete-form" method="POST" action="delete_mapping.php">
                        <input type="hidden" name="mapping_id" id="delete_mapping_id">
                        <div class="modal-buttons">
                            <button type="submit" class="btn btn-danger">Delete</button>
                            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function openUpdateModal(id, rowData) {
                    document.getElementById('mapping_id').value = id;
                    document.getElementById('course_id').value = rowData.course_id;
                    document.getElementById('course_name').value = rowData.course_name;
                    document.getElementById('program').value = rowData.type;
                    document.getElementById('batch').value = rowData.batch;
                    document.getElementById('section').value = rowData.section;
                    document.getElementById('faculty_id').value = rowData.faculty_id;
                    document.getElementById('update-modal').style.display = 'block';
                }

                function closeUpdateModal() {
                    document.getElementById('update-modal').style.display = 'none';
                }

                function confirmDelete(id) {
                    document.getElementById('delete_mapping_id').value = id;
                    document.getElementById('delete-modal').style.display = 'block';
                }

                function closeDeleteModal() {
                    document.getElementById('delete-modal').style.display = 'none';
                }
            </script>

            



<!-- Professional Elective -->
<section id="professional-elective" class="section">
    <div class="elective-container full-width">
        <h3>Professional Elective</h3>
        <form id="elective-form">
            <div>
                <label for="program-select">Select Program</label>
                <select id="program-select" name="program" required>
                    <option value="">Select Program</option>
                    <option value="B.Tech">B.Tech</option>
                    <option value="M.Tech DS">M.Tech DS</option>
                    <option value="M.Tech IS">M.Tech IS</option>
                    <option value="MCA">MCA</option>
                </select>
            </div>
            
            <div >
                            <label for="batch-select">Batch</label>
                            <input type="text" id="batch-select" name="batch" placeholder="e.g., 2023-27" required>
                        </div>
            <div>
                <label for="semester-select">Select Semester</label>
                <select id="semester-select" name="semester" required>
                    <option value="">Select Semester</option>
                </select>
            </div>
            <div>
                <label for="course-type">Course Type</label>
                <input type="text" id="course-type" name="course_type" placeholder="e.g. Professional Elective" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

        <div id="course-list-container" style="display:none;">
            <h4>Available Courses</h4>
            <div id="courses"></div>
        </div>

        <div id="faculty-selection-container" style="display:none;">
            <h4>Select Faculty</h4>
            <div id="selected-course-info"></div>
            <select id="faculty-select" name="faculty_id" required>
                <option value="">Select Faculty</option>
                <?php
                $faculty_query = "SELECT id, name FROM faculty";
                $faculty_result = $conn->query($faculty_query);
                if ($faculty_result && $faculty_result->num_rows > 0) {
                    while ($faculty_row = $faculty_result->fetch_assoc()) {
                        echo "<option value='" . htmlspecialchars($faculty_row['id']) . "'>"
                            . htmlspecialchars($faculty_row['name']) . "</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div id="student-selection-container" style="display:none;">
            <h4>Select Students</h4>
            <div id="section-selection" class="mb-3" style="display:none;">
                <h5>Select Option:</h5>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="section-option" id="section-a" value="A">
                    <label class="form-check-label" for="section-a">Section A</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="section-option" id="section-b" value="B">
                    <label class="form-check-label" for="section-b">Section B</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="section-option" id="section-mixed" value="Mixed">
                    <label class="form-check-label" for="section-mixed">Mixed (Select Students Manually)</label>
                </div>
            </div>
            <div id="students-list" class="mb-3">
                <div id="students"></div>
            </div>
            <button id="submit-selection" class="btn btn-primary">Submit Selection</button>
        </div>
    </div>
</section>

<script>
    document.getElementById('program-select').addEventListener('change', function () {
        const program = this.value;
        const batchSelect = document.getElementById('batch-select');
        batchSelect.innerHTML = '<option value="">Select Batch</option>';

        if (program === 'B.Tech') {
            const currentYear = new Date().getFullYear();
            for (let i = 0; i < 4; i++) {
                const startYear = currentYear - i;
                const endYear = startYear + 4;
                const option = document.createElement('option');
                option.value = startYear + '-' + endYear;
                option.text = startYear + '-' + endYear;
                batchSelect.appendChild(option);
            }
        } else if (program === 'M.Tech DS' || program === 'M.Tech IS') {
            const currentYear = new Date().getFullYear();
            for (let i = 0; i < 2; i++) {
                const startYear = currentYear - i;
                const endYear = startYear + 2;
                const option = document.createElement('option');
                option.value = startYear + '-' + endYear;
                option.text = startYear + '-' + endYear;
                batchSelect.appendChild(option);
            }
        } else if (program === 'MCA') {
            const currentYear = new Date().getFullYear();
            for (let i = 0; i < 3; i++) {
                const startYear = currentYear - i;
                const endYear = startYear + 3;
                const option = document.createElement('option');
                option.value = startYear + '-' + endYear;
                option.text = startYear + '-' + endYear;
                batchSelect.appendChild(option);
            }
        }

        const semesterSelect = document.getElementById('semester-select');
        semesterSelect.innerHTML = '<option value="">Select Semester</option>';
    });

    document.getElementById('batch-select').addEventListener('change', function () {
        const program = document.getElementById('program-select').value;
        const semesterSelect = document.getElementById('semester-select');
        semesterSelect.innerHTML = '<option value="">Select Semester</option>';

        if (program === 'B.Tech') {
            for (let i = 3; i <= 7; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.text = i + ' Semester';
                semesterSelect.appendChild(option);
            }
        } else if (program === 'M.Tech DS' || program === 'M.Tech IS') {
            for (let i = 1; i <= 4; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.text = i + ' Semester';
                semesterSelect.appendChild(option);
            }
        } else if (program === 'MCA') {
            for (let i = 1; i <= 6; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.text = i + ' Semester';
                semesterSelect.appendChild(option);
            }
        }
    });

    document.getElementById('elective-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const program = document.getElementById('program-select').value;
        const batch = document.getElementById('batch-select').value;
        const semester = document.getElementById('semester-select').value;
        const courseType = document.getElementById('course-type').value;

        document.getElementById('course-list-container').style.display = 'none';
        document.getElementById('faculty-selection-container').style.display = 'none';
        document.getElementById('student-selection-container').style.display = 'none';
        document.getElementById('section-selection').style.display = 'none';
        
        // Show loading indicator
        const coursesDiv = document.getElementById('courses');
        coursesDiv.innerHTML = '<p>Loading courses...</p>';
        document.getElementById('course-list-container').style.display = 'block';

        // Try using URLSearchParams and GET method instead
        const params = new URLSearchParams();
        params.append('program', program);
        params.append('batch', batch);
        params.append('semester', semester);
        params.append('course_type', courseType);

        fetch('fetch_elective_courses.php?' + params.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                coursesDiv.innerHTML = '';

                if (data.error) {
                    coursesDiv.innerHTML = `<p class="text-danger">Error: ${data.error}</p>`;
                    return;
                }

                if (data.length === 0) {
                    coursesDiv.innerHTML = '<p>No Professional Elective Courses available for this selection.</p>';
                } else {
                    data.forEach(course => {
                        const courseLink = document.createElement('div');
                        courseLink.innerHTML = `<a href="#" class="course-link" 
                                      data-course-id="${course.code}" 
                                      data-course-name="${course.name}"
                                      data-course-credits="${course.credits}"
                                      data-course-regulation="${course.regulation}">${course.name} (${course.code})</a>`;
                        coursesDiv.appendChild(courseLink);
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching courses:', error);
                coursesDiv.innerHTML = `<p class="text-danger">Failed to fetch courses: ${error.message}. Please check the server logs and try again.</p>`;
            });
    });

    document.getElementById('courses').addEventListener('click', function (e) {
        if (e.target.classList.contains('course-link')) {
            e.preventDefault();
            const courseId = e.target.getAttribute('data-course-id');
            const courseName = e.target.getAttribute('data-course-name');
            const credits = e.target.getAttribute('data-course-credits');
            const regulation = e.target.getAttribute('data-course-regulation');
            const program = document.getElementById('program-select').value;
            const semester = document.getElementById('semester-select').value;
            const batch = document.getElementById('batch-select').value;

            document.getElementById('selected-course-info').innerHTML = `
                <div class="selected-course mb-3">
                    <p><strong>Course:</strong> ${courseName} (${courseId})</p>
                    <p><strong>Credits:</strong> ${credits}</p>
                    <p><strong>Regulation:</strong> ${regulation}</p>
                    <p><strong>Batch:</strong> ${batch}</p>
                </div>
            `;

            document.getElementById('faculty-selection-container').style.display = 'block';
            document.getElementById('faculty-select').setAttribute('data-course-id', courseId);
            document.getElementById('faculty-select').setAttribute('data-batch', batch);
            document.getElementById('students').innerHTML = '';

            if (program === 'B.Tech') {
                document.getElementById('section-selection').style.display = 'block';
                document.querySelectorAll('input[name="section-option"]').forEach(radio => {
                    radio.addEventListener('change', function () {
                        const selectedSection = this.value;
                        fetchStudents(courseId, program, semester, selectedSection, batch);
                    });
                });
            } else {
                document.getElementById('section-selection').style.display = 'none';
                fetchStudents(courseId, program, semester, null, batch);
            }

            document.getElementById('student-selection-container').style.display = 'block';
        }
    });

    function fetchStudents(courseId, program, semester, section, batch) {
        document.getElementById('students').innerHTML = '<p>Loading students...</p>';
        let url = `fetch_students_for_course.php?course_id=${encodeURIComponent(courseId)}&program=${encodeURIComponent(program)}&semester=${encodeURIComponent(semester)}&batch=${encodeURIComponent(batch)}`;
        if (section) {
            url += `&section=${encodeURIComponent(section)}`;
        }

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const studentsDiv = document.getElementById('students');
                studentsDiv.innerHTML = '';

                if (data.error) {
                    studentsDiv.innerHTML = `<p class="text-danger">Error: ${data.error}</p>`;
                    return;
                }

                if (data.length === 0) {
                    studentsDiv.innerHTML = '<p>No eligible students found for this selection.</p>';
                    return;
                }

                const table = document.createElement('table');
                table.className = 'table table-striped';
                table.innerHTML = `
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            ${program === 'B.Tech' ? '<th>Section</th>' : ''}
                        </tr>
                    </thead>
                    <tbody id="student-table-body">
                    </tbody>
                `;
                studentsDiv.appendChild(table);

                const tableBody = document.getElementById('student-table-body');
                data.forEach(student => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><input type="checkbox" name="student_ids[]" value="${student.register_no}" ${section !== 'Mixed' && section ? 'checked' : ''}></td>
                        <td>${student.register_no}</td>
                        <td>${student.name}</td>
                        ${program === 'B.Tech' ? `<td>${student.section || 'N/A'}</td>` : ''}
                    `;
                    tableBody.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Error fetching students:', error);
                document.getElementById('students').innerHTML = `<p class="text-danger">Failed to fetch students: ${error.message}. Please try again.</p>`;
            });
    }

    document.getElementById('submit-selection').addEventListener('click', function () {
        const courseId = document.getElementById('faculty-select').getAttribute('data-course-id');
        const facultyId = document.getElementById('faculty-select').value;
        const batch = document.getElementById('faculty-select').getAttribute('data-batch');
        const program = document.getElementById('program-select').value;

        let section = null;
        if (program === 'B.Tech') {
            const selectedSectionRadio = document.querySelector('input[name="section-option"]:checked');
            if (selectedSectionRadio) {
                section = selectedSectionRadio.value;
            } else {
                alert('Please select a section option.');
                return;
            }
        }

        if (!facultyId) {
            alert('Please select a faculty.');
            return;
        }

        const selectedStudents = Array.from(document.querySelectorAll('input[name="student_ids[]"]:checked')).map(cb => cb.value);

        if (selectedStudents.length === 0) {
            alert('Please select at least one student.');
            return;
        }

        const formData = new FormData();
        formData.append('faculty_id', facultyId);
        formData.append('course_id', courseId);
        formData.append('section', section);
        formData.append('batch', batch);
        formData.append('type', 'elective');

        selectedStudents.forEach(studentId => {
            formData.append('student_ids[]', studentId);
        });

        fetch('submit_professional_elective.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Professional elective assigned successfully!');
                    document.getElementById('elective-form').reset();
                    document.getElementById('course-list-container').style.display = 'none';
                    document.getElementById('faculty-selection-container').style.display = 'none';
                    document.getElementById('student-selection-container').style.display = 'none';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error assigning elective:', error);
                alert('Failed to assign professional elective: ' + error.message + '. Please try again.');
            });
    });
</script>
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

            <script>
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
                            populateSemesters(degree, semId);
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

                    let maxSemester = 4;
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
                    xhr.open("POST", "../hod/get_batch.php", true);
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
                    xhr.open("POST", "../hod/get_courses_by_semester.php", true);
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
                document.getElementById('complete-detail-btn').addEventListener('click', () => {
                  const fcId = document.getElementById('report-course-select').value;

                  if (!fcId) {
                      alert('Please select a course.');
                      return;
                  }
              
                  const reportUrl = `../Faculty/ReportGeneration/complete_detail_report.php?fc_id=${fcId}`;
                  window.open(reportUrl, '_blank');
                });
            
                document.getElementById('overall-marklist-btn').addEventListener('click', () => {
                  const fcId = document.getElementById('report-course-select').value;
                
                  if (!fcId) {
                      alert('Please select a course.');
                      return;
                  }
              
                  const reportUrl = `../Faculty/ReportGeneration/overall_report.php?fc_id=${fcId}`;
                  window.open(reportUrl, '_blank');
                });
            </script>

            <script src="./co_calculation.js"></script>

            <!-- Staff Details -->
            <section id="staff-details" class="section">
                <h1>Faculty Details</h1>
                <div class="search-container">
                    <input type="text" id="search-name" class="form-control" placeholder="Search by Name">
                    <input type="text" id="search-email" class="form-control" placeholder="Search by Email">
                    <select id="search-role" class="form-control">
                        <option value="">Filter by Role</option>
                        <?php
                        $role_query = "SELECT DISTINCT role FROM faculty";
                        $role_result = $conn->query($role_query);
                        if ($role_result && $role_result->num_rows > 0) {
                            while ($row = $role_result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['role']) . "'>" . htmlspecialchars($row['role']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped" id="faculty-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $faculty_query = "SELECT id, name, email, role FROM faculty";
                            $faculty_result = $conn->query($faculty_query);

                            if ($faculty_result === false) {
                                echo "<tr><td colspan='4' class='text-center text-danger'>Query Error: " . htmlspecialchars($conn->error) . "</td></tr>";
                            } elseif ($faculty_result->num_rows > 0) {
                                while ($faculty = $faculty_result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($faculty['id']) . "</td>";
                                    echo "<td class='faculty-name'>" . htmlspecialchars($faculty['name']) . "</td>";
                                    echo "<td class='faculty-email'>" . htmlspecialchars($faculty['email']) . "</td>";
                                    echo "<td class='faculty-role'>" . htmlspecialchars($faculty['role']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>No faculty members found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const nameInput = document.getElementById("search-name");
                    const emailInput = document.getElementById("search-email");
                    const roleSelect = document.getElementById("search-role");
                    const tableRows = document.querySelectorAll("#faculty-table tbody tr");

                    function filterTable() {
                        const nameValue = nameInput.value.toLowerCase();
                        const emailValue = emailInput.value.toLowerCase();
                        const roleValue = roleSelect.value.toLowerCase();

                        tableRows.forEach(row => {
                            const name = row.querySelector(".faculty-name").textContent.toLowerCase();
                            const email = row.querySelector(".faculty-email").textContent.toLowerCase();
                            const role = row.querySelector(".faculty-role").textContent.toLowerCase();

                            if (
                                (name.includes(nameValue) || nameValue === "") &&
                                (email.includes(emailValue) || emailValue === "") &&
                                (role.includes(roleValue) || roleValue === "")
                            ) {
                                row.style.display = "";
                            } else {
                                row.style.display = "none";
                            }
                        });
                    }

                    nameInput.addEventListener("input", filterTable);
                    emailInput.addEventListener("input", filterTable);
                    roleSelect.addEventListener("change", filterTable);
                });
            </script>


           <!-- Professional Elective Current Mappings -->
<section id="professional-elective-mappings" class="section">
    <div class="mapping-container full-width">
        <div class="mapping-preview">
            <h3>Professional Elective Mappings</h3>
            <table id="elective-mapping-table">
                <thead>
                    <tr>
                        <th>Faculty ID</th>
                        <th>Faculty Name</th>
                        <th>Semester</th>
                        <th>Elective Course</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Updated query to match the actual database structure
                    $elective_mapping_query = "
                        SELECT 
                            fc.id, 
                            f.id AS faculty_id, 
                            f.name AS faculty_name, 
                            c.semester AS semester, 
                            c.name AS course_name,
                            c.code AS course_id
                        FROM 
                            faculty_course fc
                        INNER JOIN 
                            faculty f ON fc.faculty_id = f.id
                        INNER JOIN 
                            course c ON fc.course_id = c.code
                        WHERE
                            fc.type = 'elective'
                        ORDER BY
                            c.semester, c.name
                    ";
                    $elective_result = $conn->query($elective_mapping_query);

                    if ($elective_result === false) {
                        echo "<tr><td colspan='5' class='text-center'>Error executing query: " . $conn->error . "</td></tr>";
                    } else if ($elective_result->num_rows > 0) {
                        while ($row = $elective_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['faculty_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['faculty_name']) . "</td>";
                            echo "<td>" . getSemesterSuffix($row['semester']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['course_name']) . "</td>";
                            echo "<td>
                                <button class='btn btn-sm btn-update' onclick='openElectiveUpdateModal(" . 
                                $row['id'] . ", " . 
                                json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) . 
                                ")'>Update</button>
                                <button class='btn btn-sm btn-delete' onclick='confirmDeleteElective(" . 
                                $row['id'] . ", \"" . htmlspecialchars($row['course_name'], ENT_QUOTES) . "\")'>Delete</button>
                            </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center'>No professional elective mappings found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Professional Elective Update Modal -->
<div id="elective-update-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>Update Professional Elective Mapping</h2>
        <form id="elective-update-form" method="POST" action="update_elective_mapping.php">
            <input type="hidden" name="mapping_id" id="elective_mapping_id">
            <div>
                <label for="elective_faculty_id">Faculty Name</label>
                <select name="faculty_id" id="elective_faculty_id" required>
                    <option value="">Select Faculty</option>
                    <?php
                    $faculty_query = "SELECT id, name FROM faculty";
                    $faculty_result = $conn->query($faculty_query);
                    if ($faculty_result && $faculty_result->num_rows > 0) {
                        while ($faculty_row = $faculty_result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($faculty_row['id']) . "'>"
                                . htmlspecialchars($faculty_row['name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="elective_name">Course Name</label>
                <input type="text" name="course_name" id="elective_name" readonly>
            </div>
            <input type="hidden" name="course_id" id="elective_course_id">
            <div class="modal-buttons">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="closeElectiveUpdateModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="elective-delete-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>Confirm Deletion</h2>
        <p id="delete-confirmation-message">Are you sure you want to delete this elective mapping?</p>
        <form id="elective-delete-form" method="POST" action="delete_elective_mapping.php">
            <input type="hidden" name="mapping_id" id="delete_mapping_id">
            <div class="modal-buttons">
                <button type="submit" class="btn btn-danger">Delete</button>
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openElectiveUpdateModal(id, rowData) {
        document.getElementById('elective_mapping_id').value = id;
        document.getElementById('elective_name').value = rowData.course_name;
        document.getElementById('elective_faculty_id').value = rowData.faculty_id;
        document.getElementById('elective_course_id').value = rowData.course_id;
        document.getElementById('elective-update-modal').style.display = 'block';
    }

    function closeElectiveUpdateModal() {
        document.getElementById('elective-update-modal').style.display = 'none';
    }
    
    function confirmDeleteElective(id, courseName) {
        document.getElementById('delete_mapping_id').value = id;
        document.getElementById('delete-confirmation-message').textContent = 
            'Are you sure you want to delete the elective mapping for "' + courseName + '"?';
        document.getElementById('elective-delete-modal').style.display = 'block';
    }
    
    function closeDeleteModal() {
        document.getElementById('elective-delete-modal').style.display = 'none';
    }
</script>

            











            <section id="curriculum-section" class="section">
    <div class="student-container full-width">
        <div class="student-preview">
            <h3>Curriculum Management</h3>

            <form id="curriculum-form" method="post" action="save_curriculum.php">
                <div class="curriculum-filters">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="course-name">Course Name</label>
                            <input type="text" id="course-name" name="course_name" required>
                        </div>

                        <div class="filter-group">
                            <label for="course-code">Course Code</label>
                            <input type="text" id="course-code" name="course_code" required>
                        </div>

                        <div class="filter-group">
                            <label for="no-of-co">Number of COs</label>
                            <select id="no-of-co" name="no_of_co" required>
                                <option value="">Select</option>
                                <?php for ($i = 1; $i <= 6; $i++) echo "<option value='$i'>$i</option>"; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="credits">Credits</label>
                            <select id="credits" name="credits" required>
                                <option value="">Select</option>
                                <?php for ($i = 1; $i <= 15; $i++) echo "<option value='$i'>$i</option>"; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="program-type">Program Type</label>
                            <select id="program-type" name="types" required>
                                <option value="">Select</option>
                                <option value="B.Tech">B.Tech</option>
                                <option value="M.Tech IS">M.Tech IS</option>
                                <option value="M.Tech DS">M.Tech DS</option>
                                <option value="MCA">MCA</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="semester">Semester</label>
                            <select id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <?php for ($i = 1; $i <= 8; $i++) echo "<option value='$i'>$i</option>"; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="course-type">Course Type</label>
                            <input type="text" id="course-type" name="course_type" required>
                        </div>

                        <div class="filter-group">
                            <label for="regulation">Regulation</label>
                            <select id="regulation" name="regulation" required>
                                <option value="">Select</option>
                                <?php
                                $current_year = date('Y');
                                for ($year = 2021; $year <= $current_year + 20; $year++) {
                                    echo "<option value='$year'>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Save Curriculum</button>
                        <button type="reset" class="btn btn-secondary">Reset</button>
                    </div>
                </div>
            </form>

            <div id="curriculum-list-container">
                <h4>Current Curriculum</h4>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Credits</th>
                            <th>COs</th>
                            <th>Semester</th>
                            <th>Program</th>
                            <th>Type</th>
                            <th>Regulation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="curriculum-list">
                        <?php
                        $curriculum_query = "SELECT * FROM course ORDER BY regulation DESC, type, semester";
                        $curriculum_result = $conn->query($curriculum_query);

                        if ($curriculum_result->num_rows > 0) {
                            while ($row = $curriculum_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['code']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['credits']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['no_of_co']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['course_type']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['regulation']) . "</td>";
                                echo "<td>
                                    <a href='edit_course.php?code=" . urlencode($row['code']) . "&regulation=" . urlencode($row['regulation']) . "'>
                                        <button class='btn-edit'>Edit</button>
                                    </a>
                                    <a href='delete_course.php?code=" . urlencode($row['code']) . "&regulation=" . urlencode($row['regulation']) . "' onclick=\"return confirm('Are you sure you want to delete this course?');\">
                                        <button class='btn-delete'>Delete</button>
                                    </a>
                                  </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9'>No curriculum records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Form validation
    document.getElementById('curriculum-form').addEventListener('submit', function (e) {
        const requiredFields = this.querySelectorAll('[required]');
        let valid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }
        });

        if (!valid) {
            e.preventDefault();
            alert('Please fill all required fields');
        }
    });





    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            if (confirm('Are you sure you want to delete this course?')) {
                const id = this.getAttribute('data-id');
                window.location.href = 'delete_course.php?id=' + id;
            }
        });
    });
    
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function(e) {
            const id = this.getAttribute('data-id');
            window.location.href = 'edit_course.php?id=' + id;
        });
    });

    // Remove obsolete edit/delete handlers using data-id if not used
});
</script>








<section id="curriculum-mapping-section" class="section">
    <div class="student-container full-width">
        <div class="student-preview">
            <h3>Curriculum-Batch Mapping</h3>

            <form id="mapping-form" method="post" action="save_curriculum_mapping.php">
                <div class="mapping-filters">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="mapping-program-type">Program Type</label>
                            <select id="mapping-program-type" name="type" required>
                                <option value="">Select</option>
                                <option value="B.Tech">B.Tech</option>
                                <option value="M.Tech IS">M.Tech IS</option>
                                <option value="M.Tech DS">M.Tech DS</option>
                                <option value="MCA">MCA</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="mapping-regulation">Regulation</label>
                            <select id="mapping-regulation" name="regulation" required>
                                <option value="">Select</option>
                                <?php
                                $current_year = date('Y');
                                for ($year = 2021; $year <= $current_year + 20; $year++) {
                                    echo "<option value='$year'>$year</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="batch">Batch</label>
                            <input type="text" id="batch" name="batch" placeholder="e.g., 2023-27" required>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Save Mapping</button>
                        <button type="reset" class="btn btn-secondary">Reset</button>
                    </div>
                </div>
            </form>

            <div id="mapping-list-container">
    <h4>Current Curriculum-Batch Mappings</h4>
    <table class="data-table">
        <thead>
            <tr>
                <th>Program Type</th>
                <th>Regulation</th>
                <th>Batch</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="mapping-list">
            <?php
            $mapping_query = "SELECT * FROM curriculum_mapping ORDER BY type, regulation, batch";
            $mapping_result = $conn->query($mapping_query);

            if ($mapping_result->num_rows > 0) {
                while ($row = $mapping_result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['regulation']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['batch']) . "</td>";
                    echo "<td>
                        <a href='edit_mapping_curriculum.php?id=" . $row['id'] . "'>
                            <button class='btn-edit'>Edit</button>
                        </a>
                        <a href='delete_mapping_curriculum.php?id=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this mapping?');\">
                            <button class='btn-delete'>Delete</button>
                        </a>
                      </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No curriculum-batch mappings found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Form validation for mapping form
    document.getElementById('mapping-form').addEventListener('submit', function (e) {
        const requiredFields = this.querySelectorAll('[required]');
        let valid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }
        });

        if (!valid) {
            e.preventDefault();
            alert('Please fill all required fields for curriculum mapping');
        }
    });

    // Validate batch format (optional)
    // Validate batch format 
document.getElementById('batch').addEventListener('blur', function() {
    const batchPattern = /^\d{4}-\d{4}$/;
    if (this.value && !batchPattern.test(this.value)) {
        this.classList.add('error');
        alert('Please enter batch in format YYYY-YYYY (e.g., 2021-2025)');
    } else {
        this.classList.remove('error');
    }
});
});
</script>
































            
</script>          <!-- Student Details -->
            <section id="student-details" class="section">
                <div class="student-container full-width">
                    <div class="student-preview">
                        <h3>Student Details</h3>
                        <div class="student-filters">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="year-select">Select Year</label>
                                    <select id="year-select" name="year">
                                        <option value="">All Years</option>
                                        <?php
                                        $year_query = "SELECT DISTINCT year FROM student ORDER BY year";
                                        $year_result = $conn->query($year_query);

                                        while ($year = $year_result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($year['year']) . "'>"
                                                . htmlspecialchars($year['year']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="section-select">Select Section</label>
                                    <select id="section-select" name="section">
                                        <option value="">All Sections</option>
                                        <?php
                                        $section_query = "SELECT DISTINCT section FROM student ORDER BY section";
                                        $section_result = $conn->query($section_query);

                                        while ($section = $section_result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($section['section']) . "'>"
                                                . htmlspecialchars($section['section']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="semester-select">Select Semester</label>
                                    <select id="semester-select" name="semester">
                                        <option value="">All Semesters</option>
                                        <?php
                                        for ($i = 1; $i <= 8; $i++) {
                                            echo "<option value='$i'>$i</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="search-row">
                                <div class="search-group">
                                    <label for="name-search">Search by Name</label>
                                    <input type="text" id="name-search" name="name" placeholder="Enter student name">
                                </div>

                                <div class="search-group">
                                    <label for="register-search">Search by Register No</label>
                                    <input type="text" id="register-search" name="register_no" placeholder="Enter register number">
                                </div>

                                <div class="search-group">
                                    <label for="batch-select">Select Batch</label>
                                    <select id="batch-select" name="batch">
                                        <option value="">All Batches</option>
                                        <?php
                                        $batch_query = "SELECT DISTINCT batch FROM student ORDER BY batch";
                                        $batch_result = $conn->query($batch_query);

                                        while ($batch = $batch_result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($batch['batch']) . "'>"
                                                . htmlspecialchars($batch['batch']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="filter-actions">
                                <button type="button" id="search-button" class="btn                                btn-primary">Search</button>
                                <button type="button" id="reset-button" class="btn btn-secondary">Reset</button>
                            </div>
                        </div>

                        <div class="student-list-container">
                            <table class="data-table" id="student-table">
                                <thead>
                                    <tr>
                                        <th>Register No</th>
                                        <th>Name</th>
                                        <th>Year</th>
                                        <th>Semester</th>
                                        <th>Section</th>
                                        <th>Batch</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="student-list">
                                    <?php
                                    $student_query = "SELECT register_no, name, year, semester, section, batch FROM student ORDER BY year, semester, section, name";
                                    $student_result = $conn->query($student_query);

                                    if ($student_result->num_rows > 0) {
                                        while ($row = $student_result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['register_no']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['year']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['section']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['batch']) . "</td>";
                                            echo "<td>";
                                            echo "<button class='btn btn-sm btn-edit' data-register-no='" . htmlspecialchars($row['register_no']) . "'>Edit</button>";
                                            echo "<button class='btn btn-sm btn-danger' data-register-no='" . htmlspecialchars($row['register_no']) . "'>Delete</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7'>No student records found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Student Edit Modal -->
            <div id="student-edit-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <h2>Edit Student Details</h2>
                    <form id="student-edit-form" method="POST" action="update_student.php">
                        <input type="hidden" name="register_no" id="edit-register-no">
                        <div class="form-group">
                            <label for="edit-name">Name</label>
                            <input type="text" name="name" id="edit-name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-year">Year</label>
                            <select name="year" id="edit-year" required>
                                <option value="">Select Year</option>
                                <?php
                                for ($i = 1; $i <= 4; $i++) {
                                    echo "<option value='$i'>$i</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-semester">Semester</label>
                            <select name="semester" id="edit-semester" required>
                                <option value="">Select Semester</option>
                                <?php
                                for ($i = 1; $i <= 8; $i++) {
                                    echo "<option value='$i'>$i</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-section">Section</label>
                            <select name="section" id="edit-section" required>
                                <option value="">Select Section</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-batch">Batch</label>
                            <input type="text" name="batch" id="edit-batch" required>
                        </div>
                        <div class="modal-buttons">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-secondary" onclick="closeStudentEditModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Student Delete Modal -->
            <div id="student-delete-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <h2>Confirm Delete</h2>
                    <p>Are you sure you want to delete this student?</p>
                    <form id="student-delete-form" method="POST" action="delete_student.php">
                        <input type="hidden" name="register_no" id="delete-register-no">
                        <div class="modal-buttons">
                            <button type="submit" class="btn btn-danger">Delete</button>
                            <button type="button" class="btn btn-secondary" onclick="closeStudentDeleteModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Navigation
        document.addEventListener('DOMContentLoaded', function () {
            const menuItems = document.querySelectorAll('.sidebar-menu li');
            const sections = document.querySelectorAll('.section');

            menuItems.forEach(item => {
                item.addEventListener('click', function () {
                    if (this.id === 'logout-menu-item') return; // Skip logout item

                    menuItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');

                    const sectionId = this.getAttribute('data-section');
                    sections.forEach(section => {
                        section.classList.remove('active');
                        if (section.id === sectionId) {
                            section.classList.add('active');
                        }
                    });
                });
            });

            // Logout Modal
            const logoutMenuItem = document.getElementById('logout-menu-item');
            const logoutModal = document.getElementById('logout-modal');
            const confirmLogout = document.getElementById('confirm-logout');
            const cancelLogout = document.getElementById('cancel-logout');

            logoutMenuItem.addEventListener('click', function (e) {
                e.preventDefault();
                logoutModal.style.display = 'flex';
            });

            confirmLogout.addEventListener('click', function () {
                window.location.href = '../logout.php';
            });

            cancelLogout.addEventListener('click', function () {
                logoutModal.style.display = 'none';
            });

            // Student Edit Modal
            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function () {
                    const registerNo = this.getAttribute('data-register-no');
                    fetch(`get_student.php?register_no=${encodeURIComponent(registerNo)}`)
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('edit-register-no').value = data.register_no;
                            document.getElementById('edit-name').value = data.name;
                            document.getElementById('edit-year').value = data.year;
                            document.getElementById('edit-semester').value = data.semester;
                            document.getElementById('edit-section').value = data.section;
                            document.getElementById('edit-batch').value = data.batch;
                            document.getElementById('student-edit-modal').style.display = 'flex';
                        })
                        .catch(error => {
                            console.error('Error fetching student data:', error);
                            alert('Failed to fetch student data.');
                        });
                });
            });

            // Student Delete Modal
            document.querySelectorAll('.btn-danger').forEach(button => {
                button.addEventListener('click', function () {
                    const registerNo = this.getAttribute('data-register-no');
                    document.getElementById('delete-register-no').value = registerNo;
                    document.getElementById('student-delete-modal').style.display = 'flex';
                });
            });

            // Close Modals
            function closeStudentEditModal() {
                document.getElementById('student-edit-modal').style.display = 'none';
            }

            function closeStudentDeleteModal() {
                document.getElementById('student-delete-modal').style.display = 'none';
            }

            // Student Filter and Search
            const yearSelect = document.getElementById('year-select');
            const sectionSelect = document.getElementById('section-select');
            const semesterSelect = document.getElementById('semester-select');
            const nameSearch = document.getElementById('name-search');
            const registerSearch = document.getElementById('register-search');
            const batchSelect = document.getElementById('batch-select');
            const searchButton = document.getElementById('search-button');
            const resetButton = document.getElementById('reset-button');
            const studentTable = document.getElementById('student-table');

            function filterStudents() {
                const year = yearSelect.value;
                const section = sectionSelect.value;
                const semester = semesterSelect.value;
                const name = nameSearch.value.toLowerCase();
                const registerNo = registerSearch.value.toLowerCase();
                const batch = batchSelect.value;

                const rows = studentTable.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const rowYear = row.cells[2].textContent;
                    const rowSemester = row.cells[3].textContent;
                    const rowSection = row.cells[4].textContent;
                    const rowName = row.cells[1].textContent.toLowerCase();
                    const rowRegisterNo = row.cells[0].textContent.toLowerCase();
                    const rowBatch = row.cells[5].textContent;

                    const matchesYear = !year || rowYear === year;
                    const matchesSemester = !semester || rowSemester === semester;
                    const matchesSection = !section || rowSection === section;
                    const matchesName = !name || rowName.includes(name);
                    const matchesRegisterNo = !registerNo || rowRegisterNo.includes(registerNo);
                    const matchesBatch = !batch || rowBatch === batch;

                    if (matchesYear && matchesSemester && matchesSection && matchesName && matchesRegisterNo && matchesBatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            searchButton.addEventListener('click', filterStudents);
            yearSelect.addEventListener('change', filterStudents);
            sectionSelect.addEventListener('change', filterStudents);
            semesterSelect.addEventListener('change', filterStudents);
            nameSearch.addEventListener('input', filterStudents);
            registerSearch.addEventListener('input', filterStudents);
            batchSelect.addEventListener('change', filterStudents);

            resetButton.addEventListener('click', function () {
                yearSelect.value = '';
                sectionSelect.value = '';
                semesterSelect.value = '';
                nameSearch.value = '';
                registerSearch.value = '';
                batchSelect.value = '';
                filterStudents();
            });
        });
    </script>
    <script>

(function() {
    // Wait for page to fully load
    window.onload = function() {
        console.log("Page fully loaded - initializing semester dropdown");
        
        // Get the elements
        var programTypeSelect = document.getElementById('program-type');
        var semesterSelect = document.getElementById('semester');
        
        // Debug element detection
        console.log("Program type select found:", !!programTypeSelect);
        console.log("Semester select found:", !!semesterSelect);
        
        if (!programTypeSelect || !semesterSelect) {
            console.error("Could not find required form elements!");
            return;
        }
        
        // Direct implementation of change handler
        function updateSemesterOptions() {
            var programType = programTypeSelect.value;
            console.log("Updating semester options for program:", programType);
            
            // Clear the current options
            while (semesterSelect.options.length > 0) {
                semesterSelect.remove(0);
            }
            
            // Add default option
            var defaultOption = document.createElement('option');
            defaultOption.text = 'Select';
            defaultOption.value = '';
            semesterSelect.add(defaultOption);
            
            // Add appropriate options based on program type
            if (programType === 'B.Tech') {
                console.log("Adding B.Tech semesters");
                for (var i = 1; i <= 8; i++) {
                    var option = document.createElement('option');
                    option.value = i;
                    option.text = i;
                    semesterSelect.add(option);
                }
            } else if (programType === 'M.Tech IS' || programType === 'M.Tech DS' || programType === 'MCA') {
                console.log("Adding M.Tech/MCA semesters");
                for (var i = 1; i <= 4; i++) {
                    var option = document.createElement('option');
                    option.value = i;
                    option.text = i;
                    semesterSelect.add(option);
                }
            }
            
            console.log("Semester dropdown now has", semesterSelect.options.length, "options");
        }
        
        // Add event listener using traditional method
        if (programTypeSelect.addEventListener) {
            programTypeSelect.addEventListener('change', updateSemesterOptions, false);
        } else if (programTypeSelect.attachEvent) {
          
            programTypeSelect.attachEvent('onchange', updateSemesterOptions);
        } else {
            
            programTypeSelect.onchange = updateSemesterOptions;
        }
        
        
        updateSemesterOptions();
        
        console.log("Semester dropdown initialization complete");
    };
})();
</script>
</body>
</html>