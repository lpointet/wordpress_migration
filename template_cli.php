<?php
if(!empty($param) && !isset($message['fatal']) && !empty($message['warning'])) {
    // Tout s'est bien pass�
    die(implode(PHP_EOL, $message['warning']));
}

// Il y a eu des erreurs ou aucun param�tre pass�
if(!empty($message['fatal'])) {
    echo implode(PHP_EOL, $message['fatal']);
}
echo STR_CLI_USAGE;