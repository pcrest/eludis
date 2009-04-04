<?php
/*
 * Plugin CFG pour SPIP
 * (c) toggg, marcimat 2007-2008, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 * Definitions des fonctions lire_config, ecrire_config et effacer_config.
 * 
 */


// charge le depot qui va bien en fonction de l'argument demande
// exemples : 
// meta::description
// metapack::prefixe_plugin
// metapack::prefixe/casier/champ
// tablepack::auteur@extra:8/prefixe/casier/champ
// tablepack::~id_auteur@extra/prefixe/casier/champ
//
// en l'absence du nom de depot (gauche des ::) cette fonction prendra comme suit :
// ~ en premier caractere : tablepack
// : present avant un / : tablepack
// sinon metapack
function cfg_charger_depot($args){
	list($depot,$args) = explode('::',$args,2);

	// si un seul argument, il faut trouver le depot
	if (!$args) {
		$args = $depot;
		if ($args[0] == '~'){
			$depot = 'tablepack';	
		} elseif (
			(list($head, $body) = explode('/',$args,2)) &&
			(strpos($head,':') !== false)) {
				$depot = 'tablepack';
		} else {
			if (count(explode('/',$args))>1)
				$depot = 'metapack';
			else 
				$depot = 'meta';
		}
	}

	$d = cfg_charger_classe('cfg_depot');
	$depot = new $d($depot);
	$depot->charger_args($args);
	return $depot;
}

// lire_config() permet de recuperer une config depuis le php
// memes arguments que la balise (forcement)
// $cfg: la config, lire_config('montruc') est un tableau
// lire_config('montruc/sub') est l'element "sub" de cette config
// comme la balise pour ~, ~id_auteur ou table:id
// $def: un defaut optionnel

// $unserialize est mis par l'histoire, et affecte le depot 'meta' 
function lire_config($cfg='', $def=null, $unserialize=true) {
	$lire = charger_fonction("lire_config","inc");
	return $lire($cfg, $def, $unserialize);
}

function inc_lire_config_dist($cfg='', $def=null, $unserialize=true){ 
	$depot = cfg_charger_depot($cfg);
	$r = $depot->lire_config($unserialize);
	if (is_null($r)) return $def;
	return $r;
}



//
// 
// ecrire_config($chemin, $valeur) 
// permet d'enregistrer une configuration
// 
//
function ecrire_config($cfg='', $valeur=null){
	$ecrire = charger_fonction("ecrire_config","inc");
	return $ecrire($cfg, $valeur);	
}

function inc_ecrire_config_dist($cfg='', $valeur=null){ 
	$depot = cfg_charger_depot($cfg);
	return $depot->ecrire_config($valeur);
}


//
// effacer_config($chemin) 
// permet de supprimer une config 
//
function effacer_config($cfg=''){
	$effacer = charger_fonction("effacer_config","inc");
	return $effacer($cfg);	
}

function inc_effacer_config_dist($cfg=''){
	$depot = cfg_charger_depot($cfg);
	return $depot->effacer_config();	
}
	
	

//
// Cherche le vrai nom d'une table
// ainsi que ses cles primaires
//
function get_table_id($table) {	
	static $catab = array(
		'tables_principales' => 'base/serial',
		'tables_auxiliaires' => 'base/auxiliaires',
	);
	$try = array($table, 'spip_' . $table);
	foreach ($catab as $categ => $catinc) {
		include_spip($catinc);
		foreach ($try as $nom) {
			if (isset($GLOBALS[$categ][$nom])) {
				return array($nom,
					preg_split('/\s*,\s*/', $GLOBALS[$categ][$nom]['key']['PRIMARY KEY']));
			}
		}
	}
	if ($try = table_objet($table)) {
		return array('spip_' . $try, array(id_table_objet($table)));
	}
	return array(false, false);
}



?>
