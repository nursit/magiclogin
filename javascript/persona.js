/*
 *  Plugin persona pour SPIP
 *
 *  (c) Fil 2012 - Licence GNU/GPL
 *
 */

var magiclogin_persona = {
	url_verify:"",
	messages:{
		message_js_appel_incorrect:'',
		message_js_connecting:'',
		message_js_authorize_popup:'',
		message_js_unexpected_error:''
	},

	/**
	 * Afficher un message de service
	 */
	message: function(m) {
		if (!jQuery('.login-messages').length)
			jQuery('.login-links').prepend("<span class='login-messages'></span>");
		jQuery('.login-messages').html(m);
	},


	start: function(url_verify) {
		if (!url_verify)
			this.message(this.messages.message_js_appel_incorrect);
		this.url_verify = url_verify;
		this.message(this.messages.message_js_connecting);
		jQuery.getScript("https://login.persona.org/include.js",function(){
			magiclogin_persona.login();
		})
	},

	/**
	 * Lancer le login
	 */
	login: function() {
		navigator.id.get(magiclogin_persona.verify_server);
		this.message(magiclogin_persona.messages.message_js_authorize_popup);
	},

	/*
	 * fonction demandant au serveur de verifier l'assertion persona
	 * et de nous loger sur SPIP au passage, si le site est ainsi configure'
	 */
	verify_server: function(assertion) {
		console.log(magiclogin_persona.url_verify);
	  if (assertion) {
	    jQuery.post(magiclogin_persona.url_verify,
	      {
	      assertion: assertion,
	      audience: window.location.href.replace(/(\/\/.*?)\/.*/, '$1')
	      },
		    magiclogin_persona.welcome
	    );
	  }
	},

	/*
	 * Fonction appelee quand on a reussi a se connecter
	 */
	welcome: function(e) {
	  console.log(e);
	  if (e.status == "okay") {
		  if (e.message) {
        this.message(e.message);
      }
		  if (e.redirect)
			  window.location = e.redirect;
	    if (e.action) {
	      eval(e.action);
	    }
	  }
	  else {
		  magiclogin_persona.message(magiclogin_persona.messages.message_js_unexpected_error + (e.reason || ""));
	  }
	}
}
