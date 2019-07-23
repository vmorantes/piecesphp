$(document).ready(function (e) {
	formImporter('.ui.form[importer-js]')
})


function formImporter(selector) {

	let form = $(selector)

	if (form.length < 1) return

	let url = form.attr('action')

	form.form({
		inline: true,
		fields: {
			archivo: {
				identifier: 'archivo',
				rules: [
					{
						type: 'empty',
						prompt: 'El campo "{name}" es obligatorio.'
					}
				]
			},
		}
	})

	let resultContainer = $('[import-result-js]')
	let modalMessages = resultContainer.find('.ui.modal.messages')
	let contentMessages = modalMessages.find('.content')
	let totalStatistic = resultContainer.find('.statistic .number.total')
	let successStatistic = resultContainer.find('.statistic .number.success')
	let errorsStatistic = resultContainer.find('.statistic .number.errors')
	let detail = resultContainer.find('[view-detail]')

	detail.click(() => {
		modalMessages.modal('show')
	})

	resultContainer.hide()

	form.submit(function (e) {

		e.preventDefault()

		if (form.form('is valid')) {

			resultContainer.hide()

			NProgress.configure({
				parent: selector
			})

			NProgress.start()

			let titleUploading = document.createElement('h3')
			titleUploading.innerHTML = 'Cargando archivo...'
			titleUploading = $(titleUploading)

			form.prepend(titleUploading)

			let formData = new FormData(form[0])
			let req = postRequest(url, formData)

			form.find('.field').addClass('disabled')

			req.done(function (res) {

				if (res.success) {

					let messages = res.messages
					let total = res.total
					let inserted = res.inserted
					let hasSuccess = false
					let hasErrors = false

					let successMessages = $(document.createElement('ul'))
					successMessages.addClass('ui message success')
					let errorMessges = $(document.createElement('ul'))
					errorMessges.addClass('ui message error')

					successMessages.html('<div class="header"></div>')
					errorMessges.html('<div class="header"></div>')

					for (let message of messages) {

						let msg = message.message
						let success = message.success
						let position = message.position

						let li = $(document.createElement('li'))
						let strong = $(document.createElement('strong'))

						strong.html(`Fila ${position}:<br>`)

						li.append(strong)
						li.append(msg)

						if (success) {
							successMessages.find('.header').append(li)
							hasSuccess = true
						} else {
							errorMessges.find('.header').append(li)
							hasErrors = true
						}
					}

					contentMessages.html('')

					if (hasErrors) {
						contentMessages.append(errorMessges)
					}

					if (hasSuccess) {
						contentMessages.append(successMessages)
					}

					totalStatistic.html(total)
					successStatistic.html(inserted)
					errorsStatistic.html(total - inserted)

				} else {
					errorMessage('Error', res.message)
				}


			})

			req.fail((jqXHR) => {
				errorMessage('Error', 'Error desconocido. Contacte con el soporte.')
				console.error(jqXHR)
			})

			req.always(() => {
				resultContainer.show(500)
				NProgress.done()
				titleUploading.remove()
				form.find('.field').removeClass('disabled')
			})
		}
		return false
	})
}
