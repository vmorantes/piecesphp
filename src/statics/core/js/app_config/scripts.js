/// <reference path="../../js/helpers.js" />
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

	const prepare = (entry) => {
		$(entry).find('.ui.dropdown').dropdown()
		$(entry).find('.ui.checkbox').checkbox()
		entry.querySelector('[data-script-remove]').addEventListener('click', (event) => {
			if (window.confirm(event.currentTarget.getAttribute('data-confirm') || '')) {
				entry.remove()
			}
		})
	}

	list.querySelectorAll('[data-script-entry]').forEach(prepare)

	view.querySelector('[data-script-add]').addEventListener('click', () => {
		const entry = template.content.firstElementChild.cloneNode(true)
		list.appendChild(entry)
		prepare(entry)
		entry.querySelector('[data-script-field="label"]').focus()
	})

	form.addEventListener('submit', (event) => {
		event.preventDefault()
		const entries = Array.from(list.querySelectorAll('[data-script-entry]')).map((entry) => {
			const field = (name) => entry.querySelector(`[data-script-field="${name}"]`)
			return {
				label: field('label').value,
				zone: field('zone').value,
				position: field('position').value,
				active: field('active').checked,
				code: field('code').value,
			}
		})
		const formData = new FormData()
		formData.set('entries', JSON.stringify(entries))
		const button = form.querySelector('[data-scripts-save]')
		button.classList.add('loading', 'disabled')
		postRequest(form.getAttribute('action'), formData)
			.done((response) => {
				if (response.success) {
					successMessage(response.name, response.message)
				} else {
					errorMessage(response.name, response.message)
				}
			})
			.fail((jqXHR) => {
				const response = jqXHR.responseJSON || {}
				errorMessage(response.name || _i18n('titles', 'error'), response.message || _i18n('errors', 'unexpected_error_try_later'))
			})
			.always(() => {
				button.classList.remove('loading', 'disabled')
			})
	})

	removeGenericLoader('scripts')

})
