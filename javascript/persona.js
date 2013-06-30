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
	display:null,

	/**
	 * Afficher un message de service
	 */
	message: function(m,node) {
		if (node || !this.display){
			if (node){
				var l = jQuery(node).closest(".login-links");
				if (jQuery('.login-messages',l).length)
					this.display = jQuery('.login-messages',l);
				else this.display = l.prepend("<span class='login-messages'></span>");
			}
			else {
				if (!jQuery('.login-messages').length)
					this.display = jQuery('.login-links').prepend("<span class='login-messages'></span>");
				else
					this.display = jQuery('.login-messages');
			}
		}
		if (m)
			this.display.addClass("on").html(m);
		else
			this.display.removeClass("on");

	},


	start: function(url_verify, node) {
		if (!url_verify)
			this.message(this.messages.message_js_appel_incorrect, node);
		this.url_verify = url_verify;
		this.message(this.messages.message_js_connecting, node);
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
	  //console.log(e);
	  if (e.status == "okay") {
		  if (e.message) {
			  magiclogin_persona.message(e.message);
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
