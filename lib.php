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
    global $param;

    $retour = $string;

    // Domaine + path
    $retour = str_replace($param['old_domain'].$param['old_path'], $param['new_domain'].$param['new_path'], $retour);

    // Si l'ancien domaine est compris dans le nouveau
    if($param['old_domain'] != $param['new_domain'] && strstr($param['new_domain'], $param['old_domain']) !== FALSE)
        $retour = str_replace($param['new_domain'], $param['old_domain'], $retour);

    // Domaine seul
    $retour = str_replace($param['old_domain'], $param['new_domain'], $retour);

    // Path seul
    if(!empty($param['old_path'])) {
        // Si l'ancien path est compris dans le nouveau
        if($param['old_path'] != $param['new_path'] && strstr($param['new_path'], $param['old_path']) !== FALSE)
            $retour = str_replace($param['new_path'], $param['old_path'], $retour);
        $retour = str_replace($param['old_path'], $param['new_path'], $retour);
    }

    // Champs particuliers
    if($champ == 'domain')
        $retour = $param['new_domain'];
    if($champ == 'path')
        $retour = !empty($param['old_path']) || !empty($param['new_path']) ? $param['new_path'].'/' : $string;

    // Changer le path serveur
    if(!empty($param['new_filepath'])) {
        // Si l'ancien path est compris dans l'ancien path serveur, on l'a remplacé tout à l'heure
        if(!empty($param['old_path']) && strstr($param['old_filepath'], $param['old_path']) !== FALSE) {
            $path_ko = str_replace($param['old_path'], $param['new_path'], $param['old_filepath']);
            $retour = str_replace($path_ko, $param['old_filepath'], $retour);
        }
        $retour = str_replace($param['old_filepath'], $param['new_filepath'], $retour);
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
            $val[$new_k] = try_replace( $val, $k );
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
    global $blog_id, $param;

    if(!is_array($champ) || empty($champ))
        $message['fatal'][] = sprintf(STR_ERROR_FATAL_TABLE, $table);
    else {
        $table_name = $param['prefix'].$table;
        $id = $champ[0];
        $value = $champ[1];
        $sql_value = $value != 'path' ? $value : 'domain';
        $sql = 'SELECT '.implode(', ', $champ).' FROM '.$table_name.' WHERE ';
        if($blog)
            $sql.= 'blog_id = '.$blog_id.' ';
        else {
            $sql.= $sql_value.' ';
            $sql.= 'LIKE "%'.mysql_real_escape_string($param['old_domain']).'%" ';
            if(!empty($param['old_path'])) {
                $sql.= 'OR '.$sql_value.' ';
                $sql.= 'LIKE "%'.mysql_real_escape_string($param['old_path']).'%" ';
            }
            if(!empty($param['old_filepath'])) {
                $sql.= 'OR '.$sql_value.' ';
                $sql.= 'LIKE "%'.str_replace('\\\\', '\\\\\\\\', mysql_real_escape_string($param['old_filepath'])).'%" ';
            }
        }

        if($rs = mysql_query($sql)) {
            $update = 'UPDATE '.$table_name.' SET '.$value.' = "%s" WHERE '.$id.' = "%d"';
            while($row = mysql_fetch_assoc($rs)) {
                $row[$value] = try_replace( $row, $value );

                if(!do_update(sprintf($update, mysql_real_escape_string($row[$value]), $row[$id]))) {
                    $message['fatal'][] = sprintf(STR_ERROR_FATAL_CHAMP, $id, $row[$id], $value, $row[$value], $table_name);
                }
            }
        }
    }
}

/**
 * Fonction try_replace($row, $value)
 * -----
 * Essaie de remplacer une valeur dans un tableau
 * -----
 * @param   array       $row                    le tableau
 * @param   string      $value                  l'indice de la valeur à remplacer
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2013/06/07 $
 */
function try_replace( $row, $value ) {
    if(is_serialized($row[$value])) {
        $double_serialize = FALSE;
        $row[$value] = @unserialize($row[$value]);
        // Pour des options comme wp_carousel...
        if(is_serialized($row[$value])) {
            $row[$value] = @unserialize($row[$value]);
            $double_serialize = TRUE;
        }
        if(is_array($row[$value])) {
            $row[$value] = replace_recursive($row[$value]);
        }
        else if(is_object($row[$value]) || $row[$value] instanceof __PHP_Incomplete_Class) { // Étrange fonctionnement avec Google Sitemap...
            $array_object = (array) $row[$value];
            $array_object = replace_recursive($array_object);
            foreach($array_object as $key => $value)
                $row[$value]->$key = $value;
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

    return $row[$value];
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
    global $param;

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
        $param[$c] = trim($param[$c]);

    // Enlever le 'http://' des domaines s'il existe
    $param['old_domain'] = strpos($param['old_domain'], 'http://') === 0 ? substr($param['old_domain'], 7) : $param['old_domain'];
    $param['old_domain'] = strpos($param['old_domain'], 'https://') === 0 ? substr($param['old_domain'], 8) : $param['old_domain'];
    $param['new_domain'] = strpos($param['new_domain'], 'http://') === 0 ? substr($param['new_domain'], 7) : $param['new_domain'];
    $param['new_domain'] = strpos($param['new_domain'], 'https://') === 0 ? substr($param['new_domain'], 8) : $param['new_domain'];

    // Enlever le '/' des domaines s'il existe
    $l = strlen($param['old_domain']) - 1;
    $param['old_domain'] = strrpos($param['old_domain'], '/') == $l ? substr($param['old_domain'], 0, $l) : $param['old_domain'];

    $l = strlen($param['new_domain']) - 1;
    $param['new_domain'] = strrpos($param['new_domain'], '/') == $l ? substr($param['new_domain'], 0, $l) : $param['new_domain'];

    // Enlever le '/' des paths s'il existe
    $param['old_path'] = strpos($param['old_path'], '/') === 0 ? substr($param['old_path'], 1) : $param['old_path'];
    $l = strlen($param['old_path']) - 1;
    $param['old_path'] = strrpos($param['old_path'], '/') == $l ? substr($param['old_path'], 0, $l) : $param['old_path'];

    $param['new_path'] = strpos($param['new_path'], '/') === 0 ? substr($param['new_path'], 1) : $param['new_path'];
    $l = strlen($param['new_path']) - 1;
    $param['new_path'] = strrpos($param['new_path'], '/') == $l ? substr($param['new_path'], 0, $l) : $param['new_path'];

    // Enlever le '/' des filepaths s'il existe
    $l = strlen($param['old_filepath']) - 1;
    $param['old_filepath'] = strrpos($param['old_filepath'], '/') == $l || strrpos($param['old_filepath'], '\\') == $l  ? substr($param['old_filepath'], 0, $l) : $param['old_filepath'];

    $l = strlen($param['new_filepath']) - 1;
    $param['new_filepath'] = strrpos($param['new_filepath'], '/') == $l || strrpos($param['new_filepath'], '\\') == $l ? substr($param['new_filepath'], 0, $l) : $param['new_filepath'];

    // Si on ne change pas de domaine => le nouveau est l'ancien (champ obligatoire)
    if(empty($param['new_domain'])) {
        $param['new_domain'] = $param['old_domain'];
    }

    $param['old_path'] = !empty($param['old_path']) ? '/'.$param['old_path'] : '';
    $param['new_path'] = !empty($param['new_path']) ? '/'.$param['new_path'] : '';
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
    global $param;

    return table_exists($param['prefix'].'blogs');
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
    global $table, $param;

    if(empty($table)) {
        $rs = mysql_query('SHOW TABLES');
        while($row = mysql_fetch_assoc($rs))
            $table[] = $row['Tables_in_'.$param['base']];
    }

    return in_array($nom, $table);
}

/*
 * Fonction get_cli_param()
 * -----
 * Retourne les paramètres passés en CLI sous forme de tableau associatif
 * -----
 * @return  array                        Les paramètres passés
 * -----
 * $Author: Lionel POINTET $
 * $Date: 2012/10/05 $
 */
function get_cli_param() {
    // Options requises
    $options = 'h:'; // Host
    $options.= 'u:'; // User
    $options.= 'b:'; // Base

    // Options facultatives
    $options.= 'p::'; // Pass

    // Options sans valeur
    $options.= 'l'; // Link update

    // Longues options
    $longopts = array(
        // Requises
        'old_domain:', // Old domain
        // Optionnelles
        'prefix::', // Prefix
        'plugins::', // Plugins
        'new_domain::', // New domain
        'old_path::', // Old path
        'new_path::', // New path
        'old_filepath::', // Old server path
        'new_filepath::', // New server path
    );

    $params = getopt($options, $longopts);

    if( !$params )
        return $params;

    return array(
        'host' => ( !empty($params['h']) ? $params['h'] : NULL ),
        'user' => ( !empty($params['u']) ? $params['u'] : NULL ),
        'base' => ( !empty($params['b']) ? $params['b'] : NULL ),
        'pass' => ( !empty($params['pass']) ? $params['pass'] : NULL ),
        'empty_pass' => ( !empty($params['pass']) ? FALSE : TRUE ),
        'link_update' => ( isset($params['l']) ? TRUE : FALSE ),
        'plugin' => ( !empty($params['plugins']) ? explode(CFG_PLUGIN_SEP, $params['plugins']) : NULL ),
        'prefix' => ( !empty($params['prefix']) ? $params['prefix'] : NULL ),
        'old_domain' => ( !empty($params['old_domain']) ? $params['old_domain'] : NULL ),
        'new_domain' => ( !empty($params['new_domain']) ? $params['new_domain'] : NULL ),
        'old_path' => ( !empty($params['old_path']) ? $params['old_path'] : NULL ),
        'new_path' => ( !empty($params['new_path']) ? $params['new_path'] : NULL ),
        'old_filepath' => ( !empty($params['old_filepath']) ? $params['old_filepath'] : NULL ),
        'new_filepath' => ( !empty($params['new_filepath']) ? $params['new_filepath'] : NULL ),
    );
}