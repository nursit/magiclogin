<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@balise_URL_LOGIN
function balise_URL_LOGIN ($p) {return calculer_balise_dynamique($p,'URL_LOGIN', array());
}

// $args[0] = methode de login [(#URL_LOGIN{persona})]
// $args[0] = url destination apres login [(#URL_LOGIN{persona,url})]
// http://doc.spip.org/@balise_URL_LOGIN_stat
function balise_URL_LOGIN_stat ($args, $context_compil) {
	$with = isset($args[0]) ? $args[0] : '';
	$url = isset($args[1]) ? $args[1] : '';
	return array($with,$url);
}

// http://doc.spip.org/@balise_URL_LOGIN_dyn
function balise_URL_LOGIN_dyn($with, $cible) {

	if ($GLOBALS['visiteur_session']['login'] OR $GLOBALS['visiteur_session']['statut']) return '';

	if (!$cible){
		if (_request(_SPIP_PAGE)=='login' AND _request('url'))
			$cible = _request('url');
		else
			$cible = self('&');
	}

	$url = url_absolue("magiclogin.api/$with");
	$url = parametre_url($url,"redirect",$cible,"&");
	return $url;
}

?>