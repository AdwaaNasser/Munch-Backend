<?php
require_once 'DBconfig.php';
requireLogin();

$recipeID = $_POST['id'] ?? 0;

if (!$recipeID) {
    echo 'false';
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM recipe WHERE id = ?");
$stmt->execute([$recipeID]);
$recipe = $stmt->fetch();

if (!$recipe) {
    echo 'false';
    exit();
}

if ($recipe['userID'] != $_SESSION['user_id']) {
    echo 'false';
    exit();
}

// Delete photo file
if (!empty($recipe['photoFileName'])) {
    $photoPath = "images/" . $recipe['photoFileName'];
    if (file_exists($photoPath)) unlink($photoPath);
}

// Delete video file
if (!empty($recipe['videoFilePath'])) {
    $videoPath = "videos/" . $recipe['videoFilePath'];
    if (file_exists($videoPath)) unlink($videoPath);
}

// Delete recipe (CASCADE handles the rest)
$stmt = $pdo->prepare("DELETE FROM recipe WHERE id = ?");
$stmt->execute([$recipeID]);

echo 'true';
?>
