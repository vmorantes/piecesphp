//La prueba del recortador (Views/panel/pages/test-cropper.php): el resultado de cada recorte se avisa, no va a la consola.
const testCropperLoaderName = 'test-cropper-loader'
showGenericLoader(testCropperLoaderName)

window.addEventListener('load', function () {

	const langGroup = 'adminZone'
	const announce = function (file) {
		if (file instanceof Blob) {
			successMessage(_i18n(langGroup, 'Recorte listo: %r bytes.').replace('%r', String(file.size)))
		} else {
			errorMessage(_i18n(langGroup, 'No hay recorte que probar.'))
		}
	}

	const form = $('[test-cropper]')
	const imagenMain = new CropperAdapterComponent({
		containerSelector: '[cropper-image-main]',
		minWidth: 400,
		outputWidth: 400,
		cropperOptions: {
			aspectRatio: 4 / 3,
		},
	})

	form.on('submit', function (e) {
		e.preventDefault()
		const isEdit = form.data('edit') === 'yes'
		announce(isEdit ? imagenMain.getFile(null, null, null, null, true) : imagenMain.getFile())
	})

	const simpleCropperAdapter = new SimpleCropperAdapter(null, {
		aspectRatio: 1 / 1,
		format: 'image/jpeg',
		quality: 0.8,
		fillColor: 'red',
		outputWidth: '350',
	})
	simpleCropperAdapter.onCropped(function (blob) {
		announce(blob)
	})

	//La ventana: el recortador se dibuja la primera vez que se ve, porque oculto no tiene tamaño.
	let firstDrawCropper = true
	const modalCropper = $('.ui.modal.cropper-test').modal({
		onVisible: function () {
			if (firstDrawCropper) {
				simpleCropperAdapterModal.refresh()
				firstDrawCropper = false
			}
		},
	})
	const simpleCropperAdapterModal = new SimpleCropperAdapter('[simple-cropper-modal]', {
		aspectRatio: 1 / 1,
		format: 'image/jpeg',
		quality: 1,
		fillColor: 'red',
		outputWidth: '350',
	})
	simpleCropperAdapterModal.onCropped(function (blob) {
		announce(blob)
	})
	simpleCropperAdapterModal.onCancel(function () {
		modalCropper.modal('hide')
	})
	$('[open-modal]').on('click', function () {
		modalCropper.modal('show')
	})

	removeGenericLoader(testCropperLoaderName)

})
