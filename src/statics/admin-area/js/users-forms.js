$(document).ready(function (e) {

	let cropperAdapter = null

	$('.user-form-component .menu .item').tab({
		context: $('.user-form-component')
	})

	$('.user-form-component #context-sub-tabs .menu .item').tab({
		context: 'parent'
	})

	if (typeof CropperAdapterComponent != 'undefined' && $('.ui.form.cropper-adapter').length > 0) {

		cropperAdapter = new CropperAdapterComponent({
			containerSelector: '.ui.form.cropper-adapter',
			outputWidth: 400,
			minWidth: 400,
			cropperOptions: {
				aspectRatio: 1 / 1,
				viewMode: 3,
			},
		})

	}

	configProfilePhoto()

	const formConfiguration = {
		onSetFormData: function (formData) {
			return formData
		},
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let field = jElement.closest('.field')
			let label = field.find('label')
			let altLabel = field.find('.ui.label')
			let placeholder = jElement.attr('placeholder')
			let nameOnLabel = ''

			if (typeof placeholder == 'string' && placeholder.length > 0) {
				nameOnLabel = placeholder
			} else if (label.length > 0) {
				nameOnLabel = label.html()
			} else if (altLabel.length > 0) {
				nameOnLabel = altLabel.text()
			}

			errorMessage(`${nameOnLabel}`, validationMessage)

			event.preventDefault()

		},
	}

	const forms = {}
	const userTypesFormsSelectors = {
		create: {
			root: 'form.users.create.root',
			adminGral: 'form.users.create.admin-general',
			adminOrg: 'form.users.create.admin-organization',
			general: 'form.users.create.general',
			institucional: 'form.users.create.institucional',
			comunicaciones: 'form.users.create.comunicaciones',
		},
		edit: {
			root: 'form.users.edit.root',
			adminGral: 'form.users.edit.admin-general',
			adminOrg: 'form.users.edit.admin-organization',
			general: 'form.users.edit.general',
			institucional: 'form.users.edit.institucional',
			comunicaciones: 'form.users.edit.comunicaciones',
		},
		profile: {
			root: 'form.users.profile.root',
			adminGral: 'form.users.profile.admin-general',
			adminOrg: 'form.users.profile.admin-organization',
			general: 'form.users.profile.general',
			institucional: 'form.users.profile.institucional',
			comunicaciones: 'form.users.profile.comunicaciones',
		},
	}

	for (const formType in userTypesFormsSelectors) {
		const userTypeFormsSelectorsByUserType = userTypesFormsSelectors[formType]
		forms[formType] = typeof forms[formType] == 'object' ? forms[formType] : {}
		for (const userType in userTypeFormsSelectorsByUserType) {
			const userTypeFormSelector = userTypeFormsSelectorsByUserType[userType]
			forms[formType][userType] = genericFormHandler(userTypeFormSelector, formConfiguration)
			if (formType == 'create' || formType == 'edit') {
				forms[formType][userType].find(`[name="organization"][hidden]`).closest('.field').hide()
			}
		}
	}

	function configProfilePhoto() {

		let formSelector = '.profile-photo-form'
		let form = $(formSelector)
		let formExists = form.length > 0

		if (formExists && cropperAdapter !== null) {

			form.on('submit', function (e) {

				e.preventDefault()

				let user = form.find(`[name="user"]`).val()
				let isEdit = form.find(`[name="edit"]`).val() == '1'
				let saveRoute = form.attr('action')

				iziToast.question({
					timeout: false,
					close: false,
					overlay: true,
					displayMode: 'once',
					id: 'question',
					zindex: 999,
					title: _i18n('avatar', 'Confirmación'),
					message: _i18n('avatar', '¿Seguro de guardar la foto de perfil?'),
					position: 'center',
					buttons: [
						['<button>' + _i18n('avatar', 'Sí') + '</button>', function (instance, toast) {

							showGenericLoader('CARGA_FOTO_PERFIL')

							let formData = new FormData()

							formData.set('user_id', user)

							if (isEdit) {

								formData.set('image', cropperAdapter.getFile('avatar.png', null, null, null, true))

							} else {

								formData.set('image', cropperAdapter.getFile('avatar.png'))

							}

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

								removeGenericLoader('CARGA_FOTO_PERFIL')

							})

							instance.hide({}, toast)

						}, true],
						['<button>' + _i18n('avatar', 'No') + '</button>', function (instance, toast) {
							instance.hide({}, toast)
						}],
					],
				});
			})

		}
	}

})
