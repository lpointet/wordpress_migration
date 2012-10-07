<?php
define('ABSPATH', realpath(dirname(__FILE__)));
require ABSPATH . '/config.php';
require ABSPATH . '/lang.php';
require ABSPATH . '/lib.php';

$display = NULL;

if('cli' === CFG_DISPLAY) {
    // CLI
    $param = get_cli_param();
}
else {
    // CGI
    $param = $_POST;
}

$message = array();
if(!empty($param)) {
    clean();
    // Gestion des champs obligatoires
    if(empty($param['base'])) {
        $message['fatal'][] = sprintf(STR_ERROR_FATAL_REQUIRED_FIELD, STR_LIBELLE_BASE);
    }
    if(empty($param['old_domain'])) {
        $message['fatal'][] = sprintf(STR_ERROR_FATAL_REQUIRED_FIELD, STR_LIBELLE_OLD_DOMAIN);
    }
    else {
        $host = !empty($param['host']) ? $param['host'] : CFG_HOST;
        $user = !empty($param['user']) ? $param['user'] : CFG_USER;
        if(!empty($param['empty_pass']))
            $pass = '';
        else
            $pass = !empty($param['pass']) ? $param['pass'] : CFG_PASS;
        if(@mysql_connect($host, $user, $pass)) {
            if(@mysql_select_db($param['base'])) {
                mysql_query('SET NAMES UTF8');

                $multisite = is_multisite();
                
                $blog_id = 1;
                if(preg_match('/([0-9]+)_$/', $param['prefix'], $match))
                    $blog_id = $match[1];

                // Les options
                update('options', array('option_id', 'option_value'), $message);
                // Les posts
                update('posts', array('ID', 'post_content'), $message);
                update('posts', array('ID', 'guid'), $message);
                // Les postmetas
                update('postmeta', array('meta_id', 'meta_value'), $message);
                // Les liens
                if(!empty($param['link_update'])) {
                    update('links', array('link_id', 'link_url'), $message);
                    update('links', array('link_id', 'link_image'), $message);
                }

                // Le multisite
                if($multisite) {
                    // Blogs
                    update('blogs', array('blog_id', 'domain'), $message);
                    update('blogs', array('blog_id', 'path'), $message, TRUE);
                    // Site
                    update('site', array('id', 'path'), $message);
                    update('site', array('id', 'domain'), $message);
                    // Sitemetas
                    update('sitemeta', array('meta_id', 'meta_value'), $message);
                }

                // Les plugins
                if(!empty($param['plugin'])) {
                    // Pour tous les plugins cochés
                    foreach($param['plugin'] as $name) {
                        // On les connait ?
                        if(!empty($known_plugin[$name]) && !empty($known_plugin[$name]['update'])) {
                            // Il y a potentiellement plusieurs tables à mettre à jour, dans la clé 'update'
                            foreach($known_plugin[$name]['update'] as $update) {
                                if(!empty($update['table']) && !empty($update['champ'])) {
                                    // Il y a peut-être plusieurs mises à jour à faire sur cette table
                                    foreach($update['champ'] as $champ)
                                        update($update['table'], $champ, $message);
                                }
                            }
                        }
                    }
                }

                if(empty($message))
                    $message['warning'][] = STR_ERROR_WARNING_MIGRATION_DONE;
            }
            else
                $message['fatal'][] = sprintf(STR_ERROR_FATAL_BASE, $param['base']);
        }
        else
            $message['fatal'][] = STR_ERROR_FATAL_CONNEXION;
    }
}

if(file_exists(ABSPATH . '/template_' . CFG_DISPLAY . '.php')) {
    require ABSPATH . '/template_' . CFG_DISPLAY . '.php';
}
