

$(document).ready(function (e) {

	let containerForm = $('[claim]')
	let form = containerForm.find('form')

	form.on('submit', function (e) {

		e.preventDefault()

		let formData = new FormData(form[0])

		let request = postRequest('users/user-not-exists', formData)

		form.find('.field').addClass('disabled')

		request.done(function (res) {

			if (res.send_mail === true) {

				successMessage(_i18n('titles', 'success'), res.message, () => {

					form[0].reset()

				})

			} else {

				errorMessage(_i18n('titles', 'error'), res.message)

			}

		})

		request.fail(function (jqXHR) {

			console.error(jqXHR)
			errorMessage(_i18n('titles', 'error'), _i18n('errors', 'unexpected_error_try_later'))

		})

		request.always(function () {

			form.find('.field').removeClass('disabled')

		})

		return false
	})

})
