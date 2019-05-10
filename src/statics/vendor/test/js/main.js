NProgress.start()
$(document).ready(function (e) {
	$('[datatable-js]').DataTable().destroy()
	globales.configDataTables.rowReorder = true
	globales.configDataTables.colReorder = true
	let dataTable = $('[datatable-js]').DataTable(globales.configDataTables)

	$('.menu .item').tab()

	$('.button.success').click(function (e) {
		successMessage('Titulo', 'Mensaje')
	})
	$('.button.warning').click(function (e) {
		warningMessage('Titulo', 'Mensaje')
	})
	$('.button.info').click(function (e) {
		infoMessage('Titulo', 'Mensaje')
	})
	$('.button.error').click(function (e) {
		errorMessage('Titulo', 'Mensaje')
	})
	$('.button.link').click(function (e) {
		swal({
			showCloseButton: true,
			type: 'error',
			title: 'Título...',
			text: 'Mensaje error',
			footer: '<a href>Con un enlace</a>',
		})
	})

	$('.alert1').click(function (e) {
		alertify.error('Mensaje del error.', 2, function (e) {
			console.log('Mensaje cerrado')
		})
		alertify.message('Mensaje del message.', 2, function (e) {
			console.log('Mensaje cerrado')
		})
		alertify.notify('Mensaje del notify.', 'clase-custom', 2, function (e) {
			console.log('Mensaje cerrado')
		})
		alertify.success('Mensaje del success.', 2, function (e) {
			console.log('Mensaje cerrado')
		})
		alertify.warning('Mensaje del warning.', 2, function (e) {
			console.log('Mensaje cerrado')
		})
	})

	let form = $('.ui.form.example')
	let fechaInput = form.find("[name='fecha']")
	let horaInput = form.find("[name='hora']")

	horaInput.mask('00:00:00', {
		placeholder: 'HORAS:MINUTOS:SEGUNDOS'
	})

	form.form({
		fields: {
			fecha: {
				identifier: 'fecha',
				rules: [
					{
						type: 'empty',
						prompt: 'Ingresa una fecha.'
					}
				]
			},
			hora: {
				identifier: 'fecha',
				rules: [
					{
						type: 'empty',
						prompt: 'Ingresa un valor'
					}
				]
			}
		},
		onFailure: function (errors, fields) {
			console.log(errors)
			console.log(fields)
		}
	})

	form.submit(function (e) {
		e.preventDefault()
		let isValid = $(this).form('is valid')
		if (!isValid) {
		}
		return false
	})

	let quill = new Quill('.quilljs', {
		theme: 'snow'
	});

	NProgress.done()
})
