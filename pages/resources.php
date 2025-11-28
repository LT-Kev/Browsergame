<<<<<<< HEAD
<?php
require_once __DIR__ . '/../init.php';
?>

<h2>Ressourcen sammeln</h2>

<div class="info-box">
    <h4>🍖 Nahrung sammeln</h4>
    <p>Sammle Nahrung für deine Truppen</p>
    <button class="gather-btn" data-resource="food">10 Nahrung sammeln (-5 Energie)</button>
</div>

<div class="info-box">
    <h4>🪵 Holz sammeln</h4>
    <p>Holz wird für Gebäude benötigt</p>
    <button class="gather-btn" data-resource="wood">10 Holz sammeln (-5 Energie)</button>
</div>

<div class="info-box">
    <h4>🪨 Stein sammeln</h4>
    <p>Stein für Befestigungen und Upgrades</p>
    <button class="gather-btn" data-resource="stone">10 Stein sammeln (-5 Energie)</button>
</div>

<div class="info-box">
    <h4>💰 Gold sammeln</h4>
    <p>Gold zum Kaufen von Items</p>
    <button class="gather-btn" data-resource="gold">10 Gold sammeln (-5 Energie)</button>
</div>

<div id="gather-result" style="margin-top: 20px;"></div>

<script>
$('.gather-btn').on('click', function() {
    var resource = $(this).data('resource');
    
    $.ajax({
        url: 'ajax/gather_resource.php',
        type: 'POST',
        data: {
            resource: resource,
            amount: 10
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#gather-result').html('<div class="info-box" style="border-left-color: #2ecc71;">' + response.message + '</div>');
                loadPlayerData(); // Daten neu laden
            } else {
                $('#gather-result').html('<div class="info-box" style="border-left-color: #e74c3c;">' + response.message + '</div>');
            }
        }
    });
});
=======
<?php
require_once __DIR__ . '/../init.php';
?>

<h2>Ressourcen sammeln</h2>

<div class="info-box">
    <h4>🍖 Nahrung sammeln</h4>
    <p>Sammle Nahrung für deine Truppen</p>
    <button class="gather-btn" data-resource="food">10 Nahrung sammeln (-5 Energie)</button>
</div>

<div class="info-box">
    <h4>🪵 Holz sammeln</h4>
    <p>Holz wird für Gebäude benötigt</p>
    <button class="gather-btn" data-resource="wood">10 Holz sammeln (-5 Energie)</button>
</div>

<div class="info-box">
    <h4>🪨 Stein sammeln</h4>
    <p>Stein für Befestigungen und Upgrades</p>
    <button class="gather-btn" data-resource="stone">10 Stein sammeln (-5 Energie)</button>
</div>

<div class="info-box">
    <h4>💰 Gold sammeln</h4>
    <p>Gold zum Kaufen von Items</p>
    <button class="gather-btn" data-resource="gold">10 Gold sammeln (-5 Energie)</button>
</div>

<div id="gather-result" style="margin-top: 20px;"></div>

<script>
$('.gather-btn').on('click', function() {
    var resource = $(this).data('resource');
    
    $.ajax({
        url: 'ajax/gather_resource.php',
        type: 'POST',
        data: {
            resource: resource,
            amount: 10
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#gather-result').html('<div class="info-box" style="border-left-color: #2ecc71;">' + response.message + '</div>');
                loadPlayerData(); // Daten neu laden
            } else {
                $('#gather-result').html('<div class="info-box" style="border-left-color: #e74c3c;">' + response.message + '</div>');
            }
        }
    });
});
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
</script>