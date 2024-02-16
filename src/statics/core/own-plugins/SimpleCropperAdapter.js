/**
 * @function SimpleCropperAdapter
 *  
 * @param {String} [componentSelector=[simple-cropper]]
 * @param {CropperOptions} options
 */
function SimpleCropperAdapter(componentSelector = null, options = {},) {

	const LANG_GROUP = 'simple-cropper'

	SimpleCropperAdapter.registerDynamicMessages(LANG_GROUP)

	const instance = this
	let initialize = false
	let extensionsByMime = {
		'image/jpeg': 'jpg',
		'image/jpg': 'jpg',
		'image/webp': 'webp',
		'image/png': 'png',
	}
	let formatsWithQuality = [
		'image/jpeg',
		'image/jpg',
		'image/webp',
	]
	let onCroppedCallback = () => { }
	let onCancelCallback = () => { }
	let onInitCallback = () => { }

	/**
	 * @typedef CropperOptions
	 * @property {Number} [format=image/jpeg] Formato de salida
	 * @property {Number} [quality=0.7] Calidad de salida
	 * @property {Number} [fillColor=white] Color de fondo
	 * @property {Number} [outputWidth=300] Tamaño de salida
	 * @property {Number} [aspectRatio=4/4] Relación de aspecto
	 * @property {Boolean} [responsive=true] Sensible a redimensión
	 * @property {Boolean} [checkCrossOrigin=false] Revisa si la imagen es de otro dominio
	 * @property {Boolean} [center=true] Centra el cortador
	 * @property {Number} [viewMode=2] Tipo de vista
	 * @property {Number} [autoCropArea=1] Area de corte automática
	 * @property {Boolean} [zoomOnTouch=false] Activa el zoom con touch
	 * @property {Boolean} [zoomOnWheel=false] Activa el zoom con rueda del mouse
	 * @property {Boolean} [toggleDragModeOnDblclick=true] Cambian entre modo mover o modo cortar al doble clic
	 * @property {Boolean} [cropBoxResizable=true] Define si el recuadro puede variar de tamaño
	 * @property {Boolean} [movable=true] Define si puede moverse
	 */

	/**
	 * @param {?String} format 
	 * @param {?Number} quality 
	 * @param {?String} fillColor 
	 * @param {?Number} outputWidth 
	 * @returns {Promise}
	 */
	this.crop = function (format = null, quality = null, fillColor = null, outputWidth = null) {

		if (!initialize) {
			return false
		}

		showGenericLoader('SimpleCropperAdapter-crop')

		return new Promise(function (resolve) {

			let formatsValid = [
				'image/jpeg',
				'image/jpg',
				'image/webp',
				'image/png',
			]

			format = format !== null && formatsValid.indexOf(format) !== -1 ? format : options.format
			quality = quality !== null ? quality : options.quality
			const cropOptions = {
				fillColor: fillColor !== null ? fillColor : options.fillColor
			}
			if (outputWidth !== -1) {
				cropOptions.width = outputWidth !== null ? outputWidth : options.outputWidth
				cropOptions.minWidth = outputWidth !== null ? outputWidth : options.outputWidth
			}

			const canvas = cropper.getCroppedCanvas(cropOptions)
			if (formatsWithQuality.indexOf(format) !== -1) {
				canvas.toBlob(function (blob) {
					const blobURL = URL.createObjectURL(blob)
					const file = new File([blob], generateUniqueID() + '.' + extensionsByMime[format], {
						type: format,
					})
					resolve({
						blob: file,
						blobURL: blobURL,
					})
					removeGenericLoader('SimpleCropperAdapter-crop')
				}, format, quality)
			} else {
				canvas.toBlob(function (blob) {
					const blobURL = URL.createObjectURL(blob)
					const file = new File([blob], generateUniqueID() + '.' + extensionsByMime[format], {
						type: format,
					})
					resolve({
						blob: file,
						blobURL: blobURL,
					})
					removeGenericLoader('SimpleCropperAdapter-crop')
				}, format)
			}
		})

	}

	/**
	 * @returns {Blob}
	 */
	this.getFile = function () {
		return blobImage
	}

	/**
	 * @returns {Boolean}
	 */
	this.wasChange = function () {
		return wasChange
	}

	/**
	 * @param {Function} callback 
	 * @returns {SimpleCropperAdapter}
	 */
	this.onCropped = function (callback) {
		if (typeof callback == 'function') {
			onCroppedCallback = callback
		}
		return instance
	}

	/**
	 * @param {Function} callback 
	 * @returns {SimpleCropperAdapter}
	 */
	this.onCancel = function (callback) {
		if (typeof callback == 'function') {
			onCancelCallback = callback
		}
		return instance
	}

	/**
	 * @param {Function} callback 
	 * @returns {SimpleCropperAdapter}
	 */
	this.onInit = function (callback) {
		if (typeof callback == 'function') {
			onInitCallback = callback
		}
		return instance
	}

	/**
	 */
	this.refresh = function () {
		if (typeof settedImage == 'string' && settedImage.length > 0) {
			cropper.replace(settedImage)
		} else {
			cropper.replace(settedPreviewImage)
		}
		return instance
	}

	/**
	 * @returns {String} La url
	 */
	this.getSettedImage = function () {
		if (typeof settedImage == 'string' && settedImage.length > 0) {
			return settedImage
		} else {
			return settedPreviewImage
		}
	}

	/**
	 * @returns {Boolean}
	 */
	this.imageIsSetted = function () {
		return typeof settedImage == 'string' && settedImage.length > 0
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

	//──── Inicialización ────────────────────────────────────────────────────────────────────
	showGenericLoader('SimpleCropperAdapter')
	componentSelector = typeof componentSelector == 'string' ? componentSelector : '[simple-cropper]'

	const component = document.querySelector(`${componentSelector}`)
	const preview = component.querySelector(`.preview`)
	const fileInput = component.querySelector(`[file]`)
	const controlRotateLeft = component.querySelector('[rotate-left]')
	const controlRotateRight = component.querySelector('[rotate-right]')
	const controlResize = component.querySelector('[resize-slider]')
	const controlLoadImage = component.querySelector('[load-image]')
	const controlCancel = component.querySelector('[cancel]')
	const controlCrop = component.querySelector('[crop]')

	let defaultOptions = {
		format: 'image/jpeg',
		quality: 0.7,
		fillColor: 'white',
		outputWidth: 400,
		aspectRatio: 4 / 4,
		responsive: true,
		checkCrossOrigin: false,
		center: true,
		viewMode: 2,
		autoCropArea: 1,
		zoomOnTouch: false,
		zoomOnWheel: false,
		toggleDragModeOnDblclick: true,
		cropBoxResizable: true,
		movable: true,
	}
	options = processByDefaultValues(defaultOptions, options)
	if (typeof options.minCropBoxWidth == 'undefined') {
		options.minCropBoxWidth = options.outputWidth * 2
	}
	let onInitDispatched = false
	let cropper = new Cropper(preview, Object.assign(options, {
		ready: function () {
			if (!onInitDispatched) {
				onInitCallback(instance)
				onInitDispatched = true
			}
			removeGenericLoader('SimpleCropperAdapter')
		}
	}))
	let blobImage = null
	let settedImage = preview.hasAttribute('is-final') ? preview.src : ''
	let settedPreviewImage = !preview.hasAttribute('is-final') ? preview.src : ''
	let wasChange = false

	controlCrop.setAttribute('disabled', true)
	showGenericLoader('SimpleCropperAdapter-init')
	preview.addEventListener('ready', function () {
		initialize = true
		if (settedImage.length !== 0) {
			controlCrop.removeAttribute('disabled')
			showGenericLoader('SimpleCropperAdapter-settedImage')
			if (fileInput.hasAttribute('required')) {
				fileInput.removeAttribute('required')
			}
			fetch(settedImage)
				.then(function (response) {
					const mimeType = response.headers.get('content-type')
					const fileURLPathname = new URL(settedImage).pathname
					const fileName = fileURLPathname.substring(fileURLPathname.lastIndexOf('/') + 1)
					return new Promise(function (fileResolve) {
						response.blob().then(function (blob) {
							fileResolve(new File([blob], fileName, {
								type: mimeType,
							}))
						})
					})
				})
				.then(function (file) {
					blobImage = file
					removeGenericLoader('SimpleCropperAdapter-settedImage')
				})
		}
		removeGenericLoader('SimpleCropperAdapter-init')
	})

	controlRotateLeft.addEventListener('click', function (e) {
		e.preventDefault()
		cropper.rotate(-90)
	})

	controlRotateRight.addEventListener('click', function (e) {
		e.preventDefault()
		cropper.rotate(90)
	})

	$(controlResize).slider({
		min: 0.5,
		max: 5,
		step: 0.1,
		value: 0,
		onChange: function (value) {
			const containerData = cropper.getContainerData();
			cropper.zoomTo(value, {
				x: containerData.width / 2,
				y: containerData.height / 2,
			})
		},
	})

	controlLoadImage.addEventListener('click', function (e) {
		e.preventDefault()
		fileInput.click()
		controlCrop.removeAttribute('disabled')
	})

	fileInput.addEventListener('change', function (e) {

		let file = e.target.files[0]
		let reader = new FileReader()

		reader.onload = function (e) {
			cropper.replace(e.target.result, false)
			fileInput.value = ''
		}

		reader.readAsDataURL(file)
	})

	controlCancel.addEventListener('click', function (e) {
		e.preventDefault()
		cropper.reset()
		cropper.zoomTo(0)
		if (settedImage.length === 0) {
			cropper.replace(settedPreviewImage, true)
			controlCrop.addEventListener('disabled', true)
		} else {
			cropper.replace(settedImage, true)
		}
		onCancelCallback()
	})

	controlCrop.addEventListener('click', function (e) {
		e.preventDefault()
		if (controlCrop.hasAttribute('disabled')) {
			return false
		}
		const cropped = instance.crop()
		if (cropped !== false) {
			cropped.then(function (res) {
				blobImage = res.blob
				settedImage = res.blobURL
				cropper.reset()
				cropper.zoomTo(0)
				cropper.replace(settedImage, false)
				wasChange = true
				if (fileInput.hasAttribute('required')) {
					fileInput.removeAttribute('required')
				}
				onCroppedCallback(blobImage, settedImage)
			})
		}

	})

	return this
}
/**
 * @param {String} name 
 * @returns {void}
 */
SimpleCropperAdapter.registerDynamicMessages = function (name) {

	if (typeof pcsphpGlobals != 'object') {
		pcsphpGlobals = {}
	}
	if (typeof pcsphpGlobals.messages != 'object') {
		pcsphpGlobals.messages = {}
	}
	if (typeof pcsphpGlobals.messages.es != 'object') {
		pcsphpGlobals.messages.es = {}
	}
	if (typeof pcsphpGlobals.messages.en != 'object') {
		pcsphpGlobals.messages.en = {}
	}

	let es = {
	}

	let en = {
		'ESPAÑOL': 'ENGLISH',
	}

	for (let i in es) {
		if (typeof pcsphpGlobals.messages.es[name] == 'undefined') pcsphpGlobals.messages.es[name] = {}
		pcsphpGlobals.messages.es[name][i] = es[i]
	}

	for (let i in en) {
		if (typeof pcsphpGlobals.messages.en[name] == 'undefined') pcsphpGlobals.messages.en[name] = {}
		pcsphpGlobals.messages.en[name][i] = en[i]
	}

}
