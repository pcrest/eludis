<?php

/*
 * Plugin CFG pour SPIP
 * (c) toggg, marcimat 2007-2008, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


function cfg_verifier_type_id($nom, $val){
	if (!preg_match('#^[a-z_]\w*$#', $val)){
		return _T('cfg:erreur_type_id', array('champ'=>$nom));
	}
}

?>
