<!DOCTYPE html>
<html>
    <head>
        <title><?php echo STR_TITLE; ?></title>

        <link rel="stylesheet" href="style.css">
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    </head>
    <body>
        <div id="header-container">
            <header class="wrapper">
                <h1 id="title"><?php echo STR_TITLE; ?></h1>
            </header>
        </div>
        <div id="main" class="wrapper">
            <?php
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
                            <td colspan="2" class="no-padding">
                                <fieldset>
                                    <legend><?php echo STR_LIBELLE_FIELDSET_BDD; ?></legend>
                                    <table>
                                        <tr>
                                            <td><label for="host"><?php echo STR_LIBELLE_HOST; ?></label></td>
                                            <td><input type="text" name="host" id="host" /></td>
                                            <td class="legend"><?php echo sprintf(STR_LIBELLE_DEFAULT_VALUE, CFG_HOST); ?></td>
                                        </tr>
                                        <tr>
                                            <td><label for="user"><?php echo STR_LIBELLE_USER; ?></label></td>
                                            <td><input type="text" name="user" id="user" /></td>
                                            <td class="legend"><?php echo sprintf(STR_LIBELLE_DEFAULT_VALUE, CFG_USER); ?></td>
                                        </tr>
                                        <tr>
                                            <td><label for="pass"><?php echo STR_LIBELLE_PASS; ?></label></td>
                                            <td><input type="password" name="pass" id="pass" /><input type="checkbox" value="1" name="empty_pass" id="empty_pass" /><label class="inline" for="empty_pass">Cocher pour un mot de passe vide</label></td>
                                            <td class="legend"><?php echo sprintf(STR_LIBELLE_DEFAULT_VALUE, CFG_PASS); ?></td>
                                        </tr>
                                        <tr>
                                            <td><label for="base"><?php echo STR_LIBELLE_BASE.STR_FORM_REQUIRE_SYMBOL; ?></label></td>
                                            <td><input type="text" name="base" id="base" autofocus /></td>
                                        </tr>
                                    </table>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <td><label for="old_domain"><?php echo STR_LIBELLE_OLD_DOMAIN.STR_FORM_REQUIRE_SYMBOL; ?></label></td>
                            <td><input type="text" name="old_domain" id="old_domain" /></td>
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
                            <td><label for="prefix"><?php echo STR_LIBELLE_PREFIX; ?></label></td>
                            <td><input type="text" name="prefix" id="prefix" value="wp_" /></td>
                        </tr>
                        <tr>
                            <td><label for="link_update"><?php echo STR_LIBELLE_LINK_UPDATE; ?></label></td>
                            <td><input type="checkbox" name="link_update" id="link_update" value="1" checked /></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="no-padding">
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
                    <p><?php echo STR_FORM_LEGEND; ?></p>
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