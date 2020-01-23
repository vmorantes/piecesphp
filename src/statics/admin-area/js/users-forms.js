$(document).ready(function (e) {

	let cropperAdapter = null

	$('.user-form-component .menu .item').tab({
		context: $('.user-form-component')
	})

	$('.user-form-component #context-sub-tabs .menu .item').tab({
		context: 'parent'
	})

	if (typeof CropperAdapterComponent != 'undefined') {

		cropperAdapter = new CropperAdapterComponent({
			containerSelector: '.ui.form.cropper-adapter',
			outputWidth: 300,
			minWidth: 300,
			cropperOptions: {
				aspectRatio: 1 / 1,
				viewMode: 3,
			},
		})

	}

	configAvatar()
	configProfilePhoto()

	genericFormHandler('form.users.create.root')
	genericFormHandler('form.users.create.admin')
	genericFormHandler('form.users.create.general')

	genericFormHandler('form.users.edit.root')
	genericFormHandler('form.users.edit.admin')
	genericFormHandler('form.users.edit.general')

	genericFormHandler('form.users.profile.root')
	genericFormHandler('form.users.profile.admin')
	genericFormHandler('form.users.profile.general')

	function configAvatar() {

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
					title: _i18n('avatar', 'Confirmación'),
					message: _i18n('avatar', '¿Seguro de guardar el avatar?'),
					position: 'center',
					buttons: [
						['<button>' + _i18n('avatar', 'Sí') + '</button>', function (instance, toast) {

							let overlay = $(document.createElement('div'))
							let overlayID = 'overlay-avatar-upload'
							overlay.attr('id', overlayID)
							overlay.html(`<div class="ui text active inverted loader">${_i18n('avatar', 'Cargando...')}</div>`)

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
						['<button>' + _i18n('avatar', 'No') + '</button>', function (instance, toast) {
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
