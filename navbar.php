<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!function_exists('canAccessDashboard')) {
    
    function canAccessDashboard($requiredRoles) {
        $currentRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;
        return $currentRole !== null && in_array($currentRole, $requiredRoles);
    }
}


function isDashboardAccessAllowed($requestedDashboard) {
    if (!isset($_SESSION['role'])) return false;
    
    $currentRole = $_SESSION['role'];
    
    
    $dashboardAccessRules = [
        'new_faculty_dashboard.php' => ['faculty', 'admin', 'hod'],
        'admin/admin_dashboard.php' => ['admin'],
        'hod_dashboard.php' => ['hod']
    ];
    
  
    return isset($dashboardAccessRules[$requestedDashboard]) && 
           in_array($currentRole, $dashboardAccessRules[$requestedDashboard]);
}

function getCurrentActiveLink() {
    
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    
    $loginRelatedPages = ['login.php', 'forgot_password.php'];
    if (in_array($currentPage, $loginRelatedPages)) {
        
        $role = isset($_GET['role']) ? $_GET['role'] : null;
        
        switch ($role) {
            case 'faculty': return 'faculty';
            case 'admin': return 'admin';
            case 'hod': return 'hod';
        }
        
        if ($currentPage === 'login.php') {
            return 'home';
        }
        
       
        return 'home';
    }
    
    
    $activePageMap = [
        'new_faculty_dashboard.php' => 'faculty',
        'admin/admin_main_page.php' => 'admin',
        'hod_dashboard.php' => 'hod',
        'index.php' => 'home',
        'forgot_password.php' => 'home'
    ];
    
    return $activePageMap[$currentPage] ?? '';
}


$currentActiveLink = getCurrentActiveLink();


$currentPage = basename($_SERVER['PHP_SELF']);
if (in_array($currentPage, ['new_faculty_dashboard.php', 'admin/admin_dashboard.php', 'hod_dashboard.php'])) {
    if (!isDashboardAccessAllowed($currentPage)) {
        
        $roleMap = [
            'new_faculty_dashboard.php' => 'faculty',
            'admin/admin_main_page.php' => 'admin',
            'hod_dashboard.php' => 'hod'
        ];
        $role = $roleMap[$currentPage] ?? 'faculty';
        header("Location: login.php?role=$role");
        exit();
    }
}

// Add this before the navbar HTML
$scriptPath = $_SERVER['SCRIPT_FILENAME'];
$isInAdminDir = (strpos($scriptPath, '/admin/') !== false || strpos($scriptPath, '\\admin\\') !== false);
$isInFacultyDir = (strpos($scriptPath, '/Faculty/') !== false || strpos($scriptPath, '\\Faculty\\') !== false);
$isInHodyDir = (strpos($scriptPath, '/hod/') !== false || strpos($scriptPath, '\\hod\\') !== false);
$isInSubDir = $isInAdminDir || $isInFacultyDir ||  $isInHodyDir ;
$pathPrefix = $isInSubDir ? '../' : '';

// Debug information
error_log("Current SCRIPT_FILENAME: " . $scriptPath);
error_log("isInAdminDir: " . ($isInAdminDir ? 'true' : 'false'));
error_log("isInFacultyDir: " . ($isInFacultyDir ? 'true' : 'false'));
error_log("isInSubDir: " . ($isInSubDir ? 'true' : 'false'));
error_log("pathPrefix: " . $pathPrefix);

?>

<nav class="navbar">
    <div class="nav-left">
        <div class="logo-container">
            <img src="<?= $pathPrefix ?>ptu-logo.png" alt="PTU Logo" class="ptu-logo">
            <div class="logo-text">
                <a href="<?= $pathPrefix ?>index.php" class="college-name <?= $currentActiveLink === 'home' ? 'active' : '' ?>">Puducherry Technological University</a>
                <span class="tool-name">OBE Assist Tool</span>
            </div>
        </div>
    </div>
    <div class="nav-right">
        <div class="nav-links">
            <?php if (canAccessDashboard(['faculty', 'admin', 'hod'])): ?>
                <a href="<?= $pathPrefix ?>Faculty/new_faculty_dashboard.php" class="<?= $currentActiveLink === 'faculty' ? 'active' : '' ?>">Faculty</a>
            <?php else: ?>
                <a href="<?= $pathPrefix ?>login.php?role=faculty" class="<?= $currentActiveLink === 'faculty' ? 'active' : '' ?>">Faculty</a>
            <?php endif; ?>

            <?php if (canAccessDashboard(['admin'])): ?>
                <a href="<?= $pathPrefix ?>admin/admin_main_page.php" class="<?= $currentActiveLink === 'admin' ? 'active' : '' ?>">Admin</a>
            <?php else: ?>
                <a href="<?= $pathPrefix ?>login.php?role=admin" class="<?= $currentActiveLink === 'admin' ? 'active' : '' ?>">Admin</a>
            <?php endif; ?>

            <?php if (canAccessDashboard(['hod'])): ?>
                <a href="<?= $pathPrefix ?>hod/hod_dashboard.php" class="<?= $currentActiveLink === 'hod' ? 'active' : '' ?>">HOD</a>
            <?php else: ?>
                <a href="<?= $pathPrefix ?>login.php?role=hod" class="<?= $currentActiveLink === 'hod' ? 'active' : '' ?>">HOD</a>
            <?php endif; ?>

            <a href="<?= $pathPrefix ?>index.php#about" class="<?= $currentActiveLink === 'home' ? 'active' : '' ?>">About</a>
        </div>
    </div>
</nav>