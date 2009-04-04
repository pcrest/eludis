<?php

/*
 * Plugin CFG pour SPIP
 * (c) toggg 2007, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 * la fonction appelee par le core, une simple "factory" de la classe cfg
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

function exec_cfg_install_libs_dist($class = null)
{
	include_spip('inc/filtres');
	include_spip("inc/presentation");
	
	global $cfg_libs;
	
	// droits : il faut avoir le droit de choisir les plugins,
	// mais aussi d'en ajouter
	include_spip('inc/autoriser');
	if (!autoriser('configurer', 'plugins')) {
		echo minipres();
		exit;
	}


	pipeline('exec_init',array('args'=>array('exec'=>'cfg'),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('cfg:installation_librairies'), 'cfg');
	echo "<br /><br /><br />\n";

	echo gros_titre(_T('cfg:installation_librairies'), '', false);	
	
	// colonne gauche
	echo debut_gauche('', true);

	
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'cfg'),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'cfg'),'data'=>''));
		
	
	echo debut_droite("", true);
	
	// centre de la page
	echo debut_cadre_trait_couleur('', true, '', _T('cfg:installation_liste_libs'));
	
	// message pour creer le dossier lib/
	if (!is_dir(_DIR_LIB) && !is_writable(_DIR_LIB)){
		echo "<p class='important'>"._T('cfg:installer_dossier_lib',array('dir' => joli_repertoire(_DIR_LIB)))."</p>\n";	
	}
	// message pour installation 1.9.2
	if ($spip192 = (defined('_COMPAT_CFG_192') && _COMPAT_CFG_192)){
		echo "<p>"._T('cfg:installer_lib_192', array('dir' => joli_repertoire(_DIR_LIB)))."</p>\n";	
	}
	
	// mettre un lien pour telecharger les libs
	include_spip('inc/actions');
	foreach ($cfg_libs as $lib=>$info){
		// deja present ?  texte du bouton :  "mettre a jour", sinon "telecharger"
		$quoi = (is_dir(_DIR_LIB . $info['dir'])) ?'update':'install';
		
		echo "<dl>\n";
		echo "<dt>".$info['nom']."</dt>\n";
		echo "<dd>".$info['description']."</dd>\n";
		
		// cas 1.9.2
		// proposer de copier manuellement le zip decompresse dans le dossier lib
		if ($spip192){
			echo "<dd>"
				. (($quoi=='update') ? "("._T('cfg:bouton_mettre_a_jour').") ":"")
				."<a class='spip_out' href='$info[install]' />$info[install]</a></dd>\n";
		
		// cas > 1.9.2
		// chargeur plugin/lib de spip
		} else {
			echo "<dd>".redirige_action_auteur(
				'charger_plugin', 'lib', '', '',
					"<input type='hidden' name='url_zip_plugin' value='$info[install]' />"
					."<input type='hidden' name='retour' value='".self()."' />"
					."<input type='submit' class='fondo' name='ok' value='"
					. (($quoi=='update')?_T('cfg:bouton_mettre_a_jour'):_T('bouton_telecharger'))
					."' />","\nmethod='post'")."</dd>\n";
		}
		echo "</dl>\n";
	
	}
	
	echo fin_cadre_trait_couleur(true);

	// pied
	echo fin_gauche() . fin_page();
}


?>
