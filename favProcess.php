<?php
require_once 'DBconfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "false";
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo "false";
    exit();
}

$userID = $_SESSION['user_id'];
$recipeID = $_POST['recipeID'] ?? 0;

// check existing favourite
$stmt = $pdo->prepare("
    SELECT * FROM favourites
    WHERE userID=? AND recipeID=?
");

$stmt->execute([$userID, $recipeID]);

if ($stmt->rowCount() > 0) {
    echo "false";
    exit();
}

// insert favourite
$stmt = $pdo->prepare("
    INSERT INTO favourites(userID, recipeID)
    VALUES(?,?)
");

if ($stmt->execute([$userID, $recipeID])) {
    echo "true";
} else {
    echo "false";
}
?>
