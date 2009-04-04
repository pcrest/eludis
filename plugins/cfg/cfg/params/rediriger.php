<?php


/*
 * Plugin CFG pour SPIP
 * (c) toggg 2007, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 */


// restaure des messages serialises dans une meta 'cfg_message_{id_auteur}'
//
// si le formulaire cfg avait demande une redirection... 
// (et provient de cette redirection), il est possible
// qu'il y ait un message a afficher
function cfg_pre_charger_param_rediriger($valeur, &$cfg){
	if ($messages = $GLOBALS['meta']['cfg_message_'.$GLOBALS['auteur_session']['id_auteur']]){
			include_spip('inc/meta');
			effacer_meta('cfg_message_'.$GLOBALS['auteur_session']['id_auteur']);
			if (defined('_COMPAT_CFG_192')) ecrire_metas();
			$cfg->messages = array_merge($cfg->messages, unserialize($messages));
	}	
}


// Si le fond du formulaire demande expressement une redirection
// par <!-- rediriger=1 -->, on stocke le message dans une meta
// et on redirige le client, de maniere a charger la page
// avec la nouvelle config (ce qui permet par exemple a Autorite
// de controler d'eventuels conflits generes par les nouvelles autorisations)
function cfg_post_traiter_param_rediriger($valeur, &$cfg){
	if ($cfg->messages) {
		include_spip('inc/meta');
		ecrire_meta('cfg_message_'.$GLOBALS['auteur_session']['id_auteur'], serialize($cfg->messages), 'non');
		if (defined('_COMPAT_CFG_192')) ecrire_metas();
		include_spip('inc/headers');
		redirige_par_entete(parametre_url(self(),null,null,'&'));
	}
}

?>
