<?php


/*
 * Plugin CFG pour SPIP
 * (c) toggg, marcimat 2008, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 */


function cfg_charger_param_selecteur_couleur($valeur, &$cfg){
	// si la librairie farbtastic est installee,
	// on la charge dans le header prive
	$dir_lib = _DIR_LIB . 'farbtastic12/farbtastic/';
	if (file_exists($lib = $dir_lib.'farbtastic.js')) {
		$cfg->param->head .= "\n<script langage='javascript' src='$lib'></script>\n";
		$cfg->param->head .= "
<link rel='stylesheet' href='".$dir_lib."farbtastic.css' type='text/css' />
<style type='text/css'>
<!--
.colorpicker { border:2px solid #ccc; text-align:center; margin:0.5em auto; height:auto; width: 200px; }
.colorpicker_bar { background-color:#ccc; height:1.5em; text-align:right; padding-right:0.5em; color:black; }
.colorpicker_close {display:inline; height: 1em; padding:0 2px; font-weight:bold; margin: 0 0 0 auto; border:1px solid transparent;}
.colorpicker_close:hover { border:1px solid #888; background:white}
.colorpicker_hide { height:auto; display:block; }
.hover { cursor:pointer; }
-->
</style>
<script type='text/javascript'>
//<![CDATA[
	var colorpicker_is_active = false;
	$(document).ready(function() {
		$('.cfg_couleur').each(function(){
			$(this).css('background-color',$(this).attr('value'));
			/* pas de id : on en cree un aleatoire */
			if (!$(this).attr('id')){ 
				$(this).attr('id', parseInt(10000*Math.random()));
			}
		});
		$('.cfg_couleur').click(function() { 
			if(colorpicker_is_active) return(false);
			var color_dest = $(this).attr('id');
			$(this).addClass('colorpicker_hide');
			$(this).after('<div class=\'colorpicker\'><div class=\'colorpicker_bar\'><div class=\'colorpicker_close\'>X</div></div><div id=\'colorpicker\'></div></div>');
			$('#colorpicker').css({display:'block'}).farbtastic('#'+color_dest);
			colorpicker_is_active = true;
			$('.colorpicker_close').hover(function(){
				$(this).addClass('hover');
			},function(){
				$(this).removeClass('hover');
			});
			$('.colorpicker_close').click( function() { 
				$(this).removeClass('colorpicker_hide');
				$('.colorpicker').empty().remove();
				colorpicker_is_active = false;
			});
		});
	});
//]]>
</script>
";
	}
}



?>
