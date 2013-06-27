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


function action_login_dist() {

	if (!isset($GLOBALS['visiteur_session']['statut'])
	  OR !$GLOBALS['visiteur_session']['statut']){

		$with = _request('with');
		$login_with = charger_fonction("login_with_$with","action");
		$login_with();
	}
}


?>