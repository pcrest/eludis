<?php

/*
 * Plugin CFG pour SPIP
 * (c) toggg, marcimat 2007-2008, distribue sous licence GNU/GPL
 * 
 * Documentation et contact: http://www.spip-contrib.net/
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


// Compatibilite 1.9.2
if (version_compare($GLOBALS['spip_version_code'],'1.9300','<'))
	include_spip('inc/compat_cfg');
	
// inclure les fonctions lire_config(), ecrire_config() et effacer_config()
include_spip('inc/cfg_config');
// Inclure la balise #CFG_ARBO
include_spip('balise/cfg_arbo');
// Inclure les balises #CONFIG et #CFG_CHEMIN
include_spip('balise/cfg_config');


// _dir_lib possiblement utile
if (!defined('_DIR_LIB')) define('_DIR_LIB', _DIR_RACINE . 'lib/');

// librairies que cfg peut telecharger (SPIP >= 1.9.3)
// via la page ?exec=cfg_install_libs
// en globals pour pouvoir etre etendu par d'autres plugins
//
// ces librairies doivent etre fournis en zip
$GLOBALS['cfg_libs'] = array(
	// farbtastic (color picker)
	'farbtastic' => array(
		'nom' => _T('cfg:lib_farbtastic'), // nom
		'description' => _T('cfg:lib_farbtastic_description'), // description
		'dir' => 'farbtastic12/farbtastic', // repertoire une fois decompresse ou se trouvent les js
		'url' => 'http://acko.net/dev/farbtastic', // url de la documentation
		'install' => 'http://acko.net/files/farbtastic_/farbtastic12.zip' // adresse du zip a telecharger
	)
);


// fonction pour effacer les parametres cfg lors le l'inclusion d'un fond
// utile pour les #FORMULAIRE comme formulaires/cfg.html
// [(#INCLURE{fond=fonds/cfg_toto}{env}|effacer_parametres_cfg)]
function effacer_parametres_cfg($texte){
	return preg_replace('/(<!-- ([a-z0-9_]\w+)(\*)?=)(.*?)-->/sim', '', $texte);		
}

// signaler le pipeline de notification
$GLOBALS['spip_pipeline']['cfg_post_edition'] = "";



//
// cfg_charger_classe(), sur le meme code que charger_fonction()
//
// charge un fichier perso ou, a defaut, standard
// et retourne si elle existe le nom de la fonction class homonyme ($nom),
// ou de suffixe _dist
//
function cfg_charger_classe($nom, $dossier='inc', $continue=false) {

	if (class_exists($f = $nom))
		return $f;
	if (class_exists($g = $f . '_dist'))
		return $g;

	if (substr($dossier,-1) != '/') $dossier .= '/';

	// Sinon charger le fichier de declaration si plausible
	if (!preg_match(',^\w+$,', $f))
		die(htmlspecialchars($nom)." pas autorise");

	// passer en minuscules (cf les balises de formulaires)
	$inc = include_spip($d = ($dossier . strtolower($nom)));

	if (class_exists($f)) return $f;
	if (class_exists($g)) return $g;
	if ($continue) return false;

	// Echec : message d'erreur
	spip_log("class $nom ($f ou $g) indisponible" .
		($inc ? "" : " (fichier $d absent)"));

	include_spip('inc/minipres');
	echo minipres(_T('forum_titre_erreur'),
		 _T('fichier_introuvable', array('fichier'=> '<b>'.htmlentities($d).'</b>')));
	exit;
}

?>
