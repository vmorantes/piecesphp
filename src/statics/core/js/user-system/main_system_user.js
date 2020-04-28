var pcsphp = {}
pcsphp.authenticator = new PiecesPHPSystemUserHelper('users/login', 'users/verify')

addEventListener('load', function (e) {
	pcsphp.authenticator.setTriggerLogout('[pcsphp-users-logout]')
	pcsphp.authenticator.verify()
})
