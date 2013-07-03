<?php
/**
 * Plugin Clients
 * Gestion des comptes clients
 * (c) 2011 Cedric pour Nursit.net
 * Licence GPL
 *
 */
if (!defined('_ECRIRE_INC_VERSION')) return;


function formulaires_editer_profil_charger_dist($id_auteur=null){
	if (!$id_auteur AND isset($GLOBALS['visiteur_session']['id_auteur']))
		$id_auteur = $GLOBALS['visiteur_session']['id_auteur'];
	if (!$id_auteur
	  OR !$auteur = sql_fetsel('*','spip_auteurs','id_auteur='.intval($id_auteur)))
		return false;


	$valeurs = array(
		'logo_on'=>'',
		'_id_auteur'=>$id_auteur,
	);
	foreach(array('nom',
	              'bio',
	              'email'
	        ) as $champ)
		$valeurs[$champ] = $auteur[$champ];

	return $valeurs;
}


function formulaires_editer_profil_verifier_dist($id_auteur=null){
	if (!$id_auteur AND isset($GLOBALS['visiteur_session']['id_auteur']))
		$id_auteur = $GLOBALS['visiteur_session']['id_auteur'];
	$erreurs = array();

	$oblis = array('nom',
	              'email');

	foreach($oblis as $obli)
		if (!strlen(_request($obli)))
			$erreurs[$obli] = _T('editer_profil:erreur_'.$obli.'_obligatoire');

	// Verifier l'email
	if (!isset($erreurs['email'])){
		$email = trim(_request('email'));
		include_spip("inc/filtres");
		if (!email_valide($email))
			$erreurs['email'] = _T('info_email_invalide');
	}

	// verifier l'unicité de l'email
	if (!isset($erreurs['email'])
	  AND sql_countsel("spip_auteurs","id_auteur!=".intval($id_auteur)." AND email=".sql_quote(_request('email')))){
		$erreurs['email'] = _T('editer_profil:erreur_email_deja_utilise');
	}

	return $erreurs;
}

function formulaires_editer_profil_traiter_dist($id_auteur=null){
	if (!$id_auteur AND isset($GLOBALS['visiteur_session']['id_auteur']))
		$id_auteur = $GLOBALS['visiteur_session']['id_auteur'];

	$auteur = sql_fetsel('*','spip_auteurs','id_auteur='.intval($id_auteur));

	if ($auteur['email']==$auteur['login']
	  AND _request('email')
	  AND _request('email')!==$auteur['email'])
		set_request('login',_request('email'));

	include_spip('inc/editer');
	// renseigner le nom de la table auteurs :
	$res = formulaires_editer_objet_traiter('auteur',$id_auteur);

	// le logo
	include_spip('action/iconifier');
	include_spip('formulaires/editer_logo');
	$ajouter_image = charger_fonction('spip_image_ajouter','action');
	$chercher_logo = charger_fonction('chercher_logo','inc');
	$sources = formulaire_editer_logo_get_sources();

	foreach($sources as $etat=>$file) {
		if ($file and $file['error']==0) {
			$logo = $chercher_logo($id_auteur, "id_auteur", $etat);
			if ($logo)
				spip_unlink($logo[0]);
			$ajouter_image("aut".$etat.$id_auteur," ",$file);
		}
	}

	if (isset($res['message_ok'])){
		$res['message_ok'] = _T('editer_profil:message_ok_profil_modifie');
		$res['editable'] = true;
	}
	return $res;
}