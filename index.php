<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OBE Assist Tool</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <?php
    
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            
            unset($_SESSION['error_message']);
        }
        ?>
        
        <section class="hero">
            <h1>OBE Assist Tool</h1>
            <p>Simplifying Outcome-Based Education for Faculties.</p>
            <div class="primary-btn-container">
            <div class="primary-btn-container">
                <a href="login.php?role=faculty" class="primary-btn">Get Started</a>            
            </div>
        </section>

        <section class="solutions">
            <div class="solution-cards">
                <div class="card">
                    <i class="fas fa-chart-line"></i>
                    <h3>OBE-Based Analytics</h3>
                    <p>Track student performance based on Course Outcomes (COs).</p>
                </div>
                <div class="card">
                    <i class="fas fa-project-diagram"></i>
                    <h3>Automated CO Calculation</h3>
                    <p> Automatically evaluate CO attainment levels using assessment data</p>
                </div>
                <div class="card">
                    <i class="fas fa-tachometer-alt"></i>
                    <h3>Dashboards</h3>
                    <p>Manage assignments, CAT marks, and track progress.</p>
                </div>
                <div class="card">
                    <i class="fas fa-file-alt"></i>
                    <h3>Comprehensive Reports</h3>
                    <p>Generate visual reports for accreditation & decision-making.</p>
                </div>
            </div>
        </section>

        
    </div>

    <script src="script.js"></script>
</body>
</html>
