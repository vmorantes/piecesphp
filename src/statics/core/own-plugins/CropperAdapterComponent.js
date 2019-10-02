
/**
 * @function CropperAdapterComponent
 *  
 * @param {AdapterOptions} configurations  
 */
function CropperAdapterComponent(configurations = {}) {

	/**
	 * @typedef CropperOptions
	 * @property {Number} [aspectRatio=4/4] Proporciones de la imagen
	 * @property {Boolean} [responsive=true]
	 * @property {Boolean} [checkCrossOrigin=false]
	 * @property {Boolean} [center=true]
	 */
	let defaultCropperOptions = {
		aspectRatio: 4 / 4,
		responsive: true,
		checkCrossOrigin: false,
		center: true,
	}
	/**
	 * @typedef AdapterOptions
	 * @property {String} [output=image/jpeg]
	 * @property {String} [fillColor=white]
	 * @property {Number} [minWidth=400]
	 * @property {Number} [outputWidth=400]
	 * @property {String} [containerSelector=[cropper-adapter-component]]
	 * @property {Function} [onReadyCropper]
	 * @property {Function} [onInitialize]
	 * @property {CropperOptions} [cropperOptions]
	 */
	let defaultAdapterOptions = {
		output: 'image/jpeg',
		fillColor: 'white',
		minWidth: 400,
		outputWidth: 400,
		containerSelector: '[cropper-adapter-component]',
		onReadyCropper: (cropper, canvas) => { },
		onInitialize: (cropper, canvas) => { },
		cropperOptions: null,
	}

	/**
	 * @property {CropperAdapterComponent} instance
	 */
	let instance = this

	/**
	 * @property {AdapterOptions} adapterOptions
	 */
	let adapterOptions
	/**
	 * @property {CropperOptions} cropperOptions
	 */
	let cropperOptions

	/**
	 * @property {Boolean} initialized
	 */
	let initialized = false
	/**
	 * @property {$} container
	 */
	let container
	/**
	 * @property {$} inputFile
	 */
	let inputFile
	/**
	 * @property {$} canvas
	 */
	let canvas
	/**
	 * @property {$} canvasImage
	 */
	let canvasImage
	/**
	 * @property {$} preview
	 */
	let preview
	/**
	 * @property {$} cutTrigger
	 */
	let cutTrigger
	/**
	 * @property {String} hasImage
	 */
	let outputFormat
	/**
	 * @property {String} hasImage
	 */
	let fillColor
	/**
	 * @property {Boolean} hasImage
	 */
	let hasImage = false
	/**
	 * @property {Boolean} wasChanged
	 */
	let wasChanged = false
	/**
	 * @property {HTMLCanvasElement} canvasTarget
	 */
	let canvasTarget
	/**
	 * @property {Cropper} cropper
	 */
	let cropper

	init(configurations)

	/**
	 * @function init
	 * @param {AdapterOptions} configurations 
	 */
	function init(configurations = {}) {

		configOptions(configurations)

		container = $(adapterOptions.containerSelector)

		inputFile = container.find('input[type="file"]')
		canvas = container.find('canvas')
		canvasImage = canvas.attr('data-image')
		preview = container.find('[preview]')
		cutTrigger = container.find('[cut]')

		outputFormat = adapterOptions.output
		fillColor = adapterOptions.fillColor

		hasImage = false
		wasChanged = false

		if (!(container.length < 1 || canvas.length < 1)) {

			canvas.css('width', '100%')
			canvas.css('max-width', `${adapterOptions.minWidth}px`)
			canvas.css('min-width', `300px`)

			canvasTarget = canvas.get(0)
			cropper = new Cropper(canvasTarget, cropperOptions)

			canvasTarget.addEventListener('ready', function (e) {

				let isCropperEvent = this.cropper === cropper

				if (isCropperEvent) {

					adapterOptions.onReadyCropper(cropper, canvas)

					if (!initialized) {
						adapterOptions.onInitialize(cropper, canvas)
					}

				}

				initialized = true

			})

			if (canvasImage != null) {
				cropper.replace(canvasImage)
				hasImage = true
			}

			if (!hasImage) {
				cutTrigger.addClass('disabled')
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

		} else {
			alert('Error al configurar CropperAdapterComponent, no está el canvas o el componente.')
		}

	}
	/**
	 * @function init
	 * @param {AdapterOptions} configurations 
	 */
	function configOptions(configurations = {}) {

		//Configuraciones de cropperOptions 
		cropperOptions = processByDefaultValues(defaultCropperOptions, configurations.cropperOptions)

		//Configuraciones de adapterOptions 
		adapterOptions = processByDefaultValues(defaultAdapterOptions, configurations)

	}

	/**
	 * @function processByStructure
	 * @param {Object} defaultValues 
	 * @param {Object} data 
	 * @returns {Object}
	 */
	function processByDefaultValues(defaultValues, data) {

		for (let option in defaultValues) {
			let defaultOption = defaultValues[option]
			if (typeof data[option] == 'undefined') {
				data[option] = defaultOption
			} else {
				if (typeof data[option] != typeof defaultOption) {
					data[option] = defaultOption
				}
			}
		}

		return data

	}

	/**
	 * @param {Number} [quality=0.7]
	 * @param {Number} [outputWidth=null]
	 * @returns {String} base64
	 */
	this.crop = (quality = 0.7, outputWidth = null) => {

		if (!(typeof quality == 'number')) {
			quality = 0.7
		}

		if (!(typeof outputWidth == 'number')) {
			outputWidth = adapterOptions.outputWidth
		}

		let optionsCroppedCanvas = {
			fillColor: fillColor,
		}

		if (outputWidth !== -1) {
			optionsCroppedCanvas.width = outputWidth
		}

		let cropperCanvas = cropper.getCroppedCanvas(optionsCroppedCanvas)

		let formatsWithQuality = [
			'image/jpeg',
			'image/jpg',
			'image/webp',
		]

		if (formatsWithQuality.indexOf(outputFormat) !== -1) {
			return cropperCanvas.toDataURL(outputFormat, quality)
		} else {
			return cropperCanvas.toDataURL(outputFormat)
		}
	}

	/**
	 * @param {Number} [quality=0.7]
	 * @param {Number} [outputWidth=null]
	 * @param {String} [extension=null]
	 * @returns {File}
	 */
	this.getFile = (name = 'image', quality = 0.7, outputWidth = null, extension = null) => {

		extension = typeof extension == 'string' ? extension : outputFormat.replace('image/', '')

		let util = new UtilPieces()
		let utilFiles = util.file
		let file = utilFiles.dataURLToFile(
			this.crop(
				quality,
				outputWidth
			),
			`${name}.${extension}`
		)
		return file
	}

	/**
	 * @returns {Boolean}
	 */
	this.wasChanged = () => {
		return wasChanged
	}

	return this

}
