<?php
require 'config.php';
require 'lang.php';
require 'lib.php';
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo STR_TITLE; ?></title>

        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div id="header-container">
            <header class="wrapper">
                <h1 id="title"><?php echo STR_TITLE; ?></h1>
            </header>
        </div>
        <div id="main" class="wrapper">
            <?php
            $message = array();
            if(!empty($_POST)) {
                mysql_connect(CFG_HOST, CFG_USER, CFG_PASS);
                if(mysql_select_db($_POST['base'])) {
                    mysql_query('SET NAMES UTF8');
                    clean();

                    $multisite = is_multisite();

                    // Les options
                    update('options', array('option_id', 'option_value'), $message);
                    // Les posts
                    update('posts', array('ID', 'post_content'), $message);
                    update('posts', array('ID', 'guid'), $message);
                    // Les postmetas
                    update('postmeta', array('meta_id', 'meta_value'), $message);
                    // Les liens
                    if(!empty($_POST['link_update'])) {
                        update('links', array('link_id', 'link_url'), $message);
                        update('links', array('link_id', 'link_image'), $message);
                    }

                    // Le multisite
                    if($multisite) {
                        // Blogs
                        update('blogs', array('blog_id', 'domain'), $message);
                        update('blogs', array('blog_id', 'path'), $message);
                        // Site
                        update('site', array('id', 'domain'), $message);
                        update('site', array('id', 'path'), $message);
                        // Sitemetas
                        update('sitemeta', array('meta_id', 'meta_value'), $message);
                    }
                    if(empty($message))
                        $message['warning'][] = STR_ERROR_WARNING_MIGRATION_DONE;
                }
                else
                    $message['fatal'][] = sprintf(STR_ERROR_FATAL_BASE, $_POST['base']);
            }
            if(empty($_POST) || !empty($message)) {
                if(!empty($message)) {
                    if(!empty($message['warning']))
                        echo '<ul style="color:yellow;"><li>'.implode('</li><li>', $message['warning']).'</li></ul>';
                    if(!empty($message['fatal']))
                        echo '<ul style="color:red;"><li>'.implode('</li><li>', $message['fatal']).'</li></ul>';
                }
                ?>
                <form method="post" action="">
                    <table>
                        <tr>
                            <td><label for="old_domain"><?php echo STR_LIBELLE_OLD_DOMAIN; ?></label></td>
                            <td><input type="text" name="old_domain" id="old_domain" autofocus /></td>
                        </tr>
                        <tr>
                            <td><label for="new_domain"><?php echo STR_LIBELLE_NEW_DOMAIN; ?></label></td>
                            <td><input type="text" name="new_domain" id="new_domain" /></td>
                        </tr>
                        <tr>
                            <td><label for="old_path"><?php echo STR_LIBELLE_OLD_PATH; ?></label></td>
                            <td><input type="text" name="old_path" id="old_path" /></td>
                        </tr>
                        <tr>
                            <td><label for="new_path"><?php echo STR_LIBELLE_NEW_PATH; ?></label></td>
                            <td><input type="text" name="new_path" id="new_path" /></td>
                        </tr>
                        <tr>
                            <td><label for="old_filepath"><?php echo STR_LIBELLE_OLD_FILEPATH; ?></label></td>
                            <td><input type="text" name="old_filepath" id="old_filepath" value="/usr/local/apache/htdocs/" /></td>
                        </tr>
                        <tr>
                            <td><label for="new_filepath"><?php echo STR_LIBELLE_NEW_FILEPATH; ?></label></td>
                            <td><input type="text" name="new_filepath" id="new_filepath" /></td>
                        </tr>
                        <tr>
                            <td><label for="base"><?php echo STR_LIBELLE_BASE; ?></label></td>
                            <td><input type="text" name="base" id="base" /></td>
                        </tr>
                        <tr>
                            <td><label for="prefix"><?php echo STR_LIBELLE_PREFIX; ?></label></td>
                            <td><input type="text" name="prefix" id="prefix" value="wp_" /></td>
                        </tr>
                        <tr>
                            <td><label for="link_update"><?php echo STR_LIBELLE_LINK_UPDATE; ?></label></td>
                            <td><input type="checkbox" name="link_update" id="link_update" value="1" checked /></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <fieldset>
                                    <legend><?php echo STR_LIBELLE_FIELDSET_PLUGIN; ?></legend>
                                    <table>
                                        <?php
                                        $i = 0;
                                        foreach($known_plugin as $name => $plugin) {
                                            if(!empty($plugin['label'])) {
                                                ?>
                                                <td><label for="plugin_<?php echo $i; ?>"><?php echo $plugin['label']; ?></label></td>
                                                <td><input type="checkbox" name="plugin[]" id="plugin_<?php echo $i; ?>" value="<?php echo $name; ?>" /></td>
                                                <?php
                                                ++$i;
                                            }
                                        }
                                        ?>
                                    </table>
                                </fieldset>
                            </td>
                        </tr>
                        <tr><td colspan="2"><input type="submit" value="<?php echo STR_LIBELLE_SUBMIT; ?>" /></td></tr>
                    </table>
                </form>
                <?php
            }
            ?>
        </div>
        <div id="footer-container">
            <footer class="wrapper">
                <p><?php echo STR_FOOTER; ?></p>
            </footer>
        </div>
    </body>
</html>