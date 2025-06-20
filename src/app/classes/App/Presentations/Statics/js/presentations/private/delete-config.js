/// <reference path="../../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../../statics/core/js/helpers.js" />
window.addEventListener('canDeletePresentation', function (e) {

	const langGroup = 'appPresentationsLang'
	const deleteButtons = document.querySelectorAll('[delete-presentation-button]')

	registerDynamicMessages(langGroup)

	deleteButtons.forEach((deleteButton) => {
		deleteButton.removeEventListener('click', deleteHandler)
		deleteButton.addEventListener('click', deleteHandler)
	})

	function deleteHandler(e) {

		e.preventDefault()

		let routeDelete = e.currentTarget.dataset.route

		iziToast.question({
			timeout: false,
			close: false,
			overlay: true,
			displayMode: 'once',
			id: 'question',
			zindex: 999,
			title: _i18n(langGroup, 'Confirmación'),
			message: _i18n(langGroup, '¿Seguro de eliminar el elemento?'),
			position: 'center',
			buttons: [
				['<button>' + _i18n(langGroup, 'Sí') + '</button>', function (instance, toast) {

					showGenericLoader('ELIMINAR_ITEM')

					instance.hide({
						onClosed: () => {

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

						}
					}, toast)

				}, true],
				['<button>' + _i18n(langGroup, 'No') + '</button>', function (instance, toast) {
					instance.hide({}, toast)
				}],
			],
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

		let en = {
			'Confirmación': 'Confirmation',
			'¿Seguro de eliminar el elemento?': 'Are you sure you want to delete the item?',
			'Sí': 'Yes',
			'No': 'No',
		}

		//Registrar español a partir de inglés
		for (let i in en) {
			if (typeof pcsphpGlobals.messages.es[name] == 'undefined') pcsphpGlobals.messages.es[name] = {}
			pcsphpGlobals.messages.es[name][i] = i
		}

		for (let i in en) {
			if (typeof pcsphpGlobals.messages.en[name] == 'undefined') pcsphpGlobals.messages.en[name] = {}
			pcsphpGlobals.messages.en[name][i] = en[i]
		}
	}

})


