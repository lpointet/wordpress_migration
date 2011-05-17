<?php
define('CFG_DEBUG', FALSE);

function is_serialized( $data ) {
	// if it isn't a string, it isn't serialized
	if ( ! is_string( $data ) )
		return FALSE;
	$data = trim( $data );
 	if ( 'N;' == $data )
		return TRUE;
	$length = strlen( $data );
	if ( $length < 4 )
		return FALSE;
	if ( ':' !== $data[1] )
		return FALSE;
	$lastc = $data[$length-1];
	if ( ';' !== $lastc && '}' !== $lastc )
		return FALSE;
	$token = $data[0];
	switch ( $token ) {
		case 's' :
			if ( '"' !== $data[$length-2] )
				return FALSE;
		case 'a' :
		case 'O' :
			return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
		case 'b' :
		case 'i' :
		case 'd' :
			return (bool) preg_match( "/^{$token}:[0-9.E-]+;\$/", $data );
	}
	return FALSE;
}

function replace($string) {
    $retour = $string;

    $retour = str_replace($_POST['old_domain'].(!empty($_POST['old_path'])?'/'.$_POST['old_path']:''), $_POST['new_domain'].(!empty($_POST['new_path'])?'/'.$_POST['new_path']:''), $retour);
    $retour = str_replace($_POST['old_domain'], $_POST['new_domain'], $retour);
    if(!empty($_POST['old_path']))
        $retour = str_replace($_POST['old_path'], $_POST['new_path'], $retour);
    if(!empty($_POST['new_filepath']))
        $retour = str_replace($_POST['old_filepath'], $_POST['new_filepath'], $retour);
    return $retour;
}

function replace_recursive($val) {
    if(is_array($val)) {
        foreach($val as $k => $v)
            $val[$k] = replace_recursive($v);
    }
    else
        $val = replace($val);

    return $val;
}

function update($table, $champ, &$message) {
    if(!is_array($champ) || empty($champ))
        $message['fatal'][] = 'Erreur lors de la migration de "'.$table.'".';
    else {
        $table_name = $_POST['prefix'].$table;
        $id = $champ[0];
        $value = $champ[1];
        $sql = 'SELECT '.implode(', ', $champ).' FROM '.$table_name.' WHERE '.$value.' LIKE "%'.mysql_real_escape_string($_POST['old_domain']).'%" ';

        if($rs = mysql_query($sql)) {
            $update = 'UPDATE '.$table_name.' SET '.$value.' = "%s" WHERE '.$id.' = "%d"';
            while($row = mysql_fetch_assoc($rs)) {
                if(is_serialized($row[$value])) {
                    $row[$value] = @unserialize($row[$value]);
                    // Pour des options comme wp_carousel...
                    if(is_serialized($row[$value])) {
                        $row[$value] = @unserialize($row[$value]);
                        define('DOUBLE_SERIALIZE', TRUE);
                    }
                    if(is_array($row[$value])) {
                        $row[$value] = replace_recursive($row[$value]);
                    }
                    else
                        $row[$value] = replace($row[$value]);
                    $row[$value] = serialize($row[$value]);
                    // Pour des options comme wp_carousel...
                    if(defined('DOUBLE_SERIALIZE'))
                        $row[$value] = serialize($row[$value]);
                }
                else
                    $row[$value] = replace($row[$value]);

                if(!do_update(sprintf($update, mysql_real_escape_string($row[$value]), $row[$id]))) {
                    $message['fatal'][] = 'Une erreur s\'est produite lors de la mise à jour du champ : '.$id.' = '.$row[$id].', '.$value.' = '.$row[$value].' dans la table '.$table_name;
                }
            }
        }
    }
}

function clean() {
    // Enlever le 'http://' des domaines s'il existe
    $_POST['old_domain'] = strpos($_POST['old_domain'], 'http://') === 0 ? substr($_POST['old_domain'], 7) : $_POST['old_domain'];
    $_POST['old_domain'] = strpos($_POST['old_domain'], 'https://') === 0 ? substr($_POST['old_domain'], 8) : $_POST['old_domain'];
    $_POST['new_domain'] = strpos($_POST['new_domain'], 'http://') === 0 ? substr($_POST['new_domain'], 7) : $_POST['new_domain'];
    $_POST['new_domain'] = strpos($_POST['new_domain'], 'https://') === 0 ? substr($_POST['new_domain'], 8) : $_POST['new_domain'];

    // Enlever le '/' des domaines s'il existe
    $l = strlen($_POST['old_domain']) - 1;
    $_POST['old_domain'] = strpos($_POST['old_domain'], '/') == $l ? substr($_POST['old_domain'], 0, $l) : $_POST['old_domain'];

    $l = strlen($_POST['new_domain']) - 1;
    $_POST['new_domain'] = strpos($_POST['new_domain'], '/') == $l ? substr($_POST['new_domain'], 0, $l) : $_POST['new_domain'];

    // Enlever le '/' des paths s'il existe
    $_POST['old_path'] = strpos($_POST['old_path'], '/') === 0 ? substr($_POST['old_path'], 1) : $_POST['old_path'];
    $l = strlen($_POST['old_path']) - 1;
    $_POST['old_path'] = strpos($_POST['old_path'], '/') == $l ? substr($_POST['old_path'], 0, $l) : $_POST['old_path'];

    $_POST['new_path'] = strpos($_POST['new_path'], '/') === 0 ? substr($_POST['new_path'], 1) : $_POST['new_path'];
    $l = strlen($_POST['new_path']) - 1;
    $_POST['new_path'] = strpos($_POST['new_path'], '/') == $l ? substr($_POST['new_path'], 0, $l) : $_POST['new_path'];

    // Enlever le '/' des filepaths s'il existe
    $l = strlen($_POST['old_filepath']) - 1;
    $_POST['old_filepath'] = strpos($_POST['old_filepath'], '/') == $l ? substr($_POST['old_filepath'], 0, $l) : $_POST['old_filepath'];

    $l = strlen($_POST['new_filepath']) - 1;
    $_POST['new_filepath'] = strpos($_POST['new_filepath'], '/') == $l ? substr($_POST['new_filepath'], 0, $l) : $_POST['new_filepath'];
}

function do_update($sql) {
    $retour = TRUE;
    if(CFG_DEBUG)
        echo $sql.'<br/>';
    else
        $retour = mysql_query($sql);

    return $retour;
}

function is_multisite() {
    $rs = mysql_query('SHOW TABLES');
    $retour = FALSE;
    while($row = mysql_fetch_assoc($rs)) {
        if(strpos($row['Tables_in_'.$_POST['base']], 'wp_blogs') !== FALSE) {
            $retour = TRUE;
            break;
        }
    }

    return $retour;
}
?>
<!DOCTYPE html>
<html>
    <head></head>
    <body>
        <style>
            html {
                background-color:#ccc;
            }
            fieldset {
                background-color:#57d;
                box-shadow:#333 0 0 10px;
                border-top-left-radius:15px;
                border-bottom-right-radius:15px;
                position:relative;
                padding-top:30px;
            }
            legend {
                padding:3px 10px;
                background-color:#fff;
                color:#57d;
                font-weight:700;
                border-radius:5px;
                box-shadow:#333 5px 5px 5px;
                position:absolute;
                top:-25px;
                left:10%;
            }
            input[type=text] {
                width:500px;
            }
            label {
                padding-right:5px;
                font-weight:700;
                color:#eee;
            }
        </style>
        <?php
        $message = array();
        if(!empty($_POST)) {
            mysql_connect('localhost', 'username', 'password');
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
                    $message['warning'][] = 'Migration effectuée !';
            }
            else
                $message['fatal'][] = 'La base '.$_POST['base'].' n\'existe pas !';
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
                <fieldset>
                    <legend>Configuration</legend>
                    <p><label for="old_domain">Ancien domaine :</label><input type="text" name="old_domain" id="old_domain" /></p>
                    <p><label for="new_domain">Nouveau domaine :</label><input type="text" name="new_domain" id="new_domain" /></p>
                    <p><label for="old_path">Ancien path :</label><input type="text" name="old_path" id="old_path" /></p>
                    <p><label for="new_path">Nouveau path :</label><input type="text" name="new_path" id="new_path" /></p>
                    <p><label for="old_filepath">Ancien path serveur :</label><input type="text" name="old_filepath" id="old_filepath" value="/usr/local/apache/htdocs/" /></p>
                    <p><label for="new_filepath">Nouveau path serveur :</label><input type="text" name="new_filepath" id="new_filepath" /></p>
                    <p><label for="base">Nom de la base :</label><input type="text" name="base" id="base" /></p>
                    <p><label for="prefix">Préfixe des tables :</label><input type="text" name="prefix" id="prefix" value="wp_" /></p>
                    <p><label for="link_update">Mettre à jour les liens</label><input type="checkbox" name="link_update" id="link_update" value="1" checked /></p>
                    <p><input type="submit" value="Migrer !" />
                </fieldset>
            </form>
            <?php
        }
        ?>
    </body>
</html>