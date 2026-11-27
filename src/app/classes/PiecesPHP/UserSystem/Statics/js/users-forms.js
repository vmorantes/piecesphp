$(document).ready(function (e) {

	$('.user-form-component .menu .item').tab({
		context: $('.user-form-component')
	})

	$('.user-form-component #context-sub-tabs .menu .item').tab({
		context: 'parent'
	})

	configProfilePhoto()
	configRevokeUserSessions()

	const formConfiguration = {
		onSetFormData: function (formData) {
			return formData
		},
	}

	//Un selector por modo, no por tipo: user-form.php pinta el mismo formulario para cualquier tipo, también uno del
	//proyecto (user-types.php), y lleva las clases `users <modo>` (ADR 0078).
	const forms = {}
	const usersFormsSelectors = {
		create: 'form.users.create',
		edit: 'form.users.edit',
		profile: 'form.users.profile',
	}

	for (const formMode in usersFormsSelectors) {
		forms[formMode] = genericFormHandler(usersFormsSelectors[formMode], formConfiguration)
		if (formMode == 'create' || formMode == 'edit') {
			forms[formMode].find(`[name="organization"][hidden]`).closest('.field').hide()
		}
	}

	function configRevokeUserSessions() {
		const selector = 'form[revoke-user-sessions]'
		const form = $(selector)
		if (form.length < 1) {
			return
		}
		//El toast pinta el mensaje como HTML y el mensaje lleva el nombre de usuario: se escapa.
		const asText = (value) => $('<div>').text(typeof value == 'string' ? value : '').html()
		genericFormHandler(selector, {
			confirmation: {
				selector: `${selector} button[type="submit"]`,
				title: asText(form.data('confirmation-title')),
				message: asText(form.data('confirmation-message')),
			},
		})
	}

	function configProfilePhoto() {

		const section = document.querySelector('[data-user-avatar]')
		if (section === null) {
			return
		}

		new ContentImageEditor().onApply('user-avatar-card', function (file) {

			const formData = new FormData()
			formData.set('user_id', section.getAttribute('data-user-avatar'))
			formData.set('image', file)

			showGenericLoader('CARGA_FOTO_PERFIL')

			postRequest(section.getAttribute('data-user-avatar-url'), formData).done(function (res) {
				if (res.success) {
					//El aviso antes que la recarga: recargar al momento lo borraba sin que se viera.
					successMessage(_i18n('titles', 'success'), res.message, () => window.location.reload())
				} else {
					errorMessage(_i18n('titles', 'error'), res.message)
				}
			}).fail(function (res) {
				console.error(res)
			}).always(function () {
				removeGenericLoader('CARGA_FOTO_PERFIL')
			})

		})

	}

})
