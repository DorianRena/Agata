<?php
if (php_sapi_name() !== 'cli' && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) { die('Accès interdit'); }

echo "Well play"

?>