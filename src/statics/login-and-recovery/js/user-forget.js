

$(document).ready(function (e) {

	let delayHide = 500
	let delayShow = 500

	let queryURL = window.location.search
	let paramsURL = new URLSearchParams(queryURL)

	let container = $('.form-container')

	let systemMail = container.attr('data-system-mail')

	if (typeof systemMail == 'string' && systemMail.trim().length > 0) {
		systemMail = systemMail.trim()
	} else {
		systemMail = 'sample@sample.com'
	}

	let recoveryContainer = container.find('[recovery]')
	let codeContainer = container.find('[code]')
	let errorContainer = container.find('[error]')
	let finishContainer = container.find('[finish]')
	let hasCode = container.find('[has-code]')
	let repeat = container.find('[repeat]')
	let messageBox = container.find('[message]')

	let headerMain = $('.container .header.one')
	let headerCode = $('.container .header.two')
	let headerWrongMail = $('.container .header.three')
	let headerWrongCode = $('.container .header.four')
	let headerFinish = $('.container .header.five')

	let recoveryForm = recoveryContainer.find('form')
	let codeForm = codeContainer.find('form')

	let lang = pcsphpGlobals.lang == pcsphpGlobals.defaultLang ? '' : pcsphpGlobals.lang + '/'

	codeContainer.hide()
	errorContainer.hide()
	finishContainer.hide()

	recoveryForm.on('submit', function (e) {

		e.preventDefault()

		let formData = new FormData(recoveryForm[0])

		formData.set('type', 'TYPE_USER_FORGET')

		let recovery = postRequest(lang + 'users/user-forget-code', formData)

		recoveryForm.find('.field').addClass('disabled')

		recovery.done(function (res) {

			if (res.send_mail === true) {

				recoveryContainer.hide(delayHide)
				codeContainer.show(delayShow)
				headerMain.hide(delayHide)
				headerCode.show(delayShow)

				messageBox.html(
					formatStr(
						_i18n('userProblems', 'Ingrese el código enviado a su correo, el correo puede estar en "No deseado", por favor revise la carpeta de Spam. El remitente del correo es <strong>%r</strong>.'),
						[
							systemMail,
						]
					)
				)

				recoveryForm[0].reset()

			} else {

				if (res.error == 'USER_NO_EXISTS') {

					headerMain.hide(delayHide)
					headerWrongMail.show(delayShow)
					recoveryContainer.hide(delayHide)
					errorContainer.show(delayShow)

					messageBox.html(_i18n('userProblems', 'El correo ingresado no está asociado a ningún usuario, por favor ingrese otra cuenta de correo o puede crear una solicitud de soporte para asociar ese correo a su cuenta.'))
					recoveryForm[0].reset()

				} else {
					messageBox.html(res.message)
				}

			}

		})

		recovery.fail(function (jqXHR) {

			console.error(jqXHR)
			messageBox.html(_i18n('errors', 'unexpected_error_try_later'))

		})

		recovery.always(function () {

			recoveryForm.find('.field').removeClass('disabled')

		})

		return false
	})

	codeForm.on('submit', function (e) {

		e.preventDefault()

		let formData = new FormData(codeForm[0])

		formData.set('type', 'TYPE_USER_FORGET')

		let recovery = postRequest(lang + 'users/get-username', formData)

		codeForm.find('.field').addClass('disabled')

		recovery.done(function (res) {

			if (res.success === true) {

				finishContainer.show(delayShow)
				codeContainer.hide(delayHide)
				headerCode.hide(delayHide)
				headerFinish.show(delayShow)

				messageBox.html(`<h1>${res.message}</h1>`)

				codeForm[0].reset()

			} else {

				if (res.error == 'EXPIRED_OR_NOT_EXIST_CODE') {

					headerCode.hide(delayHide)
					headerWrongCode.show(delayShow)
					codeContainer.hide(delayHide)
					errorContainer.show(delayShow)

					messageBox.html(_i18n('userProblems', 'El código ingresado está errado, por favor vuelva a ingresar el código, solicite uno nuevo o cree una solicitud de soporte para informar del error.'))

					recoveryForm[0].reset()

				} else {
					messageBox.html(res.message)
				}

			}

		})

		recovery.fail(function (jqXHR) {

			console.error(jqXHR)
			messageBox.html(_i18n('errors', 'unexpected_error_try_later'))

		})

		recovery.always(function () {

			codeForm.find('.field').removeClass('disabled')

		})

		return false
	})

	hasCode.on('click', function (e) {
		e.preventDefault()
		recoveryContainer.hide(delayHide)
		codeContainer.show(delayShow)
		headerMain.hide(delayHide)
		headerCode.show(delayShow)
		return false
	})

	repeat.on('click', function (e) {
		e.preventDefault()
		recoveryContainer.show(delayShow)
		codeContainer.hide(delayHide)
		headerMain.show(delayShow)
		headerCode.hide(delayHide)
		headerWrongMail.hide(delayHide)
		headerWrongCode.hide(delayHide)
		errorContainer.hide(delayHide)
		finishContainer.hide(delayHide)
		messageBox.html('')
		return false
	})

	if (paramsURL.has('code')) {
		let code = paramsURL.get('code').trim()
		if (code.length > 0) {
			codeForm.find("[name='code']").val(code)
			hasCode.click()
		}
	}

})
