<?php
// Mode debug
define('CFG_DEBUG', FALSE);

// CLI / CGI ?
define('CFG_DISPLAY', ( 'cli' === PHP_SAPI ? 'cli' : 'cgi' ));

// Numéro et date de version
define('CFG_VERSION', '1.0.0');
define('CFG_VERSION_DATE', '17/05/2011');

// Identifiants de base de données
define('CFG_HOST', 'localhost');
define('CFG_USER', 'username');
define('CFG_PASS', 'password');

// Nom du fichier principal
define('SCRIPT_NAME', ( 'cli' === CFG_DISPLAY ? basename($argv[0]) : '' ));

// Plugins supportés
$known_plugin = array(
    'contact-form-7' => array(
        'label' => 'Contact Form 7',
        'update' => array(
            array(
                'table' => 'contact_form_7',
                'champ' => array(
                    array(
                        'cf7_unit_id',
                        'mail',
                    ),
                    array(
                        'cf7_unit_id',
                        'mail_2',
                    ),
                    array(
                        'cf7_unit_id',
                        'form',
                    ),
                ),
            ),
        ),
    ),
);