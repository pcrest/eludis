<?php

/*
 * Plugin CFG pour SPIP
 * (c) toggg 2007, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 * classe cfg_table: storage naturel dans une table
 */

if (!defined("_ECRIRE_INC_VERSION")) return;



// cfg_table retrouve et met a jour les colonnes d'une table
// ici, cfg_id est obligatoire ... 
class cfg_depot_table
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
	
	function cfg_depot_table($params=array())
	{
		foreach ($params as $o=>$v) {
			$this->$o = $v;
		}
		
		if (!$this->param->table) {
			$this->messages['message_erreur'][] = _T('cfg:nom_table_manquant');
			return false;
		}
		
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
		if (!$this->param->cfg_id AND !($this->param->autoriser_absence_id == 'oui')) {
			$this->messages['message_erreur'][] = _T('cfg:id_manquant');
			return false;
		}

		// select
		$this->_select = array();
		if ($this->champs){
			foreach ($this->champs as $nom => $def) {
				if (isset($def['id'])) {
					continue;
				}
				$this->_select[] = $nom;
			}
		} else {
			$this->_select[] = '*';	
		}
		
		// where
		$this->_where = array();
		foreach ($this->_id as $nom => $id) {
			$this->_where[] = $nom . '=' . sql_quote($id);
		}
			
		$this->_base = ($d = sql_fetsel(
			$this->_select, 
			$this->param->table, 
			$this->_where)) ? $d : array();
		
		$this->_existe = count($this->_base);
			
		$this->_ici = &$this->_base;
    	return true;	
	}
	
	// recuperer les valeurs.
	function lire()
	{
		// charger
		if (!$this->charger()){
			return array(false, $this->val, $this->messages);	
		}

        // utile ??
    	if ($this->param->cfg_id) {
    		$cles = explode('/', $this->param->cfg_id);
			foreach ($this->champs_id as $i => $name) {
				$this->_ici[$name] = $cles[$i];
		    }
    	}
	
    	// s'il y a des champs demandes, ne retourner que ceux-ci
    	if (count($this->champs)){
    		$val = array();
			foreach ($this->champs as $name => $def) {
				$val[$name] = $this->_ici[$name];
			}
			$this->_ici = $val;
    	}
	    return array(true, $this->_ici);
	}


	// ecrit une entree pour tous les champs
	function ecrire()
	{
		// charger
		if (!$this->charger()){
			return array(false, $this->val, $this->messages);	
		}

		if ($this->champs){
			foreach ($this->champs as $name => $def) {
				if (isset($def['id'])) continue;
				$this->_ici[$name] = $this->val[$name];
			}
		} else {
			$this->_ici = $this->val;	
		}	
			
		// update
		if ($this->_existe) {	
		    $ok = sql_updateq($this->param->table, $this->_ici, $this->_where );
	    } else {
			$ok = $id = sql_insertq($this->param->table, $this->_ici);
	    }

		// remettre l'id
		if ($ok && (count($this->champs_id)==1)) {
			$this->_ici[$nomid = $this->champs_id[0]] = $this->_existe ? $this->val[$nomid] : $ok;
		}
			
		return array($ok, $this->_ici);
	}
	
	
	// supprime chaque enregistrement de meta pour chaque champ
	function effacer(){
		// charger
		if (!$this->charger()){
			return array(false, $this->val, $this->messages);	
		}
		
		$ok = !$this->_existe || sql_delete($this->param->table, $this->_where );	
		return array($ok, array());
	}
	
	
	// charger les arguments
	// lire_config(table::table@colonne:id
	// lire_config(table::table:id
	function charger_args($args){

		list($table, $id) = explode(':',$args,2);
		list($table, $colonne) = explode('@',$table);
		list($table, $colid) = get_table_id($table);
		
		$this->param->cfg_id = $id;
		$this->param->champs = $colonne ? array($colonne=>true) : '';
		$this->param->table = $table ? $table : 'spip_cfg';
		
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
	

}




// cfg_table retrouve et met a jour les donnees d'une table "objet" $cfg->table
// ici, $cfg->cfg_id est obligatoire
class cfg_table
{
	function cfg_table(&$cfg, $opt = array())
	{
		$this->cfg = &$cfg;
		foreach ($opt as $o=>$v) {
			$this->$o = $v;
		}
		$this->cfg->param->table || ($this->cfg->message = _T('cfg:nom_table_manquant'));
	}
	

	/* 
	 * Recuperer les valeurs
	 * 
	 * Le parametre 'autoriser_absence_id=oui' permet d'autoriser
	 * une requete sql d'insertion de nouveau contenu
	 * meme si l'on ne donne pas la valeur du champs cle primaire (id)
	 * ce qui permet d'executer la requete si le champ id est autoincrement.
	 * 
	 * Si un message d'erreur est retourne, on ne peut 
	 * faire aucune modification.
	 * 
	 */
	function lire()
	{
		// si cfg_id n'est pas present,
		// pas la peine de continuer
 		if (!$this->cfg->param->cfg_id) {
 			// ignorer cette erreur si le champ id est 'autoincrement'
 			if (!$this->cfg->param->autoriser_absence_id == 'oui')
				$this->cfg->message = _T('cfg:id_manquant');
			return false;
		}

   		$cles = explode('/', $this->cfg->param->cfg_id);
    	$val = array();
    	// selection des champs du select
		$select = array();
		foreach ($this->cfg->champs as $name => $def) {
			if (isset($def['id'])) {
				continue;
			}
			$select[] = $name;
	    }
		$query = sql_select($select, $this->cfg->param->table, $this->where());
		($query = sql_fetch($query)) && ($val = $query);

		foreach ($this->cfg->champs_id as $i => $name) {
			$val[$name] = $cles[$i];
	    }

	    return $val;
	}
	
// fabriquer un array WHERE depuis cfg_id
	function where()
	{
   		$cles = explode('/', $this->cfg->param->cfg_id);
		$where = array();
		foreach ($this->cfg->champs_id as $i => $name) {
			$where[] = $name . '=' 
					. (is_numeric($cles[$i]) ? intval($cles[$i]) : sql_quote($cles[$i]));
	    }
		return $where;
	}
	
// modifier le fragment qui peut etre tout le meta
	function modifier($supprimer = false)
	{

		$this->cfg_id = $sep = '';
		foreach ($this->cfg->champs_id as $name) {
			$this->param->cfg_id .= $sep . _request($name);
			$sep = '/';
	    }



		

	}
}
?>
