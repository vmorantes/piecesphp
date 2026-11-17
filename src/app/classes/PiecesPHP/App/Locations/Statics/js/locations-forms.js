window.addEventListener('load', function () {

	//La región de un país: se puede escribir una que no esté en la lista.
	if ($('.ui.dropdown.regions').length > 0) {
		configFomanticDropdown('.ui.dropdown.regions', {
			allowAdditions: true,
		})
	}

	//Eliminar un punto: con confirmación, y la respuesta de la acción decide (los textos los trae el botón).
	document.querySelectorAll('[delete-point-button]').forEach(function (button) {
		button.addEventListener('click', function (e) {
			e.preventDefault()
			const data = e.currentTarget.dataset
			confirmAction({
				title: data.title,
				message: data.message,
				confirmText: data.confirm,
				cancelText: data.cancel,
				confirmClass: 'red',
			}).then(function (confirmed) {
				if (!confirmed) {
					return
				}
				showGenericLoader('ELIMINAR_PUNTO')
				postRequest(data.route).then(function (res) {
					if (res.success) {
						successMessage(res.name, res.message)
						setTimeout(function () {
							if (res.values.redirect) {
								window.location.href = res.values.redirect_to
							}
						}, 1000)
					} else {
						errorMessage(res.name, res.message)
					}
				}).always(function () {
					removeGenericLoader('ELIMINAR_PUNTO')
				})
			})
		})
	})

})
