

$(document).ready(function (e) {

	let queryURL = window.location.search
	let paramsURL = new URLSearchParams(queryURL)

	let recoveryContainer = $('[recovery]')
	let codeContainer = $('[code]')
	let hasCode = $('[has-code]')
	let repeat = $('[repeat]')
	let messageBox = $('[message] .ui.message.username')
	let messageBoxContent = messageBox.find('.content')

	let recoveryForm = recoveryContainer.find('form')
	let codeForm = codeContainer.find('form')

	messageBox.parent().hide()
	codeContainer.hide()

	if (paramsURL.has('code')) {
		let code = paramsURL.get('code').trim()
		if (code.length > 0) {
			recoveryContainer.hide(400)
			codeContainer.show(500)
			codeForm.find("[name='code']").val(code)
		}
	}

	recoveryForm.on('submit', function (e) {

		e.preventDefault()

		let formData = new FormData(recoveryForm[0])

		formData.set('type','TYPE_USER_FORGET')

		let recovery = postRequest('users/user-forget-code', formData)

		recoveryForm.find('.field').addClass('disabled')

		recovery.done(function (res) {

			if (res.send_mail === true) {

				recoveryContainer.hide(400)
				codeContainer.show(500)

				successMessage(_i18n('titles', 'success'), res.message, () => {

					recoveryForm[0].reset()

				})

			} else {

				errorMessage(_i18n('titles', 'error'), res.message)

			}

		})

		recovery.fail(function (jqXHR) {

			console.error(jqXHR)
			errorMessage(_i18n('titles', 'error'), _i18n('errors', 'unexpected_error_try_later'))

		})

		recovery.always(function () {

			recoveryForm.find('.field').removeClass('disabled')

		})

		return false
	})


	codeForm.on('submit', function (e) {

		e.preventDefault()

		let formData = new FormData(codeForm[0])

		formData.set('type','TYPE_USER_FORGET')

		let recovery = postRequest('users/get-username', formData)

		codeForm.find('.field').addClass('disabled')

		recovery.done(function (res) {

			if (res.success === true) {

				codeContainer.hide(400)
				recoveryContainer.show(500)
				messageBox.parent().show(500)				

				messageBoxContent.html(`<h3>${res.message}</h3>`)

				codeForm[0].reset()
				
				/* successMessage(_i18n('titles', 'success'), '', () => {
					codeForm[0].reset()
				}) */

			} else {

				errorMessage(_i18n('titles', 'error'), res.message)

			}

		})

		recovery.fail(function (jqXHR) {

			console.error(jqXHR)
			errorMessage(_i18n('titles', 'error'), _i18n('errors', 'unexpected_error_try_later'))

		})

		recovery.always(function () {

			codeForm.find('.field').removeClass('disabled')

		})

		return false
	})

	hasCode.on('click', function (e) {
		e.preventDefault()
		recoveryContainer.hide(400)
		codeContainer.show(500)
		return false
	})

	repeat.on('click', function (e) {
		e.preventDefault()
		codeContainer.hide(400)
		recoveryContainer.show(500)
		return false
	})

})
