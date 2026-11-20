showGenericLoader('logos-favicon')
window.addEventListener('load', () => {

	new ContentImageEditor()

	//La vista previa (H416): cada sitio, en claro y oscuro, con lo guardado. Al guardar algo se vuelve a pedir a la misma
	//ruta (`?preview=yes`): al momento si está a la vista y, si no, al volver a su pestaña.
	const brandView = document.querySelector('section.logos-favicons-view')
	const previewsBox = brandView?.querySelector('[data-brand-previews]') ?? null
	const onPreviewReady = (frame, doc) => {
		//El fondo del acceso lo pone login.js al azar: aquí, el primero, siempre el mismo.
		const brand = frame.dataset.previewAccess === 'yes' ? doc.querySelector('[bg-js]') : null
		if (brand !== null) {
			let backgrounds = []
			try {
				backgrounds = JSON.parse(atob(brand.getAttribute('bg-js') || ''))
			} catch (error) {
				backgrounds = []
			}
			if (Array.isArray(backgrounds) && typeof backgrounds[0] === 'string' && backgrounds[0] !== '') {
				brand.classList.add('with-image')
				brand.style.backgroundImage = `url(${JSON.stringify(new URL(backgrounds[0], doc.baseURI).href)})`
			}
		}
	}
	const previews = previewsBox !== null ? pcsThemePreviews(previewsBox.querySelectorAll('iframe[data-brand-preview]'), { inert: true, onReady: onPreviewReady }) : null
	let previewsStale = false
	let previewsVisible = false
	//La petición en curso: un guardado nuevo la cancela, o una respuesta vieja pisaría a la nueva.
	let previewsRequest = null
	const refreshPreviews = () => {
		previewsStale = false
		if (previewsRequest !== null) {
			previewsRequest.abort()
		}
		const request = new AbortController()
		previewsRequest = request
		const url = new URL(previewsBox.dataset.previewUrl, document.baseURI)
		fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: request.signal }).then((response) => response.ok && !response.redirected ? response.text() : Promise.reject(response.status)).then((html) => {
			//Solo la vista previa: con la sesión caducada llega otra página (el acceso), y esa no se pinta.
			const received = document.createElement('template')
			received.innerHTML = html
			if (received.content.querySelector('[data-brand-preview-site]') === null || received.content.querySelector('form') !== null) {
				return Promise.reject(new Error('preview'))
			}
			previewsBox.replaceChildren(received.content)
			previews.add(previewsBox.querySelectorAll('iframe[data-brand-preview]'))
		}).catch(() => {
			//Cancelada por otra, no queda vieja: la otra la trae.
			if (request.signal.aborted) {
				return
			}
			//La vieja se queda: se vuelve a intentar al volver a la pestaña.
			previewsStale = true
		}).finally(() => {
			if (previewsRequest === request) {
				previewsRequest = null
			}
		})
	}
	const markPreviewsStale = () => {
		if (previews === null) {
			return
		}
		if (previewsVisible) {
			refreshPreviews()
		} else {
			previewsStale = true
		}
	}
	if (brandView !== null) {
		//En captura: el evento de la tarjeta no burbujea, y sale solo cuando el servidor ya guardó.
		brandView.addEventListener('image-card-replaced', markPreviewsStale, true)
		pcsSettingsTabs($(brandView).find('[data-brand-images-tabs] [data-tab]'), 'pcs-brand-images-tab', (tabName) => {
			previewsVisible = tabName === 'preview'
			if (previewsVisible && previewsStale) {
				refreshPreviews()
			} else if (previewsVisible) {
				previews?.fitAll()
			}
		})
	}

	//El texto sobre el patrocinador: un campo por idioma activo, en un solo formulario y sin recorte.
	genericFormHandler(`form[partners-text-form]`, {
		onSuccess: () => markPreviewsStale(),
	})

	//«En modo oscuro» se guarda al cambiarlo; la vista oscura y su «Descargar», lo de SettingsController::brandImageDarkView().
	const action = document.querySelector('section.logos-favicons-view')?.dataset.action
	document.querySelectorAll('[data-brand-dark-mode]').forEach((select) => {
		const card = select.closest('article[data-image-card]')
		const preview = card.querySelector('[data-brand-dark-preview]')
		const previewImage = preview.querySelector('.checkered.image img')
		const previewEdit = preview.querySelector('[data-image-card-edit]')
		const upload = preview.querySelector('[data-brand-dark-upload]')
		const hint = card.querySelector('[data-brand-dark-invert-hint]')
		const darkEmpty = preview.querySelector('[data-brand-dark-empty]')
		//Solo la imagen del botón: el damero vacío (`[data-image-card-empty]`) también lleva un <img>, transparente.
		const lightSource = () => Array.from(card.querySelectorAll('.checkered.image [data-image-card-edit] img')).find((image) => image.closest('[data-image-card]') === card)?.getAttribute('src') ?? ''
		const reflect = () => {
			const mode = select.value
			const shown = mode === 'image' && preview.dataset.darkImage === '' ? preview.dataset.darkFallbackMode : mode
			const source = shown === 'image' ? preview.dataset.darkImage : lightSource()
			if (source !== '') {
				previewImage.src = source
			}
			previewImage.hidden = source === ''
			if (darkEmpty !== null) {
				darkEmpty.hidden = source !== ''
			}
			previewImage.classList.toggle('content-image-card-dark-invert', shown === 'invert')
			const download = Array.from(preview.querySelectorAll('[data-image-card-download]')).find((link) => link.closest('[data-image-card]') === preview)
			if (download !== undefined && source !== '') {
				download.href = source
			}
			upload.hidden = mode !== 'image'
			previewEdit.disabled = mode !== 'image'
			hint.hidden = mode !== 'invert'
		}
		const save = () => {
			select.dataset.saved = 'yes'
			const formData = new FormData()
			formData.set('darkMode', select.dataset.brandDarkMode)
			formData.set('mode', select.value)
			postRequest(action, formData).done((response) => {
				if (!response.success) {
					errorMessage(response.name, response.message)
					return
				}
				markPreviewsStale()
			}).fail(() => errorMessage('', _i18n('errors', 'unexpected_error')))
		}
		//Sin modo guardado manda la imagen: una propia toma el que le corresponde sin la de fábrica.
		card.addEventListener('image-card-replaced', () => {
			//La oscura de fábrica solo acompaña a la clara de fábrica.
			if (preview.dataset.darkImageFactory === 'yes') {
				preview.dataset.darkImage = ''
				preview.dataset.darkImageFactory = 'no'
			}
			//Y la vuelta a invertir de «Otra imagen» sin oscura, también: la clara ya no es la de fábrica.
			preview.dataset.darkFallbackMode = 'same'
			if (select.dataset.saved !== 'yes') {
				$(select).dropdown('set selected', select.dataset.onReplace, true)
			}
			reflect()
		})
		//Subir una oscura es elegir «Otra imagen»: el servidor ya guardó el modo en la misma petición.
		preview.addEventListener('image-card-replaced', () => {
			preview.dataset.darkImage = previewImage.getAttribute('src') ?? ''
			preview.dataset.darkImageFactory = 'no'
			select.dataset.saved = 'yes'
			if (select.value !== 'image') {
				$(select).dropdown('set selected', 'image', true)
			}
			reflect()
		})
		select.addEventListener('brand-dark-refresh', reflect)
		$(select).dropdown({
			onChange: () => {
				reflect()
				save()
			},
		})
	})

	//«Qué enseña el cargador»: un interruptor por capa, guardado al cambiarlo; si el servidor no lo guarda, vuelve a como estaba.
	const view = document.querySelector('section.logos-favicons-view')
	view?.querySelectorAll('[data-loader-layer]').forEach((input) => {
		const toggle = $(input).parent()
		toggle.checkbox({
			onChange: () => {
				const enabled = input.checked
				const formData = new FormData()
				formData.set('loaderLayer', input.dataset.loaderLayer)
				formData.set('enabled', enabled ? '1' : '0')
				toggle.addClass('disabled')
				const revert = () => toggle.checkbox(enabled ? 'set unchecked' : 'set checked')
				postRequest(view.dataset.action, formData).done((response) => {
					if (!response.success) {
						revert()
						errorMessage(response.name, response.message)
						return
					}
					markPreviewsStale()
					view.dispatchEvent(new Event('brand-loader-changed'))
				}).fail(() => {
					revert()
					errorMessage(view.getAttribute('data-text-error') ?? '', '')
				}).always(() => toggle.removeClass('disabled'))
			},
		})
	})

	//El área de pruebas de la pestaña «Cargador» (H417): el cargador de verdad, unos segundos, con los data-loader-* GUARDADOS
	//(se piden al lanzar: el <html> es de cuando se abrió la página) puestos en el <html> solo mientras dura.
	const playground = document.querySelector('[data-loader-playground]')
	if (playground !== null && view !== null) {
		let texts = {}
		try {
			texts = JSON.parse(playground.dataset.texts ?? '{}')
		} catch (error) {
			texts = {}
		}
		const root = document.documentElement
		const screenButton = playground.querySelector('[data-loader-test="screen"]')
		const noScreenButton = playground.querySelector('[data-loader-test="no-screen"]')
		const screenMissing = playground.querySelector('[data-loader-screen-missing]')
		const screenOff = playground.querySelector('[data-loader-screen-off]')
		const fetchSaved = () => fetch(new URL(playground.dataset.loaderUrl, document.baseURI), { credentials: 'same-origin', cache: 'no-store' })
			.then((response) => response.ok ? response.json() : Promise.reject(response.status))
			.then((data) => (data !== null && typeof data.attributes === 'object' && !Array.isArray(data.attributes)) ? data : Promise.reject(new Error('attributes')))
		//Solo hay pantalla que enseñar con su interruptor encendido y una en disco (`screenAvailable`, del servidor): si no,
		//«Con la pantalla de carga» deshabilitado con su motivo (apagada o sin ninguna) y «Sin la pantalla de carga» oculto.
		const reflectScreen = (data) => {
			const hasScreen = data.screenAvailable === true
			const motive = data.attributes['data-loader-screen-enabled'] === '0' ? screenOff : screenMissing
			screenButton.disabled = !hasScreen
			if (hasScreen) {
				screenButton.removeAttribute('aria-describedby')
			} else {
				screenButton.setAttribute('aria-describedby', motive.id)
			}
			screenMissing.hidden = hasScreen || motive !== screenMissing
			screenOff.hidden = hasScreen || motive !== screenOff
			if (noScreenButton !== null) {
				noScreenButton.hidden = !hasScreen
			}
		}
		const refreshScreen = () => fetchSaved().then(reflectScreen).catch(() => { })
		view.addEventListener('image-card-replaced', refreshScreen, true)
		view.addEventListener('brand-loader-changed', refreshScreen)

		let active = null
		const close = () => {
			if (active === null) {
				return
			}
			const { loader, timer, keydown, saved, theme, trigger } = active
			active = null
			clearInterval(timer)
			clearTimeout(timer)
			document.removeEventListener('keydown', keydown, true)
			loader.remove()
			//El <html> vuelve a como estaba: los data-loader-* de la página y el modo del panel.
			Array.from(root.attributes).filter((attribute) => attribute.name.startsWith('data-loader-')).forEach((attribute) => root.removeAttribute(attribute.name))
			Object.entries(saved).forEach(([name, value]) => root.setAttribute(name, value))
			if (theme === null) {
				root.removeAttribute('data-theme')
			} else {
				root.setAttribute('data-theme', theme)
			}
			trigger.focus()
		}
		const effectiveTheme = () => {
			const theme = root.getAttribute('data-theme')
			if (theme === 'light' || theme === 'dark') {
				return theme
			}
			return typeof window.matchMedia === 'function' && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
		}
		const launch = (test, trigger, attributes) => {
			close()
			const theme = root.getAttribute('data-theme')
			const saved = {}
			Array.from(root.attributes).filter((attribute) => attribute.name.startsWith('data-loader-')).forEach((attribute) => {
				saved[attribute.name] = attribute.value
			})
			const values = { ...attributes }
			//«El de hoy» es el cargador real, con la pantalla si la hay, y «Con la pantalla de carga» la enseña; los demás, sin ella.
			if (test !== 'screen' && test !== 'current') {
				['screen', 'screen-mobile'].forEach((name) => {
					values[`data-loader-${name}`] = ''
					values[`data-loader-${name}-dark`] = ''
				})
			}
			//«Todo apagado» son los tres interruptores de imagen apagados, solo en el <html> y mientras dura: lo guardado no cambia.
			if (test === 'off') {
				['animation', 'logo', 'screen'].forEach((name) => {
					values[`data-loader-${name}-enabled`] = '0'
				})
			}
			Object.entries(values).forEach(([name, value]) => root.setAttribute(name, typeof value === 'string' ? value : ''))
			const withMessage = test === 'message' || test === 'message-percent' || test === 'screen'
			const withPercent = test === 'percent' || test === 'message-percent' || test === 'screen'
			const loader = GenericLoaderElement.show('loader-playground', null, withPercent, withMessage ? { textMessage: texts.message ?? '' } : {})
			//«Cerrar» y el modo, en la caja del cargador: encima de la pantalla, con los botones del tema.
			const controls = document.createElement('div')
			controls.className = 'ui centered flex'
			const closeButton = document.createElement('button')
			closeButton.type = 'button'
			closeButton.className = 'ui button'
			closeButton.textContent = texts.close ?? ''
			closeButton.addEventListener('click', close)
			const themeButton = document.createElement('button')
			themeButton.type = 'button'
			themeButton.className = 'ui basic button'
			const paintTheme = () => {
				themeButton.textContent = effectiveTheme() === 'dark' ? (texts.light ?? '') : (texts.dark ?? '')
			}
			themeButton.addEventListener('click', () => {
				//Solo el atributo, como el selector de modo al aplicarse: la preferencia del usuario no se guarda.
				root.setAttribute('data-theme', effectiveTheme() === 'dark' ? 'light' : 'dark')
				paintTheme()
			})
			paintTheme()
			controls.append(themeButton, closeButton)
			loader.querySelector('.ui-pcs-box')?.appendChild(controls)
			const keydown = (event) => {
				if (event.key === 'Escape') {
					event.preventDefault()
					close()
				}
			}
			document.addEventListener('keydown', keydown, true)
			let percent = 0
			//Con porcentaje, de 0 a 100 en unos 8 s; sin él, 8 s. Al 100 se cierra con la limpieza (no con el del cargador).
			const timer = withPercent ? setInterval(() => {
				percent += 5
				if (percent >= 100) {
					close()
				} else {
					loader.updatePercent(percent)
				}
			}, 400) : setTimeout(close, 8000)
			active = { loader, timer, keydown, saved, theme, trigger }
			closeButton.focus()
		}
		playground.querySelectorAll('[data-loader-test]').forEach((button) => {
			button.addEventListener('click', () => {
				const current = {}
				Array.from(root.attributes).filter((attribute) => attribute.name.startsWith('data-loader-')).forEach((attribute) => {
					current[attribute.name] = attribute.value
				})
				//Sin respuesta del servidor, con los del <html>: lo guardado cuando se abrió la página.
				fetchSaved().then((data) => {
					reflectScreen(data)
					if ((button.dataset.loaderTest === 'screen' || button.dataset.loaderTest === 'no-screen') && (button.disabled || button.hidden)) {
						return
					}
					launch(button.dataset.loaderTest, button, data.attributes)
				}).catch(() => launch(button.dataset.loaderTest, button, current))
			})
		})
	}

	removeGenericLoader('logos-favicon')

})
