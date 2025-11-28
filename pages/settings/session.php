<?php
// Zeigt aktive Geräte/Sessions
require_once __DIR__ . '/../../init.php';

$app = new App();
$auth = $app->getAuth();
$playerId = $auth->getCurrentPlayerId();

if(!$playerId) {
    echo '<p>Nicht eingeloggt</p>';
    exit;
}

// Lösche einzelnes Token
if(isset($_POST['delete_token'])) {
    $tokenId = (int)$_POST['token_id'];
    $sql = "DELETE FROM remember_tokens WHERE id = :id AND player_id = :player_id";
    $app->getDB()->delete($sql, [':id' => $tokenId, ':player_id' => $playerId]);
    
    echo '<div class="success">Gerät abgemeldet!</div>';
}

// Lösche alle Tokens außer aktuellem
if(isset($_POST['delete_all_tokens'])) {
    $auth->getRememberMe()->deleteAllTokensForPlayer($playerId);
    echo '<div class="success">Alle Geräte abgemeldet!</div>';
}

$tokens = $auth->getRememberMe()->getActiveTokens($playerId);
?>

<h2>🔐 Aktive Geräte</h2>

<div class="info-box">
    <p>Hier siehst du alle Geräte, auf denen du angemeldet bleibst.</p>
</div>

<?php if(empty($tokens)): ?>
    <p>Keine aktiven "Angemeldet bleiben" Tokens.</p>
<?php else: ?>
    <table style="width:100%; border-collapse:collapse; margin-top:20px;">
        <thead>
            <tr style="background:#1a1a1a;">
                <th style="padding:10px; text-align:left;">Gerät</th>
                <th style="padding:10px; text-align:left;">Erstellt</th>
                <th style="padding:10px; text-align:left;">Zuletzt verwendet</th>
                <th style="padding:10px; text-align:left;">Läuft ab</th>
                <th style="padding:10px; text-align:right;">Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($tokens as $token): ?>
                <tr style="border-bottom:1px solid #333;">
                    <td style="padding:10px;">
                        <?php if($token['is_current_device']): ?>
                            <strong>🟢 Dieses Gerät</strong>
                        <?php else: ?>
                            Anderes Gerät
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px;">
                        <?php echo date('d.m.Y H:i', strtotime($token['created_at'])); ?>
</td>
<td style="padding:10px;">
<?php echo $token['last_used_at'] ? timeAgo(strtotime($token['last_used_at'])) : 'Nie'; ?>
</td>
<td style="padding:10px;">
<?php echo date('d.m.Y H:i', strtotime($token['expires_at'])); ?>
</td>
<td style="padding:10px; text-align:right;">
<form method="POST" style="display:inline;">
<input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
<button type="submit" name="delete_token" 
                                 style="padding:5px 10px; background:#e74c3c; color:#fff; border:none; border-radius:3px; cursor:pointer;"
                                 onclick="return confirm('Gerät wirklich abmelden?')">
Abmelden
</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<form method="POST" style="margin-top:20px;">
    <button type="submit" name="delete_all_tokens" 
            class="btn" 
            style="background:#e74c3c;"
            onclick="return confirm('Wirklich ALLE Geräte abmelden?')">
        🚨 Alle Geräte abmelden
    </button>
</form>
<?php endif; ?>