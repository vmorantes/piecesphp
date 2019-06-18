$(document).ready(function (e) {

	let usuariosConfigs = new UsuariosConfigs()

	usuariosConfigs.configCrearForm('.ui.form.users.crear')
	usuariosConfigs.configEditarForm('.ui.form.users.editar')
	usuariosConfigs.configTable('.ui.table.users')

	$('.menu .item').tab()

	let avatarComponentSelector = '.avatar-component'
	let avatarComponent = $(avatarComponentSelector)
	let avatarComponentExists = avatarComponent.length > 0

	if (avatarComponentExists) {

		let resourcesRoute = avatarComponent.attr('resources-route')
		let user = avatarComponent.attr('user')
		let saveRoute = avatarComponent.attr('save-route')

		if (typeof avatarComponent.attr('hide') == 'string') {
			let changeButton = $('[change-avatar]')

			changeButton.click(function () {
				changeButton.parent().hide(500, function () {
					avatarComponent.parent().show(500)
				})
			})

			avatarComponent.parent().hide()
		}

		let requestSources = $.ajax({
			dataType: 'json',
			url: resourcesRoute,
		})

		let avatar = new Avatar()

		avatar.on('save', function (image, previewContainer) {

			if (previewContainer != null) {
				let img = new Image()
				img.src = image
				img.width = 150
				$(previewContainer).html(img)
			}

			iziToast.question({
				timeout: false,
				close: false,
				overlay: true,
				displayMode: 'once',
				id: 'question',
				zindex: 999,
				title: 'Confirmación',
				message: '¿Seguro de guardar el avatar?',
				position: 'center',
				buttons: [
					['<button>Sí</button>', function (instance, toast) {

						let overlay = $(document.createElement('div'))
						let overlayID = 'overlay-avatar-upload'
						overlay.attr('id', overlayID)
						overlay.html(`<div class="ui text active inverted loader">Cargando...</div>`)

						overlay.css({
							position: 'absolute',
							zIndex: '999',
							top: '0px',
							left: '0px',
							width: '100%',
							height: '100%',
							backgroundColor: 'rgba(0,0,0,0.5)',
						})

						$(document.body).append(overlay)

						let formData = new FormData()

						let util = new UtilPieces()
						let utilFile = util.file

						let file = utilFile.dataURLToFile(image, 'avatar.png')

						formData.set('user_id', user)
						formData.set('image', file, 'avatar.png')

						let requestUploadAvatar = postRequest(saveRoute, formData)

						requestUploadAvatar.done(function (res) {
							if (res.success) {
								window.location.reload()
							} else {
								errorMessage('Error', res.message)
							}
						})
						requestUploadAvatar.fail(function (res) {
							console.error(res)
						})
						requestUploadAvatar.always(function () {
							overlay.remove()
						})

						instance.hide({}, toast)

					}, true],
					['<button>No</button>', function (instance, toast) {
						instance.hide({}, toast)
					}],
				],
			});
		})

		requestSources.done(function (sources) {
			avatar.config(sources)
		})

		requestSources.fail(function (res) {
			console.error(res)
		})
	}
})


function UsuariosConfigs() {

	this.formNew = null
	this.formEdit = null
	this.table = null

	let formData = null

	/**
	 * setFormData
	 * 
	 * Establece el objeto FormData de los formulario
	 * 
	 * @param {HTMLElement|JQuery|String} form 
	 * @param {boolean} setPassword 
	 * @returns {boolean}
	 */
	function setFormData(form, setPassword = false) {
		form = $(form)

		let data = new FormData(form[0])

		data.delete('password2')

		data.set('status', form.find("[name='status']").val())
		data.set('type', form.find("[name='type']").val())

		if (!setPassword) {
			data.delete('password')
			data.delete('current-password')
		}
		formData = data
	}

	/**
	 * configCrearForm
	 * 
	 * Configura el formulario de creación
	 * 
	 * @param {HTMLElement|JQuery|String} selector 
	 */
	this.configCrearForm = (selector) => {
		let form = $(selector)

		if (form.length == 0) return

		validarPassword(form.find('[name="password"]'), form.find('[name="password2"]'), form.find('[name="current-password"]'))

		form.submit(function (e) {
			e.preventDefault()

			NProgress.configure({ parent: selector })

			NProgress.start()

			setFormData(form, true)

			let crear = crearUsuario(formData)

			crear.done((res) => {
				if (res.success === true) {

					successMessage(_i18n('titles', 'created'), res.message)
					form[0].reset()
					setTimeout(() => window.location.reload(), 500)

				} else {
					let util = new UtilPieces()
					let htmlCreator = util.html

					let errors = []

					for (let error of res.errors) {
						if (error.message.length > 0) {
							errors.push(htmlCreator.create('li', [], null, error.message))
						}
					}

					iziToast.error({
						title: _i18n('titles', 'error'),
						message: htmlCreator.create('ul', [], errors).outerHTML,
					});

				}
			})

			crear.fail((jqXHR) => {
				console.log(jqXHR)
			})

			crear.always(() => {
				NProgress.done()
			})

			return false
		})

		this.formNew = form
	}

	/**
	 * configEditarForm
	 * 
	 * Configura el formulario de edición
	 * 
	 * @param {HTMLElement|JQuery|String} selector 
	 */
	this.configEditarForm = (selector) => {

		let form = $(selector)

		if (form.length == 0) return

		validarPassword(form.find('[name="password"]'), form.find('[name="password2"]'), form.find('[name="current-password"]'))

		form.submit(function (e) {
			e.preventDefault()

			NProgress.configure({ parent: selector })

			NProgress.start()

			let changePassword = form.find('[name="password"]').val().length > 0

			setFormData(form, changePassword)

			let editar = editarUsuario(formData)

			editar.done((res) => {
				if (res.success === true) {

					successMessage(_i18n('titles', 'edited'), res.message)
					form[0].reset()
					setTimeout(() => window.location.reload(), 500)

				} else {

					let util = new UtilPieces()
					let htmlCreator = util.html

					let errors = []

					if (typeof res.message == 'string' && res.message.length > 0) {
						errors.push(htmlCreator.create('li', [], null, res.message))
					}

					for (let error of res.errors) {
						if (error.message.length > 0) {
							errors.push(htmlCreator.create('li', [], null, error.message))
						}
					}

					iziToast.warning({
						title: _i18n('titles', 'error'),
						message: htmlCreator.create('ul', [], errors).outerHTML,
					});

				}
			})

			editar.fail((jqXHR) => {
				console.log(jqXHR)
			})

			editar.always(() => {
				NProgress.done()
			})

			return false
		})

		this.formEdit = form
	}

	this.configTable = (selector) => {

		let tabla = $(selector)

		if (tabla.length == 0) return

		let configDataTable = globales.configDataTables

		configDataTable.columnDefs = [
			{
				targets: 7,
				orderable: false,
			}
		]

		tabla.dataTable().fnDestroy()

		tabla = tabla.DataTable(configDataTable)

		this.table = tabla
	}

	return this
}

function validarPassword(element1, element2, element3) {

	let password = $(element1)
	let passwordConfirm = $(element2)
	let currentPassword = $(element3)


	if (password.length == 0 || passwordConfirm.length == 0) {
		return;
	}

	function validate() {
		if (password.val() != passwordConfirm.val()) {

			passwordConfirm[0].setCustomValidity(_i18n('errors', 'pass_not_match'))

		} else {

			if(currentPassword.length > 0){
				if (currentPassword.val().trim().length > 0) {
					passwordConfirm[0].setCustomValidity('')
					currentPassword[0].setCustomValidity('')
				} else {
					currentPassword[0].setCustomValidity('Introduzca la contraseña actual')
				}
			}else{
				passwordConfirm[0].setCustomValidity('')
			}
		}
	}

	password.change(validate)
	passwordConfirm.change(validate)
	currentPassword.change(validate)
}

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
