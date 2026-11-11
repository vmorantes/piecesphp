
window.addEventListener(pcsphpGlobals.events.configurationsAndWindowLoad, function (e) {
	changeImageLogin()
	configLoginForm()
})

/**
 * Selecciona una imagen de entre varias al azar
 */
function changeImageLogin() {

	let bgElement = $('[bg-js]')
	let backgrounds = atob(bgElement.attr('bg-js'))
	backgrounds = JSON.parse(backgrounds)

	let randomImageLoginIndex = randomNumber(backgrounds.length > 0 ? backgrounds.length - 1 : backgrounds.length)

	let urlImage = backgrounds[randomImageLoginIndex]

	//Sin fondos queda el degradado del tema.
	if (typeof urlImage == 'string' && urlImage !== '') {
		bgElement.addClass('with-image').css({
			'background-image': `url(${urlImage})`,
		})
	}

	function randomNumber(max = 5) {
		let number = Math.random() * max
		number = Math.round(number)
		if (number > max) {
			number--
		}
		return number
	}
}

/**
 * Configura el formulario de logueo: dos métodos (contraseña y código de acceso), el error dentro del formulario y el
 * segundo factor de aplicación en los dos. Los textos nuevos salen de los data-text-* de la vista.
 */
function configLoginForm() {

	configPasswordEye()

	pcsphp.authenticator.verify(() => window.location.reload())

	const STORAGE_USER_NAME = 'user_name_storage'
	const CODE_DIGITS = 6
	const RESEND_SECONDS = 45
	const userStorage = localStorage.getItem(STORAGE_USER_NAME)

	const form = $('[login-form-js]')
	const formElement = form.get(0)
	const text = (name) => formElement.getAttribute(`data-text-${name}`) || ''
	const subtitle = $('[data-login-subtitle]')
	const methods = form.find('[data-login-methods] .item')
	const errorBox = form.find('[data-login-error]')
	const userNameInput = form.find("[name='username']")
	const passwordInput = form.find("[name='password']")
	const rememberCheckbox = form.find('.remember-me input[type=checkbox]')
	const twoFactorContainer = form.find('.field[twofa-field]')
	const twoFactorInput = twoFactorContainer.find("[name='twoFactor']")
	const submitField = form.find('[data-login-submit]')
	const submitButton = submitField.find('button')
	const codeUserInput = form.find('#login-code-username')
	const codeStep1 = form.find('[data-login-code-step="1"]')
	const codeStep2 = form.find('[data-login-code-step="2"]')
	const codeBoxes = form.find('[data-login-code-boxes] input')
	const codeField = form.find('[data-login-code-field]')
	const resendButton = form.find('[data-login-code-resend]')
	let method = 'password'
	let codeTarget = ''
	let resendTimer = null

	if (userStorage) {
		userNameInput.val(userStorage)
		codeUserInput.val(userStorage)
		rememberCheckbox.prop('checked', true)
	}
	toggleTwoFactor(false)

	//──── El selector de método: pestañas de Fomantic, con flechas y su ARIA ─────────────────────────
	methods.tab({
		onVisible: function (tab) {
			method = tab === 'login-code' ? 'code' : 'password'
			methods.each(function () {
				const active = $(this).data('tab') === tab
				$(this).attr({ 'aria-selected': String(active), tabindex: active ? '0' : '-1' })
			})
			if (method === 'code' && codeUserInput.val().trim() === '') {
				codeUserInput.val(userNameInput.val())
			}
			clearError()
			toggleTwoFactor(false)
			syncMethod()
		},
	})
	methods.on('keydown', function (event) {
		if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
			event.preventDefault()
			const step = event.key === 'ArrowRight' ? 1 : -1
			const next = methods.eq((methods.index(this) + step + methods.length) % methods.length)
			next.trigger('click').trigger('focus')
		}
	})

	function inCodeStep2() {
		return method === 'code' && !codeStep2.prop('hidden')
	}

	function syncMethod() {
		const step2 = inCodeStep2()
		subtitle.text(subtitle.attr(step2 ? 'data-text-code' : 'data-text-password'))
		submitButton.text(submitButton.attr(method === 'code' ? 'data-text-code' : 'data-text-password'))
		//En el paso 1 del código el botón es «Enviar código»: el de ingresar no tiene nada que enviar.
		submitField.prop('hidden', method === 'code' && !step2)
	}

	//──── El error, dentro del formulario ────────────────────────────────────────────────────────────
	function clearError() {
		form.removeClass('error')
		form.find('.field.error').removeClass('error')
		form.find('[aria-invalid]').removeAttr('aria-invalid')
		form.find('[data-login-field-error]').remove()
	}

	//Algunas traducciones de loginForm llevan marcado (H174): el título va como texto, sin él.
	function plain(value) {
		return new DOMParser().parseFromString(String(value), 'text/html').body.textContent || ''
	}

	function showError(title, message, options = {}) {
		errorBox.find('[data-login-error-title]').text(plain(title))
		errorBox.find('[data-login-error-text]').text(typeof message == 'string' ? message : '')
		errorBox.find('[data-login-error-help]').prop('hidden', options.help === false)
			; (options.fields || []).forEach((field) => markField(field.input, field.message || ''))
		form.addClass('error')
	}

	function markField(input, message) {
		//Una casilla del código marca el campo entero del código, no su .field interior.
		const field = input.closest('[data-login-code-field]').length > 0 ? codeField : input.closest('.field')
		field.addClass('error')
		input.attr('aria-invalid', 'true')
		if (message !== '' && field.find('[data-login-field-error]').length === 0) {
			field.append($('<div class="ui basic red pointing prompt label" data-login-field-error></div>').text(message))
		}
	}

	function waitText(seconds) {
		seconds = Number(seconds)
		if (!Number.isFinite(seconds) || seconds <= 0) {
			return ''
		}
		const minutes = Math.floor(seconds / 60)
		const rest = String(Math.floor(seconds % 60)).padStart(2, '0')
		return text('wait').replace('${time}', `${minutes}:${rest}`)
	}

	function setMessageError(error, data) {

		const message = typeof data.message == 'string' ? data.message : ''
		const credentialsFields = method === 'code' ? [{ input: codeBoxes.first() }] : [{ input: userNameInput }, { input: passwordInput }]

		if ('INCORRECT_PASSWORD' == error) {
			if (method === 'code') {
				showError(text('code-invalid-title'), text('code-invalid'), { fields: credentialsFields })
				codeField.addClass('error')
			} else {
				showError(_i18n('loginForm', 'USUARIO_O_CONTRASEÑA_INCORRECTOS'), _i18n('loginForm', 'Por favor, verifique los datos de ingreso y vuelva a intentar.'), { fields: credentialsFields })
			}
		} else if ('BLOCKED_FOR_ATTEMPTS' == error) {
			showError(_i18n('loginForm', 'USUARIO_BLOQUEADO'), _i18n('loginForm', 'Por favor, ingrese al siguiente enlace para desbloquear su usuario.'), { help: false })
		} else if ('TOO_MANY_ATTEMPTS' == error) {
			showError(_i18n('loginForm', 'Demasiados intentos'), `${message} ${waitText(data.retryAfter)}`.trim())
		} else if ('INVALID_USER_TYPE' == error) {
			showError(_i18n('loginForm', 'Cuenta sin un tipo válido'), message)
		} else if ('MAINTENANCE_MODE' == error) {
			showError(_i18n('loginForm', 'Sitio en mantenimiento'), message)
		} else if ('MISSING_OR_UNEXPECTED_PARAMS' == error) {
			showError('Error', message)
		} else if ('GENERIC_ERROR' == error) {
			showError(_i18n('loginForm', 'ERROR_AL_INGRESAR'), _i18n('loginForm', 'Se ha presentado un error al momento de ingresar, por favor intente nuevamente.'))
		} else {
			showError('Error', message)
		}
	}

	//──── Ingresar: con la contraseña o con el código, y el segundo factor si la cuenta lo tiene ──────
	function codeValue() {
		return codeBoxes.map(function () { return this.value }).get().join('')
	}

	function credentials() {
		if (method === 'code') {
			return { username: codeTarget, password: codeValue() }
		}
		return { username: userNameInput.val().trim(), password: passwordInput.val() }
	}

	function validate() {
		const missing = []
		if (method === 'code') {
			if (!/^[0-9]{6}$/.test(codeValue())) {
				missing.push({ input: codeBoxes.first(), message: text('code-required') })
				codeField.addClass('error')
			}
		} else {
			if (userNameInput.val().trim() === '') {
				missing.push({ input: userNameInput, message: text('required') })
			}
			if (passwordInput.val() === '') {
				missing.push({ input: passwordInput, message: text('required') })
			}
		}
		if (twoFactorContainer.is(':visible') && twoFactorInput.val().trim() === '') {
			missing.push({ input: twoFactorInput, message: text('required') })
		}
		missing.forEach((field) => markField(field.input, field.message))
		if (missing.length > 0) {
			missing[0].input.trigger('focus')
		}
		return missing.length === 0
	}

	form.on('submit', function (e) {

		e.preventDefault()
		clearError()

		if (method === 'code' && !inCodeStep2()) {
			sendCode(false)
			return false
		}

		if (!validate()) {
			return false
		}

		const data = credentials()

		if (method === 'password') {
			if (rememberCheckbox.is(':checked')) {
				localStorage.setItem(STORAGE_USER_NAME, data.username)
			} else {
				localStorage.removeItem(STORAGE_USER_NAME)
			}
		}

		const loaderVerifyTwoFactorStatus = 'VerifyTwoFactorStatus'
		showGenericLoader(loaderVerifyTwoFactorStatus)
		pcsphp.authenticator.verifyTwoFactorStatus(data.username, function (required2FA) {

			const toLogin = function () {
				const LoaderName = 'login'
				showGenericLoader(LoaderName)
				const login = pcsphp.authenticator.authenticateWithUsernamePassword(data.username, data.password, twoFactorInput.val())
				const lastURL = form.attr('last-uri')
				login.then(function (res) {

					if (res.auth === true || res.isAuth === true) {
						if (typeof lastURL == 'string' && lastURL.trim().length > 0) {
							window.location.href = lastURL
						} else {
							window.location.reload()
						}
					} else {
						setMessageError(res.error, res)
						removeGenericLoader(LoaderName)
					}

				}).catch(function (jqXHR) {
					removeGenericLoader(LoaderName)
					console.error(jqXHR)
					errorMessage(_i18n('loginForm', 'Error'), _i18n('loginForm', 'Ha ocurrido un error inesperado, intente más tarde.'))
				})
			}

			if (!required2FA) {
				toLogin()
				toggleTwoFactor(false)
			} else if (!twoFactorContainer.is(':visible')) {
				toggleTwoFactor(true)
			} else {
				toLogin()
			}

			removeGenericLoader(loaderVerifyTwoFactorStatus)

		}).catch(function () {
			removeGenericLoader(loaderVerifyTwoFactorStatus)
		})

		return false
	})

	//──── El código de acceso: pedirlo (la ruta de siempre), las casillas y reenviar ─────────────────
	function maskTarget(value) {
		const at = value.indexOf('@')
		if (at < 1) {
			return value
		}
		return `${value.charAt(0)}***${value.slice(at)}`
	}

	function startResendTimer() {
		let left = RESEND_SECONDS
		clearInterval(resendTimer)
		const paint = () => {
			const time = `${Math.floor(left / 60)}:${String(left % 60).padStart(2, '0')}`
			resendButton.text(left > 0 ? text('resend-in').replace('${time}', time) : text('resend'))
			resendButton.prop('disabled', left > 0)
		}
		paint()
		resendTimer = setInterval(() => {
			left--
			paint()
			if (left <= 0) {
				clearInterval(resendTimer)
			}
		}, 1000)
	}

	function showCodeStep(step) {
		codeStep1.prop('hidden', step !== 1)
		codeStep2.prop('hidden', step !== 2)
		toggleTwoFactor(false)
		syncMethod()
	}

	function sendCode(resend) {
		const username = resend ? codeTarget : codeUserInput.val().trim()
		clearError()
		if (username === '') {
			markField(codeUserInput, text('required'))
			codeUserInput.trigger('focus')
			return
		}
		const requestURL = new URL(formElement.getAttribute('data-otp-url'), document.baseURI)
		requestURL.searchParams.set('username', username)
		showGenericLoader('OTPHandler')
		fetch(requestURL).then((res) => res.json()).then(function (response) {
			const values = typeof response.values == 'object' && response.values !== null ? response.values : {}
			if (response.success === true) {
				codeTarget = username
				form.find('[data-login-code-sent-message]').text(typeof response.message == 'string' ? response.message : '')
				form.find('[data-login-code-sent-target]').text(text('sent-to').replace('${target}', maskTarget(username)))
				form.find('[data-login-code-sent-expires]').text(text('expires'))
				codeBoxes.val('')
				showCodeStep(2)
				startResendTimer()
				codeBoxes.first().trigger('focus')
			} else if (response.error === 'TOO_MANY_ATTEMPTS') {
				showError(text('code-error-title'), `${response.message || ''} ${waitText(response.retryAfter)}`.trim(), { help: false })
			} else {
				const message = typeof values.message == 'string' && values.message !== '' ? values.message : response.message
				showError(text('code-error-title'), message, resend ? {} : { fields: [{ input: codeUserInput }] })
			}
		}).catch(function () {
			errorMessage(_i18n('loginForm', 'Error'), _i18n('loginForm', 'Ha ocurrido un error inesperado, intente más tarde.'))
		}).finally(function () {
			removeGenericLoader('OTPHandler')
		})
	}

	form.find('[data-login-code-send]').on('click', () => sendCode(false))
	resendButton.on('click', () => sendCode(true))
	form.find('[data-login-code-change]').on('click', function () {
		clearInterval(resendTimer)
		clearError()
		showCodeStep(1)
		codeUserInput.trigger('focus')
	})

	codeBoxes.each(function (index) {
		const box = $(this)
		box.on('input', function () {
			this.value = this.value.replace(/\D/g, '').slice(-1)
			codeField.removeClass('error')
			if (this.value !== '' && index + 1 < codeBoxes.length) {
				codeBoxes.eq(index + 1).trigger('focus')
			}
		})
		box.on('keydown', function (event) {
			if (event.key === 'Backspace' && this.value === '' && index > 0) {
				codeBoxes.eq(index - 1).val('').trigger('focus')
				event.preventDefault()
			} else if (event.key === 'ArrowLeft' && index > 0) {
				codeBoxes.eq(index - 1).trigger('focus')
			} else if (event.key === 'ArrowRight' && index + 1 < codeBoxes.length) {
				codeBoxes.eq(index + 1).trigger('focus')
			}
		})
		box.on('paste', function (event) {
			const pasted = ((event.originalEvent.clipboardData || window.clipboardData).getData('text') || '').replace(/\D/g, '').slice(0, CODE_DIGITS)
			if (pasted === '') {
				return
			}
			event.preventDefault()
			pasted.split('').forEach((digit, position) => codeBoxes.eq(position).val(digit))
			codeBoxes.eq(Math.min(pasted.length, CODE_DIGITS - 1)).trigger('focus')
		})
	})

	function toggleTwoFactor(active = true) {
		twoFactorInput.val('')
		if (active) {
			twoFactorContainer.show(500)
			twoFactorInput.attr('required', true)
		} else {
			twoFactorContainer.hide()
			twoFactorInput.removeAttr('required')
		}
	}

	syncMethod()
}

/**
 * El ojo de la contraseña: un botón de verdad, con el teclado y su estado.
 */
function configPasswordEye() {

	const eye = $('[data-login-eye]')
	const input = $(`#${eye.attr('aria-controls')}`)

	eye.on('click', function () {
		const show = input.attr('type') === 'password'
		input.attr('type', show ? 'text' : 'password')
		eye.find('.icon').toggleClass('slash', show)
		eye.attr('aria-pressed', show ? 'true' : 'false')
		eye.attr('aria-label', eye.attr(show ? 'data-hide-text' : 'data-show-text'))
	})

}
