showGenericLoader('seo')
window.addEventListener('load', () => {

	const TOPBAR_OFFSET = 84
	const stacked = window.matchMedia('(max-width: 991.98px)')
	const editor = new ContentImageEditor()

	/**
	 * La vista previa de un idioma: su texto, por escrito, y su sitio. Apilada va tras Open Graph y no se fija.
	 * @param {HTMLFormElement} form
	 */
	const previewOf = function (form) {
		const $form = $(form)
		const $preview = $form.find('[data-seo-preview]')
		const $aside = $form.find('[data-seo-aside]')
		const $openGraph = $form.find('[data-seo-open-graph]')
		const value = (selector) => $form.find(selector).val()
		let stuck = false

		const updateTexts = function () {
			$form.find('[data-seo-preview-title]').text(value('[data-seo-share-title]') || value('[data-seo-title]'))
			$form.find('[data-seo-preview-description]').text(value('[data-seo-share-description]') || value('[data-seo-description]'))
		}

		const updateCounters = function () {
			$form.find('[data-seo-counter]').each((_, counter) => {
				const length = document.getElementById(counter.dataset.seoCounter).value.length
				const max = Number(counter.dataset.max)
				counter.textContent = `${length} / ${max}`
				$(counter).toggleClass('ui red text', length > max)
			})
		}

		//`ui sticky` en una pestaña oculta mide cero y se queja: solo vive en la visible. Llamar a 'destroy' sin haberlo
		//iniciado lo iniciaría.
		const place = function () {
			const sticks = !stacked.matches && $form.is(':visible')
			if (stuck && !sticks) {
				$preview.sticky('destroy').removeClass('fixed bound top bottom')
				stuck = false
			}
			if (stacked.matches) {
				$preview.insertAfter($openGraph)
			} else {
				$aside.append($preview)
			}
			if (sticks && stuck) {
				$preview.sticky('refresh')
			} else if (sticks) {
				$preview.sticky({
					context: $form.find('[data-seo-grid]'),
					offset: TOPBAR_OFFSET,
				})
				stuck = true
			}
		}

		$form.on('input', 'input, textarea', () => {
			updateTexts()
			updateCounters()
		})
		updateTexts()
		updateCounters()

		return { place, $form }
	}

	/**
	 * «Usar el título/la descripción del sitio»: marcada, el campo de compartir se desactiva y enseña el del sitio, en
	 * vivo; al guardar va VACÍO, que en MetaTags es «el del sitio». Desmarcada, se edita desde el valor del sitio.
	 * Y los de solo lectura que reflejan otro campo: og:site_name (el Título) y meta author (el Propietario).
	 * @param {HTMLFormElement} form
	 * @returns {{emptyNames: function(): string[]}}
	 */
	const siteValuesOf = function (form) {
		const $form = $(form)
		const pairs = {
			title: { source: $form.find('[data-seo-title]'), target: $form.find('[data-seo-share-title]') },
			description: { source: $form.find('[data-seo-description]'), target: $form.find('[data-seo-share-description]') },
		}
		const boxes = $form.find('[data-seo-use-site]')
		const sync = function () {
			boxes.each((_, box) => {
				const pair = pairs[box.getAttribute('data-seo-use-site')]
				if (pair !== undefined && box.checked) {
					pair.target.val(pair.source.val())
				}
			})
			$form.find('[data-seo-site-name]').val($form.find('[data-seo-title]').val())
			$form.find('[data-seo-author]').val($form.find('[data-seo-owner]').val())
		}
		boxes.each((_, box) => {
			const pair = pairs[box.getAttribute('data-seo-use-site')]
			$(box).closest('.ui.checkbox').checkbox({
				onChange: function () {
					if (pair !== undefined) {
						pair.target.prop('disabled', box.checked)
						//Al desmarcar, el valor del sitio queda como punto de partida.
						pair.target.val(pair.source.val())
						pair.target.trigger('input')
					}
				},
			})
		})
		$form.on('input', '[data-seo-title], [data-seo-description], [data-seo-owner]', sync)
		sync()
		return {
			emptyNames: () => boxes.toArray().filter((box) => box.checked).map((box) => {
				const pair = pairs[box.getAttribute('data-seo-use-site')]
				return pair !== undefined ? String(pair.target.attr('name') || '') : ''
			}).filter((name) => name !== ''),
		}
	}

	const previews = {}

	$('form.seo[lang]').toArray().forEach((element) => {

		const lang = element.getAttribute('lang')
		/** @type {File|null} */
		let openGraphFile = null
		const siteValues = siteValuesOf(element)

		const form = genericFormHandler($(element), {
			onSetFormData: (formData) => {
				//Un campo desactivado no viaja: el de compartir con su casilla marcada se manda, y vacío.
				for (const name of siteValues.emptyNames()) {
					formData.set(name, '')
				}
				//Sin archivo aplicado no se manda nada: el servidor conserva la imagen actual.
				if (openGraphFile !== null) {
					formData.set(`open-graph`, openGraphFile)
				}
				return formData
			},
			onSuccess: () => location.reload(),
		})

		form.find('.ui.dropdown.keywords').dropdown({
			allowAdditions: true,
		})

		editor.onApply(`seo-open-graph-${lang}`, (file, url) => {
			openGraphFile = file
			const $holder = form.find('[data-seo-preview-image]').empty()
			$('<img alt="">').attr('src', url).appendTo($holder)
			form.trigger('submit')
		})

		previews[lang] = previewOf(element)
	})

	const placeAll = () => Object.values(previews).forEach((preview) => preview.place())

	//Acotado a esta vista: `.menu .item` sin más tomaría también el menú lateral.
	$('[data-seo-tabs] .item').tab({
		onVisible: () => placeAll(),
	})

	stacked.addEventListener('change', placeAll)
	placeAll()

	removeGenericLoader('seo')

})
