<?php
//
// Erreurs
//
define('STR_ERROR_FATAL_TABLE', 'Erreur lors de la migration de "%s".');
define('STR_ERROR_FATAL_CHAMP', 'Une erreur s\'est produite lors de la mise à jour du champ : %s = %s, %s = %s dans la table %s');
define('STR_ERROR_FATAL_BASE', 'La base %s n\'existe pas !');
define('STR_ERROR_FATAL_CONNEXION', 'Les identifiants de connexion à la base ne sont pas bons, connexion impossible');

//
// Warnings
//
define('STR_ERROR_WARNING_MIGRATION_DONE', 'Migration effectuée !');

//
// Formulaire
//
define('STR_LIBELLE_OLD_DOMAIN', 'Ancien domaine');
define('STR_LIBELLE_NEW_DOMAIN', 'Nouveau domaine');
define('STR_LIBELLE_OLD_PATH', 'Ancien path');
define('STR_LIBELLE_NEW_PATH', 'Nouveau path');
define('STR_LIBELLE_OLD_FILEPATH', 'Ancien path serveur');
define('STR_LIBELLE_NEW_FILEPATH', 'Nouveau path serveur');
define('STR_LIBELLE_BASE', 'Nom de la base');
define('STR_LIBELLE_PREFIX', 'Préfixe des tables');
define('STR_LIBELLE_LINK_UPDATE', 'Mettre à jour les liens');
define('STR_LIBELLE_SUBMIT', 'Migrer !');
define('STR_LIBELLE_FIELDSET_PLUGIN', 'Plugins actifs');
define('STR_FORM_REQUIRE_SYMBOL', '*');
define('STR_FORM_LEGEND', '* Champs obligatoires');
define('STR_ERROR_FATAL_REQUIRED_FIELD', 'Le champ &laquo;%s&raquo; est requis');
define('STR_LIBELLE_FIELDSET_BDD', 'Identifiants de base de données');
define('STR_LIBELLE_DEFAULT_VALUE', 'Valeur par défaut : %s');
define('STR_LIBELLE_HOST', 'Host');
define('STR_LIBELLE_USER', 'Username');
define('STR_LIBELLE_PASS', 'Mot de passe');

//
// Contenu
//
define('STR_TITLE', 'Outil de migration pour base WordPress');
define('STR_FOOTER', 'Outil distribué sous license BSD. Copyright © Lionel POINTET - '.CFG_VERSION.' - '.CFG_VERSION_DATE);

//
// CLI
//
define('STR_CLI_USAGE', "usage: php " . SCRIPT_NAME . " -h <host> -u <user> -b <db_name>" . PHP_EOL .
                        "           " . str_repeat(" ", strlen(SCRIPT_NAME)) . " --old_domain=<old_domain> [<options>]" . PHP_EOL .
                        PHP_EOL .
                        "Options:" . PHP_EOL .
                        "   -p <password>                       Use <password> to connect to database," . PHP_EOL .
                        "                                         don't use password otherwise" . PHP_EOL .
                        "   -l                                  Update links" . PHP_EOL .
                        "   --new_domain=<new_domain>           The new domain of installation" . PHP_EOL . 
                        "                                         (if empty, don't move)" . PHP_EOL .
                        "   --old_path=<old_path>               The old path of installation" . PHP_EOL .
                        "                                         (http://<domain>/<path>)" . PHP_EOL .
                        "   --new_path=<new_path>               The new path of installation" . PHP_EOL .
                        "                                         (if empty & old_path too, don't move)" . PHP_EOL .
                        "   --old_server_path=<old_server_path> The old server path of installation" . PHP_EOL .
                        "                                         (/var/www/)" . PHP_EOL .
                        "   --new_server_path=<new_path>        The new server path of installation" . PHP_EOL .
                        "                                         (if empty & old_server_path too, don't move)" . PHP_EOL .
                        "   --prefix=<prefix>                   DB tables are prefixed with <prefix>" . PHP_EOL .
                        "   --plugins=<plugins list>            Comma separated list of plugin slugs" . PHP_EOL .
                        "");