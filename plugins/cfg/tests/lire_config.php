<?php

/*
 * Plugin CFG pour SPIP
 * (c) toggg 2007, distribue sous licence GNU/GPL
 * Documentation et contact: http://www.spip-contrib.net/
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


// nom du test
$test = 'cfg:lire_config';

// recherche test.inc qui nous ouvre au monde spip
$deep = 2;
$include = '../../tests/test.inc';
while (!defined('_SPIP_TEST_INC') && $deep++ < 4) {
	$include = '../' . $include;
	@include $include;
}
if (!defined('_SPIP_TEST_INC')) {
	die("Pas de $include");
}

// c'est ca qu'on teste
include_spip('cfg_options');

// pour recuperer_fond(), un premier tour à vide pour demarrer la machine ...
include_spip('public/assembler');
recuperer_fond('local/cache-tests/cfg-test', array());

// les bases de test
$assoc = array('one' => 'element 1', 'two' => 'element 2');
$serassoc = serialize($assoc);
// on flingue meta a juste nos donnees
$GLOBALS['meta'] = array(
	'zero' => 0,
	'zeroc' => '0',
	'chaine' => 'une chaine',
	'assoc' => $assoc,
	'serie' => serialize($assoc)
);
$sermeta = serialize($GLOBALS['meta']);

// tableau de test, chaque ligne a de 2 a 4 elements,
// le 2eme sert de defaut pour les suivants si absents
$essais = array(
//        argument       res. 1 argument   res. + defaut     res + serialize
// presents
	array(array(),       $GLOBALS['meta'], $GLOBALS['meta'], $sermeta),
	array('',            $GLOBALS['meta'], $GLOBALS['meta'], $sermeta),
	array('/',           $GLOBALS['meta'], $GLOBALS['meta'], $sermeta),
	array('//',          $GLOBALS['meta'], $GLOBALS['meta'], $sermeta),
	array('zero',        0,                0),
	array('zeroc',       '0',              '0'),
	array('chaine',      'une chaine'),
	array('chaine/',     'une chaine'),
	array('chaine//',    'une chaine'),
	array('assoc',       $assoc,           $assoc,           $serassoc),
	array('assoc/two',   'element 2'),
	array('serie',       $assoc,           $assoc,           $serassoc),
	array('serie/two',   'element 2'),
// pas la
	array('assoc/pasla', array(),             'defaut' , null),
	array('serie/pasla', array(),             'defaut' , null),
	array('la/testid/',  array(),             'defaut' , null),
	array('pasla',       array(),             'defaut' , null),
	array('la/pasla',    array(),             'defaut' , null)
);
$ok = true;
$r = array(null, array(), array(), array());
$s = array(null, '<dl>', '<dl>');
$err = array();
$fun = 'lire_config';
$bal = 'CONFIG';

foreach ($essais as $i => $spec) {
	if (!is_array($spec[0])) {
		$spec[0] = array($spec[0]);
	}
	switch (count($spec[0])) {
		case 0:
			$r[1][$i] = $fun();
			$r[2][$i] = $fun(null, 'defaut');
			$r[3][$i] = $fun(null, null, true);
			$tst1 = $bal;
			$tst2 = $bal . "{'',defaut}";
		break;
		case 1:
			$r[1][$i] = $fun($spec[0][0]);
			$r[2][$i] = $fun($spec[0][0], 'defaut');
			$r[3][$i] = $fun($spec[0][0], null, true);
			$tst1 = $bal . '{' . $spec[0][0] . '}';
			$tst2 = $bal . '{' . ($spec[0][0] ? $spec[0][0] : "''") . ',defaut}';
		break;
/*		case 2:
			$r[1][$i] = $fun($spec[0][0], $spec[0][1]);
			$r[2][$i] = $fun($spec[0][0], $spec[0][1]);
			$r[3][$i] = $fun($spec[0][0], $spec[0][1], true);
		break;
*/
	}
	$att = array(null);
	for ($nbarg = 1; $nbarg < 4; ++$nbarg) {
		if ($r[$nbarg][$i] !== ($att[$nbarg] = count($spec) > $nbarg ? $spec[$nbarg] : $spec[1])) {
			$err[] = $i . "({$essais[$i][0]}) $nbarg (" . print_r($r[$nbarg][$i], true) .
				') attendu (' . print_r($att[$nbarg], true) . ')';
		}
	}
	$s[1] .= '<dt>&#035;' . $tst1 . '</dt><dd>' .
		(is_array($att[1]) ? (!empty($att[1]) ? serialize($att[1]) : '') : $att[1]) .
		'</dd><dd>#' . $tst1 . "</dd>\n";
	$s[2] .= '<dt>&#035;' . $tst2 . '</dt><dd>' .
		(is_array($att[2]) ? (!empty($att[2]) ? serialize($att[2]) : '') : $att[2]) .
		'</dd><dd>#' . $tst2 . "</dd>\n";
}
$s[1] .= '</dl>';
$s[2] .= '</dl>';

function test_bal($bali, $skel, $contexte = array())
{
	$dossier = sous_repertoire(_DIR_TMP, 'cache-tests');
	$fichier = "$dossier$bali.html";

	if (($handle = fopen($fichier, 'w'))) {
		fwrite($handle, "#CACHE{0}\n[(#REM) " . $bali . " ]\n" . $skel);
		fclose($handle);
	    return recuperer_fond('tmp/cache-tests/' . $bali, $contexte);
	}
	return '';
}

for ($i = 1; $i < 3; ++$i) {
	$s[$i] = test_bal($bal . $i, $s[$i]);
	$count = count($r[$i]);

	if (preg_match_all(',<dt>([^<]*)</dt><dd>([^<]*)</dd><dd>([^<]*)</dd>,ms',
			$s[$i], $matches, PREG_SET_ORDER)) {
		foreach ($matches as $regs) {
			--$count;
			if ($regs[3] !== $regs[2]) {
				$err[] = $regs[1] . ' attendu (' . $regs[2] . ') obtenu (' . $regs[3] . ')';
			}
		}
	}
	if ($count) {
		$err[] = "#$bal avec $i arguments, difference dans le compte de tests: $count";
	}
}

echo $err ? 'Echec:<ul><li>' . join("</li>\n<li>", $err) . "</li></ul>\n" : 'OK';

if ($_GET['dump']) {
	echo "<div>\n" . print_r($r[1], true) . "</div>\n";
	echo "<div>\n" . print_r($r[2], true) . "</div>\n";
	echo "<div>\n" . print_r($r[3], true) . "</div>\n";
	echo "<div>\n" . print_r($s[1], true) . "</div>\n";
	echo "<div>\n" . print_r($s[2], true) . "</div>\n";
}
