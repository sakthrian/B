<?php

require_once '../config.php';


if (!function_exists('getSemesterSuffix')) {
    function getSemesterSuffix($semester) {
        return $semester == 3 ? '3rd' : $semester . 'th';
    }
}


$year = isset($_GET['year']) ? $_GET['year'] : null;
$section = isset($_GET['section']) ? $_GET['section'] : null;
$name = isset($_GET['name']) ? $_GET['name'] : null;
$register_no = isset($_GET['register_no']) ? $_GET['register_no'] : null;


$query = "
    SELECT 
        register_no, 
        name, 
        year, 
        semester, 
        section,
        batch
    FROM 
        student 
    WHERE 1=1
";
$params = [];
$types = '';

if ($year) {
    $query .= " AND year = ?";
    $params[] = $year;
    $types .= 'i';
}

if ($section) {
    $query .= " AND section = ?";
    $params[] = $section;
    $types .= 's';
}

if ($name) {
    $query .= " AND (name LIKE ? OR name LIKE ?)";
    $params[] = $name . '%';  
    $params[] = '%' . $name . '%';  
    $types .= 'ss';
}


if ($register_no) {
    $query .= " AND (register_no LIKE ? OR register_no LIKE ?)";
    $params[] = $register_no . '%';  
    $params[] = '%' . $register_no . '%'; 
    $types .= 'ss';
}


$query .= " ORDER BY year, semester, section, name LIMIT 100";


$stmt = $conn->prepare($query);


if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$student_result = $stmt->get_result();


if ($student_result->num_rows > 0) {
    ?>
       <table id="student-table">
        <thead>
            <tr>
                <th>Register No</th>
                <th>Name</th>
                <th>Year</th>
                <th>Semester</th>
                <th>Section</th>
                <th>Batch</th>
            </tr>
        </thead>
        <tbody>
            <?php
            
            while ($student = $student_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($student['register_no']) . "</td>";
                echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                echo "<td>" . htmlspecialchars($student['year']) . "</td>";
                echo "<td>" . getSemesterSuffix($student['semester']) . "</td>";
                echo "<td>" . htmlspecialchars($student['section']) . "</td>";
                echo "<td>" . htmlspecialchars($student['batch']) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="table-summary">
        <p>Total Students: <?php echo $student_result->num_rows; ?></p>
    </div>

    <?php
} else {
   
    echo "<div class='alert alert-info'>No students found matching the search criteria.</div>";
}


$stmt->close();
$conn->close();
?>
