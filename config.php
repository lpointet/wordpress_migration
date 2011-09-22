<?php
// Mode debug
define('CFG_DEBUG', FALSE);

// Num�ro et date de version
define('CFG_VERSION', '1.0.0');
define('CFG_VERSION_DATE', '17/05/2011');

// Identifiants de base de donn�es
define('CFG_HOST', 'localhost');
define('CFG_USER', 'username');
define('CFG_PASS', 'password');

// Plugins support�s
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