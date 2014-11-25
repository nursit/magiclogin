<?php
/**
 * Fichier gérant le login avec Persona
 *
 * @plugin     MagicLogin
 * @copyright  2013
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Magiclogin\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


define('_PERSONA_VERIFY', "https://verifier.login.persona.org/verify");

/**
 * S'identifier via Persona
 *   Lancer l'authorisation puis recuperer les tokens
 * @return null|string
 */
function action_magiclogin_with_persona_dist() {

	include_spip('inc/filtres_mini');
	include_spip('inc/distant');
	include_spip('inc/json');

	$redirect = _request('redirect');
	$res = array();

	if (!$assertion = _request('assertion')
	OR !$audience = _request('audience')){
		$res['status'] = 'failure';
		$res['reason'] = "need assertion and audience";
	}
	elseif(!$server = url_absolue('/')
	  OR $server !== "$audience/") {
		$res['status'] = 'failure';
		$res['reason'] = "incorrect audience";
	}
	else {
		// verifier l'assertion et l'audience
		$data = array(
			'assertion' => $assertion,
			'audience' => $audience
		);
		# forcer l'absence de boundary : persona.org/verify ne le tolere pas
		# cf. https://github.com/mozilla/browserid/issues/649
		$boundary = false;
		$response = recuperer_page(_PERSONA_VERIFY, false, false, null,$data,$boundary);

		if (!$response){
			$res['status'] = 'failure';
			$res['reason'] = "could not connect to the verification server; please retry";
		}
		elseif(!$response = json_decode($response,true)){
			$res['status'] = 'failure';
			$res['reason'] = "invalid response from the verification server; please retry";
		}
		else {
			$res = $response;
			if ($res['status'] == 'okay'
				AND $email = $res['email']
				AND $res['expires'] > $_SERVER['REQUEST_TIME']
				AND $res['audience'] == $audience){

				// retrouver l'auteur
				$auteur = magiclogin_informer_personaaccount($res);
				if (!isset($auteur['id_auteur'])){
					// si pas trouvé, on redirige vers l'inscription en notant en session les infos collectees
					// pour le pre-remplissage
					include_spip("inc/session");
					session_set("magiclogin_pre_signup",$auteur);
					// et rediriger vers la page de signup
					$res['redirect'] = parametre_url(generer_url_public("signup","",true),"redirect",$redirect,"&");
				}
				else {
					// loger l'auteur
					include_spip("inc/auth");
					auth_loger($auteur);
					// et voila
					if (_request('redirect')){
						$res['redirect'] = _request('redirect');
						$res['message'] = _T('persona:message_succes_redirige');
					}
				}
			}
			else {
				$res['status'] = 'failure';
				$res['reason'] = "acces forbidden";
			}
		}
	}

	header('Content-Type: text/json; charset=utf-8');
	echo json_encode($res);
	die();
}


/**
 * Retrouver l'auteur associe a l'email Persona
 * et si il n'existe pas le pre-remplir a partir des infos collectees aupres de Persona
 *
 * @param array $response
 * @return array
 */
function magiclogin_informer_personaaccount($response){

	// chercher l'auteur avec ces tokens
	if (!$infos = sql_fetsel("*",
		  "spip_auteurs",
		  "statut!=".sql_quote('5poubelle')."AND email=".sql_quote($response['email']))){

		$infos = array();
		$infos['source'] = "persona";
		$infos['email'] = $response['email'];
	}

	return $infos;
}


function magiclogin_signup_with_persona_dist($desc, $pre_signup_infos){
	$desc['email'] = $pre_signup_infos['email'];
	return $desc;
}