<<<<<<< HEAD
<?php

spl_autoload_register(function ($class) {

    $file = __DIR__ . "/class." . strtolower($class) . ".php";

    if (file_exists($file)) {
        require_once $file;
    }
});

=======
<?php

spl_autoload_register(function ($class) {

    $file = __DIR__ . "/class." . strtolower($class) . ".php";

    if (file_exists($file)) {
        require_once $file;
    }
});

>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
?>