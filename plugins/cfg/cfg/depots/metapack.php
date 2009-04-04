<?php

/*
 * Plugin CFG pour SPIP
 * (c) toggg 2007, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 * classe cfg_metapack: storage serialise dans spip_meta
 */

if (!defined("_ECRIRE_INC_VERSION")) return;



// cfg_metapack retrouve et met a jour les donnees dans spip_meta
class cfg_depot_metapack
{
	var $champs = array();
	var $champs_id = array();
	var $val = array();
	var $param = array();
	var $messages = array('message_ok'=>array(), 'message_erreur'=>array(), 'erreurs'=>array());
	
	var $_arbre = array();
	
	// version du depot
	var $version = 2;
	
	function cfg_depot_metapack($params=array())
	{
		foreach ($params as $o=>$v) {
			$this->$o = $v;
		}	
	}
	

	// charge la base (racine) et le point de l'arbre sur lequel on se trouve (ici)
	function charger(){
		$this->_base = is_array($c = $GLOBALS['meta'][$this->param->nom]) ? $c : @unserialize($c);
		$this->_arbre = array();
		$this->_ici = &$this->_base;
    	$this->_ici = &$this->monte_arbre($this->_ici, $this->param->casier);
    	$this->_ici = &$this->monte_arbre($this->_ici, $this->param->cfg_id);
    	return true;	
	}
	
	// recuperer les valeurs.
	function lire()
	{
		if (!$this->charger()){
			return array(false, $this->val);	
		}
		$ici = &$this->_ici;
    	
        // utile ??
    	if ($this->param->cfg_id) {
    		$cles = explode('/', $this->param->cfg_id);
			foreach ($this->champs_id as $i => $name) {
				$ici[$name] = $cles[$i];
		    }
    	}
    	
    	// s'il y a des champs demandes, les retourner... sinon, retourner la base
    	// (cas de lire_config('metapack::nom') tout court)
    	if (count($this->champs)){
    		$val = array();
			foreach ($this->champs as $name => $def) {
				$val[$name] = $ici[$name];
			}
			$ici = $val;
    	}

	    return array(true, $ici);
	}


	// ecrit une meta pour tous les champs
	function ecrire()
	{
  		// si pas de champs : on ecrit directement (ecrire_meta(metapack::nom,$val))...
  		if (!$this->champs){
  			ecrire_meta($this->param->nom, serialize($this->val));
  			if (defined('_COMPAT_CFG_192')) ecrire_metas();
  			return array(true, $this->val);
  		}
  		
		if (!$this->charger()){
			return array(false, $this->val);	
		}
		$ici = &$this->_ici;
		
		foreach ($this->champs as $name => $def) {
			if (isset($def['id'])) continue;
			$ici[$name] = $this->val[$name];
		}

		ecrire_meta($this->param->nom, serialize($this->_base));
		if (defined('_COMPAT_CFG_192')) ecrire_metas();
		return array(true, $ici);
	}
	
	
	// supprime chaque enregistrement de meta pour chaque champ
	function effacer(){
  		// si pas de champs : on supprime directement (effacer_meta(metapack::nom))...
  		if (!$this->champs){
  			effacer_meta($this->param->nom);
  			if (defined('_COMPAT_CFG_192')) ecrire_metas();
  			return array(true, array());
  		}
  		
		if (!$this->charger()){
			return array(false, $this->val);	
		}
		$ici = &$this->_ici;

		// supprimer les champs
		foreach ($this->champs as $name => $def) {
			if (isset($def['id'])) continue;
			unset($ici[$name]);
		}

		// supprimer les dossiers vides
		for ($i = count($this->_arbre); $i--; ) {
			if ($this->_arbre[$i][0][$this->_arbre[$i][1]]) {
				break;
			}
			unset($this->_arbre[$i][0][$this->_arbre[$i][1]]);
		}
		
		if (!$this->_base) {
		    effacer_meta($this->param->nom);
		} else {
		    ecrire_meta($this->param->nom, serialize($this->_base));
	    }		
		if (defined('_COMPAT_CFG_192')) ecrire_metas();
		
		return array(true, array());
	}
	
	
	// charger les arguments de lire_config(metapack::nom/casier/champ)
	// $args = 'nom'; ici
	// il se peut qu'il n'y ait pas de champs si : lire_config(metapack::nom);
	function charger_args($args){
		$args = explode('/',$args);
		$this->param->nom = array_shift($args);
		if ($champ = array_pop($args)) {
			$this->champs = array($champ=>true);
		}
		$this->param->casier = implode('/',$args);
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
