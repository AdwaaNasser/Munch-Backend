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

// check existing report
$stmt = $pdo->prepare("
    SELECT * FROM report
    WHERE userID=? AND recipeID=?
");

$stmt->execute([$userID, $recipeID]);

if ($stmt->rowCount() > 0) {
    echo "false";
    exit();
}

// insert report
$stmt = $pdo->prepare("
    INSERT INTO report(recipeID, userID)
    VALUES(?,?)
");

if ($stmt->execute([$recipeID, $userID])) {
    echo "true";
} else {
    echo "false";
}
?>
