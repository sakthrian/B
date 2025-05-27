<?php

include '../config.php'; 


$id = 0;
$type = '';
$regulation = '';
$batch = '';
$error_message = '';
$success_message = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Fetch the existing mapping data
    $stmt = $conn->prepare("SELECT * FROM curriculum_mapping WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $type = $row['type'];
        $regulation = $row['regulation'];
        $batch = $row['batch'];
    } else {
        $error_message = "Mapping not found";
    }
    $stmt->close();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_mapping'])) {
    $id = intval($_POST['id']);
    $type = $_POST['type'];
    $regulation = intval($_POST['regulation']);
    $batch = $_POST['batch'];
    
    // Validate data
    if (empty($type) || empty($regulation) || empty($batch)) {
        $error_message = "All fields are required!";
    } else {
        // Update the mapping in the database
        $stmt = $conn->prepare("UPDATE curriculum_mapping SET type = ?, regulation = ?, batch = ? WHERE id = ?");
        $stmt->bind_param("sisi", $type, $regulation, $batch, $id);
        
        if ($stmt->execute()) {
            $success_message = "Mapping updated successfully!";
            // Redirect after short delay
            header("Refresh:2; url=admin_main_page.php#curriculum-mapping-section");
        } else {
            $error_message = "Error updating mapping: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Curriculum Mapping</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Edit Curriculum-Batch Mapping</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (empty($error_message) || !empty($id)): ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="form-group">
                    <label for="type">Program Type:</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="">Select</option>
                        <option value="B.Tech" <?php echo ($type == 'B.Tech') ? 'selected' : ''; ?>>B.Tech</option>
                        <option value="M.Tech IS" <?php echo ($type == 'M.Tech IS') ? 'selected' : ''; ?>>M.Tech IS</option>
                        <option value="M.Tech DS" <?php echo ($type == 'M.Tech DS') ? 'selected' : ''; ?>>M.Tech DS</option>
                        <option value="MCA" <?php echo ($type == 'MCA') ? 'selected' : ''; ?>>MCA</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="regulation">Regulation:</label>
                    <select id="regulation" name="regulation" class="form-control" required>
                        <option value="">Select</option>
                        <?php
                        $current_year = date('Y');
                        for ($year = 2021; $year <= $current_year + 20; $year++) {
                            $selected = ($regulation == $year) ? 'selected' : '';
                            echo "<option value='$year' $selected>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="batch">Batch:</label>
                    <input type="text" id="batch" name="batch" class="form-control" value="<?php echo htmlspecialchars($batch); ?>" placeholder="e.g., 2023-27" required>
                </div>
                
                <div class="form-group buttons">
                    <button type="submit" name="update_mapping" class="btn btn-primary">Update Mapping</button>
                    <a href="curriculum.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center">
                <a href="curriculum.php" class="btn btn-primary">Back to Curriculum Management</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validate batch format
        document.getElementById('batch').addEventListener('blur', function() {
            const batchPattern = /^\d{4}-\d{2,4}$/;
            if (this.value && !batchPattern.test(this.value)) {
                this.classList.add('error');
                alert('Please enter batch in format YYYY-YY or YYYY-YYYY (e.g., 2023-27)');
            } else {
                this.classList.remove('error');
            }
        });
    });
    </script>
</body>
</html>