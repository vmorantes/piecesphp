/**
 * crearUsuario
 * 
 * Espera los items [username,name,lastname,email, type, status, password] en el FormData
 * 
 * @param {FormData} formData Información enviada
 * @returns {jqXHR}
 */
function crearUsuario(formData) {
	return postRequest('users/register', formData)
}

/**
 * editarUsuario
 * 
 * Espera los items [username,name,lastname,email, type, status[, password]] en el FormData
 * 
 * @param {FormData} formData Información enviada
 * @returns {jqXHR}
 */
function editarUsuario(formData) {
	return postRequest('users/edit', formData)
}
