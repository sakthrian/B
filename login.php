<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

try {
   
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $ROLE_PERMISSIONS = [
        'admin' => ['admin', 'faculty', 'hod'],
        'hod' => ['hod', 'faculty'],
        'faculty' => ['faculty']
    ];

    $message = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      
        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';
        $requested_role = $_GET['role'] ?? $_POST['role'] ?? null;

        $stmt = $conn->prepare("SELECT id, password, role FROM faculty WHERE email = ?");
        
        if (!$stmt) {
            $message = "<p style='color: red; text-align: center;'>Database Error: " . $conn->error . "</p>";
            exit;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            $message = "<p style='color: red; text-align: center;'>User not found!</p>";
        } else {
            $stmt->bind_result($id, $stored_password, $db_role);
            $stmt->fetch();

            if ($password !== $stored_password) {
                $message = "<p style='color: red; text-align: center;'>Incorrect password!</p>";
            } else {
                
                $is_role_allowed = isset($ROLE_PERMISSIONS[$db_role]) && 
                    in_array($requested_role, $ROLE_PERMISSIONS[$db_role]);

                if (!$is_role_allowed) {
                    $message = "<p style='color: red; text-align: center;'>Access Restricted: Please login through your designated dashboard.</p>";
                    session_destroy();
                } else {
                    $_SESSION["user_id"] = $id;
                    $_SESSION["role"] = $db_role;

                    $name_stmt = $conn->prepare("SELECT name FROM faculty WHERE id = ?");
                    $name_stmt->bind_param("i", $id);
                    $name_stmt->execute();
                    $name_stmt->bind_result($name);
                    $name_stmt->fetch();
                    $name_stmt->close();

                    // Set session variables for HOD
                    if ($db_role === 'hod') {
                        $_SESSION["hod_name"] = $name;
                        $_SESSION["faculty_name"] = $name; // Set faculty name for HOD
                        $_SESSION["faculty_id"] = $id; // Ensure faculty_id is set for HOD
                    }

                    switch ($db_role) {
                        case 'admin':
                            $_SESSION["admin_name"] = $name;
                            $_SESSION["faculty_name"] = $name;
                            $_SESSION["faculty_id"] = $id;
                            
                            if ($requested_role === 'admin') {
                                header("Location: admin/admin_main_page.php");
                                exit();
                            } elseif ($requested_role === 'faculty') {
                                header("Location: Faculty/new_faculty_dashboard.php");
                                exit();
                            } else {
                                $message = "<p style='color: red; text-align: center;'>Access Restricted: Invalid dashboard access.</p>";
                            }
                            break;

                        case 'hod':
                            if ($requested_role === 'hod') {
                                header("Location: hod/hod_dashboard.php");
                                exit();
                            } elseif ($requested_role === 'faculty') {
                                header("Location:Faculty/new_faculty_dashboard.php");
                                exit();
                            } else {
                                $message = "<p style='color: red; text-align: center;'>Access Restricted: Invalid dashboard access.</p>";
                            }
                            break;

                        case 'faculty':
                            $_SESSION["faculty_name"] = $name;
                            $_SESSION["faculty_id"] = $id;
                            
                            if ($requested_role === 'faculty') {
                                header("Location: Faculty/new_faculty_dashboard.php");
                                exit();
                            } else {
                                $message = "<p style='color: red; text-align: center;'>Access Restricted: Invalid dashboard access.</p>";
                            }
                            break;
                    }
                }
            }
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $message = "<p style='color: red; text-align: center;'>Error: " . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OBE Assist Tool</title>
    <link rel="stylesheet" href="styles.css?v=<?= time() ?>">
    <link rel="stylesheet" href="navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="login.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php
    include 'navbar.php';
    ?>

    <main>
        <div class="login-container">
            <h1>Welcome</h1>
            
            <?php
            if (!empty($message)) {
                echo '<div class="form-error-message">' . $message . '</div>';
            }
            ?>
            
            <form class="login-form" method="POST">
                <?php 
                $current_role = isset($_GET['role']) ? htmlspecialchars($_GET['role']) : '';
                ?>
                <input type="hidden" name="role" value="<?= $current_role ?>">
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>
                <div class="form-group password-group">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <button type="button" class="toggle-password">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
                <button type="submit" class="login-btn">Login</button>
                <div class="login-footer">
                    <a href="forgot_password.php" class="reset-password">Forgot Password?</a>
                </div>
            </form>
        </div>
    </main>

    <script src="script.js"></script>
    <script src="login.js"></script>
</body>
</html>
</create_file>
