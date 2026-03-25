<?php
$pageTitle = 'Choose Create Mode';
$favicon = 'stickers/Logo MYOP.png';
$extraStyles = ['create.css'];
include 'header.php';
?>

<div class="create-selection-page">
    <section class="create-selection-header">
        <h1>Create</h1>
        <p>Kies eerst wat je wilt ontwerpen.</p>
    </section>

    <section class="create-selection-grid" aria-label="Create keuzes">
        <a class="create-selection-card" href="create_propaganda.php">
            <h2>Create your own propagande</h2>
            <p>Open de huidige poster-editor om je eigen ontwerp te maken.</p>
        </a>

        <a class="create-selection-card" href="create_playing_cards.php">
            <h2>Create your playing cards</h2>
            <p>Open een tweede editor-pagina voor playing cards design.</p>
        </a>
    </section>
</div>

</body>
</html>
