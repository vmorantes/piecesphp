/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('canDeletePublication', function (e) {

	const langGroup = 'appPublicationsLang'
	const deleteButtons = document.querySelectorAll('[delete-publication-button]')

	registerDynamicMessages(langGroup)

	deleteButtons.forEach((deleteButton) => {
		const eventAttached = deleteButton.eventAttached === true
		if (!eventAttached) {
			deleteButton.removeEventListener('click', deleteHandler)
			deleteButton.addEventListener('click', deleteHandler)
		}
		deleteButton.eventAttached = true
	})

	function deleteHandler(e) {

		e.preventDefault()

		let routeDelete = e.currentTarget.dataset.route
		let title = e.currentTarget.dataset.title
		let message = e.currentTarget.dataset.message
		title = typeof title == 'string' && title.length > 0 ? title : _i18n(langGroup, 'Confirmación')
		message = typeof message == 'string' && message.length > 0 ? message : _i18n(langGroup, '¿Seguro de eliminar el elemento?')

		$('body').addClass('wait-to-action')
		$.toast({
			title: title,
			message: message,
			displayTime: 0,
			class: 'white',
			position: 'top center',
			classActions: 'top attached',
			actions: [{
				text: _i18n(langGroup, 'Sí'),
				class: 'red',
				click: function () {

					showGenericLoader('ELIMINAR_ITEM')

					let formData = new FormData()

					postRequest(routeDelete).then(function (res) {

						let success = res.success
						let name = res.name
						let message = res.message
						let values = res.values
						let redirect = values.redirect
						let redirect_to = values.redirect_to

						if (success) {

							successMessage(name, message)

							setTimeout(function () {
								if (redirect) {
									window.location.href = redirect_to
								}
							}, 1000)


						} else {
							errorMessage(name, message)
						}

					}).always(function () {
						removeGenericLoader('ELIMINAR_ITEM')
					})

					$('body').removeClass('wait-to-action')

				}
			}, {
				text: _i18n(langGroup, 'No'),
				class: 'blue',
				click: function () {
					$('body').removeClass('wait-to-action')
					return true
				}
			}]
		})

	}

	function registerDynamicMessages(name) {

		if (typeof pcsphpGlobals != 'object') {
			window.pcsphpGlobals = {}
		}
		if (typeof pcsphpGlobals.messages != 'object') {
			pcsphpGlobals.messages = {}
		}
		if (typeof pcsphpGlobals.messages.es != 'object') {
			pcsphpGlobals.messages.es = {}
		}
		if (typeof pcsphpGlobals.messages.en != 'object') {
			pcsphpGlobals.messages.en = {}
		}

		let es = {
			'Confirmación': 'Confirmación',
			'¿Seguro de eliminar el elemento?': '¿Seguro de eliminar el elemento?',
			'Sí': 'Sí',
			'No': 'No',
		}
		let en = {
			'Confirmación': 'Confirmation',
			'¿Seguro de eliminar el elemento?': 'Are you sure you want to delete the item?',
			'Sí': 'Yes',
			'No': 'No',
		}

		for (let i in es) {
			if (typeof pcsphpGlobals.messages.es[name] == 'undefined') pcsphpGlobals.messages.es[name] = {}
			pcsphpGlobals.messages.es[name][i] = es[i]
		}

		for (let i in en) {
			if (typeof pcsphpGlobals.messages.en[name] == 'undefined') pcsphpGlobals.messages.en[name] = {}
			pcsphpGlobals.messages.en[name][i] = en[i]
		}
	}

})


