<?php

// cette classe charge les fonctions de lecture et ecriture d'un depot (dans inc/depot/)
//
// Ces depots ont une version qui evoluera en fonction si des changements d'api apparaissent

// version 2 (fonctions)
// - charger_args
// - lire, ecrire, effacer


class cfg_depot_dist{
	
	var $nom;
	var $depot;
	
	//
	// Constructeur de la classe
	// 'depot' est le nom du fichier php stocke dans /inc/depot/{depot}.php
	// qui contient une classe 'cfg_depot_{depot}' ou 'cfg_depot_{depot}_dist'
	//
	// $params est un tableau de parametres passes a la classe cfg_depot_{depot} qui peut contenir :
	// 'champs' => array(
	//		'nom'=>array(
	//			'balise' => 'select|textarea|input', // nom de la balise
	//			'type' => 'checkbox|hidden|text...', // type d'un input 
	//			'tableau' => bool, // est-ce un champ tableau name="champ[]" ?
	//			'cfg' => 'xx',   // classe css commencant par css_xx
	//			'id' => y, // cle du tableau 'champs_id' (emplacement qui possede ce champ)
	//		),
	// 'champs_id' => array(
	//		cle => 'nom' // nom d'un champ de type id
	//		),
	//	'param' => array(
	//		'parametre_cfg' => 'valeur' // les parametres <!-- param=valeur --> passes dans les formulaires cfg
	//		),
	//	'val' => array(
	//		'nom' => 'valeur' // les valeurs des champs sont stockes dedans
	//		)
	//	);
	//
	//
	function cfg_depot_dist($depot='metapack', $params=array()){
		if (!isset($params['param'])) {
			$p = cfg_charger_classe('cfg_params');
			$params['param'] = new $p;
		}
		
		include_spip('cfg/depots/'.$depot);
		if (class_exists($class = 'cfg_depot_'.$depot)) {
			$this->depot = &new $class($params);
		} else {
			die("CFG ne trouve pas le d&eacute;pot $depot");
		}
		
		$this->version = $this->depot->version;
		$this->nom = $depot;
	}
	
	// ajoute les parametres transmis dans l'objet du depot
	function add_params($params){
		foreach ($params as $o=>$v) {
			$this->depot->$o = $v;
		}	
	}
	
	function lire($params = array()){
		$this->add_params($params);
		return $this->depot->lire(); // array($ok, $val, $messages)
	}
		
	function ecrire($params = array()){
		$this->add_params($params);
		return $this->depot->ecrire(); // array($ok, $val, $messages)
	}
	
	function effacer($params = array()){
		$this->add_params($params);
		return $this->depot->effacer(); // array($ok, $val, $messages)
	}	
	
	function lire_config($unserialize=true){
		list($ok, $s) = $this->depot->lire($unserialize);
		if ($ok && ($nom = $this->nom_champ())) {
			return $s[$nom];
		} elseif ($ok) {
			return $s;	
		} 
	}
	
	function ecrire_config($valeur){
		if ($nom = $this->nom_champ()) {
			$this->depot->val = array($nom=>$valeur);
		} else {
			$this->depot->val = $valeur;
		}
		list($ok, $s) =  $this->depot->ecrire();
		return $ok;	
	}
	
	function effacer_config(){
		if ($nom = $this->nom_champ()){
			$this->depot->val[$nom] = false;
		} else {
			$this->depot->val = null;	
		}
		list($ok, $s) =  $this->depot->effacer();
		return $ok;	

	}	
	
	function nom_champ(){
		if (count($this->depot->champs)==1){
			foreach ($this->depot->champs as $nom=>$def){
				return $nom;	
			}
		}
		return false;			
	}
	
	// charge les arguments d'un lire/ecrire/effacer_config
	// dans le depot : lire_config($args = 'metapack::prefixe/casier/champ');
	function charger_args($args){
		if (method_exists($this->depot, 'charger_args')){
			return $this->depot->charger_args($args);	
		}
		return false;
	}
}
?>
