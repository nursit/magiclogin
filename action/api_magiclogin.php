<?php
/**
 * Fichier gérant le routage du login vers le bon service
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


function action_api_magiclogin_dist() {

	$arg = _request('arg');
	$arg = explode("/",$arg);

	$with = array_shift($arg);
	if ($with=="confirm"){
		$confirm = charger_fonction("magiclogin_confirm_signin","action");
		$confirm();
	}
	else {
		$magiclogin_with = charger_fonction("magiclogin_with_$with","action");
		$magiclogin_with($arg);
	}
}


?>