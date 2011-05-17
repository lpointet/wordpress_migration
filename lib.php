<?php
/*
 * Fonction is_serialized($data)
 * -----
 * Fonction WordPress de détection de valeur sérialisée
 * -----
 * @param   mixed      $data                    valeur d'entrée
 * -----
 * @return  bool                                valeur sérialisée ?
 * -----
 * $Author: WordPress $
 */
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

/*
 * Fonction replace($string)
 * -----
 * Remplacement des valeurs dans une chaîne
 * -----
 * @param   string      $string                 chaîne de caractères dans laquelle doivent être remplacées les valeurs
 * -----
 * @return  string      $retour                 la chaîne avec les nouvelles valeurs
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2011/05/17 $
 */
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

/*
 * Fonction replace_recursive($val)
 * -----
 * Fonction récursive de remplacement des valeurs. Appelle replace() lorsque la valeur n'est plus un tableau, s'appelle elle-même sinon
 * -----
 * @param   mixed      $val                 la variable dans laquelle les valeurs doivent être remplacées
 * -----
 * @return  mixed      $val                 la variable avec les nouvelles valeurs
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2011/05/17 $
 */
function replace_recursive($val) {
    if(is_array($val)) {
        foreach($val as $k => $v)
            $val[$k] = replace_recursive($v);
    }
    else
        $val = replace($val);

    return $val;
}

/*
 * Fonction update($table, $champ, &$message)
 * -----
 * Récupère les valeurs d'une table et les met à jour ensuite. Fait appel à replace_recursive() et replace() pour le remplacement et à do_update() pour la mise à jour
 * -----
 * @param   string      $table                  le nom de la table à mettre à jour
 * @param   array       $champ                  les champs utiles : 0 => id, 1 => value
 * @param   array       &$message               si une erreur survient, on remplit ce tableau
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2011/05/17 $
 */
function update($table, $champ, &$message) {
    if(!is_array($champ) || empty($champ))
        $message['fatal'][] = sprintf(STR_ERROR_FATAL_TABLE, $table);
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
                    $message['fatal'][] = sprintf(STR_ERROR_FATAL_CHAMP, $id, $row[$id], $value, $row[$value], $table_name);
                }
            }
        }
    }
}

/*
 * Fonction clean()
 * -----
 * Supprime les caractères superflus des chaînes envoyées en POST.
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2011/05/17 $
 */
function clean() {
    $champ = array(
        'old_domain',
        'new_domain',
        'old_path',
        'new_path',
        'old_filepath',
        'new_filepath',
    );
    // Enlever les espaces inutiles
    foreach($champ as $c)
        $_POST[$c] = trim($_POST[$c]);

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

/*
 * Fonction do_update($sql)
 * -----
 * En mode debug, affiche la requête, sinon la joue pour mettre à jour la base
 * -----
 * @param   string      $sql                    la requête de mise à jour
 * -----
 * @return  bool        $retour                 tout s'est bien passé ?
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2011/05/17 $
 */
function do_update($sql) {
    $retour = TRUE;
    if(CFG_DEBUG)
        echo $sql.'<br/>';
    else
        $retour = mysql_query($sql);

    return $retour;
}

/*
 * Fonction is_multisite()
 * -----
 * Détecte si l'installation WordPress est en multisite, grâce à la table wp_blogs.
 * TODO : Bugfix -> $_POST['prefix'] devrait être utilisé
 * -----
 * @return  bool        $retour                 installation multisite ?
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2011/05/17 $
 */
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