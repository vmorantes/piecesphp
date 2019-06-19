window.pcsphp = {}
window.pcsphp.authenticator = new PiecesPHPSystemUserHelper('users/login', 'users/verify')

window.onload = () => {
	window.pcsphp.authenticator.setTriggerLogout('[pcsphp-users-logout]')
}
