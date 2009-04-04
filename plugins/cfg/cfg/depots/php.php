<?php

/*
 * Plugin CFG pour SPIP
 * (c) toggg 2007, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 * classe cfg_php: storage dans un fichier php
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

// cfg_php retrouve et met a jour les donnees d'un fichier php
class cfg_depot_php
{
	var $champs = array();
	var $champs_id = array();
	var $val = array();
	var $param = array();
	var $messages = array('message_ok'=>array(), 'message_erreur'=>array(), 'erreurs'=>array());
	
	var $_arbre = array();
	
	// version du depot
	var $version = 2;
	
	
	function cfg_depot_php($params=array()) {
		foreach ($params as $o=>$v) {
			$this->$o = $v;
		}
	}
	
	// calcule l'emplacepent du fichier
	function get_fichier(){
		static $fichier = array();
		$cle = $this->param->nom . ' - ' . $this->param->fichier;
		if (isset($fichier[$cle])) 
			return $fichier[$cle];
		
		if (!$this->param->fichier) 
			$f = _DIR_VAR . 'cfg/' . $this->param->nom . '.php';	
		else
			$f = _DIR_RACINE . $this->param->fichier;

		include_spip('inc/flock');
		return $fichier[$cle] = sous_repertoire(dirname($f)) . basename($f);
	}
	
	
	// charge la base (racine) et le point de l'arbre sur lequel on se trouve (ici)
	function charger(){
		$fichier = $this->get_fichier();

		// inclut une variable $cfg
    	if (!@include $fichier) {
    		$this->_base = array();
    	} elseif (!$cfg OR !is_array($cfg)) {
    		$this->_base = array();
    	} else {
    		$this->_base = $cfg;	
    	}

    	$this->_ici = &$this->_base;
    	$this->_ici = &$this->monte_arbre($this->_ici, $this->param->nom);
    	$this->_ici = &$this->monte_arbre($this->_ici, $this->param->casier);
    	$this->_ici = &$this->monte_arbre($this->_ici, $this->param->cfg_id);	
    	return true;
	}
	
	// recuperer les valeurs.
	function lire() {
		if (!$this->charger()){
			return array(false, $this->val);	
		}
		
    	// utile ??
    	if ($this->param->cfg_id) {
    		$cles = explode('/', $this->param->cfg_id);
			foreach ($this->champs_id as $i => $name) {
				$this->_ici[$name] = $cles[$i];
		    }
    	}
	    return array(true, $this->_ici);
	}


	// ecrit chaque enregistrement pour chaque champ
	function ecrire() {
		if (!$this->charger()){
			return array(false, $this->val);	
		}

		if (!$this->ecrire_fichier($this->_base)){
			return array(false, $this->val);
		}
		
		return array(true, $this->_ici);
	
	}
	
	
	// supprime chaque enregistrement pour chaque champ
	function effacer(){
		if (!$this->charger()){
			return array(false, $this->val);	
		}
    	
    	// effacer les champs
    	foreach ($this->champs as $name => $def) {
			if (isset($def['id'])) continue;
			unset($this->_ici[$name]);
		}
		
		// supprimer les dossiers vides
		for ($i = count($this->_arbre); $i--; ) {
			if ($this->_arbre[$i][0][$this->_arbre[$i][1]]) {
				break;
			}
			unset($this->_arbre[$i][0][$this->_arbre[$i][1]]);
		}
		
		return array($this->ecrire_fichier($this->_base), $this->_ici);
	}
	
	
	function ecrire_fichier($contenu){
		$fichier = $this->get_fichier();

		if (!$contenu) {
			return supprimer_fichier($fichier);
		}

$contenu = '<?php
/**************
* Config ecrite par cfg le ' . date('r') . '
* 
* NE PAS EDITER MANUELLEMENT !
***************/

$cfg = ' . var_export($contenu, true) . ';
?>
';
		return ecrire_fichier($fichier, $contenu);
	}
	
	// charger les arguments de 
	// - lire_config(php::nom/casier/champ)
	// - lire_config(php::adresse/fichier.php:nom/casier/champ)
	function charger_args($args){
		list($fichier, $args) = explode(':',$args);
		if (!$args) {
			$args = $fichier;
			$fichier = _DIR_VAR . 'cfg/' . $fichier . '.php';	
		}

		$arbre = explode('/',$args);
		$this->param->nom = array_shift($arbre);
		$champ = array_pop($arbre);
		$this->champs = array($champ=>true);
		$this->param->casier = implode('/',$arbre);
		return true;	
	}
	
	
	// se positionner dans le tableau arborescent
	function & monte_arbre(&$base, $chemin){
		if (!$chemin) {
			return $base;
		}
		if (!is_array($chemin)) {
			$chemin = explode('/', $chemin);
		}
		if (!is_array($base)) {
			$base = array();
		}
		
		foreach ($chemin as $dossier) {
			if (!isset($base[$dossier])) {
				$base[$dossier] = array();
			}
			$this->_arbre[] = array(&$base, $dossier);
			$base = &$base[$dossier];
		}
		
		return $base;
	}
}

?>
