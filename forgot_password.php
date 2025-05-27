<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


$message = '';
$error = '';


require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Debug information
    $message .= "<p style='color: blue; text-align: center;'>Form submitted. Email: " . htmlspecialchars($email) . "</p>";

   
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $message = "<p style='color: red; text-align: center;'>All fields are required.</p>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<p style='color: red; text-align: center;'>Passwords do not match.</p>";
    } else {
       
        if ($conn->connect_error) {
            $message = "<p style='color: red; text-align: center;'>Connection failed: " . $conn->connect_error . "</p>";
        } else {
           
            $stmt = $conn->prepare("SELECT id FROM faculty WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();


            if ($stmt->num_rows > 0) {
             
                $update_stmt = $conn->prepare("UPDATE faculty SET password = ? WHERE email = ?");
                $update_stmt->bind_param("ss", $new_password, $email);
               
                if ($update_stmt->execute()) {
                    $message = "<p style='color: green; text-align: center;'>Password successfully updated. You can now log in with your new password.</p>";
                } else {
                    $message = "<p style='color: red; text-align: center;'>Error updating password: " . $update_stmt->error . "</p>";
                }
                $update_stmt->close();
            } else {
                $message = "<p style='color: red; text-align: center;'>Email not found in our system.</p>";
            }
           
            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - OBE Assist Tool</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="login.css">
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
            <h1>Reset Password</h1>
           
            <?php
         
            if (!empty($message)) {
                echo $message;
            }
            ?>
           
            <form class="login-form" method="POST" action="forgot_password.php">
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>
               
                <div class="form-group password-group">
                    <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
                    <button type="button" class="toggle-password">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
               
                <div class="form-group password-group">
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm New Password" required>
                    <button type="button" class="toggle-password">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
               
                <button type="submit" class="login-btn">Reset Password</button>
               
                <div class="login-footer">
                    <a href="login.php?role=faculty" class="reset-password">Back to Login</a>
                </div>
            </form>
        </div>
    </main>


    <script src="script.js"></script>
    <script src="login.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('.login-form');
        const newPasswordInput = form.querySelector('input[name="new_password"]');
        const confirmPasswordInput = form.querySelector('input[name="confirm_password"]');

        form.addEventListener('submit', (e) => {
            if (newPasswordInput.value !== confirmPasswordInput.value) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    });
    </script>
</body>
</html>
