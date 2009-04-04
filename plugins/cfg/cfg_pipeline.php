<?php

/*
 * Plugin CFG pour SPIP
 * (c) toggg 2007, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

// Ajoute le bouton d'amin aux webmestres
function cfg_ajouter_boutons($flux) {
	// si on est admin
	if (autoriser('configurer','cfg')) {
	  // on voit le bouton dans la barre "configuration"
		$flux['configuration']->sousmenu['cfg']= new Bouton(
		"../"._DIR_PLUGIN_CFG."cfg-22.png",  // icone
		_T('cfg:CFG'));
	}
	return $flux;
}


/*
 * Gerer l'option <!-- head= xxx --> des fonds CFG
 * 
 * (pas sur que cela fonctionne avec #FORMULAIRE_CFG, 
 *  il faudra verifier)
 */
function cfg_insert_head($flux){
	// a voir
	return $flux;
}

function cfg_header_prive($flux){
	
	if (!_request('cfg') || (!_request('exec') == 'cfg')) {
		return $flux;
	}

	// Ajout des css de cfg (uniquement balise arbo pour l'instant) dans le header prive
	$flux .= '<link rel="stylesheet" href="'._DIR_PLUGIN_CFG.'css/cfg.css" type="text/css" media="all" />';

	include_spip('inc/filtres');
	$cfg_formulaire = cfg_charger_classe('cfg_formulaire','inc');
	$config = &new $cfg_formulaire(
				sinon(_request('cfg'), ''),
				sinon(_request('cfg_id'),''));
	
	if ($config->param->head) 
		$flux .= "\n".$config->param->head;
	
	return $flux;
}
?>
