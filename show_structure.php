<<<<<<< HEAD
<?php
/**
 * Rekursive Ordner-/Dateiausgabe
 * --------------------------------
 * Ausgabe: Baumstruktur aller Dateien & Ordner
 * Zeigt KEINE Inhalte an (nur Namen)
 */

$root = __DIR__;

function listFolderStructure($dir, $prefix = '') {
    $items = scandir($dir);
    $items = array_diff($items, ['.', '..']); // "." und ".." entfernen

    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;

        // Formatierung für die Anzeige
        echo $prefix . "|-- " . $item . "<br>";

        // Rekursiv, wenn Ordner
        if (is_dir($path)) {
            listFolderStructure($path, $prefix . "&nbsp;&nbsp;&nbsp;");
        }
    }
}

echo "<h2>📁 Projektstruktur</h2>";
echo "<p><strong>Root:</strong> $root</p>";
echo "<pre>";
listFolderStructure($root);
echo "</pre>";
?>
=======
<?php
/**
 * Rekursive Ordner-/Dateiausgabe
 * --------------------------------
 * Ausgabe: Baumstruktur aller Dateien & Ordner
 * Zeigt KEINE Inhalte an (nur Namen)
 */

$root = __DIR__;

function listFolderStructure($dir, $prefix = '') {
    $items = scandir($dir);
    $items = array_diff($items, ['.', '..']); // "." und ".." entfernen

    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;

        // Formatierung für die Anzeige
        echo $prefix . "|-- " . $item . "<br>";

        // Rekursiv, wenn Ordner
        if (is_dir($path)) {
            listFolderStructure($path, $prefix . "&nbsp;&nbsp;&nbsp;");
        }
    }
}

echo "<h2>📁 Projektstruktur</h2>";
echo "<p><strong>Root:</strong> $root</p>";
echo "<pre>";
listFolderStructure($root);
echo "</pre>";
?>
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
