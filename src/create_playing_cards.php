<?php
$pageTitle = 'Create Your Playing Cards';
$favicon = 'logo/Logo MYOP.png';
$extraStyles = ['create.css'];
include 'db.php';
include 'header.php';
?>

<div class="create-page">
    <section class="create-header">
        <div class="create-header-left">
            <a href="#" class="site-logo-link" aria-label="MYOP logo">
                <img src="logo/Logo MYOP.png" alt="Logo MYOP" class="site-logo">
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
                <button class="tab-btn" data-tab="card" type="button">🃏 Kaart</button>
                <button class="tab-btn" data-tab="background" type="button">🎨 Achtergrond</button>
            </div>

            <div class="tab-content active" id="stickers-tab">
                <div class="stickers-container">
                    <?php
                    $allStickers = ff_get_all_stickers('sitckers2');
                    foreach ($allStickers as $sticker): ?>
                        <img src="<?php echo htmlspecialchars($sticker['path']); ?>" class="sticker" alt="Sticker <?php echo htmlspecialchars($sticker['name']); ?>">
                    <?php endforeach; ?>
                    <?php if (empty($allStickers)): ?>
                        <p class="hint">Geen stickers gevonden.</p>
                    <?php endif; ?>
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

            <div class="tab-content" id="card-tab">
                <h4>Kies Kaartkleur</h4>
                <div class="card-color-row">
                    <button type="button" class="card-color-btn active" data-theme="green" aria-label="Groen">Groen</button>
                    <button type="button" class="card-color-btn" data-theme="red" aria-label="Rood">Rood</button>
                </div>

                <div id="greenThemeInputs" class="theme-inputs active" data-theme="green">
                    <h4>Groen: Bovenkant</h4>
                    <label for="greenSourceInput">Bronlabel</label>
                    <input type="text" id="greenSourceInput" data-target="cardSource" data-fallback="news" value="news">

                    <label for="greenTitleInput">Titel</label>
                    <input type="text" id="greenTitleInput" data-target="cardTitle" data-fallback="SAMENWERKING TUSSEN GEMEENTEN" value="SAMENWERKING TUSSEN GEMEENTEN">

                    <label for="greenQuoteInput">Beschrijving</label>
                    <textarea id="greenQuoteInput" rows="3" data-target="cardQuote" data-fallback='"Jij en een rivaal schudden elkaar de hand, het volk gelooft weer even in de politiek."'>"Jij en een rivaal schudden elkaar de hand, het volk gelooft weer even in de politiek."</textarea>

                    <h4>Groen: Onderaan</h4>
                    <label for="greenStat1ValueInput">Punten 1</label>
                    <input type="text" id="greenStat1ValueInput" data-target="cardStat1Value" data-fallback="+2" value="+2">
                    <label for="greenStat1LabelInput">Tekst 1</label>
                    <input type="text" id="greenStat1LabelInput" data-target="cardStat1Label" data-fallback="Publieke support" value="Publieke support">

                    <label for="greenStat2ValueInput">Punten 2</label>
                    <input type="text" id="greenStat2ValueInput" data-target="cardStat2Value" data-fallback="-1" value="-1">
                    <label for="greenStat2LabelInput">Tekst 2</label>
                    <input type="text" id="greenStat2LabelInput" data-target="cardStat2Label" data-fallback="Stemming" value="Stemming">

                    <label for="greenStat3ValueInput">Punten 3</label>
                    <input type="text" id="greenStat3ValueInput" data-target="cardStat3Value" data-fallback="0" value="0">
                    <label for="greenStat3LabelInput">Tekst 3</label>
                    <input type="text" id="greenStat3LabelInput" data-target="cardStat3Label" data-fallback="Angst-token" value="Angst-token">
                </div>

                <div id="redThemeInputs" class="theme-inputs" data-theme="red">
                    <h4>Rood: Bovenkant</h4>
                    <label for="redSourceInput">Bronlabel</label>
                    <input type="text" id="redSourceInput" data-target="cardSource" data-fallback="NL-Alert" value="NL-Alert">

                    <label for="redTitleInput">Titel</label>
                    <input type="text" id="redTitleInput" data-target="cardTitle" data-fallback="MISDAADGOLF!" value="MISDAADGOLF!">

                    <label for="redQuoteInput">Beschrijving</label>
                    <textarea id="redQuoteInput" rows="3" data-target="cardQuote" data-fallback='"De straten zijn gevaarlijk, wees op je hoede! Gelukkig heb jij de oplossing wanneer de verkiezingen er zijn."'>"De straten zijn gevaarlijk, wees op je hoede! Gelukkig heb jij de oplossing wanneer de verkiezingen er zijn."</textarea>

                    <h4>Rood: Onderaan</h4>
                    <label for="redStat1ValueInput">Punten 1</label>
                    <input type="text" id="redStat1ValueInput" data-target="cardStat1Value" data-fallback="+3" value="+3">
                    <label for="redStat1LabelInput">Tekst 1</label>
                    <input type="text" id="redStat1LabelInput" data-target="cardStat1Label" data-fallback="Publieke support" value="Publieke support">

                    <label for="redStat2ValueInput">Punten 2</label>
                    <input type="text" id="redStat2ValueInput" data-target="cardStat2Value" data-fallback="+1" value="+1">
                    <label for="redStat2LabelInput">Tekst 2</label>
                    <input type="text" id="redStat2LabelInput" data-target="cardStat2Label" data-fallback="Stemming" value="Stemming">

                    <label for="redStat3ValueInput">Punten 3</label>
                    <input type="text" id="redStat3ValueInput" data-target="cardStat3Value" data-fallback="+1" value="+1">
                    <label for="redStat3LabelInput">Tekst 3</label>
                    <input type="text" id="redStat3LabelInput" data-target="cardStat3Label" data-fallback="Angst-token" value="Angst-token">
                </div>

                <p class="hint">Per kleur vul je de tekstvakken in. Het midden blijft vrij om zelf te ontwerpen met stickers en tekst.</p>
            </div>

            <div class="tab-content" id="background-tab">
                <h4>Achtergrondkleur</h4>
                <input type="color" id="bgColor" value="#ffffff">
                <p class="hint">Deze kleur wordt alleen toegepast op het design-vak.</p>
            </div>
        </aside>

        <div class="canvas-container">
            <div id="canvas" data-mode="card" data-download-name="playing-cards-design.png">
                <div id="playingCardShell" class="playing-card-shell theme-green">
                    <div class="card-top-panel">
                        <p id="cardSource" class="card-source">news</p>
                        <h3 id="cardTitle" class="card-title">SAMENWERKING TUSSEN GEMEENTEN</h3>
                        <p id="cardQuote" class="card-quote">"Jij en een rivaal schudden elkaar de hand, het volk gelooft weer even in de politiek."</p>
                    </div>

                    <div id="cardDesignBox" class="card-design-box"></div>

                    <div class="card-stats-row">
                        <div class="card-stat-col">
                            <div id="cardStat1Value" class="card-stat-value">+2</div>
                            <p id="cardStat1Label" class="card-stat-label">Publieke support</p>
                        </div>
                        <div class="card-stat-col">
                            <div id="cardStat2Value" class="card-stat-value">-1</div>
                            <p id="cardStat2Label" class="card-stat-label">Stemming</p>
                        </div>
                        <div class="card-stat-col">
                            <div id="cardStat3Value" class="card-stat-value">0</div>
                            <p id="cardStat3Label" class="card-stat-label">Angst-token</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="create.js"></script>
</body>
</html>
