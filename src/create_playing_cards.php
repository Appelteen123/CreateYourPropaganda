<?php
$pageTitle = 'Create Your Playing Cards';
$favicon = 'stickers/Logo MYOP.png';
$extraStyles = ['create.css'];
include 'db.php';
include 'header.php';
?>

<div class="create-page">
    <section class="create-header">
        <div class="create-header-left">
            <a href="#" class="site-logo-link" aria-label="MYOP logo">
                <img src="stickers/Logo MYOP.png" alt="Logo MYOP" class="site-logo">
            </a>
        </div>
        <div class="create-header-right">
            <button id="rulesToggle" class="tool-btn small" type="button">Spelregels</button>
        </div>
    </section>

    <div id="rulesPanel" class="rules-panel" role="region" aria-label="Spelregels">
        <h2>Spelregels</h2>
        <ul>
            <li>Bedenk iets dat bij je playing cards past.</li>
            <li>Voeg stickers en tekst toe aan je design.</li>
            <li>Gebruik de tools om te draaien, spiegel en formaten aan te passen.</li>
            <li>Maak je kaart helder en herkenbaar.</li>
            <li>Download je ontwerp met de knop “Download als PNG”.</li>
        </ul>
    </div>

    <div class="editor">
        <aside class="sidebar">
            <div class="sidebar-tabs">
                <button class="tab-btn active" data-tab="stickers" type="button">🎨 Stickers</button>
                <button class="tab-btn" data-tab="tools" type="button">⚙️ Tools</button>
                <button class="tab-btn" data-tab="background" type="button">🎨 Achtergrond</button>
            </div>

            <div class="tab-content active" id="stickers-tab">
                <div class="stickers-container">
                    <?php
                    $allStickers = ff_get_all_stickers();
                    foreach ($allStickers as $sticker): ?>
                        <img src="<?php echo htmlspecialchars($sticker['path']); ?>" class="sticker" alt="Sticker <?php echo htmlspecialchars($sticker['name']); ?>">
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-content" id="tools-tab">
                <button id="addText" class="tool-btn" type="button">📝 Voeg Tekst Toe</button>
                <button id="savePNG" class="tool-btn" type="button">💾 Download als PNG</button>

                <h4>Post Je Ontwerp</h4>
                <label for="postDescription" class="hint">Beschrijving</label>
                <textarea id="postDescription" rows="3" placeholder="Vertel iets over je ontwerp..."></textarea>
                <button id="postDesignBtn" class="tool-btn" type="button">📤 Post naar feed</button>
                <p id="postStatus" class="hint"></p>

                <h4>Selectie Modus</h4>
                <button id="toggleSelectMode" class="tool-btn select-mode-btn" type="button">🖱️ Select Mode aan</button>
                <p class="hint" id="selectModeHint">Klik op items om te selecteren. Shift+Click voor meerdere.</p>

                <h4 id="selectedItemsLabel">Geselecteerd Item</h4>

                <div id="sizeControls" style="display:none;">
                    <label for="sizeSlider">Grootte:</label>
                    <input type="range" id="sizeSlider" min="12" max="120" value="40">
                    <span id="sizeValue">40px</span>
                </div>

                <div id="textStyleControls" style="display:none;">
                    <label for="textColor">Tekstkleur:</label>
                    <input type="color" id="textColor" value="#dc2626">
                    <label for="fontFamily">Lettertype:</label>
                    <select id="fontFamily">
                        <option value="Impact, Arial Black, sans-serif">Impact</option>
                        <option value="Arial, sans-serif">Arial</option>
                        <option value="'Comic Sans MS', cursive, sans-serif">Comic Sans</option>
                        <option value="'Times New Roman', serif">Times</option>
                        <option value="'Courier New', monospace">Courier</option>
                    </select>
                </div>

                <div id="zoomControls">
                    <label for="zoomSlider">Zoom: <span id="zoomValue">100%</span></label>
                    <input type="range" id="zoomSlider" min="40" max="200" value="100">
                </div>

                <button id="flipHorizontal" class="tool-btn" type="button" disabled>🔄 Spiegelen Horizontaal</button>
                <button id="flipVertical" class="tool-btn" type="button" disabled>⬇️ Spiegelen Verticaal</button>
                <button id="deleteSelected" class="tool-btn" type="button" disabled>🗑️ Verwijderen</button>
            </div>

            <div class="tab-content" id="background-tab">
                <h4>Achtergrondkleur</h4>
                <input type="color" id="bgColor" value="#ffffff">
                <p class="hint">Klik op een kleur om te selecteren</p>
            </div>
        </aside>

        <div class="canvas-container">
            <div id="canvas" data-download-name="playing-cards-design.png"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="create.js"></script>
</body>
</html>
