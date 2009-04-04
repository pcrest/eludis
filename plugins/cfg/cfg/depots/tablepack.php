<?php

/*
 * Plugin CFG pour SPIP
 * (c) toggg 2007, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 * classe cfg_extrapack: storage serialise dans extra de spip_auteurs ou autre
 */

if (!defined("_ECRIRE_INC_VERSION")) return;



// cfg_tablepack retrouve et met a jour les donnees serialisees dans une colonne d'une table
// par défaut : colonne 'cfg' et table 'spip_auteurs'
// ici, cfg_id est obligatoire ... peut-être mappé sur l'auteur courant (a voir)
class cfg_depot_tablepack
{
	var $champs = array();
	var $champs_id = array();
	var $val = array();
	var $param = array();
	var $messages = array('message_ok'=>array(), 'message_erreur'=>array(), 'erreurs'=>array());
	
	var $_arbre = array();
	var $_id = array();
	var $_base = null;
	var $_ici = null;
	
	// version du depot
	var $version = 2;
	
	function cfg_depot_tablepack($params=array())
	{
		foreach ($params as $o=>$v) {
			$this->$o = $v;
		}	

		if (!$this->param->colonne)	$this->param->colonne = 'cfg'; 
		if (!$this->param->table) 	$this->param->table = 'spip_auteurs';
		// colid : nom de la colonne primary key
		list($this->param->table, $colid) = get_table_id($this->param->table);
		
		// renseigner les liens id=valeur
		$id = explode('/',$this->param->cfg_id);
		foreach ($colid as $n=>$c) {
			if (isset($id[$n])) {
				$this->_id[$c] = $id[$n];
			}
		}
	}
	
	// charge la base (racine) et le point de l'arbre sur lequel on se trouve (ici)
	function charger($creer = false){
		if (!$this->param->cfg_id) {
			$this->messages['message_erreur'][] = _T('cfg:id_manquant');
			return false;
		}
		
		// verifier que la colonne existe
		if (!$this->verifier_colonne($creer)) {
			return false;
		} else {
			// recuperer la valeur du champ de la table sql
			$this->_where = array();
			foreach ($this->_id as $nom => $id) {
				$this->_where[] = $nom . '=' . sql_quote($id);
			}
			
			$this->_base = ($d = sql_getfetsel($this->param->colonne, $this->param->table, $this->_where)) ? unserialize($d) : array();
		}	
		
		$this->_arbre = array();
		$this->_ici = &$this->_base;
    	$this->_ici = &$this->monte_arbre($this->_ici, $this->param->nom);
    	$this->_ici = &$this->monte_arbre($this->_ici, $this->param->casier);
    	return true;	
	}
	
	// recuperer les valeurs.
	function lire()
	{
		// charger
		if (!$this->charger()){
			return array(false, $this->val, $this->messages);	
		}
		$ici = &$this->_ici;

        // utile ??
    	if ($this->param->cfg_id) {
    		$cles = explode('/', $this->param->cfg_id);
			foreach ($this->champs_id as $i => $name) {
				$ici[$name] = $cles[$i];
		    }
    	}
	
    	// s'il y a des champs demandes, ne retourner que ceux-ci
    	if (count($this->champs)){
    		$val = array();
			foreach ($this->champs as $name => $def) {
				$val[$name] = $ici[$name];
			}
			$ici = $val;
    	}
	    return array(true, $ici);
	}


	// ecrit une entree pour tous les champs
	function ecrire()
	{
		// charger
		if (!$this->charger()){
			return array(false, $this->val, $this->messages);	
		}
		$ici = &$this->_ici;
		
		if ($this->champs){
			foreach ($this->champs as $name => $def) {
				if (isset($def['id'])) continue;
				$ici[$name] = $this->val[$name];
			}
		} else {
			$ici = $this->val;	
		}	

		$ok = sql_updateq($this->param->table, array($this->param->colonne => serialize($this->_base)), $this->_where);	
		return array($ok, $ici);
	}
	
	
	// supprime chaque enregistrement de meta pour chaque champ
	function effacer(){
		// charger
		if (!$this->charger()){
			return array(false, $this->val, $this->messages);	
		}
		$ici = &$this->_ici;
		
		if ($this->champs){
			foreach ($this->champs as $name => $def) {
				if (isset($def['id'])) continue;
				unset($ici[$name]);
			}
		} else {
			unset($ici);	
		}	

		// supprimer les dossiers vides
		for ($i = count($this->_arbre); $i--; ) {
			if ($this->_arbre[$i][0][$this->_arbre[$i][1]]) {
				break;
			}
			unset($this->_arbre[$i][0][$this->_arbre[$i][1]]);
		}
		
		$ok = sql_updateq($this->param->table, array($this->param->colonne => serialize($this->_base)), $this->_where);	
		return array($ok, array());
	}
	
	
	// charger les arguments
	// lire_config(tablepack::table@colonne:id/nom/casier/champ
	// lire_config(tablepack::~id_auteur@colonne/chemin/champ
	// lire_config(tablepack::~@colonne/chemin/champ
	function charger_args($args){
		$args = explode('/',$args);
		// cas ~id_auteur/
		if ($args[0][0] == '~'){
			$table = 'spip_auteurs';
			$colid = array('id_auteur');
			list($auteur, $colonne) = explode('@',array_shift($args));
			if (count($auteur)>1){
				$id = substr($auteur,1);
			} else {
				$id = $GLOBALS['auteur_session'] ? $GLOBALS['auteur_session']['id_auteur'] : '';
			}
		// cas table:id/
		// peut etre table:id:id/ si la table a 2 cles
		} else {
			list($table, $id) = explode(':',array_shift($args),2);
			list($table, $colonne) = explode('@',$table);
			list($table, $colid) = get_table_id($table);
		}
		$this->param->cfg_id = $id;
		$this->param->colonne = $colonne ? $colonne : 'cfg';
		$this->param->table = $table ? $table : 'spip_auteurs';
		$this->param->nom = array_shift($args);
		if ($champ = array_pop($args)) {
			$this->champs = array($champ=>true);
		}
		$this->param->casier = implode('/',$args);
		
		// renseigner les liens id=valeur
		$id = explode(':',$id);
		foreach ($colid as $n=>$c) {
			if (isset($id[$n])) {
				$this->_id[$c] = $id[$n];
			}
		}
		
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
	
	
	function verifier_colonne($creer = false) {
		$col = sql_showtable($table = $this->param->table);
		if (!array_key_exists($colonne = $this->param->colonne, $col['field'])) {
			if (!$creer){
				return false;
			}
			
			if (!sql_alter("TABLE " . $table . " ADD " . $colonne . " TEXT DEFAULT ''")) {
				spip_log("CFG (ecrire_config) n'a pas reussi a creer automatiquement la colonne " . $colonne . " dans la table " . $table . ".");
				return false;	
			}
			
			spip_log("CFG (ecrire_config) a cree automatiquement la colonne " . $colonne . " dans la table " . $table . ".");
		}
		return true;
	}
}

?>
