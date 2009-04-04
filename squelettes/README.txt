---------- Installation d'Eludis -------

1. Installer SPIP 1.9.2 [9220]

2. Installer les plugins suivant :
	a. 'publiePublic' 1.0.0
	b. 'squelette par profil' 0.1
	c. 'Recherche Etendu' 0.1 [optionnel]

3. Restaurer la base exemple de eludis.eludia.net

4. Placer les fichiers d'Eludis :
	a. les répertoires : 'squelettes', 'squelettes=comite' et 'squelettes=minirezo' sur la racine
	b. '.htaccess' dans le répertoire : ecrire
	c. Créer un répertoire 'dist_squelettes_origines'
	d. Y placer tous les fichiers html sauf :
		- jquery.js.html
		- style_prive.html

5. Modififier le formulaire : 'dist/formulaires/recherche.html'
		<!-- <label for="recherche"><:info_rechercher:></label> -->

6. Modifier le formulaire : 'dist/formulaires/forum.html'
	a. Mettre en commentaire et en hidden les informations sour l'auteur pour interdire les messages anonymes
	----------------********----------------
<!--	<fieldset>
	<legend><:forum_qui_etes_vous:></legend>
	<p><label for="auteur"><:forum_votre_nom:></label> -->
	<input type="hidden" class="forml" name="auteur" id="auteur" value="#ENV{auteur}" size="40"[ readonly="(#ENV{readonly})"] /></p>
	<!-- <p><label for="email_auteur"><:forum_votre_email:></label> -->
	<input type="hidden" class="forml" name="email_auteur" id="email_auteur" value="#ENV{email_auteur}" size="40"[ readonly="(#ENV{readonly})"] /></p>
<!--	</fieldset> -->

<!--	[(#REM) Piege a robots spammeurs ]
	<p style='display:none;'><label for="nobot"><:antispam_champ_vide:></label>
	<input type="text" name="nobot" id="nobot" value="#ENV{nobot}" size="10" /></p>
-->
	----------------********----------------
	b.Déplacement du groupe ci-dessous pour mettre les mots clefs après le texte
	----------------********----------------
<BOUCLE_G(GROUPES_MOTS)
	{forum=(#CONFIG{mots_cles_forums}|choixsiegal{oui,oui,jamais})}
	{articles==(#ENV{table}|choixsiegal{articles,'oui','.*'})}
	{breves==(#ENV{table}|choixsiegal{breves,'oui','.*'})}
	{rubriques==(#ENV{table}|choixsiegal{rubriques,'oui','.*'})}
	{syndic==(#ENV{table}|choixsiegal{syndic,'oui','.*'})}
>
[(#ID_GROUPE|in_any{#ENV**{ajouter_groupe},' '})
	<fieldset>
	<legend><:mots_clefs:> : #TITRE</legend>
		<INCLURE{fond=formulaires/choix_mots}{id_groupe}{ajouter_mot}{unseul}>
	</fieldset>
]</BOUCLE_G>

[(#ENV**{afficher_texte}|choixsiegal{'non', ' ',''})
[<input type="hidden" name="titre" value="(#ENV{titre})" />]
<p class="spip_bouton"><input type="submit" value="<:forum_valider:>" /></p>]

[(#ENV**{afficher_texte}|choixsiegal{'non', '',' '})
	----------------********----------------
	c. Suppression de  <:mots_clefs:>
	d. déplacer les <p> pour rapprocher la zone texte

7. Modifier le formulaire : 'dist/formulaires/site.html'
	- Remplacer : <legend><:info_site:></legend> par <legend><:proposer_site:></legend>
	- Supprimer : <p></p>
	
8. Modifier le fichier 'squelettes=minirezo/inc-menu_horizontal.html' [optionnel]
Si la structure dispose aussie de GestPlan placer le fichier IMG/hta/gestplan.hta
Sinon supprimer le lien vers l'application

9. Modifier le fichier 'squelettes=minirezo/plan.html' [optionnel]
<BOUCLE_racine(RUBRIQUES){racine}{par titre}{id_rubrique!=XXX}>
ou XXX est égale au numéro de la rubrique Annuaire = 326 dans la version restaurée

10. Modification du fichier 'ecrire/inc/formulaire_site.php'
Ligne 50 est mis en commentaire pour éviter le bug des sites non accepté.
A vérivier sur les versions ultérieurs de SPIP 

----------- Remarque ssur la configuration ---------------
Elles sont importées avec la restauration de la base eludis.eludia.net

A. Rubrique dont id_rubrique est codé en dans les squelettes
=> id_rubrique=326 pour la rubrique 'Annuaire'

B. Mots clefs obligatoires
=> id_mot=4 pour "Extranet" dans le groupe id_group=4 "Pour être diffusé sur"
=> id_mot=13 pour "Page d'accueil" dans le groupe id_groupe=6 "Affichage"
=> id_mot=17 pour le l'annuaire dans les rubriques : 
		- Ce mot clef est à utiliser pour les articles qui sont des redirections
		sur un article de la rubrique Annuaire=326. 
		Alors ces articles s'affiche dans le cartouche 'Annuaire' dans le menu de droite
