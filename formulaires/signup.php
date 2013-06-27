<?php
/**
 * Signup Form
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

function formulaires_signup_charger_dist(){

	$valeurs = array(
		'nom' => '',
		'email' => '',
		'_logo' => '',
	);

	if (isset($GLOBALS['visiteur_session']['magiclogin_pre_signup']['nom']))
		$valeurs['nom'] = $GLOBALS['visiteur_session']['magiclogin_pre_signup']['nom'];
	if (isset($GLOBALS['visiteur_session']['magiclogin_pre_signup']['logo']))
		$valeurs['_logo'] = $GLOBALS['visiteur_session']['magiclogin_pre_signup']['logo'];

	return $valeurs;
}