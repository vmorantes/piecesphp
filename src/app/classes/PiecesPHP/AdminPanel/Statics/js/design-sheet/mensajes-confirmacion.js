window.addEventListener('load', function () {
	//confirmAction() devuelve una promesa: true si se confirma, false si se cancela. Aquí solo se enseña la respuesta.
	$('[example-confirm]').on('click', function () {
		const button = $(this)
		confirmAction({
			title: button.data('title'),
			message: button.data('message'),
			confirmText: button.data('confirm-text'),
			cancelText: button.data('cancel-text'),
			confirmClass: 'red',
		}).then((confirmed) => {
			$('[example-confirm-answer]').text(confirmed ? '→ true' : '→ false')
		})
	})
})
