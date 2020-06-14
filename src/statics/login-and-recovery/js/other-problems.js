

$(document).ready(function (e) {

	let delayHide = 500
	let delayShow = 500

	let container = $('.form-container')

	let systemMail = container.attr('data-system-mail')

	if (typeof systemMail == 'string' && systemMail.trim().length > 0) {
		systemMail = systemMail.trim()
	} else {
		systemMail = 'sample@sample.com'
	}

	let containerForm = container.find('[claim]')
	let finishContainer = container.find('[finish]')
	let messageBox = container.find('[message]')
	let form = containerForm.find('form')

	let headerMain = $('.container .header.one')
	let headerFinish = $('.container .header.two')

	let lang = pcsphpGlobals.lang == pcsphpGlobals.defaultLang ? '' : pcsphpGlobals.lang + '/'

	finishContainer.hide()

	form.on('submit', function (e) {

		e.preventDefault()

		let formData = new FormData(form[0])

		let request = postRequest(lang + 'users/other-problems', formData)

		form.find('.field').addClass('disabled')

		request.done(function (res) {

			if (res.send_mail === true) {

				headerMain.hide(delayHide)
				containerForm.hide(delayHide)

				headerFinish.show(delayShow)
				finishContainer.show(delayShow)

				messageBox.html(
					formatStr(
						_i18n('userProblems', 'Será solucionada muy pronto, por favor verifique su correo en las próximas horas. <br> El correo puede estar en "No deseado", por favor revise la carpeta de Spam. El remitente del correo es <strong>%r</strong>.'),
						[
							systemMail,
						]
					)
				)

				form[0].reset()

			} else {

				messageBox.html(res.message)

			}

		})

		request.fail(function (jqXHR) {

			console.error(jqXHR)
			messageBox.html(_i18n('errors', 'unexpected_error_try_later'))

		})

		request.always(function () {

			form.find('.field').removeClass('disabled')

		})

		return false
	})

})
