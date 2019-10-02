
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
	/**
	 * @typedef AdapterOptions
	 * @property {String} [output=image/jpeg]
	 * @property {String} [outputFillColor=white]
	 * @property {Number} [minWidth=400]
	 * @property {Number} [outputWidth=400]
	 * @property {String} [containerSelector=[cropper-adapter-component]]
	 * @property {function(Cropper, HTMLCanvasElement)} [onReadyCropper]
	 * @property {function(Cropper, HTMLCanvasElement)} [onInitialize]
	 * @property {CropperOptions} [cropperOptions]
	 */

	/** @type {CropperOptions} Configuración por defecto de cropper */
	let defaultCropperOptions = {
		aspectRatio: 4 / 4,
		responsive: true,
		checkCrossOrigin: false,
		center: true,
	}
	/** @type {AdapterOptions} Configuración por defecto de la clase */
	let defaultAdapterOptions = {
		output: 'image/jpeg',
		outputFillColor: 'white',
		minWidth: 400,
		outputWidth: 400,
		containerSelector: '[cropper-adapter-component]',
		onReadyCropper: (cropper, canvas) => { },
		onInitialize: (cropper, canvas) => { },
		cropperOptions: null,
	}
	/** @type {CropperAdapterComponent} Instancia */ let instance = this
	/** @type {AdapterOptions} Configuraciones de la clase*/ let adapterOptions
	/** @type {CropperOptions} Configuraciones de cropper*/ let cropperOptions
	/** @type {Boolean} Verifica si ya ha sido inicializado copper por primera vez*/ let initialized = false
	/** @type {$} Contenedor del componente*/ let container
	/** @type {$} Input file*/ let inputFile
	/** @type {$} Canvas*/ let canvas
	/** @type {$} Imagen por defecto*/ let canvasImage
	/** @type {$} Contenedor de la vista previa al disparar el corte*/ let preview
	/** @type {$} Disparador de evento de corte*/ let cutTrigger
	/** @type {String} Formato de la imagen al exportar*/ let outputFormat
	/** @type {String} Color de relleno de la imagen al exportar*/ let outputFillColor
	/** @type {Boolean} Verifica si tiene una imagen*/ let hasImage = false
	/** @type {Boolean} Verifica si la imagen cambió*/ let wasChanged = false
	/** @type {Cropper} Instancia de cropper*/ let cropper

	prepare(configurations)

	/**
	 * @method crop
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
			fillColor: outputFillColor,
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
	 * @method getFile
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
	 * @method wasChanged
	 * @returns {Boolean}
	 */
	this.wasChanged = () => {
		return wasChanged
	}

	/**
	 * @function prepare
	 * @param {AdapterOptions} configurations 
	 */
	function prepare(configurations = {}) {

		configOptions(configurations)

		if (!(container.length < 1 || canvas.length < 1)) {

			canvas.css('width', '100%')
			canvas.css('max-width', `${adapterOptions.minWidth}px`)
			canvas.css('min-width', `300px`)

			let canvasTarget = canvas.get(0)
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
	 * @function configOptions
	 * @param {AdapterOptions} configurations 
	 */
	function configOptions(configurations = {}) {

		//Configuraciones de cropperOptions 
		cropperOptions = processByDefaultValues(defaultCropperOptions, configurations.cropperOptions)

		//Configuraciones de adapterOptions 
		adapterOptions = processByDefaultValues(defaultAdapterOptions, configurations)


		//Establecer valores
		container = $(adapterOptions.containerSelector)

		inputFile = container.find('input[type="file"]')
		canvas = container.find('canvas')
		canvasImage = canvas.attr('data-image')
		preview = container.find('[preview]')
		cutTrigger = container.find('[cut]')

		outputFormat = adapterOptions.output
		outputFillColor = adapterOptions.outputFillColor

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

	return this

}
