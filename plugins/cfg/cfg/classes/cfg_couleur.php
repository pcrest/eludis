<?php

/*
 * Plugin CFG pour SPIP
 * (c) toggg, marcimat 2007-2008, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


// apres que le parseur a trouve les champs (mais avant l'action 'charger' des parametres)
// ajouter automatiquement le parametre 'selecteur_couleur' (ajoute les js de farbtastic)
function cfg_charger_cfg_couleur($nom, &$cfg){

	$cfg->param->selecteur_couleur = 1;
	$cfg->ajouter_extension_parametre('selecteur_couleur');
	    
	return $cfg;
}


?>
