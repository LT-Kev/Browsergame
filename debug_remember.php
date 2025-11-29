<?php
/**
 * Debug-Tool für Remember Me System
 * Erstelle diese Datei im Root-Verzeichnis als debug_remember.php
 */

require_once __DIR__ . '/init.php';

$app = new App();
$auth = $app->getAuth();
$db = $app->getDB();

echo "<pre style='background:#1a1a1a; color:#0f0; padding:20px; font-family:monospace;'>";
echo "====================================\n";
echo "🔍 REMEMBER ME DEBUG TOOL\n";
echo "====================================\n\n";

// 1. Session Status
echo "📋 SESSION STATUS:\n";
echo "-----------------------------------\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '❌ Inactive') . "\n";
echo "Logged In: " . (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? '✅ Yes' : '❌ No') . "\n";
echo "Player ID: " . ($_SESSION['player_id'] ?? 'NULL') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'NULL') . "\n";
echo "Last Activity: " . (isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) . ' (' . (time() - $_SESSION['last_activity']) . 's ago)' : 'NULL') . "\n";
echo "Logged via Remember: " . (isset($_SESSION['logged_via_remember']) && $_SESSION['logged_via_remember'] ? '✅ Yes' : '❌ No') . "\n\n";

// 2. Cookie Status
echo "🍪 COOKIE STATUS:\n";
echo "-----------------------------------\n";
echo "Remember Cookie Exists: " . (isset($_COOKIE['remember_token']) ? '✅ Yes' : '❌ No') . "\n";
if(isset($_COOKIE['remember_token'])) {
    $cookieValue = $_COOKIE['remember_token'];
    $parts = explode(':', $cookieValue);
    echo "Cookie Value Length: " . strlen($cookieValue) . " chars\n";
    echo "Cookie Parts: " . count($parts) . " (should be 2)\n";
    if(count($parts) === 2) {
        echo "Selector: " . $parts[0] . "\n";
        echo "Validator: " . substr($parts[1], 0, 10) . "... (truncated)\n";
    } else {
        echo "⚠️ INVALID COOKIE FORMAT!\n";
    }
} else {
    echo "ℹ️ No remember_token cookie found\n";
}
echo "\n";

// 3. Database Tokens
echo "💾 DATABASE TOKENS:\n";
echo "-----------------------------------\n";
$playerId = $_SESSION['player_id'] ?? null;

if($playerId) {
    $sql = "SELECT * FROM remember_tokens WHERE player_id = :player_id ORDER BY created_at DESC";
    $tokens = $db->select($sql, [':player_id' => $playerId]);
    
    if($tokens) {
        echo "Found " . count($tokens) . " token(s) for Player ID $playerId:\n\n";
        
        foreach($tokens as $i => $token) {
            echo "Token #" . ($i + 1) . ":\n";
            echo "  ID: " . $token['id'] . "\n";
            echo "  Selector: " . $token['selector'] . "\n";
            echo "  Hashed Validator: " . substr($token['hashed_validator'], 0, 20) . "...\n";
            echo "  Created: " . $token['created_at'] . "\n";
            echo "  Last Used: " . ($token['last_used_at'] ?? 'Never') . "\n";
            echo "  Expires: " . $token['expires_at'];
            
            $expiresTime = strtotime($token['expires_at']);
            $now = time();
            if($expiresTime > $now) {
                $remaining = $expiresTime - $now;
                echo " (✅ Valid for " . round($remaining / 86400, 1) . " days)\n";
            } else {
                echo " (❌ EXPIRED!)\n";
            }
            echo "\n";
        }
    } else {
        echo "❌ No tokens found in database for Player ID $playerId\n";
    }
} else {
    echo "⚠️ No player_id in session, checking all tokens...\n";
    
    if(isset($_COOKIE['remember_token'])) {
        $cookieValue = $_COOKIE['remember_token'];
        $parts = explode(':', $cookieValue);
        
        if(count($parts) === 2) {
            $selector = $parts[0];
            $sql = "SELECT * FROM remember_tokens WHERE selector = :selector";
            $token = $db->selectOne($sql, [':selector' => $selector]);
            
            if($token) {
                echo "✅ Token found by selector:\n";
                echo "  Player ID: " . $token['player_id'] . "\n";
                echo "  Created: " . $token['created_at'] . "\n";
                echo "  Expires: " . $token['expires_at'] . "\n";
            } else {
                echo "❌ No token found with selector: $selector\n";
            }
        }
    }
}
echo "\n";

// 4. Config Check
echo "⚙️ CONFIGURATION:\n";
echo "-----------------------------------\n";
echo "Session Lifetime: " . (defined('SESSION_LIFETIME') ? SESSION_LIFETIME . 's' : 'NOT SET') . "\n";
echo "Session Cookie Lifetime: " . ini_get('session.cookie_lifetime') . "s\n";
echo "Session GC Maxlifetime: " . ini_get('session.gc_maxlifetime') . "s\n";
echo "DEV_MODE: " . (defined('DEV_MODE') && DEV_MODE ? '✅ Yes' : '❌ No') . "\n";
echo "\n";

// 5. Test Remember Token Validation
echo "🧪 TEST VALIDATION:\n";
echo "-----------------------------------\n";

if(isset($_COOKIE['remember_token'])) {
    echo "Attempting to validate remember token...\n";
    
    try {
        $validatedPlayerId = $auth->getRememberMe()->validateToken();
        
        if($validatedPlayerId) {
            echo "✅ Token validation SUCCESS!\n";
            echo "   Validated Player ID: $validatedPlayerId\n";
        } else {
            echo "❌ Token validation FAILED!\n";
            echo "   Token exists but validation returned false\n";
        }
    } catch(Exception $e) {
        echo "❌ EXCEPTION during validation:\n";
        echo "   " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ No token to validate\n";
}

echo "\n";

// 6. Recommendations
echo "💡 RECOMMENDATIONS:\n";
echo "-----------------------------------\n";

$issues = [];

if(!isset($_COOKIE['remember_token'])) {
    $issues[] = "❌ No remember_token cookie - User didn't check 'Remember Me' or cookie was deleted";
}

if(isset($_SESSION['last_activity'])) {
    $inactiveTime = time() - $_SESSION['last_activity'];
    if($inactiveTime > 7200) {
        $issues[] = "⚠️ Session inactive for " . round($inactiveTime/60) . " minutes (>2h threshold)";
    }
}

if($playerId) {
    $sql = "SELECT COUNT(*) as count FROM remember_tokens WHERE player_id = :player_id AND expires_at > NOW()";
    $result = $db->selectOne($sql, [':player_id' => $playerId]);
    if($result['count'] == 0) {
        $issues[] = "❌ No valid tokens in database - Token expired or was deleted";
    }
}

if(empty($issues)) {
    echo "✅ Everything looks good!\n";
} else {
    foreach($issues as $issue) {
        echo $issue . "\n";
    }
}

echo "\n";
echo "====================================\n";
echo "Debug completed at: " . date('Y-m-d H:i:s') . "\n";
echo "====================================\n";
echo "</pre>";

// Links
echo "<p style='background:#252525; padding:20px; color:#fff;'>";
echo "<strong>Actions:</strong><br>";
echo "<a href='debug_remember.php' style='color:#3498db;'>🔄 Refresh Debug</a> | ";
echo "<a href='index.php' style='color:#3498db;'>🏠 Go to Home</a> | ";
echo "<a href='login.php' style='color:#3498db;'>🔐 Go to Login</a>";
echo "</p>";