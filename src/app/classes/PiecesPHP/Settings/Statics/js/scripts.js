showGenericLoader('scripts')
window.addEventListener('load', () => {

	const view = document.querySelector('[data-scripts-view]')
	if (view === null) {
		removeGenericLoader('scripts')
		return
	}
	const form = view.querySelector('[data-scripts-form]')
	const list = view.querySelector('[data-scripts-list]')
	const template = view.querySelector('[data-script-template]')

	const field = (entry, name) => entry.querySelector(`[data-script-field="${name}"]`)

	//La cabecera del bloque se lee sin abrir sus campos: el nombre, el estado y dónde va.
	const sync = (entry) => {
		const title = entry.querySelector('[data-script-title]')
		const zone = field(entry, 'zone')
		const position = field(entry, 'position')
		title.textContent = field(entry, 'label').value.trim() || title.getAttribute('data-script-untitled')
		entry.querySelector('[data-script-state="on"]').hidden = !field(entry, 'active').checked
		entry.querySelector('[data-script-state="off"]').hidden = field(entry, 'active').checked
		entry.querySelector('[data-script-summary]').textContent = `${zone.selectedOptions[0].text} · ${position.selectedOptions[0].text}`
	}

	const prepare = (entry) => {
		$(entry).find('.ui.dropdown').dropdown()
		$(entry).find('.ui.checkbox').checkbox()
		entry.addEventListener('input', () => sync(entry))
		entry.addEventListener('change', () => sync(entry))
		entry.querySelector('[data-script-remove]').addEventListener('click', (event) => {
			confirmAction({ message: event.currentTarget.getAttribute('data-confirm') || '', confirmClass: 'red' }).then((confirmed) => {
				if (confirmed) {
					entry.remove()
				}
			})
		})
	}

	//Se repite al añadir: genericFormHandler escucha `invalid` en los campos que hay al llamarlo.
	const bindForm = () => genericFormHandler($(form), {
		ignoreRedirection: true,
		ignoreReload: true,
		onSetFormData: (formData) => {
			const entries = Array.from(list.querySelectorAll('[data-script-entry]')).map((entry) => ({
				label: field(entry, 'label').value,
				zone: field(entry, 'zone').value,
				position: field(entry, 'position').value,
				active: field(entry, 'active').checked,
				code: field(entry, 'code').value,
			}))
			formData.set('entries', JSON.stringify(entries))
			return formData
		},
	})

	list.querySelectorAll('[data-script-entry]').forEach(prepare)
	bindForm()

	let added = 0
	view.querySelector('[data-script-add]').addEventListener('click', () => {
		const entry = template.content.firstElementChild.cloneNode(true)
		//La plantilla lleva un sufijo fijo: cada bloque nuevo necesita sus propios id.
		added++
		entry.querySelectorAll('[id], [for], [aria-labelledby]').forEach((element) => {
			['id', 'for', 'aria-labelledby'].filter((name) => element.hasAttribute(name)).forEach((name) => {
				element.setAttribute(name, element.getAttribute(name).replace(/-new$/, `-new-${added}`))
			})
		})
		entry.setAttribute('aria-labelledby', entry.getAttribute('aria-labelledby').replace(/-new$/, `-new-${added}`))
		list.appendChild(entry)
		prepare(entry)
		bindForm()
		field(entry, 'label').focus()
	})

	removeGenericLoader('scripts')

})
