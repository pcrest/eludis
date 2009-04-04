<?php

function pub_insert_head($flux){

// on ajoute la feuille de style pub.css

$flux .=

'
<link rel="stylesheet" href="'.url_absolue(find_in_path('css/pub.css')).'" type="text/css" />';
	return $flux;
}

?>
