<?php
if(!empty($param) && !isset($message['fatal']) && !empty($message['warning'])) {
    // Tout s'est bien passé
    die(implode(PHP_EOL, $message['warning']));
}

// Il y a eu des erreurs ou aucun paramètre passé
if(!empty($message['fatal'])) {
    echo PHP_EOL . str_replace(array('&laquo;', '&raquo;'), '"', implode(PHP_EOL, $message['fatal'])) . PHP_EOL . PHP_EOL;
}
echo STR_CLI_USAGE;