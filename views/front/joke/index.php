<?php include BASE_PATH . '/views/templates/front/header.php'; ?>

<div class="joke-box">

    <h1>😂 Joke Generator</h1>

    <form method="GET" action="<?php echo htmlspecialchars(routeUrl('joke', 'index', ['office' => 'front']), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="controller" value="joke"> 
        <input type="hidden" name="action" value="index"> 
        <input type="hidden" name="office" value="front">
        <select name="category">
            <option value="Any">Any</option>
            <option value="Programming">Programming</option>
            <option value="Misc">Misc</option>
            <option value="Dark">Dark</option>
            <option value="Pun">Pun</option>
            <option value="Spooky">Spooky</option>
            <option value="Christmas">Christmas</option>
        </select>

        <button type="submit">Get Joke</button>
    </form>

    <hr>

    <?php if ($jokeData): ?>
        <?php if ($jokeData['type'] == 'single'): ?>
            <p><?= $jokeData['joke']; ?></p>
        <?php else: ?>
            <p><strong><?= $jokeData['setup']; ?></strong></p>
            <p><?= $jokeData['delivery']; ?></p>
        <?php endif; ?>
    <?php else: ?>
        <p>No joke found.</p>
    <?php endif; ?>

</div>

<?php include BASE_PATH . '/views/templates/front/footer.php'; ?>