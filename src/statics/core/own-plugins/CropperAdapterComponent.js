/**
 * @function CropperAdapterComponent
 *  
 * @param {AdapterOptions} configurations  
 */
function CropperAdapterComponent(configurations = {}) {

	/**
	 * @typedef CropperOptions
	 * @property {Number} [aspectRatio=4/4] Define the fixed aspect ratio of the crop box
	 * @property {Boolean} [responsive=true] Re-render the cropper when resizing the window
	 * @property {Boolean} [checkCrossOrigin=false] Check if the current image is a cross-origin image
	 * @property {Boolean} [center=true] Show the center indicator above the crop box
	 * @property {Number} [viewMode=3] Define the view mode of the cropper
	 * @property {Number} [autoCropArea=1] A number between 0 and 1. Define the automatic cropping area size (percentage)
	 * @property {Boolean} [zoomOnTouch=false] Enable to zoom the image by dragging touch
	 * @property {Boolean} [zoomOnWheel=false] Enable to zoom the image by wheeling mouse
	 * @property {Boolean} [toggleDragModeOnDblclick=false] Enable to toggle drag mode between "crop" and "move" when clicking twice on the cropper
	 */
	/**
	 * @typedef AdapterOptions
	 * @property {String} [outputFormat=image/jpeg] Formato de la imagen al exportar
	 * @property {String} [outputFillColor=white] Color de relleno de la imagen al exportar
	 * @property {Number} [outputWidth=400] Ancho de exportación
	 * @property {Number} [minWidth=400] Ancho mínimo de la imagen entrante
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
		viewMode: 3,
		autoCropArea: 1,
		zoomOnTouch: false,
		zoomOnWheel: false,
		toggleDragModeOnDblclick: false,
	}
	/** @type {AdapterOptions} Configuración por defecto de la clase */
	let defaultAdapterOptions = {
		outputFormat: 'image/jpeg',
		outputFillColor: 'white',
		outputWidth: 400,
		minWidth: 400,
		containerSelector: '[cropper-adapter-component]',
		onReadyCropper: (cropper, canvas) => { },
		onInitialize: (cropper, canvas) => { },
		cropperOptions: null,
	}
	/** @type {CropperAdapterComponent} Instancia */ let instance = this
	/** @type {AdapterOptions} Configuraciones de la clase */ let adapterOptions
	/** @type {CropperOptions} Configuraciones de cropper */ let cropperOptions
	/** @type {Cropper} Instancia de cropper */ let cropper

	/** @type {Boolean} Verifica si ya ha sido inicializado copper por primera vez */ let initialized = false
	/** @type {Boolean} Verifica si tiene una imagen */ let hasImage = false
	/** @type {Boolean} Verifica si la imagen cambió */ let wasChanged = false

	/** @type {$} Contenedor del componente */ let container
	/** @type {$} Input file */ let inputFile
	/** @type {$} Canvas */ let canvas
	/** @type {$} Imagen por defecto */ let canvasImage
	/** @type {$} Contenedor de la vista previa al disparar el corte */ let preview
	/** @type {$} Disparador de evento de corte */ let cutTrigger

	prepare(configurations)

	/**
	 * @method crop
	 * @param {Number} [quality=0.7] Calidad de exportación
	 * @param {Number} [oWidth=null] Ancho de exportación
	 * @returns {String} base64
	 */
	this.crop = (quality = 0.7, oWidth = null) => {

		if (!(typeof quality == 'number')) {
			quality = 0.7
		}

		if (!(typeof oWidth == 'number')) {
			oWidth = adapterOptions.outputWidth
		}

		let optionsCroppedCanvas = {
			fillColor: adapterOptions.outputFillColor,
		}

		if (oWidth !== -1) {
			optionsCroppedCanvas.width = oWidth
			optionsCroppedCanvas.minWidth = oWidth
		}

		let cropperCanvas = cropper.getCroppedCanvas(optionsCroppedCanvas)

		let formatsWithQuality = [
			'image/jpeg',
			'image/jpg',
			'image/webp',
		]

		if (formatsWithQuality.indexOf(adapterOptions.outputFormat) !== -1) {
			return cropperCanvas.toDataURL(adapterOptions.outputFormat, quality)
		} else {
			return cropperCanvas.toDataURL(adapterOptions.outputFormat)
		}
	}

	/**
	 * @method getFile
	 * @param {String} [name=image] Nombre del archivo
	 * @param {Number} [quality=0.7] Calidad de exportación
	 * @param {Number} [oWidth=null] Ancho de exportación
	 * @param {String} [extension=null] Extensión del archivo
	 * @returns {File}
	 */
	this.getFile = (name = 'image', quality = 0.7, oWidth = null, extension = null) => {

		extension = typeof extension == 'string' ? extension : adapterOptions.outputFormat.replace('image/', '')

		let util = new UtilPieces()
		let utilFiles = util.file
		let file = utilFiles.dataURLToFile(
			this.crop(
				quality,
				oWidth
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
