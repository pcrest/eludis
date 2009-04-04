<?php

/*
 * Plugin CFG pour SPIP
 * (c) toggg, marcimat 2007-2008, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 */


class cfg_params_dist{
	
	function cfg_params_dist($opt=array()){
		
		$defaut = array(
			'afficher_messages' => true, // afficher ce compte rendu ?
			'autoriser' => 'configurer',	// le "faire" de autoriser($faire), par defaut, autoriser_configurer_dist()	
			'autoriser_absence_id' => 'non', // autoriser l'insertion de nouveau contenu dans une table sans donner d'identifiant ?
			'casier' => '', // sous tableau optionel du meta ou va etre stocke le fragment de config
			'cfg_id' => '', // pour une config multiple , l'id courant
			'descriptif' => '', // descriptif
			'depot' => 'metapack', // (ancien 'storage') le depot utilise pour stocker les donnees, par defaut metapack: spip_meta serialise 
			'fichier' => '', // pour storage php, c'est l'adresse du fichier (depuis la racine de spip), sinon ca prend /local/cfg/nom.php
			'head' => '', // partie du fond cfg a inserer dans le head par le pipeline header_prive (todo insert_head?)
			'icone' => '', // lien pour une icone
			'liens' => array(), // liens optionnels sur des sous-config <!-- liens*=xxx -->
			'liens_multi' => array(), // liens optionnels sur des sous-config pour des fonds utilisant un champ multiple  <!-- liens_multi*=xxx -->
			'nom' => '', // le nom du meta (ou autre) ou va etre stocke la config concernee
			'onglet' => 'oui', // cfg doit-il afficher un lien vers le fond sous forme d'onglet dans la page ?exec=cfg
			'presentation' => 'auto', // cfg doit-il encadrer le formulaire tout seul ?
			'refus' => '', // en cas de refus d'autorisation, un message informatif [(#REM) refus=...]
			'table' => '', // nom de la table sql pour storage extra ou table
		);
		
		$opt = array_merge($defaut, $opt);
		
		// stockage dans $this->cle
		foreach ($opt as $cle=>$val){
			$this->$cle = $val;	
		}	
	}	
}

?>
