/// <reference path="../../js/helpers.js" />
showGenericLoader('backups')
window.addEventListener('load', () => {

	const view = document.querySelector('[data-backups-view]')
	if (view === null) {
		removeGenericLoader('backups')
		return
	}
	const form = view.querySelector('[data-backups-form]')

	$(form).find('.ui.checkbox').checkbox()

	const field = (name) => form.querySelector(`[data-backup-field="${name}"]`)
	const numbers = ['interval_minutes', 'keep_recent', 'keep_daily', 'keep_weekly', 'keep_monthly']

	form.addEventListener('submit', (event) => {
		event.preventDefault()

		const policy = {
			enabled: field('enabled').checked,
			rotate: field('rotate').checked,
			data_excluded_tables: Array.from(form.querySelectorAll('[data-backup-table]:checked')).map((input) => input.value),
		}
		//Los números van como enteros: la normalización del servidor rechaza un decimal o un texto.
		for (const name of numbers) {
			policy[name] = Number.parseInt(field(name).value, 10)
		}

		const formData = new FormData()
		formData.set('policy', JSON.stringify(policy))
		const button = form.querySelector('[data-backups-save]')
		button.classList.add('loading', 'disabled')
		postRequest(form.getAttribute('action'), formData)
			.done((response) => {
				if (response.success) {
					successMessage(response.name, response.message)
					//Lo que la conservación haría cambia con la política: se relee del servidor.
					window.setTimeout(() => window.location.reload(), 1200)
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

	removeGenericLoader('backups')

})
