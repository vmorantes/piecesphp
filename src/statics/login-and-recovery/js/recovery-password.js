

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
	let changePasswordContainer = container.find('[change-password]')
	let errorContainer = container.find('[error]')
	let finishContainer = container.find('[finish]')
	let hasCode = container.find('[has-code]')
	let repeat = container.find('[repeat]')
	let messageBox = container.find('[message]')

	let headerMain = $('.container .header.one')
	let headerCode = $('.container .header.two')
	let headerChangePassword = $('.container .header.two-two')
	let headerWrongMail = $('.container .header.three')
	let headerWrongCode = $('.container .header.four')
	let headerFinish = $('.container .header.five')

	let lang = pcsphpGlobals.lang == pcsphpGlobals.defaultLang ? '' : pcsphpGlobals.lang + '/'

	let recoveryForm = recoveryContainer.find('form')
	let codeForm = codeContainer.find('form')
	let changePasswordForm = changePasswordContainer.find('form')

	codeContainer.hide()
	changePasswordContainer.hide()
	errorContainer.hide()
	finishContainer.hide()

	recoveryForm.on('submit', function (e) {

		e.preventDefault()

		let recovery = postRequest(lang + 'users/recovery-code', new FormData(recoveryForm[0]))

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

		let recovery = postRequest(lang + 'users/verify-create-password-code', new FormData(codeForm[0]))

		codeForm.find('.field').addClass('disabled')

		recovery.done(function (res) {

			if (res.success === true) {

				headerCode.hide(delayHide)
				codeContainer.hide(delayHide)

				headerChangePassword.show(delayShow)
				changePasswordContainer.show(delayShow)

				messageBox.html(``)
				changePasswordForm.find("[name='code']").val(codeForm.find("[name='code']").val())
				codeForm[0].reset()

			} else {

				if (res.error == 'EXPIRED_OR_NOT_EXIST_CODE') {

					headerCode.hide(delayHide)
					codeContainer.hide(delayHide)

					headerWrongCode.show(delayShow)
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

	changePasswordForm.on('submit', function (e) {

		e.preventDefault()

		let recovery = postRequest(lang + 'users/create-password-code', new FormData(changePasswordForm[0]))

		codeForm.find('.field').addClass('disabled')

		recovery.done(function (res) {

			if (res.success === true) {

				headerChangePassword.hide(delayHide)
				changePasswordContainer.hide(delayHide)

				headerFinish.show(delayShow)
				finishContainer.show(delayShow)

				messageBox.html(`<h1>${_i18n('userProblems', 'Ingrese con su usuario y la nueva contraseña')}</h1>`)

				changePasswordForm[0].reset()

			} else {

				if (res.error == 'NOT_MATCH_PASSWORDS') {

					messageBox.html(_i18n('userProblems', 'Las contraseñas no coinciden'))
					codeForm.find("[type='password']").parent().addClass('error')

				} else {

					codeForm.find("[type='password']").parent().removeClass('error')
					messageBox.html(res.message)

				}

			}

		})

		recovery.fail(function (jqXHR) {

			console.error(jqXHR)
			messageBox.html(_i18n('errors', 'unexpected_error_try_later'))

		})

		recovery.always(function () {

			changePasswordForm.find('.field').removeClass('disabled')

		})

		return false
	})

	hasCode.on('click', function (e) {
		e.preventDefault()

		headerMain.hide(delayHide)
		recoveryContainer.hide(delayHide)

		headerCode.show(delayShow)
		codeContainer.show(delayShow)

		return false
	})

	repeat.on('click', function (e) {
		e.preventDefault()
		recoveryContainer.show(delayShow)
		headerMain.show(delayShow)

		headerCode.hide(delayHide)
		codeContainer.hide(delayHide)

		headerChangePassword.hide(delayHide)
		changePasswordContainer.hide(delayHide)

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
