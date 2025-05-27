<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="../navbar.css">
    <script src="admin_dashboard.js"></script>

</head>

<body>
    <?php
    // Session and Database Configuration
    require_once '../config.php';








    // Function to delete faculty-course mapping
    function deleteFacultyMapping($conn, $mapping_id)
    {
        // Validate input
        if (empty($mapping_id)) {
            return ['error' => "Invalid mapping ID."];
        }

        // Prepare delete statement
        $delete_query = "DELETE FROM faculty_course WHERE id = ?";
        $stmt = $conn->prepare($delete_query);

        // Error handling for statement preparation
        if (!$stmt) {
            return ['error' => "Database preparation error: " . $conn->error];
        }

        // Bind parameters and execute
        $stmt->bind_param("i", $mapping_id);
        if (!$stmt->execute()) {
            return ['error' => "Error deleting faculty mapping: " . $stmt->error];
        }

        // Return success status
        return $stmt->affected_rows > 0
            ? ['success' => "Faculty mapping deleted successfully!"]
            : ['error' => "No mapping found with the specified ID."];
    }



    // Function to map faculty to course
    function mapFacultyCourse($conn, $faculty_id, $course_id, $semester, $section)
    {

        // Check for existing mapping
        $check_query = "SELECT id FROM faculty_course 
                        WHERE faculty_id = ? AND course_id = ? AND section = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("sss", $faculty_id, $course_id, $section);
        $check_stmt->execute();
        $check_stmt->store_result();

        // If mapping exists, return error
        if ($check_stmt->num_rows > 0) {
            $check_stmt->close();
            return ['error' => "This faculty is already mapped to the course and section."];
        }
        $check_stmt->close();

        // Insert new mapping
        $insert_query = "INSERT INTO faculty_course (faculty_id, course_id, section) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);

        // Error handling and execution
        if (!$stmt) {
            return ['error' => "Database preparation error: " . $conn->error];
        }


        $stmt->bind_param("sss", $faculty_id, $course_id, $section);

        if (!$stmt->execute()) {
            return ['error' => "Error mapping faculty: " . $stmt->error];
        }


        return ['success' => "Faculty mapped successfully!"];
    }


    // Handle form submissions
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['map_faculty'])) {
        // Handle faculty mapping
        if (isset($_POST['map_faculty'])) {
            $program = $_POST['program'] ?? '';
            $semester = $_POST['semester'] ?? '';
            $section = $_POST['section'] ?? '';
            $facultyMappings = $_POST['faculty'] ?? [];

            if (empty($program) || empty($semester) || empty($section)) {
                $_SESSION['mapping_error'] = "All fields are required";
            } elseif (empty($facultyMappings)) {
                $_SESSION['mapping_error'] = "No courses selected";
            } else {
                $successCount = 0;
                $errors = [];

                foreach ($facultyMappings as $course_code => $faculty_id) {
                    if (empty($faculty_id))
                        continue;

                    // Check for existing mapping
                    $check_query = "SELECT id FROM faculty_course 
                               WHERE course_id = ? AND section = ?";
                    $stmt = $conn->prepare($check_query);
                    $stmt->bind_param("ss", $course_code, $section);
                    $stmt->execute();
                    $existing = $stmt->get_result()->fetch_assoc();

                    if ($existing) {
                        // Update existing mapping
                        $update_query = "UPDATE faculty_course SET faculty_id = ? WHERE id = ?";
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param("si", $faculty_id, $existing['id']);
                    } else {
                        // Insert new mapping
                        $insert_query = "INSERT INTO faculty_course (faculty_id, course_id, section) 
                                   VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($insert_query);
                        $stmt->bind_param("sss", $faculty_id, $course_code, $section);
                    }

                    if ($stmt->execute()) {
                        $successCount++;
                    } else {
                        $errors[] = "Error processing course $course_code: " . $stmt->error;
                    }
                }

                if (!empty($errors)) {
                    $_SESSION['mapping_error'] = implode('<br>', $errors);
                }
                if ($successCount > 0) {
                    $_SESSION['mapping_success'] = "Successfully processed $successCount mappings!";
                }
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "#faculty-mapping");
            exit();
        }
    }
    include '../navbar.php';
    ?>

    <div class="admin-dashboard">
        <div class="dashboard-sidebar">
            <div class="admin-profile">
                <div class="admin-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="admin-info">
                    <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="admin-role">Administrator</span>
                </div>
            </div>
            <div class="sidebar-menu">
                <ul>
                    <li data-section="student-upload" class="active">
                        <a href="#student-upload">
                            <i class="fas fa-upload"></i>
                            <span>Upload Student Details</span>
                        </a>
                    </li>
                    <li data-section="faculty-mapping">
                        <a href="#faculty-mapping">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Faculty Mapping</span>
                        </a>
                    </li>
                    <li data-section="current-mappings">
                        <a href="#current-mappings">
                            <i class="fas fa-table"></i>
                            <span>Current Mappings</span>
                        </a>
                    </li>
                    <li data-section="staff-details">
                        <a href="#staff-details">
                            <i class="fas fa-users"></i>
                            <span>Faculty Details</span>
                        </a>
                    </li>
                    <li data-section="student-details">
                        <a href="#student-details">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Student Details</span>
                        </a>
                    </li>
                    <li id="logout-menu-item">
                        <a href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="dashboard-content">

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

            <!-- <!-- <section id="faculty-mapping" class="section">
                <div class="mapping-form">
                    <h3>Faculty Mapping</h3>
                    <?php

                    if ($mapping_success) {
                        echo "<div class='alert alert-success'>" . htmlspecialchars($mapping_success) . "</div>";
                    }
                    if ($mapping_error) {
                        echo "<div class='alert alert-danger'>" . htmlspecialchars($mapping_error) . "</div>";
                    }
                    ?>

                    <form method="POST" id="facultyMappingForm">
                        <div class="form-group">
                            <label for="program">Program</label>
                            <select name="program" id="program" required>
                                <option value="B.Tech">B.Tech</option>
                                <option value="M.Tech">M.Tech</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="semester">Semester</label>
                            <select name="semester" id="semesterSelect">
                            <option value="">Select Semester</option>
                                <?php

                                for ($i = 3; $i <= 8; $i++) {
                                    $semester_text = $i == 3 ? '3rd' : $i . 'th';
                                    echo "<option value='$i'>$semester_text Semester</option>";
                                }
                                ?>

                            </select>
                        </div>

                        <div class="form-group">
                            <label for="section">Section</label>
                            <select name="section" id="section">
                                <option value="A">A</option>
                                <option value="B">B</option>
                            </select>
                        </div>

                        <button type="button" id="loadCoursesBtn" class="btn">Submit</button>


                        <div id="coursesContainer" style="display: none;">
                            <table class="mapping-table">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Faculty</th>
                                    </tr>
                                </thead>
                                <tbody id="coursesBody"></tbody>

                            </table>
                            <button type="submit" name="map_faculty" class="btn btn-primary">Save Mappings</button>
                        </div>


                    </form> 



                </div>

            </section> -->


            <!-- Faculty Mapping Section -->
            <!-- Faculty Mapping Section -->
            <section id="faculty-mapping" class="section">
                <div class="mapping-container full-width">
                    <div class="mapping-preview">
                        <h3>Faculty Course Mapping</h3>
                        <?php
                        // Ensure session is started
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }

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

                        // Process first form submission
                        if (isset($_POST['step1_submit'])) {



                            $program = $conn->real_escape_string($_POST['program']);
                            $semester = $conn->real_escape_string($_POST['semester']);
                            $section = isset($_POST['section']) ? $conn->real_escape_string($_POST['section']) : '';


                            // For M.Tech, set default section value
                            if ($program == 'M.Tech') {
                                $section = 'NA';
                            }

                            // Query to fetch courses - FIXED: Use type column as mentioned in your query
                            $sql = "SELECT code, name, credits, no_of_co FROM course 
                       WHERE type = '$program' AND semester = '$semester'";
                            $result = $conn->query($sql);

                            // Debug SQL query if it fails
                            if (!$result) {
                                debug_to_console("Course query failed: " . $conn->error);
                                debug_to_console("SQL: $sql");
                            }

                            // Query to fetch faculty
                            $faculty_sql = "SELECT id, name FROM faculty WHERE role = 'faculty'";
                            $faculty_result = $conn->query($faculty_sql);

                            // Debug faculty query if it fails
                            if (!$faculty_result) {
                                debug_to_console("Faculty query failed: " . $conn->error);
                                debug_to_console("SQL: $faculty_sql");
                            }

                            if ($result && $result->num_rows > 0) {
                                echo "<h4>$program - " . getSemesterSuffix($semester) . " Semester</h4>";
                                echo "<form method='post' action=''>";
                                echo "<input type='hidden' name='program' value='" . htmlspecialchars($program) . "'>";
                                echo "<input type='hidden' name='semester' value='" . htmlspecialchars($semester) . "'>";
                                echo "<input type='hidden' name='section' value='" . htmlspecialchars($section) . "'>";

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

                                    echo "<select name='faculty_id[]' class='form-select' required>";
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
                                echo "<button type='submit' name='step2_submit' class='btn btn-primary'>Save Mappings</button>";
                                echo "</form>";
                            } else {
                                echo "<div class='alert alert-danger'>No courses found for the selected criteria.</div>";
                            }
                        }
                        // Process second form submission
                        elseif (isset($_POST['step2_submit'])) {
                            // Debug the incoming POST data
                            debug_to_console($_POST);

                            $program = $conn->real_escape_string($_POST['program']);
                            $section = $conn->real_escape_string($_POST['section']);
                            $course_ids = isset($_POST['course_id']) ? $_POST['course_id'] : [];
                            $faculty_ids = isset($_POST['faculty_id']) ? $_POST['faculty_id'] : [];

                            $success_count = 0;
                            $error_messages = [];

                            // Verify faculty_course table structure
                            $table_check = $conn->query("DESCRIBE faculty_course");
                            if (!$table_check) {
                                debug_to_console("Table structure check failed: " . $conn->error);
                                $error_messages[] = "Faculty course table might not exist or is misconfigured";
                            } else {
                                debug_to_console("Table structure exists");
                            }

                            // Process each course-faculty pair
                            // Loop through each course and faculty assignment
                            for ($i = 0; $i < count($course_ids); $i++) {
                                if (!empty($faculty_ids[$i])) {
                                    $faculty_id = $conn->real_escape_string($faculty_ids[$i]);
                                    $course_id = $conn->real_escape_string($course_ids[$i]);

                                    // Explicitly show the query being executed
                                    $insert_sql = "INSERT INTO faculty_course (faculty_id, course_id, section) 
                               VALUES ('$faculty_id', '$course_id', '$section')";

                                    echo "<div class='debug-info'>Executing SQL: $insert_sql</div>";

                                    if ($conn->query($insert_sql) === TRUE) {
                                        $success_count++;
                                    } else {
                                        $error_messages[] = "Error assigning faculty to course $course_id: " . $conn->error;
                                    }
                                }
                            }

                            if ($success_count > 0) {
                                $_SESSION['mapping_success'] = "Successfully processed $success_count mappings!";
                            } else {
                                $_SESSION['mapping_error'] = "No mappings were processed. Please check your selections.";
                            }

                            if (!empty($error_messages)) {
                                $_SESSION['mapping_error'] = implode("<br>", $error_messages);
                            }

                            // Redirect to avoid form resubmission
                            header("Location: " . $_SERVER['PHP_SELF'] . "#faculty-mapping");
                            exit();
                        }
                        // Initial form
                        else {
                            ?>
                            <form method="post" action="#faculty-mapping">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="program">Program</label>
                                        <select id="program" name="program" required onchange="updateSemesters()">
                                            <option value="">Select Program</option>
                                            <option value="B.Tech">B.Tech</option>
                                            <option value="M.Tech">M.Tech</option>
                                        </select>
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
                                function updateSemesters() {
                                    const program = document.getElementById('program').value;
                                    const semesterSelect = document.getElementById('semester');
                                    const sectionDiv = document.getElementById('section_div');

                                    semesterSelect.innerHTML = '<option value="">Select Semester</option>';

                                    if (program === 'B.Tech') {
                                        for (let i = 3; i <= 8; i++) {
                                            const option = document.createElement('option');
                                            option.value = i;
                                            option.text = getSemesterText(i);
                                            semesterSelect.appendChild(option);
                                        }
                                        sectionDiv.style.display = 'block';
                                    } else if (program === 'M.Tech') {
                                        for (let i = 1; i <= 4; i++) {
                                            const option = document.createElement('option');
                                            option.value = i;
                                            option.text = getSemesterText(i);
                                            semesterSelect.appendChild(option);
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


                            </script>
                        <?php } ?>
                    </div>
                </div>
            </section>




            <section id="current-mappings" class="section">
                <div class="mapping-container full-width">
                    <div class="mapping-preview">
                        <h3>Current Mappings</h3>
                        <?php

                        if ($delete_success) {
                            echo "<div class='alert alert-success'>" . htmlspecialchars($delete_success) . "</div>";
                        }
                        if ($delete_error) {
                            echo "<div class='alert alert-danger'>" . htmlspecialchars($delete_error) . "</div>";
                        }
                        ?>
                        <table id="mapping-table">
                            <thead>
                                <tr>
                                    <th>Faculty ID</th>
                                    <th>Faculty Name</th>
                                    <th>Semester</th>
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
                                        fc.section
                                    FROM 
                                        faculty_course fc
                                    JOIN 
                                        faculty f ON fc.faculty_id = f.id
                                    JOIN 
                                        course c ON fc.course_id = c.code
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
                                        echo "<td>" . htmlspecialchars($row['course_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['section']) . "</td>";
                                        echo "<td>
                                            <form method='POST' action='#current-mappings' style='display:inline;'>
                                                <input type='hidden' name='mapping_id' value='" . $row['id'] . "'>
                                                <button type='submit' name='delete_mapping' class='btn btn-sm btn-delete' onclick='return confirm(\"Are you sure you want to delete this mapping?\")'>Delete</button>
                                            </form>
                                        </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>No faculty mappings found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="staff-details" class="section">
                <h1>Faculty Details</h1>
                <div class="table-responsive">
                    <table class="table table-striped">
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
                            // Fetch faculty list with detailed error handling
                            $faculty_query = "SELECT id, name, email, role FROM faculty";
                            $faculty_result = $conn->query($faculty_query);

                            if ($faculty_result === false) {
                                // Query failed
                                echo "<tr><td colspan='4' class='text-center text-danger'>Query Error: " . htmlspecialchars($conn->error) . "</td></tr>";
                            } elseif ($faculty_result->num_rows > 0) {
                                while ($faculty = $faculty_result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($faculty['id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($faculty['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($faculty['email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($faculty['role']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>No faculty members found. Total rows: " . $faculty_result->num_rows . "</td></tr>";
                            }

                            // Additional debugging
                            error_log("Faculty Query: $faculty_query");
                            error_log("Faculty Query Result: " . ($faculty_result ? "Success" : "Failed"));
                            error_log("Connection Error: " . $conn->error);
                            error_log("Number of Rows: " . ($faculty_result ? $faculty_result->num_rows : "N/A"));
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

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
                            </div>

                            <div class="search-row">
                                <div class="search-group">
                                    <label for="name-search">Search by Name</label>
                                    <input type="text" id="name-search" name="name" placeholder="Enter student name">
                                </div>

                                <div class="search-group">
                                    <label for="register-search">Search by Register No</label>
                                    <input type="text" id="register-search" name="register_no"
                                        placeholder="Enter register number">
                                </div>
                            </div>
                        </div>

                        <div id="student-list-container">
                            <?php

                            include 'fetch_students.php';
                            ?>
                        </div>
                    </div>
                </div>
            </section>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
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


                    const fileUpload = document.getElementById('student-file-upload');
                    const uploadPreviewTable = document.getElementById('upload-preview-table')?.getElementsByTagName('tbody')[0];
                    const uploadBtn = document.querySelector('.upload-btn');
                    const confirmUploadBtn = document.querySelector('.confirm-upload');
                    const cancelUploadBtn = document.querySelector('.cancel-upload');
                    const uploadPreview = document.querySelector('.upload-preview');


                    if (!fileUpload || !uploadPreviewTable || !uploadBtn || !confirmUploadBtn || !cancelUploadBtn || !uploadPreview) {
                        console.error('One or more required elements are missing');
                        return;
                    }


                    function parseCSV(csvData) {

                        csvData = csvData.replace(/^\uFEFF/, '').trim();


                        const lines = csvData.split(/\r\n|\n|\r/);


                        const cleanLines = lines.filter(line => line.trim() !== '');


                        return cleanLines.map((line, lineIndex) => {

                            const fields = [];
                            let currentField = '';
                            let inQuotes = false;

                            for (let char of line) {
                                if (char === '"') {
                                    inQuotes = !inQuotes;
                                } else if (char === ',' && !inQuotes) {
                                    fields.push(currentField.trim());
                                    currentField = '';
                                } else {
                                    currentField += char;
                                }
                            }


                            fields.push(currentField.trim());

                            return fields;
                        });
                    }


                    function resetUploadState() {
                        fileUpload.value = '';
                        uploadPreviewTable.innerHTML = '';
                        uploadPreview.style.display = 'none';
                    }


                    uploadBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        fileUpload.click();
                    });


                    fileUpload.addEventListener('change', function (event) {

                        event.stopPropagation();

                        const file = event.target.files[0];


                        if (!file) {
                            console.error('No file selected');
                            resetUploadState();
                            return;
                        }


                        if (file.type !== 'text/csv' && !file.name.toLowerCase().endsWith('.csv')) {
                            alert('Please select a valid CSV file.');
                            resetUploadState();
                            return;
                        }


                        const maxSizeBytes = 5 * 1024 * 1024; // 5MB
                        if (file.size > maxSizeBytes) {
                            alert('File is too large. Maximum file size is 5MB.');
                            resetUploadState();
                            return;
                        }

                        const reader = new FileReader();

                        reader.onload = function (e) {
                            try {
                                const csvData = e.target.result;
                                console.log('FileReader Result:', csvData);


                                const parsedData = parseCSV(csvData);


                                const dataRows = parsedData.slice(1);
                                console.log('Data Rows:', dataRows);


                                uploadPreviewTable.innerHTML = '';


                                let validRowCount = 0;
                                let invalidRows = [];
                                for (let rowIndex = 0; rowIndex < dataRows.length; rowIndex++) {
                                    const row = dataRows[rowIndex];
                                    console.log(`Checking Row ${rowIndex}:`, row);


                                    const isValidRow = row.length === 6 &&
                                        row[0] && // Register No
                                        row[1] && // Name
                                        row[2] && // Year (ensure it's not empty and is a valid number)
                                        !isNaN(Number(row[2])) &&
                                        Number(row[2]) > 0 &&
                                        row[3] && // Semester (ensure it's not empty and is a valid number)
                                        !isNaN(Number(row[3])) &&
                                        Number(row[3]) > 0 &&
                                        row[4] && // Section
                                        row[5]; // Batch

                                    if (isValidRow) {
                                        const tableRow = document.createElement('tr');
                                        tableRow.innerHTML = `
                            <td>${row[0]}</td>
                            <td>${row[1]}</td>
                            <td>${row[2]}</td>
                            <td>${row[3]}</td>
                            <td>${row[4]}</td>
                            <td>${row[5]}</td>
                        `;
                                        uploadPreviewTable.appendChild(tableRow);

                                        validRowCount++;

                                        if (validRowCount >= 5) break;
                                    } else {
                                        invalidRows.push({
                                            rowIndex: rowIndex,
                                            row: row,
                                            reasons: [
                                                row.length !== 6 ? 'Incorrect number of columns' : '',
                                                !row[0] ? 'Missing Register No' : '',
                                                !row[1] ? 'Missing Name' : '',
                                                isNaN(parseInt(row[2])) ? 'Invalid Year' : '',
                                                isNaN(parseInt(row[3])) ? 'Invalid Semester' : '',
                                                !row[4] ? 'Missing Section' : '',
                                                !row[5] ? 'Missing Batch' : ''
                                            ].filter(reason => reason !== '')
                                        });
                                    }
                                }

                                if (validRowCount > 0) {
                                    uploadPreview.style.display = 'block';
                                } else {
                                    console.error('No valid rows found');
                                    console.error('Invalid Rows:', invalidRows);

                                    const errorDetails = invalidRows.map(invalid =>
                                        `Row ${invalid.rowIndex + 2}: ${invalid.reasons.join(', ')}`
                                    ).join('\n');

                                    alert('No valid data found in the CSV file. Please check the file format:\n' +
                                        '- Ensure 6 columns: Register No, Name, Year, Semester, Section, Batch\n' +
                                        '- Check for correct data types\n' +
                                        '- Verify no extra commas or quotes\n\n' +
                                        'Detailed Errors:\n' + errorDetails);
                                    resetUploadState();
                                }
                            } catch (error) {
                                console.error('Catch Block Error:', error);
                                alert('Error processing the CSV file. Please check the file format and try again.');
                                resetUploadState();
                            }
                        };

                        reader.onerror = function (error) {
                            console.error('FileReader Error:', error);
                            alert('Error reading the file. Please try again.');
                            resetUploadState();
                        };


                        reader.readAsText(file, 'UTF-8');
                    });


                    fileUpload.addEventListener('click', function (e) {
                        e.stopPropagation();
                    });

                    confirmUploadBtn.addEventListener('click', function () {
                        const file = fileUpload.files[0];
                        if (file) {
                            const formData = new FormData();
                            formData.append('csvFile', file);

                            fetch('upload_students.php', {
                                method: 'POST',
                                body: formData
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert(data.message);
                                        resetUploadState();
                                    } else {
                                        alert('Upload failed: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Upload Error:', error);
                                    alert('An error occurred during upload.');
                                });
                        }
                    });

                    cancelUploadBtn.addEventListener('click', resetUploadState);


                    const semesterSelect = document.getElementById('semester-select');
                    const courseSelect = document.getElementById('course-select');


                    if (semesterSelect && courseSelect) {

                        const courseOptions = Array.from(courseSelect.querySelectorAll('.course-option'));


                        function filterCourses() {
                            const selectedSemester = semesterSelect.value;


                            courseSelect.innerHTML = '<option value="">Select Course</option>';


                            if (!selectedSemester) {
                                courseSelect.disabled = true;
                                return;
                            }


                            courseSelect.disabled = false;


                            courseOptions.forEach(option => {
                                const optionSemester = option.getAttribute('data-semester');
                                if (optionSemester === selectedSemester) {
                                    courseSelect.appendChild(option.cloneNode(true));
                                }
                            });
                        }


                        semesterSelect.addEventListener('change', filterCourses);


                        filterCourses();
                    }


                    const yearSelect = document.getElementById('year-select');
                    const sectionSelect = document.getElementById('section-select');
                    const nameSearch = document.getElementById('name-search');
                    const registerSearch = document.getElementById('register-search');
                    const studentListContainer = document.getElementById('student-list-container');


                    if (yearSelect && sectionSelect && nameSearch && registerSearch && studentListContainer) {

                        function debounce(func, delay) {
                            let debounceTimer;
                            return function () {
                                const context = this;
                                const args = arguments;
                                clearTimeout(debounceTimer);
                                debounceTimer = setTimeout(() => func.apply(context, args), delay);
                            }
                        }


                        function fetchFilteredStudents() {

                            const params = new URLSearchParams();


                            if (yearSelect.value) {
                                params.append('year', yearSelect.value);
                            }


                            if (sectionSelect.value) {
                                params.append('section', sectionSelect.value);
                            }


                            const nameValue = nameSearch.value.trim();
                            if (nameValue) {
                                params.append('name', nameValue);
                            }


                            const registerValue = registerSearch.value.trim();
                            if (registerValue) {
                                params.append('register_no', registerValue);
                            }


                            fetch('fetch_students.php?' + params.toString())
                                .then(response => response.text())
                                .then(html => {
                                    studentListContainer.innerHTML = html;
                                })
                                .catch(error => {
                                    console.error('Error fetching students:', error);
                                    studentListContainer.innerHTML = '<div class="alert alert-danger">Error loading students</div>';
                                });
                        }


                        yearSelect.addEventListener('change', fetchFilteredStudents);
                        sectionSelect.addEventListener('change', fetchFilteredStudents);


                        nameSearch.addEventListener('input', debounce(fetchFilteredStudents, 300));
                        registerSearch.addEventListener('input', debounce(fetchFilteredStudents, 300));
                    }
                });
            </script>

            </script>
        </div>
    </div>

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
</body>

</html>