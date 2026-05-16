
<?php
require_once 'DBconfig.php';
requireLogin();
$userID = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM recipe WHERE userID = ?");
$stmt->execute([$userID]);
$recipes = $stmt->fetchAll();

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Recipes</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <style>
.recipe-delete-button {
    background: #CC3434 !important;
    color: #F8E1B8 !important;
    border: none !important;
    padding: 10px 24px !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    font-size: 15px !important;
    font-family: "Georgia", serif !important;
}
</style>
<script>
$(document).on('click', '.recipe-delete-button', function () {
    if (!confirm('Are you sure you want to delete this recipe?')) return;

    const btn = $(this);
    const row = btn.closest('tr');

    $.ajax({
        url: 'delete-recipe.php',
        type: 'POST',
        data: { id: btn.data('id') },
        success: function (response) {
            if (response.trim() === 'true') {
                row.remove();
            } else {
                alert('Could not delete. Please try again.');
            }
        },
        error: function () {
            alert('An error occurred. Please try again.');
        }
    });
});
</script>
</head>

<body class="my-recipes-page">

<div class="my-recipes-card">

  <div class="my-recipes-header">
    <h1>My Recipes</h1>
    <a href="addRecipe.php" class="add-recipe-link">+ Add New Recipe</a>
  </div>

  <table class="recipes-table">
    <thead>
      <tr>
        <th>Recipe</th>
        <th>Ingredients</th>
        <th>Instructions</th>
        <th>Video</th>
        <th>Likes</th>
        <th>Edit</th>
        <th>Delete</th>
      </tr>
    </thead>

    <tbody>

<?php foreach($recipes as $r): ?>

<tr>

<!-- Recipe -->
<td class="recipe-cell">
  <a href="view-recipe.php?id=<?= $r['id'] ?>">
    <img src="images/<?= $r['photoFileName'] ?>">
    <span><?= $r['name'] ?></span>
  </a>
</td>

<!-- Ingredients -->
<td>
<ul>
<?php
$ing = $pdo->prepare("SELECT * FROM ingredients WHERE recipeID=?");
$ing->execute([$r['id']]);

foreach($ing as $i){
  echo "<li>{$i['IngredientQuantity']} {$i['IngredientName']}</li>";
}
?>
</ul>
</td>

<!-- Instructions -->
<td>
<ol>
<?php
$st = $pdo->prepare("SELECT * FROM instructions WHERE recipeID=? ORDER BY stepOrder");
$st->execute([$r['id']]);

foreach($st as $s){
  echo "<li>{$s['step']}</li>";
}
?>
</ol>
</td>

<!-- Video -->
<td>
<?php if(empty($r['videoFilePath'])): ?>
  No video for recipe
<?php else: ?>
  <a href="videos/<?= $r['videoFilePath'] ?>">Watch video</a>
<?php endif; ?>
</td>

<!-- Likes -->
<td>
<?php
$l = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE recipeID=?");
$l->execute([$r['id']]);
echo $l->fetchColumn();
?>
</td>

<!-- Edit -->
<td>
<a href="edit-recipe.php?id=<?= $r['id'] ?>" class="edit-link">Edit</a>
</td>

<!-- Delete -->
<td style="text-align: center; vertical-align: middle;">
    <button class="recipe-delete-button" data-id="<?= $r['id'] ?>">Delete</button>
</td>

</tr>

<?php endforeach; ?>

    </tbody>
  </table>

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
          <img src="images/Bakery.png">
        </div>
        <small>©2026 Munch. All rights reserved</small>
      </div>

      <div class="footer-col right">
        <h4>contact Info</h4>
        <p>+966444282741</p>
        <p><a href="#">Munch@gmail.com</a></p>
      </div>
    </div>
  </div>
</footer>

</body>
</html>
