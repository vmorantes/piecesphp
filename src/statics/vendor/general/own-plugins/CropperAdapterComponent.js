
/**
 * @function CropperAdapterComponent
 * 
 * @param {AdapterOptions} adapterOptions 
 * @param {CropperOptions} cropperOptions 
 */
function CropperAdapterComponent(adapterOptions = {}, cropperOptions = {}) {

	/**
	 * @typedef AdapterOptions
	 * @property {String} [output=image/jpeg]
	 * @property {String} [fillColor=white]
	 * @property {Number} [minWidth=400]
	 * @property {Number} [outputWidth=400]
	 * @property {String} [containerSelector=[cropper-adapter-component]]
	 */
	/**
	 * @typedef CropperOptions
	 * @property {Number} [aspectRatio=4/4] Proporciones de la imagen
	 * @property {Boolean} [responsive=true]
	 * @property {Boolean} [checkCrossOrigin=false]
	 * @property {Boolean} [center=true]
	 */
	let ignore;

	/**
	 * @property {CropperAdapterComponent} instance
	 */
	let instance = this;

	cropperOptions.aspectRatio =
		typeof cropperOptions.aspectRatio == 'number' ?
			cropperOptions.aspectRatio :
			4 / 4

	cropperOptions.responsive =
		typeof cropperOptions.responsive == 'boolean' ?
			cropperOptions.responsive :
			true

	cropperOptions.checkCrossOrigin =
		typeof cropperOptions.checkCrossOrigin == 'boolean' ?
			cropperOptions.checkCrossOrigin :
			true

	cropperOptions.center =
		typeof cropperOptions.center == 'boolean' ?
			cropperOptions.center :
			true

	adapterOptions.output =
		typeof adapterOptions.output == 'string' ?
			adapterOptions.output :
			'image/jpeg'

	adapterOptions.fillColor =
		typeof adapterOptions.fillColor == 'string' ?
			adapterOptions.fillColor :
			'white'

	adapterOptions.minWidth =
		typeof adapterOptions.minWidth == 'number' ?
			adapterOptions.minWidth :
			400

	adapterOptions.outputWidth =
		typeof adapterOptions.outputWidth == 'number' ?
			adapterOptions.outputWidth :
			adapterOptions.minWidth



	adapterOptions.containerSelector =
		typeof adapterOptions.containerSelector == 'string' ?
			adapterOptions.containerSelector :
			'[cropper-adapter-component]'

	let container = $(adapterOptions.containerSelector)

	let inputFile = container.find('input[type="file"]')
	let canvas = container.find('canvas')
	let canvasImage = canvas.attr('data-image')
	let preview = container.find('[preview]')
	let cutTrigger = container.find('[cut]')

	let outputFormat = adapterOptions.output
	let fillColor = adapterOptions.fillColor

	let hasImage = false
	let wasChanged = false

	if (container.length < 1 || canvas.length < 1) {
		return
	}

	canvas.css('width', '100%')
	canvas.css('max-width', `${adapterOptions.minWidth}px`)
	canvas.css('min-width', `300px`)

	let cropper = new Cropper(canvas.get(0), cropperOptions)

	if (canvasImage != null) {
		cropper.replace(canvasImage)
		hasImage = true
	}

	if (!hasImage) {
		cutTrigger.addClass('disabled')
	}

	/**
	 * @returns {String} base64
	 */
	this.crop = () => {
		return cropper.getCroppedCanvas({
			width:
				adapterOptions.outputWidth > adapterOptions.minWidth ?
					adapterOptions.minWidth :
					adapterOptions.outputWidth,
			fillColor: fillColor,
		}).toDataURL(outputFormat)
	}

	/**
	 * @returns {File}
	 */
	this.getFile = (name = 'image') => {
		return (new UtilPieces()).file.dataURLToFile(this.crop(), `${name}.${outputFormat.replace('image/', '')}`)
	}

	/**
	 * @returns {Boolean}
	 */
	this.wasChanged = () => {
		return wasChanged
	}

	inputFile.on('change', function () {

		let files = this.files

		if (files.length > 0) {

			let file = files[0]
			let tipo = file.type

			if (tipo.match(/^image\//)) {

				let reader = new FileReader()

				reader.readAsDataURL(file)

				reader.onload = function (e) {

					let img = new Image()

					let dataURIImg = e.target.result
					img.src = dataURIImg

					img.onload = function () {

						let inputWidth = img.width
						
						if (inputWidth < adapterOptions.minWidth) {
							errorMessage('Error', `El ancho mínimo de la imagen debe ser: ${adapterOptions.minWidth}px`)
							return
						}

						cropper.replace(dataURIImg)
						hasImage = true
						wasChanged = true
						cutTrigger.removeClass('disabled')

					}

				}

			} else {

				errorMessage('Error', 'Seleccione una imagen, por favor.')

			}

		} else {

			errorMessage('Error', 'No hay imágenes seleccionadas.')

		}

	})

	cutTrigger.click(function (e) {

		e.preventDefault()

		if (!hasImage) return

		let cutImage = new Image()
		cutImage.id = 'image'
		cutImage.src = instance.crop()
		wasChanged = true

		cutImage.onload = () => {
			let title = document.createElement('strong')
			title.innerHTML = 'Vista previa:<br>'
			preview.html('')
			preview.append(title)
			preview.append(cutImage)
			preview.css('text-align', 'center')
			preview.css('margin', '4px auto')
			preview.css('max-width', '300px')
			preview.find('img').css('max-width', '100%')
		}

		return false

	})

}
