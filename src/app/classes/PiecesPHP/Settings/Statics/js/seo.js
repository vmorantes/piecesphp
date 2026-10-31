/// <reference path="../../js/helpers.js" />
/// <reference path="../../own-plugins/ContentImageEditor.js" />
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

	const previews = {}

	$('form.seo[lang]').toArray().forEach((element) => {

		const lang = element.getAttribute('lang')
		/** @type {File|null} */
		let openGraphFile = null

		const form = genericFormHandler($(element), {
			onSetFormData: (formData) => {
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
