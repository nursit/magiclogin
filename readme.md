# MagicLogin

Ce plugin permet le login rapide via un réseau social (Twitter, Facebook, Persona) en guise de SSO.
Pour l'utiliser, il faut une page `spip.php?page=signup` qui contient `#FORMULAIRE_SIGNUP{6forum,#ENV{redirect}}` (si vous voulez que les nouveaux inscrits aient un statut 6forum par défaut).
Si l'inscription est interdite sur le site, le formulaire ne permettra pas la création de compte, mais il permet d'associer un compte social avec un auteur existant lors de la première utilisation du compte social.

## Parcours utilisateur

Sur le formulaire de login de SPIP, le plugin ajoute une mention "Se connecter avec" et les liens de connexion via les réseaux sociaux activés et configurés.
Lorsque l'utilisateur utilise un de ces boutons pour se connecter :

- la 1ère fois il est redirigé vers la page `signup` pour indiquer son pseudo et son email.
  - Si c'est un email qui n'est pas en base et que l'inscription est autorisée on l'inscrit et on le connecte immédiatement, et le compte social est associé à son compte auteur SPIP ;
  - Si c'est un email déjà en base on lui envoie un email avec un lien pour vérifier son identité. Quand il clic sur le lien, on associe le compte social avec son compte auteur SPIP et on le connecte.
- les fois suivantes, on reconnait le compte social et l'auteur SPIP associé et on le connecte immédiatement

Lorsque l'utilisateur utilise un compte social sur lequel il est déjà logué, il évite ainsi toute saisie de mot de passe pour s'identifier à son site SPIP.

## Requis techniques

Pour permettre la connexion avec Twitter, le plugin nécessite le plugin Twitter configuré (qui a donc un accès à l'API Twitter via une Application Twitter).

Pour permettre la connexion avec Facebook, il faut créer une application Facebook dédiée au site concerné et indiquer les clés d'accès à l'Application dans la configuration du Plugin

Pour la connexion avec Persona il n'y a pas de pré-requis technique.