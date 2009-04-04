<?php

/*
 * Plugin CFG pour SPIP
 * (c) Marcimat, toggg 2007, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


//
// #CONFIG etendue interpretant les /, ~ et table:
//
// Par exemple #CONFIG{xxx/yyy/zzz} fait comme #CONFIG{xxx}['yyy']['zzz']
// xxx est un tableau serialise dans spip_meta comme avec exec=cfg&cfg=xxx
//
// si xxx demarre par ~ on utilise la colonne 'extra' 
// ('cfg' sera prochainement la colonne par defaut) de spip_auteurs
// cree pour l'occasion. 
//   ~ tout court veut dire l'auteur connecte,
//   ~123 celui de l'auteur 123

// Pour utiliser une autre colonne que 'cfg', il faut renseigner @colonne
//   ~@extra/champ ou 
//   ~id_auteur@prefs/champ
//
// Pour recuperer des valeurs d'une table particuliere,
// il faut utiliser 'table:id/champ' ou 'table@colonne:id/champ'
//   table:123 contenu de la colonne 'cfg' de l'enregistrement id 123 de "table"
//   rubriques@extra:3/qqc  rubrique 3, colonne extra, champ 'qqc'
//
// "table" est un nom de table ou un raccourci comme "article"
// on peut croiser plusieurs id comme spip_auteurs_articles:6:123
// (mais il n'y a pas d'extra dans spip_auteurs_articles ...)
// Le 2eme argument de la balise est la valeur defaut comme pour la dist
//
// pour histoire
// Le 3eme argument permet de controler la serialisation du resultat
// (mais ne sert que pour le depot 'meta') qui ne doit pas deserialiser tout le temps
// même si c'est possible lorsqu'on le demande avec #CONFIG...
//
function balise_CONFIG($p) {
	if (!$arg = interprete_argument_balise(1,$p)) {
		$arg = "''";
	}
	$sinon = interprete_argument_balise(2,$p);
	$unserialize = sinon(interprete_argument_balise(3,$p),"false");

	// cas particulier historique : |in_array{#CONFIG{toto,'',''}}
	// a remplacer par  |in_array{#CONFIG{toto/,#ARRAY}}
	// il sert aussi a lire $GLOBALS['meta']['param'] qui serait un array()...
	if (($sinon === "''") AND ($unserialize === "''") AND (false === strpos('::',$arg))){
		$sinon = "array()";
		$unserialize = true;
		$arg = "'metapack::'.".$arg;
	}
	$p->code = 'lire_config(' . $arg . ',' . 
		($sinon && $sinon != "''" ? $sinon : 'null') . ',' . $unserialize . ')';	

	return $p;
}

//
// La balise CFG_CHEMIN retourne le chemin d'une image stockee
// par cfg.
//
// cfg stocke : 'config/vue/champ.ext' (ce qu'affiche #CONFIG)
// #cfg_chemin retourne l'adresse complete : 'IMG/config/vue/champ.ext'
//
function balise_CFG_CHEMIN($p) {
	if (!$arg = interprete_argument_balise(1,$p)) {
		$arg = "''";
	}
	$sinon = interprete_argument_balise(2,$p);
	
	$p->code = '($l = lire_config(' . $arg . ',' . 
		($sinon && $sinon != "''" ? $sinon : 'null') . ')) ? _DIR_IMG . $l : null';		
	
	return $p;
}

?>
