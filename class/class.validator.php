// Neue Datei: class/class.validator.php
<?php
class Validator {
    public static function username($username) {
        if(strlen($username) < 3 || strlen($username) > 20) {
            return false;
        }
        return preg_match('/^[a-zA-Z0-9_]+$/', $username);
    }
    
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function positiveInt($value) {
        return filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0]
        ]) !== false;
    }
}
?>