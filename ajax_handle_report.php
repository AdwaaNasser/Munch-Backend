<?php
// ============================================================
// ajax_handle_report.php — AJAX Handler for Admin Actions
// Returns JSON response for AJAX requests
// ============================================================
require_once 'DBconfig.php';

// Set JSON header
header('Content-Type: application/json');

// Only accept POST requests via AJAX
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Verify admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data (supports both JSON and form data)
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $reportID = isset($input['report_id']) ? (int)$input['report_id'] : 0;
    $recipeID = isset($input['recipe_id']) ? (int)$input['recipe_id'] : 0;
    $creatorID = isset($input['creator_id']) ? (int)$input['creator_id'] : 0;
    $action = isset($input['action']) ? $input['action'] : '';
} else {
    $reportID = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
    $recipeID = isset($_POST['recipe_id']) ? (int)$_POST['recipe_id'] : 0;
    $creatorID = isset($_POST['creator_id']) ? (int)$_POST['creator_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
}

// Validate inputs
if ($reportID <= 0 || $recipeID <= 0 || $creatorID <= 0 || !in_array($action, ['block_user', 'dismiss_report'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit();
}

// Helper function to delete recipe files
function deleteRecipeFiles($recipe) {
    if (!empty($recipe['photoFileName'])) {
        $photoPath = 'images/' . $recipe['photoFileName'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }
    if (!empty($recipe['videoFilePath'])) {
        $videoPath = $recipe['videoFilePath'];
        if (file_exists($videoPath)) {
            unlink($videoPath);
        }
    }
}

try {
    $pdo->beginTransaction();
    
    if ($action === 'block_user') {
        // 1. Delete the specific report
        executeQuery($pdo, "DELETE FROM report WHERE id = ?", [$reportID]);
        
        // 2. Get user information
        $userStmt = executeQuery($pdo, "SELECT * FROM user WHERE id = ?", [$creatorID]);
        
        if ($userStmt && $userStmt->rowCount() > 0) {
            $user = $userStmt->fetch();
            
            // 3. Get all recipes by this user
            $recipesStmt = executeQuery($pdo, "SELECT * FROM recipe WHERE userID = ?", [$creatorID]);
            
            if ($recipesStmt && $recipesStmt->rowCount() > 0) {
                $recipes = $recipesStmt->fetchAll();
                
                foreach ($recipes as $recipe) {
                    $currentRecipeID = $recipe['id'];
                    deleteRecipeFiles($recipe);
                    
                    // Delete related data
                    executeQuery($pdo, "DELETE FROM ingredients WHERE recipeID = ?", [$currentRecipeID]);
                    executeQuery($pdo, "DELETE FROM instructions WHERE recipeID = ?", [$currentRecipeID]);
                    executeQuery($pdo, "DELETE FROM likes WHERE recipeID = ?", [$currentRecipeID]);
                    executeQuery($pdo, "DELETE FROM favourites WHERE recipeID = ?", [$currentRecipeID]);
                    executeQuery($pdo, "DELETE FROM comment WHERE recipeID = ?", [$currentRecipeID]);
                    executeQuery($pdo, "DELETE FROM report WHERE recipeID = ?", [$currentRecipeID]);
                }
                
                executeQuery($pdo, "DELETE FROM recipe WHERE userID = ?", [$creatorID]);
            }
            
            // Delete user activity
            executeQuery($pdo, "DELETE FROM comment WHERE userID = ?", [$creatorID]);
            executeQuery($pdo, "DELETE FROM likes WHERE userID = ?", [$creatorID]);
            executeQuery($pdo, "DELETE FROM favourites WHERE userID = ?", [$creatorID]);
            executeQuery($pdo, "DELETE FROM report WHERE userID = ?", [$creatorID]);
            
            // Delete profile photo
            if (!empty($user['photoFileName'])) {
                $photoPath = 'images/' . $user['photoFileName'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            
            // Add to blocked users
            $checkBlocked = executeQuery($pdo, "SELECT * FROM blockeduser WHERE emailAddress = ?", [$user['emailAddress']]);
            if ($checkBlocked && $checkBlocked->rowCount() == 0) {
                executeQuery($pdo, 
                    "INSERT INTO blockeduser (firstName, lastName, emailAddress) VALUES (?, ?, ?)",
                    [$user['firstName'], $user['lastName'], $user['emailAddress']]
                );
            }
            
            // Delete user
            executeQuery($pdo, "DELETE FROM user WHERE id = ?", [$creatorID]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'User has been blocked and all content removed']);
        
    } elseif ($action === 'dismiss_report') {
        // Delete ONLY the specific report
        $result = executeQuery($pdo, "DELETE FROM report WHERE id = ?", [$reportID]);
        
        if ($result && $result->rowCount() > 0) {
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Report dismissed successfully']);
        } else {
            throw new Exception('Report not found or already deleted');
        }
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("AJAX Admin action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

exit();
?>
