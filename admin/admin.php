<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Faculty Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input[type="submit"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #d6e9c6;
            border-radius: 4px;
        }
        .error-message {
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ebccd1;
            border-radius: 4px;
        }
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Course Faculty Assignment</h2>
        
        <?php
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "oats";
        $port = 3306;
        
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            die("<div class='error-message'>Connection failed: " . $conn->connect_error . "</div>");
        }
        
        // Process first form submission
        if (isset($_POST['step1_submit'])) {
            $program = $conn->real_escape_string($_POST['program']);
            $semester = $conn->real_escape_string($_POST['semester']);
            $section = isset($_POST['section']) ? $conn->real_escape_string($_POST['section']) : '';
            
            // For M.Tech, set default section value
            if ($program == 'M.Tech') {
                $section = 'NA'; // Not Applicable for M.Tech
            }
            
            // Query to fetch courses based on selection
            $sql = "SELECT code, name, credits, no_of_co FROM course 
                   WHERE type = '$program' AND semester = '$semester'";
            
            $result = $conn->query($sql);
            
            // Query to fetch faculty
            $faculty_sql = "SELECT id, name FROM faculty WHERE role = 'faculty'";
            $faculty_result = $conn->query($faculty_sql);
            
            if ($result && $result->num_rows > 0) {
                echo "<h3>Available Courses for $program - Semester $semester</h3>";
                
                echo "<form method='post' action=''>";
                echo "<input type='hidden' name='program' value='$program'>";
                echo "<input type='hidden' name='semester' value='$semester'>";
                echo "<input type='hidden' name='section' value='$section'>";
                
                echo "<table>";
                echo "<tr><th>Code</th><th>Course Name</th><th>Credits</th><th>CO Count</th><th>Faculty</th></tr>";
                
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row["code"] . "</td>";
                    echo "<td>" . $row["name"] . "</td>";
                    echo "<td>" . $row["credits"] . "</td>";
                    echo "<td>" . $row["no_of_co"] . "</td>";
                    echo "<td>";
                    echo "<input type='hidden' name='course_id[]' value='" . $row["code"] . "'>";
                    
                    echo "<select name='faculty_id[]' required>";
                    echo "<option value=''>Select Faculty</option>";
                    
                    // Reset faculty pointer to beginning
                    if ($faculty_result) {
                        $faculty_result->data_seek(0);
                        
                        while($faculty_row = $faculty_result->fetch_assoc()) {
                            echo "<option value='" . $faculty_row["id"] . "'>" . $faculty_row["name"] . "</option>";
                        }
                    } else {
                        echo "<option value=''>No faculty found</option>";
                    }
                    
                    echo "</select>";
                    echo "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                echo "<input type='submit' name='step2_submit' value='Assign Faculty'>";
                echo "</form>";
                
                echo "<br><a href='javascript:history.back()'>Go Back</a>";
            } else {
                echo "<div class='error-message'>No courses found for the selected criteria.</div>";
                if ($conn->error) {
                    echo "<div class='error-message'>Database error: " . $conn->error . "</div>";
                }
                echo "<a href='javascript:history.back()'>Go Back</a>";
            }
        }
        
        // Process second form submission
        elseif (isset($_POST['step2_submit'])) {
            $program = $conn->real_escape_string($_POST['program']);
            $section = $conn->real_escape_string($_POST['section']);
            $course_ids = $_POST['course_id'];
            $faculty_ids = $_POST['faculty_id'];
            
            // Debug information
            echo "<div class='debug-info'>";
            echo "Program: $program<br>";
            echo "Section: $section<br>";
            echo "Course IDs: " . print_r($course_ids, true) . "<br>";
            echo "Faculty IDs: " . print_r($faculty_ids, true) . "<br>";
            echo "</div>";
            
            $success_count = 0;
            $error_messages = [];
            
            // Check if faculty_course table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'faculty_courses'");
            if ($table_check->num_rows == 0) {
                // Create the table if it doesn't exist
                $create_table_sql = "CREATE TABLE faculty_courses (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    faculty_id VARCHAR(50) NOT NULL,
                    course_id VARCHAR(50) NOT NULL,
                    section VARCHAR(10) NOT NULL,
                    program VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                
                if ($conn->query($create_table_sql) === TRUE) {
                    echo "<div class='success-message'>Created faculty_courses table.</div>";
                } else {
                    echo "<div class='error-message'>Error creating table: " . $conn->error . "</div>";
                }
            }
            
            // Loop through each course and faculty assignment
            for ($i = 0; $i < count($course_ids); $i++) {
                if (!empty($faculty_ids[$i])) {
                    $faculty_id = $conn->real_escape_string($faculty_ids[$i]);
                    $course_id = $conn->real_escape_string($course_ids[$i]);
                    
                    // Explicitly show the query being executed
                    $insert_sql = "INSERT INTO faculty_courses (faculty_id, course_id, section, program) 
                               VALUES ('$faculty_id', '$course_id', '$section', '$program')";
                    
                    echo "<div class='debug-info'>Executing SQL: $insert_sql</div>";
                    
                    if ($conn->query($insert_sql) === TRUE) {
                        $success_count++;
                    } else {
                        $error_messages[] = "Error assigning faculty to course $course_id: " . $conn->error;
                    }
                }
            }
            
            if ($success_count > 0) {
                echo "<div class='success-message'>Successfully assigned $success_count course(s) to faculty members.</div>";
            } else {
                echo "<div class='error-message'>No assignments were saved.</div>";
            }
            
            if (!empty($error_messages)) {
                echo "<div class='error-message'>";
                foreach ($error_messages as $message) {
                    echo "$message<br>";
                }
                echo "</div>";
            }
            
            echo "<a href='" . $_SERVER['PHP_SELF'] . "'>Start New Assignment</a>";
        }
        
        // Initial form
        else {
        ?>
            <form method="post" action="">
                <label for="program">Program:</label>
                <select id="program" name="program" required onchange="updateSemesters()">
                    <option value="">Select Program</option>
                    <option value="B.Tech">B.Tech</option>
                    <option value="M.Tech">M.Tech</option>
                </select>
                
                <label for="semester">Semester:</label>
                <select id="semester" name="semester" required>
                    <option value="">Select Semester</option>
                </select>
                
                <div id="section_div">
                    <label for="section">Section:</label>
                    <select id="section" name="section">
                        <option value="">Select Section</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                    </select>
                </div>
                
                <input type="submit" name="step1_submit" value="Find Courses">
            </form>
            
            <script>
                function updateSemesters() {
                    var program = document.getElementById('program').value;
                    var semesterSelect = document.getElementById('semester');
                    var sectionDiv = document.getElementById('section_div');
                    
                    // Clear existing options
                    semesterSelect.innerHTML = '<option value="">Select Semester</option>';
                    
                    if (program === 'B.Tech') {
                        for (var i = 3; i <= 8; i++) {
                            var option = document.createElement('option');
                            option.value = i;
                            option.text = i;
                            semesterSelect.appendChild(option);
                        }
                        sectionDiv.style.display = 'block';
                    } else if (program === 'M.Tech') {
                        for (var i = 1; i <= 4; i++) {
                            var option = document.createElement('option');
                            option.value = i;
                            option.text = i;
                            semesterSelect.appendChild(option);
                        }
                        sectionDiv.style.display = 'none';
                    }
                }
            </script>
        <?php
        }
        ?>
        
        <!-- Database Structure Information -->
        <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 15px;">
            <h3>Required Database Structure</h3>
            <p>This application requires the following database tables:</p>
            
            <pre style="background: #f5f5f5; padding: 10px; overflow: auto;">

            </pre>
        </div>
    </div>
</body>
</html>