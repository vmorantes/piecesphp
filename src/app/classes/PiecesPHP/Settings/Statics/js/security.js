showGenericLoader('security')
window.addEventListener('load', () => {

	/**
	 * @param {FormData} formData 
	 */
	const onSetFormData = function (formData) {
		formData.set('check_aud_on_auth', form.find(`[name="check_aud_on_auth"]`).parent().checkbox('is checked') ? 1 : 0)
		formData.set('hide_app_key_warning', form.find(`[name="hide_app_key_warning"]`).parent().checkbox('is checked') ? 1 : 0)
		return formData
	}
	let form = genericFormHandler(`form.security`, {
		onSetFormData: (formData) => {
			return onSetFormData(formData)
		},
	})

	form.find('.ui.checkbox').checkbox()

	//«Añadir esta IP a la lista»: pone la de la conexión en el área si no está; guardar sigue siendo «Guardar».
	//Si la IP de la conexión es pública (data-remote-public), se confirma antes: puede ser la de quien administra.
	form.find('[data-add-remote-addr]').on('click', function () {
		const button = $(this)
		const add = function () {
			const remoteAddr = form.find('[data-remote-addr]').text().trim()
			const area = form.find('[data-trusted-proxies] textarea')
			const lines = area.val().split(/\r?\n/).map((line) => line.trim()).filter((line) => line.length > 0)
			if (remoteAddr.length > 0 && !lines.includes(remoteAddr)) {
				lines.push(remoteAddr)
				area.val(lines.join('\n'))
			}
		}
		if (!button.is('[data-remote-public]')) {
			add()
			return
		}
		confirmAction({ message: $('<p>').text(button.attr('data-confirm-message') || '').prop('outerHTML') }).then(function (confirmed) {
			if (confirmed === true) {
				add()
			}
		})
	})

	removeGenericLoader('security')

})
