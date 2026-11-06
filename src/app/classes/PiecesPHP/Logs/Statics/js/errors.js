window.addEventListener('load', function () {
	$('[data-logs-errors].ui.accordion').accordion({ exclusive: false })
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

	//La referencia, al portapapeles; el clic no abre ni cierra la entrada.
	document.addEventListener('click', function (event) {
		const label = event.target.closest('[data-logs-copy]')
		if (label === null || navigator.clipboard === undefined) {
			return
		}
		event.stopPropagation()
		navigator.clipboard.writeText(label.getAttribute('data-logs-copy')).then(function () {
			successMessage(label.getAttribute('data-logs-copy'))
		})
	}, true)
})
