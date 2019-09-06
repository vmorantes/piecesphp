window.pcsphp = {}
window.pcsphp.authenticator = new PiecesPHPSystemUserHelper('users/login', 'users/verify')

window.addEventListener('load', function (e) {
	window.pcsphp.authenticator.setTriggerLogout('[pcsphp-users-logout]')
})
