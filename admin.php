<?php
// ============================================================
// admin.php — Munch Admin Dashboard with AJAX
// ============================================================
require_once 'DBconfig.php';

// Check admin session
requireAdmin();

// Get admin info
$adminID = (int)$_SESSION['user_id'];
$stmt = executeQuery($pdo, "SELECT * FROM user WHERE id = ?", [$adminID]);
if (!$stmt || $stmt->rowCount() === 0) {
    session_destroy();
    header("Location: login.php");
    exit();
}
$admin = $stmt->fetch();

// Get all recipe reports
$reportsStmt = executeQuery($pdo,
    "SELECT 
         rp.id AS report_id,
         rec.id AS recipe_id,
         rec.name AS recipe_name,
         u.id AS creator_id,
         u.firstName AS creator_first,
         u.lastName AS creator_last,
         u.photoFileName AS creator_photo
     FROM report rp
     JOIN recipe rec ON rp.recipeID = rec.id
     JOIN user u ON rec.userID = u.id
     ORDER BY rp.id DESC"
);
$reports = ($reportsStmt) ? $reportsStmt->fetchAll() : [];

// Get all blocked users
$blockedStmt = executeQuery($pdo, "SELECT * FROM blockeduser ORDER BY id DESC");
$blockedUsers = ($blockedStmt) ? $blockedStmt->fetchAll() : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Munch</title>
    <link rel="stylesheet" href="style.css">
    <!-- jQuery Library -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>
<body class="admin-page">

<div class="page admin-page">

    <!-- Sign-out link -->
    <div class="logout-box">
        <a href="signout.php">LOG-OUT</a>
    </div>

    <div class="admin-container">

        <h1>Welcome <?php echo htmlspecialchars($admin['firstName']); ?>!</h1>

        <!-- Admin Information -->
        <div class="admin-info-box">
            <h3>My Information</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['emailAddress']); ?></p>
        </div>

        <!-- Message Container for AJAX responses -->
        <div id="ajax-message" style="display:none; margin:10px 0; padding:10px; border-radius:5px;"></div>

        <!-- Reported Recipes Table -->
        <div class="section">
            <h3>Reported Recipes</h3>
            <?php if (empty($reports)): ?>
                <p id="no-reports-message">No reported recipes at this time.</p>
            <?php else: ?>
                <table class="bakery-table" id="reports-table">
                    <thead>
                        <tr>
                            <th>Recipe Name</th>
                            <th>Recipe Creator</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="reports-tbody">
                        <?php foreach ($reports as $report): ?>
                        <tr id="report-row-<?php echo (int)$report['report_id']; ?>" data-report-id="<?php echo (int)$report['report_id']; ?>" data-recipe-id="<?php echo (int)$report['recipe_id']; ?>" data-creator-id="<?php echo (int)$report['creator_id']; ?>">
                            <td>
                                <a href="view-recipe.php?id=<?php echo (int)$report['recipe_id']; ?>" class="recipe-link">
                                    <?php echo htmlspecialchars($report['recipe_name']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="creator-info">
                                    <span><?php echo htmlspecialchars($report['creator_first'] . ' ' . $report['creator_last']); ?></span>
                                    <img src="<?php echo !empty($report['creator_photo']) ? 'images/' . htmlspecialchars($report['creator_photo']) : 'images/defult-image.png'; ?>" 
                                         class="table-avatar" 
                                         alt="User Photo"
                                         onerror="this.src='images/defult-image.png'">
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <label>
                                        <input type="radio" name="action_<?php echo $report['report_id']; ?>" value="block_user" class="action-radio" data-report-id="<?php echo $report['report_id']; ?>"> Block User
                                    </label>
                                    <label>
                                        <input type="radio" name="action_<?php echo $report['report_id']; ?>" value="dismiss_report" class="action-radio" data-report-id="<?php echo $report['report_id']; ?>"> Dismiss Report
                                    </label>
                                    <br>
                                    <button type="button" class="table-btn submit-action" data-report-id="<?php echo $report['report_id']; ?>">Submit</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Blocked Users Table -->
        <div class="section">
            <h3>Blocked Users List</h3>
            <?php if (empty($blockedUsers)): ?>
                <p>No blocked users at this time.</p>
            <?php else: ?>
                <table class="bakery-table blocked-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blockedUsers as $blocked): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($blocked['firstName'] . ' ' . $blocked['lastName']); ?></td>
                            <td><?php echo htmlspecialchars($blocked['emailAddress']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>

<footer class="site-footer">
    <div class="container footer-box">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>Find us</h4>
                <ul class="social">
                    <li><a href="#">X</a></li>
                    <li><a href="#">f</a></li>
                    <li><a href="#">in</a></li>
                </ul>
            </div>
            <div class="footer-col center">
                <div class="brand">
                    <img src="images/Bakery1.png" alt="Bakery logo">
                </div>
                <small>&copy;2026 Munch Bakery. All rights reserved</small>
            </div>
            <div class="footer-col right">
                <h4>Contact Info</h4>
                <p>+966537282741</p>
                <p><a href="mailto:bakery@gmail.com" style="color:#FFB575;">Bakery@gmail.com</a></p>
            </div>
        </div>
    </div>
</footer>

<script>
$(document).ready(function() {
    
    // Function to show message
    function showMessage(message, isSuccess) {
        var msgDiv = $('#ajax-message');
        msgDiv.removeClass().addClass(isSuccess ? 'success-message' : 'error-message');
        msgDiv.html(message);
        msgDiv.fadeIn();
        
        // Auto hide after 3 seconds
        setTimeout(function() {
            msgDiv.fadeOut();
        }, 3000);
    }
    
    // Handle submit button click
    $('.submit-action').on('click', function() {
        var $button = $(this);
        var reportId = $button.data('report-id');
        var $row = $('#report-row-' + reportId);
        var $radioSelected = $('input[name="action_' + reportId + '"]:checked');
        
        // Validate that an action is selected
        if ($radioSelected.length === 0) {
            showMessage('Please select an action (Block User or Dismiss Report)', false);
            return;
        }
        
        var selectedAction = $radioSelected.val();
        var recipeId = $row.data('recipe-id');
        var creatorId = $row.data('creator-id');
        
        // Disable button and show loading state
        $button.prop('disabled', true).text('Processing...');
        
        // Prepare data for AJAX
        var requestData = {
            report_id: reportId,
            recipe_id: recipeId,
            creator_id: creatorId,
            action: selectedAction
        };
        
        // Send AJAX request
        $.ajax({
            url: 'ajax_handle_report.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(requestData),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Remove the row from the table
                    $row.fadeOut(400, function() {
                        $(this).remove();
                        
                        // Check if no more rows in tbody
                        if ($('#reports-tbody tr').length === 0) {
                            // Replace table with "no reports" message
                            $('.section:first .bakery-table').remove();
                            $('.section:first').append('<p id="no-reports-message">No reported recipes at this time.</p>');
                        }
                    });
                    
                    showMessage(response.message, true);
                } else {
                    showMessage(response.message, false);
                    $button.prop('disabled', false).text('Submit');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                showMessage('An error occurred while processing your request. Please try again.', false);
                $button.prop('disabled', false).text('Submit');
            }
        });
    });
    
    // Optional: Prevent form submission on Enter key
    $(document).on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
        }
    });
    
});
</script>

</body>
</html>
