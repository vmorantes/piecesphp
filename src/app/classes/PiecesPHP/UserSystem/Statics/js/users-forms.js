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
					window.location.reload()
				} else {
					errorMessage('Error', res.message)
				}
			}).fail(function (res) {
				console.error(res)
			}).always(function () {
				removeGenericLoader('CARGA_FOTO_PERFIL')
			})

		})

	}

})
