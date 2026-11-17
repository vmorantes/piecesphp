var pcsphp = {}
pcsphp.authenticator = new PiecesPHPSystemUserHelper('users/login', 'users/verify', 'users/user-system-features/two-factor-auth-status', document.querySelector('html').getAttribute('lang'))

addEventListener('load', function (e) {
	//En la página de acceso no hay sesión (también tras caducar sin salir): fuera la conversación del asistente.
	if (document.querySelector('[login-form-js]') !== null) {
		PiecesPHPSystemUserHelper.forgetAssistantConversations()
	}
	pcsphp.authenticator.setTriggerLogout('[pcsphp-users-logout]')
	pcsphp.authenticator.verify()
	if (typeof pcsphpGlobals.globalAuthenticator !== 'undefined') {
		pcsphpGlobals.globalAuthenticator = pcsphp.authenticator
	}
})
