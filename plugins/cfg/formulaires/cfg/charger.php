<?php

//
// Ce fichier charge dans l'environnement du formulaire
// les valeurs par defaut de celui-ci
//
// Pour CFG, il va donc lire le fond demande ainsi que les valeurs des champs presents dans ce fond
// pour les renvoyer dans l'environnement du formulaire, ces variables sont donc accessible par #ENV{nom}
//
function formulaires_cfg_charger_dist($cfg="", $cfg_id=""){

	if (!$cfg) return false;

	// ici, on a le nom du fond cfg... 
	// on recupere donc les parametres du formulaire.	
	$cfg_formulaire = cfg_charger_classe('cfg_formulaire','inc');
	$config = &new $cfg_formulaire($cfg, $cfg_id);

	$valeurs = array(
		'_cfg_fond' => 'fonds/cfg_'.$cfg,
		'_cfg_nom' => $cfg,
		'id' => $cfg_id,
		// passer aussi les arguments spécifiques a cfg
		'_cfg_' => $config->creer_hash_cfg('cfg') // passer action=cfg pour avoir un hash formulaire correct
	);

	// il faut passer les noms des champs (input et consoeurs) de CFG dans l'environnement
	// pour pouvoir faire #ENV{nom_du_champ}
	if (is_array($config->val)){
		foreach($config->val as $nom=>$val){
			$valeurs[$nom] = $val;	
		}
	}

	// return $valeurs; // retourner simplement les valeurs
	return array(true,$valeurs); // forcer l'etat editable du formulaire et retourner les valeurs

}

?>
