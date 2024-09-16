var pcsphp = {}
pcsphp.authenticator = new PiecesPHPSystemUserHelper('users/login', 'users/verify', 'users/user-system-features/two-factor-auth-status')

addEventListener('load', function (e) {
	pcsphp.authenticator.setTriggerLogout('[pcsphp-users-logout]')
	pcsphp.authenticator.verify()
})
