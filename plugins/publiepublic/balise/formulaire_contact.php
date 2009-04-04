<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('inc/texte');
include_spip('inc/lang');
include_spip('inc/mail');
include_spip('inc/date');
include_spip ("inc/meta");
include_spip ("inc/session");
include_spip ("inc/filtres");
include_spip ("inc/acces");
include_spip ("inc/documents");
include_spip ("inc/ajouter_documents");
include_spip ("inc/getdocument");
include_spip('inc/barre');
include_spip('base/abstract_sql');


spip_connect();

charger_generer_url();

function balise_FORMULAIRE_CONTACT ($p) {

	/* Cette fonction défini la balise, et en particulier les variables à récuperer dans le contexte et à passer à la fonction _stat en appelant 
  	 la fonction "calculer_balise_dynamique". On pourra ainsi récuperer l’id_article d’une boucle englobante ou la langue contenue dans l’url.
   	C’est un peu comme les paramètres que l’on passe à une balise INCLURE spip.
	   Déclare le nom de la balise et un tableau des variables à récupérer dans le contexte.
	*/

	$p = calculer_balise_dynamique($p,'FORMULAIRE_CONTACT',array('0'));
	return $p;
}

function balise_FORMULAIRE_CONTACT_stat($args, $filtres) {

	/* Cette fonction reçois deux tableaux :
   1. le premier contient les variables collectée avec le tableau cité plus haut ainsi que les paramètres passés directement à la balise.
   2. le second reçoit les filtres appliqués à la balise, au cas où on veuille faire un prétraitement dessus (pour récuperer des variables
      par exemple).
	Elle doit retourner soit :
    * une chaîne qui représente un message d’erreur
    * un tableau qui sera passé à la balise _dyn (contenant des arguments pour la balise, en générale, le paramètre $args)
	*/
	return ($args);
}

function balise_FORMULAIRE_CONTACT_dyn($id_machin) {

	/* c’est ici qu’on met le traitement des données (insertion en base etc).
   Elle reçoit les valeures retournées par la fonction _stat et doit retourner soit :
    * un message d’erreur
    * un tableau représentant un squelette SPIP :
         1. le nom du fond (e.g. "formulaires/formulaire_forum")
         2. le délais
         3. un tableau des paramètres à passer à ce squelette (ensuite accessible par #ENV)
   On peut acceder ici aux variables postées par le formulaire en utilisation la fonction _request('name'); et faire des traitements 
   en fonction de celles ci pour faire l’insertion en base, envoyer un mail etc...
	*/
	
	global $_FILES, $_HTTP_POST_FILES; // ces variables sont indispensables pour récuperer les documents joints
	// Récupération de id de la rubrique en cours
	$rubrique = intval(_request('id_rubrique'));
	// on recuperer l'id de l'auteur anonymous
	$connect_id_auteur = intval(_request('id_auteur'));
	// si il n'est pas dans la base => plugins openpublishing mal installé
	if(!$connect_id_auteur) {
		echo "Attention votre URL ne comporte pas d'id_auteur !";
		die("Veuillez modifier l'appelle à cette page pour y inclure cet identifiant auteur");
	}
	// récupération des variables du formulaire HTML
	// données actions
	$previsualiser= _request('previsualiser');
	$valider= _request('valider');
	$media=_request('media');
	$abandonner=_request('abandonner');
	// on quitte et renvoie vers le sommaire
	if ($abandonner) {
		$retour = '<div align="center"> <h3>Vous avez abandonn&eacute; la cr&eacute;ation d\'un article. <br /> <br /> Aucne information na &eacute;t&eacute; enregistr&eacute;e.</h3> </div>';
		$message = '<META HTTP-EQUIV="refresh" content="3; url=spip.php?page=rubrique&amp;id_rubrique='.$rubrique.'">';
		$message = $message . $retour;
		return $message;
	}
	// on recupere l'id article (sinon on créer un nouveau article à chaque prévisualisation ou ajout de document ...
	$article = intval(stripslashes(_request('article')));
	// données pour formulaire document
	$formulaire_documents = stripslashes(_request('formulaire_documents'));
	$doc = stripslashes(_request('doc'));
	$type_doc = stripslashes(_request('type'));
	// donnée article
	$surtitre= stripslashes(_request('surtitre'));
	$titre= stripslashes(_request('titre'));
	$soustitre=  stripslashes(_request('soustitre'));
	$descriptif= stripslashes(_request('descriptif'));
	$nom_site= stripslashes(_request('nom_site'));
	$url_site= stripslashes(_request('url_site'));
	$chapo= stripslashes(_request('chapo'));
	$texte= stripslashes(_request('texte'));
	$ps= stripslashes(_request('ps'));
	// déclarations de variables supplémentaires (pour la fonction ajout_document)
	$documents_actifs = array();
	// autres variables
	$lang = _request('var_lang');	
	$nom = 'changer_lang';
	lang_dselect();
	$langues = liste_options_langues($nom, $lang);
	// remise à zero 
	$formulaire_previsu = '';
	$bouton= '';
	$mess_error = '';
	$erreur_document = 0;
	// filtrage des zones de texte si elles sont emplies
	if ($titre) $titre = entites_html($titre);
	// on demande un nouvel identifiant
	if (($previsualiser) || ($media) || ($valider)) {
		if (!$article) $article=op_request_new_id($connect_id_auteur);
	}
	// l'auteur demande la publication de son article
	if($valider) {
		// statut de l'article : proposé
		$statut= 'prop';
		/****** préparation de la mise en base de donnée 	 ********/
		// on recupere le secteur et la langue associée
		$s = spip_query("SELECT id_secteur, lang FROM spip_rubriques WHERE id_rubrique = '$rubrique' ");
		if ($r = spip_fetch_array($s)) {
			$id_secteur = $r["id_secteur"];
			$lang = $r["lang"];
			// L'article existe déjà, on fait donc un UPDATE, et non un INSERT
			$retour = spip_query('UPDATE spip_articles SET titre = ' . spip_abstract_quote($titre) .
			',	id_rubrique = ' . spip_abstract_quote($rubrique) .
			',	texte = ' . spip_abstract_quote($texte) .
			',	statut = ' . spip_abstract_quote($statut) .
			',	lang = ' . spip_abstract_quote($lang) .
			',	id_secteur = ' . spip_abstract_quote($id_secteur) .
			',	ps ='.  spip_abstract_quote($ps) .
			',  nom_site =' . spip_abstract_quote($nom_site) .
			',  url_site =' . spip_abstract_quote($url_site) .
			',  surtitre =' . spip_abstract_quote($surtitre) .
			',  soustitre =' .  spip_abstract_quote($soustitre) .
			',  chapo =' . spip_abstract_quote($chapo) .
			',  descriptif =' . spip_abstract_quote($descriptif) .
			',	date = NOW()' .
			',	date_redac = NOW()' .
			',	date_modif = NOW()' .
			' WHERE id_article = ' . spip_abstract_quote($article) );
			if ($retour == 1){ 
				// tout c'est bien passé
				$retour = '';
			}
			else{
				$retour = "Erreur lors de l'insertion de votre article dans la base de donn&eacute;e, veuillez contactez les responsables du site";
			}
		} 
		$retour = '<div align="center"> <h3>Votre article a bien &eacute;t&eacute; enregistr&eacute;. <br /> <br /> Il sera publi&eacute; apr&egrave;s validation.</h3> </div>';
		$message = '<META HTTP-EQUIV="refresh" content="3; url=spip.php?page=rubrique&amp;id_rubrique='.$rubrique.'">';
		$message = $message . $retour;
		return $message;
	}
	// si l'auteur ne valide pas ou entre pour la première fois
	else{
		// statut de l'article : en préparation
		$statut="prepa";
		// si l'auteur demande la prévisualisation
		if($previsualiser){
			// quelques petites vérifications
			if (strlen($titre) < 3){$erreur .= _T('forum_attention_trois_caracteres');}
			if(!$erreur){$bouton= _T('Proposer');}
			// on rempli le formulaire de prévisualisation
			$formulaire_previsu = inclure_balise_dynamique(
			array('formulaires/FORMULAIRE_CONTACT_previsu', 0,
				array(
					'date_redac' => $date_redac,
					'titre' => interdire_scripts(typo($titre)),
					'texte' => propre($texte),
					'ps' => propre($ps),
					'nom_site' => propre($nom_site),
					'url_site' => propre($url_site),
					'surtitre' => propre($surtitre),
					'soustitre' => propre($soustitre),
					'chapo' => propre($chapo),
					'descriptif' => propre($descriptif),
					'erreur' => $erreur
				)
			), false);
		// aucune idée de ce que c'est, mais ça à l'air important
		$formulaire_previsu = preg_replace("@<(/?)f(orm[>[:space:]])@ism",
		"<\\1no-f\\2", $formulaire_previsu);
	}
	// si l'auteur ajoute un documents
	if($media) {
		// compatibilité php < 4.1
  	if (!$_FILES) $_FILES = $GLOBALS['HTTP_POST_FILES'];
		// récupération des variables
		$fichier = $_FILES['doc']['name'];
		$size = $_FILES['doc']['size'];
		$tmp = $_FILES['doc']['tmp_name'];
		$type = $_FILES['doc']['type'];
		$error = $_FILES['doc']['error'];
		// vérification si upload OK
		if( !is_uploaded_file($tmp) ) {
			echo $error;
			$mess_error = "Erreur d'upload ! le fichier temporaire est introuvable. <br /> Il ce peut que vous tentiez d'uploader un fichier trop volumineux. <br /> La taille maximale autoris&eacute;e est de 5 Mo";
			$erreur_document = 1;
   	}
		else{
			inc_ajouter_documents_dist ($tmp, $fichier, "article", $article, $type_doc, $id_document, &$documents_actifs);
		}
	}
	// cas d'un nouvel article ou re-affichage du formulaire
	// Gestion des documents
	$bouton= "Ajouter un nouveau document";
	$formulaire_documents = inclure_balise_dynamique(
	array('formulaires/formulaire_documents',	0,
		array(
			'id_article' => $article,
			'bouton' => $bouton,
			)
		), false);
	// Liste des documents associés à l'article
	op_liste_vignette($article);
	// le bouton valider
	$bouton= _T('Proposer');
	// et on remplit le formulaire avec tout ça
	return array('formulaires/formulaire_contact', 0,
		array(
			'formulaire_documents' => $formulaire_documents,
			'formulaire_previsu' => $formulaire_previsu,
			'formulaire_agenda' => $formulaire_agenda,
			'bouton' => $bouton,
			'article' => $article,
			'rubrique' => $rubrique,
			'surtitre' => $surtitre,
			'soustitre' => $soustitre,
			'descriptif' => $descriptif,
			'nom_site' => $nom_site,
			'url_site' => $url_site,
			'chapo' => $chapo,
			'ps' => $ps,
			'mess_error' => $mess_error,
			'url' =>  $url,
			'titre' => interdire_scripts(typo($titre)),
			'texte' => $texte
	));
}
}

// fonction qui affiche la zone de texte et la barre de typographie
function barre_article($texte, $rows, $cols, $lang=''){
	static $num_formulaire = 0;
	include_ecrire('inc/layer');
	$texte = entites_html($texte);
	if (!$GLOBALS['browser_barre'])
		return "<textarea name='texte' rows='$rows' class='forml' cols='$cols'>$texte</textarea>";
		$num_formulaire++;
		return afficher_barre("document.getElementById('formulaire_$num_formulaire')", false) .
	  "<textarea name='texte' rows='$rows' class='forml' cols='$cols'
		id='formulaire_$num_formulaire'
		onselect='storeCaret(this);'
		onclick='storeCaret(this);'
		onkeyup='storeCaret(this);'
		ondbclick='storeCaret(this);'>$texte</textarea>" .
		$GLOBALS['options'];
}

// pour garder la valeur lors d'un rechargement de page
function selected_option($id_rubrique, $rubrique_boucle,$titre_rubrique){
	$selected = '';
	if ($id_rubrique == $rubrique_boucle) $selected = "SELECTED";
	return "[<option value='$rubrique_boucle' $selected >&nbsp;$titre_rubrique</option>]";
}

// fonction qui demande à la base un nouvel id_article
function op_request_new_id($connect_id_auteur){
	$statut_nouv='prepa';
	$forums_publics = substr(lire_meta('forums_publics'),0,3);
	spip_query("INSERT INTO spip_articles (statut, date, accepter_forum) VALUES ( 'prepa', NOW(), '$forums_publics')");
	$article = mysql_insert_id();
	spip_query("DELETE FROM spip_auteurs_articles WHERE id_article = $article");
	spip_query("INSERT INTO spip_auteurs_articles (id_auteur, id_article) VALUES ($connect_id_auteur, $article)");
	return $article;
}

// fonction qui liste les documents
function op_liste_vignette($article){
	$result = spip_query("SELECT * FROM spip_documents_articles WHERE id_article = $article");
	if (mysql_num_rows($result) > 0 ) {
		echo '<div id="block-center">';
		echo '<div id="block-center-titre"><b>&nbsp;&nbsp;Vos documents</b></div>';
		echo '<div id="block-content">';
	}
	else return;
	echo '<center><table><tr>';
	while($row=mysql_fetch_array($result)){
		$id_doc = $row[0];
		$result2 = spip_query("SELECT fichier, mode FROM spip_documents WHERE id_document = $id_doc");
		while($row2=mysql_fetch_array($result2)){
			$empla = $row2['fichier'];
			$mode = $row2['mode'];
			// ajout du code inclusion
			if ($mode == "vignette") {
				echo '<td align="center"><img src="'.$empla.'" width="100" height="100" \><br />';
				echo '<code>&lt;img'.$id_doc.'&gt;</code><br />';
			}
			else {
				$tableau = split('[.]', $empla);
				$ext = $tableau[1];
				// ajout pour utiliser les vignettes spip pour documents
				list($fic, $largeur, $hauteur) = vignette_par_defaut($ext);
 				$image = "<img src='$fic'\n\theight='$hauteur' width='$largeur' />";
				echo '<td align="center">'.$image.'<br />';
				echo '<code>&lt;doc'.$id_doc.'&gt;</code><br />';
			}
			echo '</td>';
		}
	}
	echo '</tr></table>';
	echo '</center>';
	echo '</div></div><br />';
}
?>