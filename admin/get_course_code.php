<?php
// Turn off PHP's default error display for production
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

try {
    


    $servername = "localhost";
$username = "root";
$password = "";
$dbname = "newoat";
$port = 3306;


$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) 
{
  die("Connection failed: " . $conn->connect_error);
}

    
    // Clear any previous output
    ob_clean();
    

    header('Content-Type: application/json');
   
    if (isset($_GET['name']) && !empty($_GET['name'])) {
        $course_name = $_GET['name'];
        
        // Prepare the statement
        $stmt = $conn->prepare("SELECT code FROM course_detail WHERE name = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $course_name);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(['code' => $row['code']]);
        } else {
            echo json_encode(['code' => '', 'error' => 'Course not found']);
        }
        
        $stmt->close();
        $conn->close();
    } else {
        echo json_encode([
            'code' => '',
            'error' => 'Course name not provided'
        ]);
    }
} catch (Exception $e) {
   
    ob_clean();
    
    error_log("Database error in get_course_code.php: " . $e->getMessage());
    
 
    header('Content-Type: application/json');
    echo json_encode([
        'code' => '',
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

ob_end_flush();
?>