<?php
require_once 'DBconfig.php';

// =========================
// Check Recipe ID
// =========================
if (!isset($_GET['id'])) {
    die("Invalid Recipe ID");
}

$recipeID = $_GET['id'];

$userID = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? 'user';

// =========================
// Recipe + Creator
// =========================
$sql = "
SELECT 
    r.*, 
    u.firstName, 
    u.lastName,
    u.photoFileName AS userPhoto,
    c.categoryName

FROM recipe r

JOIN user u 
ON r.userID = u.id

JOIN recipecategory c
ON r.categoryID = c.id

WHERE r.id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$recipeID]);

$recipe = $stmt->fetch();

if (!$recipe) {
    die("Recipe not found");
}

// =========================
// Ingredients
// =========================
$stmt = $pdo->prepare("
SELECT * FROM ingredients
WHERE recipeID=?
");

$stmt->execute([$recipeID]);

$ingredients = $stmt->fetchAll();

// =========================
// Instructions
// =========================
$stmt = $pdo->prepare("
SELECT * FROM instructions
WHERE recipeID=?
ORDER BY stepOrder
");

$stmt->execute([$recipeID]);

$steps = $stmt->fetchAll();

// =========================
// Comments
// =========================
$stmt = $pdo->prepare("
SELECT c.*, u.firstName

FROM comment c

JOIN user u
ON c.userID = u.id

WHERE recipeID=?

ORDER BY date DESC
");

$stmt->execute([$recipeID]);

$comments = $stmt->fetchAll();

// =========================
// Owner/Admin Check
// =========================
$isOwner = ($userID == $recipe['userID']);
$isAdmin = ($userType == 'admin');

// =========================
// Favourite Check
// =========================
$stmt = $pdo->prepare("
SELECT * FROM favourites
WHERE userID=? AND recipeID=?
");

$stmt->execute([$userID, $recipeID]);

$isFav = $stmt->rowCount() > 0;

// =========================
// Like Check
// =========================
$stmt = $pdo->prepare("
SELECT * FROM likes
WHERE userID=? AND recipeID=?
");

$stmt->execute([$userID, $recipeID]);

$isLiked = $stmt->rowCount() > 0;

// =========================
// Report Check
// =========================
$stmt = $pdo->prepare("
SELECT * FROM report
WHERE userID=? AND recipeID=?
");

$stmt->execute([$userID, $recipeID]);

$isReported = $stmt->rowCount() > 0;

// =========================
// Counts
// =========================

// Likes
$stmt = $pdo->prepare("
SELECT COUNT(*) FROM likes
WHERE recipeID=?
");

$stmt->execute([$recipeID]);

$totalLikes = $stmt->fetchColumn();

// Favourites
$stmt = $pdo->prepare("
SELECT COUNT(*) FROM favourites
WHERE recipeID=?
");

$stmt->execute([$recipeID]);

$totalFavs = $stmt->fetchColumn();

// Comments
$totalComments = count($comments);

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">

<title>
<?= htmlspecialchars($recipe['name']) ?>
</title>

<link rel="stylesheet" href="style.css">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

</head>

<body class="view-page">

<div class="recipe-card">

<!-- ========================= -->
<!-- Recipe Name -->
<!-- ========================= -->
<h1>
<?= htmlspecialchars($recipe['name']) ?>
</h1>

<!-- ========================= -->
<!-- Recipe Image -->
<!-- ========================= -->
<img
src="images/<?= htmlspecialchars($recipe['photoFileName']) ?>"
class="recipe-img"
>

<!-- ========================= -->
<!-- Creator + Buttons -->
<!-- ========================= -->
<div class="creator-actions">

    <!-- Creator -->
    <div class="creator">

        <img
        src="images/<?= htmlspecialchars($recipe['userPhoto']) ?>"
        class="creator-img"
        >

        <div>

            <strong>
            <?= htmlspecialchars($recipe['firstName']) ?>
            <?= htmlspecialchars($recipe['lastName']) ?>
            </strong>

            <small>
            <?= htmlspecialchars($recipe['categoryName']) ?>
            </small>

        </div>

    </div>

    <!-- Buttons -->
    <?php if (!$isOwner && !$isAdmin): ?>

    <div class="recipe-actions">

        <!-- Favourite -->
        <button
        id="favBtn"
        class="action-btn"
        data-id="<?= $recipeID ?>"
        <?= $isFav ? 'disabled' : '' ?>
        >

        ❤️
        <?= $isFav ? 'Added' : 'Add to Favorites' ?>
        

        </button>

        <!-- Like -->
        <button
        id="likeBtn"
        class="action-btn like"
        data-id="<?= $recipeID ?>"
        <?= $isLiked ? 'disabled' : '' ?>
        >

        👍
        <?= $isLiked ? 'Liked' : 'Like' ?>
        

        </button>

        <!-- Report -->
        <button
        id="reportBtn"
        class="action-btn danger"
        data-id="<?= $recipeID ?>"
        <?= $isReported ? 'disabled' : '' ?>
        >

        ⚠
        <?= $isReported ? 'Reported' : 'Report' ?>

        </button>

    </div>

    <?php endif; ?>

</div>

<!-- ========================= -->
<!-- Description -->
<!-- ========================= -->
<div class="recipe-info">

<p>
<strong>Category:</strong>
<?= htmlspecialchars($recipe['categoryName']) ?>
</p>

<p>
<?= htmlspecialchars($recipe['description']) ?>
</p>

</div>

<!-- ========================= -->
<!-- Ingredients + Instructions -->
<!-- ========================= -->
<div class="recipe-sections">

    <!-- Ingredients -->
    <div class="recipe-box">

        <h2>Ingredients</h2>

        <ul>

        <?php foreach($ingredients as $ing): ?>

            <li>

            <?= htmlspecialchars($ing['IngredientQuantity']) ?>

            -

            <?= htmlspecialchars($ing['IngredientName']) ?>

            </li>

        <?php endforeach; ?>

        </ul>

    </div>

    <!-- Instructions -->
    <div class="recipe-box">

        <h2>Instructions</h2>

        <ol>

        <?php foreach($steps as $step): ?>

            <li>
            <?= htmlspecialchars($step['step']) ?>
            </li>

        <?php endforeach; ?>

        </ol>

    </div>

</div>

<!-- ========================= -->
<!-- Video -->
<!-- ========================= -->
<?php if (!empty($recipe['videoFilePath'])): ?>

<a
href="<?= htmlspecialchars($recipe['videoFilePath']) ?>"
class="video-box exciting-video"
>

<span class="play-icon">▶</span>

<div>

<strong>Watch the Baking Process</strong>

<small>See step by step</small>

</div>

</a>

<?php endif; ?>

<!-- ========================= -->
<!-- Comments -->
<!-- ========================= -->
<div class="comments">

<h2>
Comments (<?= $totalComments ?>)
</h2>

<!-- Existing Comments -->
<?php foreach($comments as $c): ?>

<div class="comment">

<strong>
<?= htmlspecialchars($c['firstName']) ?>:
</strong>

<?= htmlspecialchars($c['comment']) ?>

</div>

<?php endforeach; ?>

<!-- Add Comment -->
<form action="addComment.php" method="POST">

<input
type="hidden"
name="recipeID"
value="<?= $recipeID ?>"
>

<textarea
name="comment"
required
placeholder="Add your comment"
></textarea>

<button type="submit">
Post
</button>

</form>

</div>

</div>

<!-- ========================= -->
<!-- AJAX -->
<!-- ========================= -->
<script>

// =========================
// LIKE
// =========================
$("#likeBtn").click(function(){

    let btn = $(this);

    $.ajax({

        url: "likeProcess.php",

        type: "POST",

        data: {
            recipeID: btn.data("id")
        },

        success: function(response){

            if(response.trim() === "true"){

                btn.prop("disabled", true);

                btn.text("👍 Liked");

            }

        }

    });

});

// =========================
// FAVOURITE
// =========================
$("#favBtn").click(function(){

    let btn = $(this);

    $.ajax({

        url: "favProcess.php",

        type: "POST",

        data: {
            recipeID: btn.data("id")
        },

        success: function(response){

            if(response.trim() === "true"){

                btn.prop("disabled", true);

                btn.text("❤️ Added");

            }

        }

    });

});

// =========================
// REPORT
// =========================
$("#reportBtn").click(function(){

    let btn = $(this);

    $.ajax({

        url: "reportProcess.php",

        type: "POST",

        data: {
            recipeID: btn.data("id")
        },

        success: function(response){

            if(response.trim() === "true"){

                btn.prop("disabled", true);

                btn.text("⚠ Reported");

            }

        }

    });

});

</script>

</body>
</html>
