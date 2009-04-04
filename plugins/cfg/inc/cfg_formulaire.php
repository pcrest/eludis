<?php


/*
 * Plugin CFG pour SPIP
 * (c) toggg 2007, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;



// la classe cfg represente une page de configuration
class cfg_formulaire_dist{

// les parametres des formulaires cfg sont stockes dans cet objet
	var $param;
// l'objet de classe cfg_depot qui assure lecture/ecriture/effacement des config
	var $depot = null;
// le fond html utilise , en general pour config simple idem $nom
	var $vue = '';
// compte-rendu des mises a jour
	var $messages = array('message_ok'=>array(), 'message_erreur'=>array(), 'erreurs'=>array());
// les champs trouve dans le fond
	var $champs = array();
// les champs index
	var $champs_id = array();
// leurs valeurs
	var $val = array();
// pour tracer les valeurs modifiees
	var $log_modif = '';
// contenu du fichier de formulaire
	var $controldata ='';
// stockage du fond compile par recuperer_fond()
	var $fond_compile = '';
// y a t-il des extensions (classes css 'type_{nom}' ou 'cfg_{nom}' sur champs) a traiter ?
	var $extensions = array();

// Alias pour passer facilement les parametres aux classes appelees
	var $params = array();

	//
	// Constructeur de la classe
	//
	function cfg_formulaire_dist($nom, $cfg_id = '', $opt = array())
	{
			
		$cfg_params = cfg_charger_classe("cfg_params");
		$this->param = &new $cfg_params();
		$this->param->nom = $this->vue = $nom;
		$this->param->cfg_id = $cfg_id;
		
		// definition de l'alias params
		$this->params = array(
			'champs' => &$this->champs, 
			'champs_id' => &$this->champs_id,
			'messages' => &$this->messages,
			'val' => &$this->val,
			'param' => &$this->param
		);	
		
		foreach ($opt as $o=>$v) {
			$this->$o = $v;
		}    
		// charger les donnees du fond demande
		$this->charger();
	}
	
	// retourne true en cas d'erreur...
	function erreurs(){
		return $this->messages['erreurs'] || $this->messages['message_erreur'];
	}
	
	
	// pre-analyser le formulaire
	// c'est a dire recuperer les parametres CFG 
	// et les noms des champs du formulaire	
	function charger(){
		$ok = true;
		
		// si pas de fichier, rien a charger
		if (!$this->vue) return false;
		
		// lecture de la vue (fond cfg)
		// il s'agit de recuperer le contenu du fichier
		$fichier = find_in_path($nom = 'fonds/cfg_' . $this->vue .'.html');
		if (!lire_fichier($fichier, $this->controldata)) {
			$ok = false;
			$this->messages['message_erreur'][] =  _T('cfg:erreur_lecture', array('nom' => $nom));
		}
		
		// recherche et stockage des parametres de cfg 
		$this->recuperer_parametres();

		// recherche et stockage des noms de champs de formulaire
		if ($err = $this->recuperer_noms_champs()){
			$ok = false;
			$this->messages['message_erreur'][] = $err;
		}
	    
		// charger les champs particuliers si existants
		$this->actionner_extensions('pre_charger');	  
		  
		// creer le storage et lire les valeurs
		$this->param->depot = strtolower(trim($this->param->depot));
		$cfg_depot = cfg_charger_classe('cfg_depot','inc');
		$this->depot = new $cfg_depot($this->param->depot, $this->params);
		$ok &= $this->lire();

		// charger les champs particuliers si existants
		$this->actionner_extensions('charger');
		
		return $ok;
	}


	//
	// Doit controler la validite des valeurs transmises
	// 
	// Verifie les valeurs postees.
	// - stocke les valeurs qui ont changees dans $this->val[$nom_champ] = 'nouvelle_valeur'
	// - verifie que les types de valeurs attendus sont corrects ($this->verifier_champs_types)
	// 
	// retourne les messages d'erreur
	//
	function verifier() {

		if ($this->erreurs() || !$this->autoriser()) 
				return false;

		// si on a pas poste de formulaire, pas la peine de controler
		// ce qui mettrait de fausses valeurs dans l'environnement
		if  (!_request('_cfg_ok') && !_request('_cfg_delete')) return true;
		
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$securiser_action();

		// actions par champs speciaux, avant les tests des nouvelles valeurs
		$this->actionner_extensions('pre_verifier');
		
		// stockage des nouvelles valeurs
		foreach ($this->champs as $name => $def) {
			// enregistrement des valeurs postees
			$oldval = $this->val[$name];
		    $this->val[$name] = _request($name);
		    
		    // tracer les modifications
		    if ($oldval != $this->val[$name]) {
		    	$this->log_modif .= $name . ':' . var_export($oldval, true) . '/' . var_export($this->val[$name], true) .', ';
		    }
		}
	    
		// si pas de changement, pas la peine de continuer
		if (!$this->log_modif && !_request('_cfg_delete')) {
			$this->messages['message_erreur'][] = _T('cfg:pas_de_changement', array('nom' => $this->nom_config()));
			return false;
		}
		
		// charger une eventuelle fonction cfg_{vue}_verifier()
		// vue etant le nom du fichier : fonds/cfg_{vue}.html
		// la fonction (qui peut etre mise dans fonds/cfg_{vue}_fonctions.php)
		// retourne un array() sur le meme modele que /formulaires/x/verifier.php() de spip
		if (function_exists($f = 'cfg_'.$this->vue.'_verifier') && ($err = $f())){
			if ($err['message_erreur']) $this->messages['message_erreur'][] = $err['message_erreur'];
			if ($err['message_ok']) 	$this->messages['message_ok'][] = $err['message_ok'];
			unset($err['message_erreur'], $err['message_ok']);
			if ($err) $this->messages['erreurs'] = $err;
		}
		
		// verifier la validite des champs speciaux (cfg_xx, type_xx)
		$this->actionner_extensions('verifier');
		
		// stocker le fait que l'on a controle les valeurs
		$this->verifier = true;
	    return !$this->erreurs();
	}
	

	
	//
	// Gere le traitement du formulaire.
	// 
	// Si le chargement ou le controle n'ont pas ete fait,
	// la fonction s'en occupe.
	// 
	//
	function traiter()
	{
		if (!$this->verifier) $this->verifier();
		
		if ($this->erreurs() || !$this->autoriser()) return false;
	
		if (!_request('_cfg_ok') && !_request('_cfg_delete')) return false;
		
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$securiser_action();
		
		// traiter les champs speciaux
		$this->actionner_extensions('pre_traiter');	
		
		if ($this->erreurs()) return false;		
			
		// suppression
		if (_request('_cfg_delete')) {
			$this->effacer();
		
		// sinon modification
		} else {
			$this->ecrire();
		}

		// pipeline 'cfg_post_edition'
		$this->messages = pipeline('cfg_post_edition',array('args'=>array('nom_config'=>$this->nom_config()),'data'=>$this->messages));
		
		// traiter les champs speciaux
		$this->actionner_extensions('post_traiter');	
	}




	//
	// Determine l'arborescence ou CFG doit chercher les valeurs deja enregistrees
	// si nom=toto, casier=chose/truc, cfg_id=2, 
	// cfg cherchera dans #CONFIG{toto/chose/truc/2}
	// 
	function nom_config()
	{
	    return $this->param->nom . 
	    		($this->param->casier ? '/' . $this->param->casier : '') .
	    		($this->param->cfg_id ? '/' . $this->param->cfg_id : '');
	}



	//
	// Recherche et stockage
	// des parametres #REM passes a CFG
	// (DEPRECIE)
	//
	function recuperer_parametres_rem(){
		// cas de #REM (deprecie)
		preg_replace_callback('/(\[\(#REM\) ([a-z0-9_]\w+)(\*)?=)(.*?)\]/sim',
					array(&$this, 'post_params'), $this->controldata);
	}
	
	
	// cette fonction recherche et stocke les parametres passes a cfg par <!-- param=valeur -->
	// ces lignes sont alors effacees du code html. Ces proprietes sont lues apres recuperer_fond(),
	// et interpretent donc les balises spip et les chaines de langues
	//
	// si la fonction est appelee 2 fois, les parametres identiques ne seront pas copies
	// sauf si le parametre est un tableau (<!-- param*=valeur -->), les valeurs seront dupliquees
	function recuperer_parametres(){

		// pour compatibilite, recuperer l'ancien code #REM
		$this->recuperer_parametres_rem();	
		
		$this->recuperer_fond();
		$this->fond_compile = preg_replace_callback('/(<!-- ([a-z0-9_]\w+)(\*)?=)(.*?)-->/sim',
							array(&$this, 'post_params'), $this->fond_compile);

		// s'il en reste : il y a un probleme !
		// est-ce utile de tester ça ?
		if (preg_match('/<!-- [a-z0-9_]\w+\*?=/', $this->fond_compile)) {
			die('Un parametre CFG n\'a pas pu etre importe depuis '.$this->vue);
		}

		// pour compatibilite avec les anciennes versions (<1.4.1)
		if (isset($this->param->storage)) 
			$this->param->depot = $this->param->storage;
		
		if ($this->param->depot == 'classic')
			$this->param->depot = 'meta';
			
		if ($this->param->depot == 'extrapack'){
			$this->param->depot = 'tablepack';
			$this->param->colonne = 'extra';
		}
		
		// definir les parametres qui sont a traiter comme des extensions
		// il faut que le parametre ne soit pas vide et qu'un fichier 
		// /cfg/params/{param}.php existe
		$this->extensions_parametres = array();
		foreach ($this->param as $nom=>$val){
			if ($val) $this->ajouter_extension_parametre($nom);		
		}
	}
	
	
	// une fonction pour effacer les parametres du code html
	// ce qui evite de dupliquer les tableaux 
	// (si on utilisait recuperer_parametres() a la place)
	function effacer_parametres(){
			$this->fond_compile = effacer_parametres_cfg($this->fond_compile);		
	}
	
	
	
	// 
	// Recherche  des noms des champs (y) du formulaire
	// <input type="x" name="y"... />
	// stockes dans le tableau $this->champs
	// a l'exception des noms par _cfg_, reserves a ce plugin
	// 
	function recuperer_noms_champs(){	
		if (!$this->vue) return;

		// recherche d'au moins un champ de formulaire pour savoir si la vue est valide
		$this->recuperer_fond();
		if (!preg_match_all(
		/*  '#<(?:(select|textarea)|input type="(text|password|checkbox|radio|hidden|file)") name="(\w+)(\[\])?"(?: class="[^"]*?(?:type_(\w+))?[^"]*?(?:cfg_(\w+))?[^"]*?")?( multiple=)?[^>]*?>#ims', */
		  '#<(?:(select|textarea)|input type="(text|password|checkbox|radio|hidden|file)") name="(\w+)(\[\])?"(?: class="([^"]*)")?( multiple=)?[^>]*?>#ims',
						$this->fond_compile, $matches, PREG_SET_ORDER)) {
			return _T('cfg:pas_de_champs_dans', array('nom' => $this->vue));
		}
		
		foreach ($matches as $regs) {
			$name = $regs[3];
			if (substr($name, 0, 5) == '_cfg_') continue;

			$this->champs[$name] = array('balise' => $regs[1]); 
			// input type
		    if ($regs[2]) $this->champs[$name]['type'] = $regs[2];
		    // champs tableau[]
			if ($regs[4]) $this->champs[$name]['tableau'] = true;

			//
			// Extensions et validations des champs
			// via les classes css
			//
			// attention : ordre important : <balise (type="xx")? name="xx" class="xx" multiple="xx" />
			//
			if ($regs[5]) {
				$tcss = explode(' ',trim($regs[5]));
				foreach($tcss as $css){
					// classes css type_xx
					if (substr($css,0,5)=='type_') {
						$this->ajouter_extension($css, $name);
					// classes css cfg_xx
					} elseif (substr($css,0,4)=='cfg_') {
						$this->champs[$name]['cfg'] = substr($css,4); // juste 'id' si classe = cfg_id
						$this->ajouter_extension($css, $name);
					}
				}
			}
			
			// cas particulier automatiques : 
			// * input type file => type de verification : fichier
			if (($regs[2] == 'file') AND (!$this->champs[$name]['cfg'])){
				$this->champs[$name]['cfg'] = 'fichier';
				$this->ajouter_extension('cfg_fichier', $name);	
			}
			
	    }

	    return '';
	}	 
	
	// ajoute une extension (classe cfg_xx ou type_xx) 
	// ce qui dit a cfg d'executer des fonctions particulieres
	// si elles existent : ex: cfg_traiter_cfg_xx()
	// lors de l'appel de 'actionner_extensions($faire)'
	function ajouter_extension($ext, $nom){
		if (!is_array($this->extensions[$ext])) $this->extensions[$ext] = array();
		$this->extensions[$ext][] = $nom;	
	}
	
	// ajoute une extension sur un parametre
	// seulement si un fichier sur ce parametre existe
	function ajouter_extension_parametre($param){
		if (in_array($param, $this->extensions_parametres))
			return true;
		
		if (find_in_path('cfg/params/'.$param.'.php')){
			$this->extensions_parametres[] = $param;
			return true;
		}
		return false;
	}
	
	
	// 
	// Compiler le fond CFG si ce n'est pas fait
	// 
	function recuperer_fond($contexte = array(), $forcer = false){

		if (!$this->fond_compile OR $forcer){
			include_spip('inc/presentation'); // offrir les fonctions d'espace prive
			include_spip('public/assembler');
			
			// rendre editable systematiquement
			// sinon, ceux qui utilisent les fonds CFG avec l'API des formulaires dynamiques
			// et mettent des [(#ENV**{editable}|?{' '}) ... ] ne verraient pas leurs variables
			// dans l'environnement vu que CFG ne pourrait pas lire les champs du formulaire
			#if (!isset($contexte['editable'])) $contexte['editable'] = true; // plante 1.9.2 !!
			
			// passer cfg_id...
			if (!isset($contexte['cfg_id']) && $this->param->cfg_id) {
				$contexte['cfg_id'] = $this->param->cfg_id;
			}
			// passer id aussi
			if (!isset($contexte['id']) && $this->param->cfg_id) {
				$contexte['id'] = $this->param->cfg_id;
			}
			// passer 'message_ok', 'message_erreur', 'erreurs'	
			if (!isset($contexte['message_ok']) && $this->messages['message_ok']) {
				$contexte['message_ok'] = join('<br />',$this->messages['message_ok']);
			}
			if (!isset($contexte['message_erreur']) && $this->messages['message_erreur']) {
				$contexte['message_erreur'] = join('<br />',$this->messages['message_erreur']);
			}
			if (!isset($contexte['erreurs']) && $this->messages['erreurs']) {
				$contexte['erreurs'] = $this->messages['erreurs'];
			}
			
			$val = $this->val ? array_merge($contexte, $this->val) : $contexte;
			$this->fond_compile = recuperer_fond('fonds/cfg_' . $this->vue, $val);
		}
		return $this->fond_compile;
	}
	
	
	//
	// Verifie les autorisations 
	// d'affichage du formulaire
	// (parametre autoriser=faire)
	//
	function autoriser()
	{
		static $autoriser=-1;
		if ($autoriser !== -1) return $autoriser;
		
		include_spip('inc/autoriser');
		if (!$autoriser = autoriser($this->param->autoriser)){
			$this->messages['message_refus'] = $this->param->refus;
		}
		return $autoriser;
	}


	//
	// Log le message passe en parametre
	// $this->log('message');
	//
	function log($message)
	{
		($GLOBALS['auteur_session'] && ($qui = $GLOBALS['auteur_session']['login']))
		|| ($qui = $GLOBALS['ip']);
		spip_log('cfg (' . $this->nom_config() . ') par ' . $qui . ': ' . $message);
	}
	
	
	// lit les donnees depuis le depot
	function lire(){
		list ($ok, $val, $messages) = $this->depot->lire($this->params);
		if ($messages) $this->messages = $messages;
		if ($ok) {
			$this->val = $val;	
		} else {
			$this->messages['message_erreur'][] = _T('cfg:erreur_lecture', array('nom' => $this->nom_config()));
		}
		return $ok;
	}
	
	
	// Ecrit les donnees dans le depot
	function ecrire() {
		list ($ok, $val, $messages) = $this->depot->ecrire($this->params);
		if ($messages) $this->messages = $messages;
		if ($ok){
			$this->val = $val;
			$this->messages['message_ok'][] = $msg = _T('cfg:config_enregistree', array('nom' => $this->nom_config()));
		} else {
			$this->messages['message_erreur'][] = $msg =  _T('cfg:erreur_enregistrement', array('nom' => $this->nom_config()));
		}
		$this->log($msg . ' ' . $this->log_modif);
		return $msg;
	}


	// Efface les donnees dans le depot
	//
	// dans le cas d'une suppression, il faut vider $this->val qui
	// contient encore les valeurs du formulaire, sinon elles sont 
	// passees dans le fond et le formulaire garde les informations
	// d'avant la suppression	
	function effacer(){
		list ($ok, $val, $messages) = $this->depot->effacer($this->params);
		if ($messages) $this->messages = $messages;
		if ($ok) {
			$this->val = $val;
			$this->messages['message_ok'][] = $msg = _T('cfg:config_supprimee', array('nom' => $this->nom_config()));
		} else {
			$this->messages['message_erreur'][] = $msg = _T('cfg:erreur_suppression', array('nom' => $this->nom_config()));
		}
		$this->log($msg);	
		return $msg;	
	}
	

	//
	// Fabriquer les balises des champs d'apres un modele fonds/cfg_<driver>.html
	// $contexte est un tableau (nom=>valeur)
	// qui sera enrichi puis passe a recuperer_fond
	//
	function formulaire($contexte = array())
	{
		if (!find_in_path('fonds/cfg_' . $this->vue . '.html'))
			return '';

		$contexte['_cfg_'] = $this->creer_hash_cfg();

		// recuperer le fond avec le contexte
		// forcer le calcul.
		$this->recuperer_fond($contexte, true);
		$this->recuperer_parametres();
		//$this->effacer_parametres(); // pour enlever les <!-- param=valeur --> ... sans dedoubler le contenu lorsque ce sont des tableau (param*=valeur)
		return $this->fond_compile;
	}
	
	
	//
	function creer_hash_cfg($action=''){
		include_spip('inc/securiser_action');
	    $arg = 'cfg0.0.0-' . $this->param->nom . '-' . $this->vue;
		return 
			'?cfg=' . $this->vue .
			'&cfg_id=' . $this->param->cfg_id .
		    '&arg=' . $arg .
		    '&hash=' .  calculer_action_auteur($action . '-' . $arg);		
	}
	
	
	//
	// charge des actions sur les champs particuliers
	// notifies par 'type_XX' ou 'cfg_YY' sur les classes css
	// s'ils existent dans /cfg/classes/
	//
	// charge ensuite des actions sur les parametres particuliers presents
	// s'ils existent dans /cfg/params/
	//
	// les actions possibles sont :
	// - pre_charger, charger, 
	// - pre_verifier, verifier, 
	// - pre_traiter, post_traiter
	//
	// Apres avoir inclu le fichier,
	// CFG recherche une eventuelle fonction :
	// cfg_{action}_{objet}() et l'execute.
	// Objet pouvant etre : 'type_XX', 'cfg_XX' ou 'param_XX'
	//
	function actionner_extensions($action){

		if ($this->extensions) {
			foreach ($this->extensions as $type => $champs){
				// si un fichier de ce type existe, on lance la fonction 
				// demandee pour chaque champs possedant la classe css en question
				if (include_spip('cfg/classes/'.$type)) {
					$cfg = (strpos($type,'cfg')!==false);
					foreach ($champs as $champ){
						if (function_exists($f = 'cfg_' . $action . '_' . $type)){ // absence possible normale
							if ($cfg){
								// de type 'cfg_' : artillerie lourde
								// on passe le nom du champ et la classe
								$f($champ, $this);
							} else {
								// de type : 'type_' : nom et valeur suffisent
								// retour : 
								// - chaine = erreur...
								// - tableau(ok, valeur);
								if ($res = $f($champ, $this->val[$champ])){
									if (is_array($res)){
										list($ok, $this->val[$champ]) = $res;
									} else {
										$this->messages['erreurs'][$champ] = $res;	
									}
								}
							}
						}
					}
				}	
			}
		}
		if ($this->extensions_parametres){
			foreach ($this->extensions_parametres as $param){
				if (include_spip('cfg/params/'.$param)) {
					if (function_exists($f = 'cfg_' . $action . '_param_' . $param)){ // absence possible normale
						// artillerie lourde on passe
						// la valeur et la classe
						$f($this->param->$param, $this);						
					}
				}
			}
		}
	}
	

	
	// 
	//callback pour interpreter les parametres objets du formulaire
	// commun avec celui de set_vue()
	// 
	// Parametres : 
	// - $regs[2] = 'parametre'
	// - $regs[3] = '*' ou ''
	// - $regs[4] = 'valeur'
	// 
	// Lorsque des parametres sont passes dans le formulaire 
	// par <!-- param=valeur -->
	// stocker $this->param->parametre=valeur
	// 
	// Si <!-- param*=valeur -->
	// Stocker $this->param->parametre[]=valeur
	// 
	//
	function post_params($regs) {

		// $regs[3] peut valoir '*' pour signaler un tableau
		$regs[4] = trim($regs[4]);
		
		if (empty($regs[3])) {
		    $this->param->{$regs[2]} = $regs[4];
		} elseif (is_array($this->param->{$regs[2]})) {
		    $this->param->{$regs[2]}[] = $regs[4];
		}
		// plus besoin de garder ca
		return '';
	}
}

?>
