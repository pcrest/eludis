<?php
/*
 * Plugin CFG pour SPIP
 * (c) toggg, marcimat 2007-2008, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

function cfg_verifier_type_idnum($nom, $val){
	if (!is_numeric($val)){
		return _T('cfg:erreur_type_idnum', array('champ'=>$nom));
	}
}

function cfg_pre_traiter_type_idnum($nom, $val){
	return array(1, intval($val));
}

?>
