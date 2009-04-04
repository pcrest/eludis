<?php

//    Fichier créé pour SPIP avec un bout de code emprunté à celui ci.
//    Distribué sans garantie sous licence GPL.
//    Copyright (C) 2006  Pierre ANDREWS
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


function parser_dossier_squelettes() {
  global $dossier_squelettes;
  return split(':',$dossier_squelettes);
}

function creer_dossier_squelettes($liste) {
  global $dossier_squelettes;
  $dossier_squelettes = join(':',$liste);
}

function ajouter_profil() {
  $connect_statut = $GLOBALS['auteur_session']['statut'];

  $profil = substr($connect_statut,1);

  $dossiers = parser_dossier_squelettes();
  $final = array();
  foreach($dossiers as $d) {
	if($d) {
	  $final[] = "$d=$profil";
	  $final[] = $d;
	}
  }
  $final[] = "squelettes=$profil";
  creer_dossier_squelettes($final);
}

ajouter_profil();
?>
