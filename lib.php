<?php
/*
 * Fonction is_serialized($data)
 * -----
 * Fonction WordPress de d�tection de valeur s�rialis�e
 * -----
 * @param   mixed      $data                    valeur d'entr�e
 * -----
 * @return  bool                                valeur s�rialis�e ?
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
 * Fonction replace($string, $champ)
 * -----
 * Remplacement des valeurs dans une cha�ne
 * -----
 * @param   string      $string                 cha�ne de caract�res dans laquelle doivent �tre remplac�es les valeurs
 * @param   string      $champ                 si $champ vaut 'domain' ou 'path' => on ne fait pas tout
 * -----
 * @return  string      $retour                 la cha�ne avec les nouvelles valeurs
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2011/05/17 $
 */
function replace($string, $champ = NULL) {
    $retour = $string;

    // Domaine + path
    $retour = str_replace($_POST['old_domain'].$_POST['old_path'], $_POST['new_domain'].$_POST['new_path'], $retour);

    // Si l'ancien domaine est compris dans le nouveau
    if($_POST['old_domain'] != $_POST['new_domain'] && strstr($_POST['new_domain'], $_POST['old_domain']) !== FALSE)
        $retour = str_replace($_POST['new_domain'], $_POST['old_domain'], $retour);

    // Domaine seul
    $retour = str_replace($_POST['old_domain'], $_POST['new_domain'], $retour);

    // Path seul
    if(!empty($_POST['old_path'])) {
        // Si l'ancien path est compris dans le nouveau
        if($_POST['old_path'] != $_POST['new_path'] && strstr($_POST['new_path'], $_POST['old_path']) !== FALSE)
            $retour = str_replace($_POST['new_path'], $_POST['old_path'], $retour);
        $retour = str_replace($_POST['old_path'], $_POST['new_path'], $retour);
    }

    // Champs particuliers
    if($champ == 'domain')
        $retour = $_POST['new_domain'];
    if($champ == 'path')
        $retour = !empty($_POST['old_path']) || !empty($_POST['new_path']) ? $_POST['new_path'].'/' : $string;

    // Changer le path serveur
    if(!empty($_POST['new_filepath'])) {
        // Si l'ancien path est compris dans l'ancien path serveur, on l'a remplacé tout à l'heure
        if(!empty($_POST['old_path']) && strstr($_POST['old_filepath'], $_POST['old_path']) !== FALSE) {
            $path_ko = str_replace($_POST['old_path'], $_POST['new_path'], $_POST['old_filepath']);
            $retour = str_replace($path_ko, $_POST['old_filepath'], $retour);
        }
        $retour = str_replace($_POST['old_filepath'], $_POST['new_filepath'], $retour);
    }

    return $retour;
}

/*
 * Fonction replace_recursive($val)
 * -----
 * Fonction r�cursive de remplacement des valeurs. Appelle replace() lorsque la valeur n'est plus un tableau, s'appelle elle-m�me sinon
 * -----
 * @param   mixed      $val                 la variable dans laquelle les valeurs doivent �tre remplac�es
 * -----
 * @return  mixed      $val                 la variable avec les nouvelles valeurs
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2011/05/17 $
 */
function replace_recursive($val) {
    $unset = array();
    if(is_array($val)) {
        foreach($val as $k => $v) {
            $new_k = replace($k);
            if($new_k != $k)
                $unset[] = $k;
            $val[$new_k] = replace_recursive($v);
        }
    }
    else
        $val = replace($val);

    foreach($unset as $k)
        unset($val[$k]);

    return $val;
}

/*
 * Fonction update($table, $champ, &$message)
 * -----
 * R�cup�re les valeurs d'une table et les met � jour ensuite. Fait appel � replace_recursive() et replace() pour le remplacement et � do_update() pour la mise � jour
 * -----
 * @param   string      $table                  le nom de la table � mettre � jour
 * @param   array       $champ                  les champs utiles : 0 => id, 1 => value
 * @param   array       &$message               si une erreur survient, on remplit ce tableau
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2011/05/17 $
 */
function update($table, $champ, &$message, $blog = FALSE) {
    global $blog_id;

    if(!is_array($champ) || empty($champ))
        $message['fatal'][] = sprintf(STR_ERROR_FATAL_TABLE, $table);
    else {
        $table_name = $_POST['prefix'].$table;
        $id = $champ[0];
        $value = $champ[1];
        $sql_value = $value != 'path' ? $value : 'domain';
        $sql = 'SELECT '.implode(', ', $champ).' FROM '.$table_name.' WHERE ';
        if($blog)
            $sql.= 'blog_id = '.$blog_id.' ';
        else {
            $sql.= $sql_value.' ';
            $sql.= 'LIKE "%'.mysql_real_escape_string($_POST['old_domain']).'%" ';
            if(!empty($_POST['old_path'])) {
                $sql.= 'OR '.$sql_value.' ';
                $sql.= 'LIKE "%'.mysql_real_escape_string($_POST['old_path']).'%" ';
            }
            if(!empty($_POST['old_filepath'])) {
                $sql.= 'OR '.$sql_value.' ';
                $sql.= 'LIKE "%'.str_replace('\\\\', '\\\\\\\\', mysql_real_escape_string($_POST['old_filepath'])).'%" ';
            }
        }

        if($rs = mysql_query($sql)) {
            $update = 'UPDATE '.$table_name.' SET '.$value.' = "%s" WHERE '.$id.' = "%d"';
            while($row = mysql_fetch_assoc($rs)) {
                $double_serialize = FALSE;
                if(is_serialized($row[$value])) {
                    $row[$value] = @unserialize($row[$value]);
                    // Pour des options comme wp_carousel...
                    if(is_serialized($row[$value])) {
                        $row[$value] = @unserialize($row[$value]);
                        $double_serialize = TRUE;
                    }
                    if(is_array($row[$value])) {
                        $row[$value] = replace_recursive($row[$value]);
                    }
                    else
                        $row[$value] = replace($row[$value], $value);
                    $row[$value] = serialize($row[$value]);
                    // Pour des options comme wp_carousel...
                    if($double_serialize)
                        $row[$value] = serialize($row[$value]);
                }
                else
                    $row[$value] = replace($row[$value], $value);

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
 * Supprime les caract�res superflus des cha�nes envoy�es en POST.
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
        'host',
        'user',
        'pass',
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
    $_POST['old_domain'] = strrpos($_POST['old_domain'], '/') == $l ? substr($_POST['old_domain'], 0, $l) : $_POST['old_domain'];

    $l = strlen($_POST['new_domain']) - 1;
    $_POST['new_domain'] = strrpos($_POST['new_domain'], '/') == $l ? substr($_POST['new_domain'], 0, $l) : $_POST['new_domain'];

    // Enlever le '/' des paths s'il existe
    $_POST['old_path'] = strpos($_POST['old_path'], '/') === 0 ? substr($_POST['old_path'], 1) : $_POST['old_path'];
    $l = strlen($_POST['old_path']) - 1;
    $_POST['old_path'] = strrpos($_POST['old_path'], '/') == $l ? substr($_POST['old_path'], 0, $l) : $_POST['old_path'];

    $_POST['new_path'] = strpos($_POST['new_path'], '/') === 0 ? substr($_POST['new_path'], 1) : $_POST['new_path'];
    $l = strlen($_POST['new_path']) - 1;
    $_POST['new_path'] = strrpos($_POST['new_path'], '/') == $l ? substr($_POST['new_path'], 0, $l) : $_POST['new_path'];

    // Enlever le '/' des filepaths s'il existe
    $l = strlen($_POST['old_filepath']) - 1;
    $_POST['old_filepath'] = strrpos($_POST['old_filepath'], '/') == $l || strrpos($_POST['old_filepath'], '\\') == $l  ? substr($_POST['old_filepath'], 0, $l) : $_POST['old_filepath'];

    $l = strlen($_POST['new_filepath']) - 1;
    $_POST['new_filepath'] = strrpos($_POST['new_filepath'], '/') == $l || strrpos($_POST['new_filepath'], '\\') == $l ? substr($_POST['new_filepath'], 0, $l) : $_POST['new_filepath'];

    // Si on ne change pas de domaine => le nouveau est l'ancien (champ obligatoire)
    if(empty($_POST['new_domain'])) {
        $_POST['new_domain'] = $_POST['old_domain'];
    }

    $_POST['old_path'] = !empty($_POST['old_path']) ? '/'.$_POST['old_path'] : '';
    $_POST['new_path'] = !empty($_POST['new_path']) ? '/'.$_POST['new_path'] : '';
}

/*
 * Fonction do_update($sql)
 * -----
 * En mode debug, affiche la requ�te, sinon la joue pour mettre � jour la base
 * -----
 * @param   string      $sql                    la requ�te de mise � jour
 * -----
 * @return  bool        $retour                 tout s'est bien pass� ?
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
 * D�tecte si l'installation WordPress est en multisite, gr�ce � la table wp_blogs.
 * -----
 * @return  bool        $retour                 installation multisite ?
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2011/05/17 $
 */
function is_multisite() {
    return table_exists($_POST['prefix'].'blogs');
}

/*
 * Fonction table_exists($nom)
 * -----
 * D�tecte si une table existe dans l'installation
 * -----
 * @param  string   $nom                le nom de la table � trouver
 * -----
 * @return  bool                        la table existe ?
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2011/05/18 $
 */
function table_exists($nom) {
    global $table;

    if(empty($table)) {
        $rs = mysql_query('SHOW TABLES');
        while($row = mysql_fetch_assoc($rs))
            $table[] = $row['Tables_in_'.$_POST['base']];
    }

    return in_array($nom, $table);
}