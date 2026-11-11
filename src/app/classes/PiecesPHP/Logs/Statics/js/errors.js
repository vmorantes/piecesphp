window.addEventListener('load', function () {
	$('[data-logs-error-detail].ui.accordion').accordion()
	$('[data-logs-errors-filters] select.ui.dropdown').dropdown({ fullTextSearch: true })

	//Los marcos de Slim y vendor/ plegados: el enlace despliega el grupo que tiene justo debajo.
	document.addEventListener('click', function (event) {
		const toggle = event.target.closest('[data-logs-fold]')
		if (toggle === null) {
			return
		}
		event.preventDefault()
		const group = toggle.closest('tbody').nextElementSibling
		if (group === null || !group.hasAttribute('data-logs-folded')) {
			return
		}
		group.hidden = !group.hidden
		const icon = toggle.querySelector('.icon')
		if (icon !== null) {
			icon.classList.toggle('right', group.hidden)
			icon.classList.toggle('down', !group.hidden)
		}
	})

	//La referencia, al portapapeles: el tooltip dice «Copiado» 1,5 s y vuelve al suyo; el aviso de siempre se queda.
	document.addEventListener('click', function (event) {
		const button = event.target.closest('[data-logs-copy]')
		if (button === null || navigator.clipboard === undefined) {
			return
		}
		event.preventDefault()
		const reference = button.getAttribute('data-logs-copy')
		navigator.clipboard.writeText(reference).then(function () {
			const tooltip = button.getAttribute('data-tooltip')
			const copied = button.getAttribute('data-copied-tooltip')
			if (copied !== null && copied !== '' && !button.hasAttribute('data-logs-copied')) {
				button.setAttribute('data-logs-copied', '')
				button.setAttribute('data-tooltip', copied)
				setTimeout(function () {
					button.setAttribute('data-tooltip', tooltip)
					button.removeAttribute('data-logs-copied')
				}, 1500)
			}
			successMessage(reference)
		})
	})
})
