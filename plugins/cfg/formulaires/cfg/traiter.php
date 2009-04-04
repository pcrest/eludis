<?php

//
// Cette fonction enregistre les variables postees par le formulaire.
// Ces variables ayant etes verifies dans 'valider.php' au prealable,
// il y a simplement a les enregistrer.
//
function formulaires_cfg_traiter_dist($cfg="", $cfg_id=""){

	$cfg_formulaire = cfg_charger_classe('cfg_formulaire','inc');
	$config = &new $cfg_formulaire($cfg, $cfg_id);
	
	if ($config->verifier())
		$config->traiter();
		
	$message = join('<br />',$config->messages['message_ok']);	

	//return $message; // retourner simplement un message, le formulaire ne sera pas resoumis
	return array(true,$message); // forcer l'etat editable du formulaire et retourner le message
}
?>
