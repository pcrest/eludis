<?php

/*
 * Plugin CFG pour SPIP
 * (c) Marcimat, toggg 2007, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


/*
 * Affiche une arborescence du contenu d'un #CONFIG
 * 
 * #CFG_ARBO, 
 * #CFG_ARBO{ma_meta}, 
 * #CFG_ARBO{~toto}, 
 * #CFG_ARBO{ma_meta/mon_casier},
 * #CFG_ARBO{ma_table:mon_id/mon_champ}
 * 
 */
function balise_CFG_ARBO($p) {
	if (!$arg = interprete_argument_balise(1,$p)) {
		$arg = "''";
	}
	$p->interdire_scripts = false;
	$p->code = 'affiche_arborescence(' . $arg . ')';
	return $p;
}

function affiche_arborescence($cfg='') {

	$sortie = '';
	$hash = substr(md5(rand()*rand()),0,6);
	
	// integration du css
	// Suppression de cette inclusion des css arbo au profit d'une inclusion d'un fichier cfg.css dans le header prive
// 	$sortie .= "<style type='text/css'>\n"
// 			.  ".cfg_arbo{}\n"
// 			.  ".cfg_arbo h5{padding:0.2em 0.2em; margin:0.2em 0;}\n"
// 			.  ".cfg_arbo ul{border:1px solid #ccc; margin:0; padding:0.2em 0.5em; list-style-type:none;}\n"
// 			.  "</style>\n";

	// integration du js	
	$sortie .= "<script type='text/javascript'><!--
				
				$(document).ready(function(){
					function cfg_arbo(){
						jQuery('#cfg_arbo_$hash ul').hide();
						jQuery('#cfg_arbo_$hash h5')
						.prepend('<b>[+] </b>')
						.toggle(
						  function () {
							$(this).children('b').text('[-] ');
							$(this).next('ul').show();
						  },
						  function () {
							$(this).children('b').text('[+] ');
							$(this).next('ul').hide();
						  });						
					}
					setTimeout(cfg_arbo,100);

				});
				// --></script>\n";
				
	$tableau = lire_config($cfg);
	if ($c = @unserialize($tableau)) $tableau = $c;
	
	if (empty($cfg)) $cfg = 'spip_meta';
	// parcours des donnees
	$sortie .= 
		"<div class='cfg_arbo' id='cfg_arbo_$hash'>\n" .
		affiche_sous_arborescence($cfg, $tableau) .
		"\n</div>\n";


	return $sortie;
}

function affiche_sous_arborescence($nom, $tableau){
	$sortie = "\n<h5>$nom</h5>\n";
	$sortie .= "\n<ul>";
	if (is_array($tableau)){
		ksort($tableau);
		foreach ($tableau as $tab=>$val){
			if (is_array($val)) 
				$sortie .= affiche_sous_arborescence($tab, $val);
			elseif (false !== $v = @unserialize($val))
				$sortie .= affiche_sous_arborescence($tab, $v);
			else
				$sortie .= "<li>$tab = " . htmlentities($val) ."</li>\n";
			
		}
	} else {
		$sortie .= "<li>$nom = " . htmlentities($tableau) . "</li>";
	}
	$sortie .= "</ul>\n";
	return $sortie;	
}

?>
