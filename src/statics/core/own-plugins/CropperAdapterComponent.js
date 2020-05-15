/**
 * @function CropperAdapterComponent
 *  
 * @param {AdapterOptions} configurations  
 * @param {Boolean} prepareOnCreation  
 */
function CropperAdapterComponent(configurations = {}, prepareOnCreation = true) {

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
	 * @property {Boolean} [cropBoxResizable=false] Enable to resize the crop box by dragging
	 */
	/**
	 * @typedef AdapterOptions
	 * @property {String} [outputFormat=image/jpeg] Formato de la imagen al exportar
	 * @property {String} [outputFillColor=white] Color de relleno de la imagen al exportar
	 * @property {Number} [outputWidth=400] Ancho de exportación
	 * @property {Number} [minWidth=400] Ancho mínimo de la imagen entrante
	 * @property {String} [containerSelector=[cropper-adapter-component]]
	 * @property {function(Cropper, HTMLCanvasElement)} [onReadyCropper] Se ejecuta la cada vez que se configura el canvas de cropper
	 * @property {function(Cropper, HTMLCanvasElement)} [onInitialize] Se ejecuta la primera vez que se configura el canvas de cropper
	 * @property {function(Event)} [onCrop] Se ejecuta la primera vez que se configura el canvas de cropper
	 * @property {Number} [pxOnMove=10] Pixeles de desplazamiento al moverse
	 * @property {Number} [zoomRatio=0.1] Proporción de aumento/reducción en zoom
	 * @property {Boolean} [allowResizeCrop=false]
	 * @property {CropperOptions} [cropperOptions]
	 */

	//──── Valores de configuración ──────────────────────────────────────────────────────────
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
		cropBoxResizable: false,
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
		onCrop: (event) => { },
		pxOnMove: 10,
		zoomRatio: 0.1,
		allowResizeCrop: false,
		cropperOptions: null,
	}

	//──── Objetos ───────────────────────────────────────────────────────────────────────────
	/** @type {CropperAdapterComponent} Instancia */ let instance = this
	/** @type {AdapterOptions} Configuraciones de la clase */ let adapterOptions
	/** @type {CropperOptions} Configuraciones de cropper */ let cropperOptions
	/** @type {Cropper} Instancia de cropper */ let cropper

	//──── Verificadores ─────────────────────────────────────────────────────────────────────
	/** @type {Boolean} Verifica si ya ha sido inicializado copper por primera vez */ let initialized = false
	/** @type {Boolean} Verifica si tiene una imagen desde el inicio */ let initWithImage = false
	/** @type {Boolean} Verifica si tiene una imagen */ let hasImage = false
	/** @type {Boolean} Verifica si la imagen cambió */ let wasChanged = false
	/** @type {Boolean} Verifica si está en proceso de edición */ let isOnEdit = false
	/** @type {Boolean} Verifica si ha sido recortado una vez */ let wasSaved = false
	/** @type {Boolean} Verifica si hay una imagen en el canvas sin guardar */ let unSaveImage = false
	/** @type {Boolean} Verifica si el input es requerido */ let fileInputIsRequired = false

	//──── Contenedores, canvas e imagen ─────────────────────────────────────────────────────
	/** @type {$} Contenedor del componente */ let container
	/** @type {$} Canvas */ let canvas
	/** @type {$} Contenedor de la vista previa al disparar el corte */ let previewContainer
	/** @type {String} Imagen por defecto */ let canvasImage
	/** @type {String} Título por asignado */ let presetTitle
	/** @type {HTMLImageElement} La última imagen guardada o cargada al inicio si no se ha guardado ninguna */ let lastSavedImage
	/** @type {String} El base64 de la última imagen guardada */ let lastImageBase64
	/** @type {String} El último título guardada */ let lastTitleSave

	//──── Inputs ────────────────────────────────────────────────────────────────────────────	
	/** @type {$} Input file */ let fileInput
	/** @type {$} Input para el título */ let titleInput

	//──── Botones de acción ─────────────────────────────────────────────────────────────────
	/** @type {$} Disparador de carga de imagen */ let loadImageButton
	/** @type {$} Disparador de rotación a la izquierda */ let actionRotateLeft
	/** @type {$} Disparador de rotación a la izquierda */ let actionRotateRight
	/** @type {$} Disparador de volteado horizontal */ let actionFlipHorizontal
	/** @type {$} Disparador de volteado vertical */ let actionFlipVertical
	/** @type {$} Disparador de movimiento hacia arriba */ let actionMoveUp
	/** @type {$} Disparador de movimiento hacia abajo */ let actionMoveDown
	/** @type {$} Disparador de movimiento hacia la izquierda */ let actionMoveLeft
	/** @type {$} Disparador de movimiento hacia la derecha */ let actionMoveRight
	/** @type {$} Disparador de reducción */ let actionZoomOut
	/** @type {$} Disparador de aumento */ let actionZoomIn

	//──── Textos ────────────────────────────────────────────────────────────────────────────
	/** @type {String} Texto agregar imagen */ let addImageText = _i18n('cropper', 'Agregar imagen')
	/** @type {String} Texto cambiar imagen */ let changeImageText = _i18n('cropper', 'Cambiar imagen')
	/** @type {String} Texto título por defecto */ let title = _i18n('cropper', 'imagen') + '_' + (Date.now().toString(36) + Math.random().toString(36).substr(2, 5)).toUpperCase()

	//──── Elementos de interfaz ─────────────────────────────────────────────────────────────
	/** @type {$} Disparador de inicio de la interfaz */ let startButton
	/** @type {$} Pasos de la interfaz */ let steps
	/** @type {$} Paso de adición */ let addStep
	/** @type {$} Paso de edición */ let editStep
	/** @type {$} Controles */ let controls
	/** @type {$} Opciones principales */ let options
	/** @type {$} Sub opciones */ let subOptions
	/** @type {$} Botones pricipales de guardar (cortar) y cancelar */ let mainButtons
	/** @type {$} Botón de guardar (cortar) */ let saveButton
	/** @type {$} Botón de recortar */ let cancelButton
	/** @type {$} Botón para regresar a las opciones principales */ let backOptionsbutton

	//──── Estado previo del cropper ─────────────────────────────────────────────────────────
	/** @type {Object} */ let cropperData
	/** @type {Object} */ let cropperCanvasData
	/** @type {Object} */ let cropperCropBoxData

	//──── Eventos personalizados ────────────────────────────────────────────────────────────	
	/** @type {String} */ let eventsPrefix = 'event-cropper'
	/** @type {HTMLElement} */ let eventer = document.createElement('eventer-cropper')
	/** @type {Event[]} */ let events = {
		prepare: {
			event: new Event(`${eventsPrefix}-prepare`),
			data: {},
			wasDispatch: false,
			canDispatch: function (element) {
				return element.wasDispatch
			},
		},
		save: {
			event: new Event(`${eventsPrefix}-save`),
			data: {},
			wasDispatch: false,
			canDispatch: function (element) {
				return element.wasDispatch
			},
		},
	}

	//──── Varios ────────────────────────────────────────────────────────────────────────────
	/** @type {Boolean} */ let isPrepared = false

	if (prepareOnCreation) {
		prepare(configurations)
	}

	/**
	 * @method crop
	 * @param {Number} [quality=1] Calidad de exportación
	 * @param {Number} [oWidth=null] Ancho de exportación
	 * @param {Boolean} [last=false] Para devolver la última imagen guardada
	 * @returns {String|null} base64
	 */
	this.crop = (quality = 1, oWidth = null, last = false) => {

		let b64 = null

		if (initialized) {

			if (!(typeof quality == 'number')) {
				quality = 1
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
				b64 = cropperCanvas.toDataURL(adapterOptions.outputFormat, quality)
			} else {
				b64 = cropperCanvas.toDataURL(adapterOptions.outputFormat)
			}

			if (isOnEdit || last) {
				b64 = typeof lastImageBase64 == 'string' && lastImageBase64.length > 0 ? lastImageBase64 : null
			}

		}

		return b64

	}

	/**
	 * @method getFile
	 * @param {String} [name=imagen] Nombre del archivo
	 * @param {Number} [quality=0.7] Calidad de exportación
	 * @param {Number} [oWidth=null] Ancho de exportación
	 * @param {String} [extension=null] Extensión del archivo
	 * @param {Boolean} [last=false] Para devolver la última imagen guardada
	 * @returns {File|null}
	 */
	this.getFile = (name = '', quality = 0.7, oWidth = null, extension = null, last = false) => {

		let file = null

		if (initialized) {

			extension = typeof extension == 'string' ? extension : adapterOptions.outputFormat.replace('image/', '')

			if (typeof name == 'string' && name.trim().length > 0) {
				name = name.trim()
			} else {
				name = this.getTitle()
			}

			let util = new UtilPieces()
			let utilFiles = util.file
			let b64 = this.crop(
				quality,
				oWidth,
				last
			)

			if (typeof b64 == 'string') {
				file = utilFiles.dataURLToFile(
					b64,
					`${name}.${extension}`
				)

			}

		}

		return file
	}

	/**
	 * @method getTile
	 * @returns {String}
	 */
	this.getTitle = () => {
		let string = titleInput.length > 0 ? friendlyURL(titleInput.val().trim()) : title
		string = string.length > 0 ? string : title
		return isOnEdit ? lastTitleSave : string
	}

	/**
	 * @method wasChanged
	 * @returns {Boolean}
	 */
	this.wasChanged = () => {
		return wasChanged
	}

	/**
	 * @method initWithImage
	 * @returns {Boolean}
	 */
	this.initWithImage = () => {
		return initWithImage
	}

	/**
	 * @method hasImage
	 * @returns {Boolean}
	 */
	this.hasImage = () => {
		return hasImage
	}

	/**
	 * @method getHeightByAspectRatioFromWidth
	 * @param {Number} width
	 * @param {Number} aspectRatio
	 * @returns {Number|null}
	 */
	this.getHeightByAspectRatioFromWidth = (width, aspectRatio) => {

		if (!isNaN(width) && !isNaN(aspectRatio)) {

			return width / (aspectRatio)

		}

		return null

	}

	/**
	 * @method getWidthByAspectRatioFromHeight
	 * @param {Number} height
	 * @param {Number} aspectRatio
	 * @returns {Number|null}
	 */
	this.getWidthByAspectRatioFromHeight = (height, aspectRatio) => {

		if (!isNaN(height) && !isNaN(aspectRatio)) {

			return height * (aspectRatio)

		}

		return null

	}

	/**
	 * @method forceInitialize
	 * @returns {void}
	 */
	this.forceInitialize = () => {
		if (!initialized && isPrepared) {
			initialize({})
		}
	}

	/**
	 * @method prepare
	 * @returns {void}
	 */
	this.prepare = () => {

		if (!isPrepared) {
			prepare(configurations)
		}

	}

	/**
	 * @method destroy
	 * @returns {void}
	 */
	this.destroy = () => {
		container.remove()
		delete this
	}

	/**
	 * @method on
	 * @param {String} eventName
	 * @param {Function} callback
	 * @returns {void}
	 */
	this.on = (eventName, callback) => {

		let eventPrefixed = `${eventsPrefix}-${eventName}`

		if (typeof events[eventName] !== 'undefined' && typeof callback == 'function') {

			eventer.addEventListener(eventPrefixed, function (e) {

				let eventConfig = events[eventName]

				callback(e, eventConfig.data)

				events[eventName].wasDispatch = true

			})

		}

	}

	/**
	 * @method dispatch
	 * @param {String} eventName
	 * @returns {void}
	 */
	this.dispatch = (eventName) => {

		if (typeof events[eventName] !== 'undefined') {

			let eventConfig = events[eventName]

			let eventObject = eventConfig.event
			let wasDispatch = eventConfig.wasDispatch
			let canDispatch = eventConfig.canDispatch(eventConfig)

			if (canDispatch) {
				eventer.dispatchEvent(events[eventName].event)
			}

		}

	}

	/**
	 * @function prepare
	 * @param {AdapterOptions} configurations 
	 */
	function prepare(configurations = {}) {

		try {

			configOptions(configurations)

			if (typeof canvasImage == 'string' && canvasImage.trim().length > 0) {
				initWithImage = true
			}

			if (initWithImage) {

				startButton.html(changeImageText)

				let imageElement = previewContainer.find('img')

				if (imageElement.length > 0) {
					imageElement.attr('src', canvasImage)
				} else {
					imageElement = new Image()
					imageElement.src = canvasImage
					previewContainer.append(imageElement)
				}

				lastSavedImage = new Image()
				lastSavedImage.src = canvasImage

				let imageURL = null

				try {
					imageURL = new URL(canvasImage)
				} catch (e) {
					try {

						let baseURL = $('base').attr('href')

						if (typeof baseURL != 'string' || baseURL.trim().length < 1) {
							baseURL = window.location.origin
						}

						if (baseURL[baseURL.length - 1] != '/') {
							baseURL += '/'
						}

						imageURL = new URL(canvasImage, baseURL)

					} catch (e) {
						console.warn('No se instanciar una url con la ruta de la imagen por defecto.')
						console.warn(`La ruta probada es: ${canvasImage}`)
						console.warn(e.message)
					}
				}

				if (imageURL !== null) {
					let imageURLParts = imageURL.href.split('/')
					if (imageURLParts.length > 0) {
						let nameResource = imageURLParts[imageURLParts.length - 1]
						let indexPoint = nameResource.lastIndexOf('.')
						let nameWithoutExtension = nameResource
						if (indexPoint !== -1) {
							nameWithoutExtension = nameResource.substring(0, indexPoint)
						}
						if (nameWithoutExtension.length > 0) {
							presetTitle = nameWithoutExtension
						}
					}
				}

			} else {
				startButton.html(addImageText)
			}

			let height = (adapterOptions.outputWidth / (adapterOptions.cropperOptions.aspectRatio))
			let sizeOutputString = `${adapterOptions.outputWidth}x${height}(px)`
			let wMinString = `${adapterOptions.minWidth}(px)`
			container.find(`[show-output]`).html(sizeOutputString)
			container.find(`[min-w-output]`).html(wMinString)

			isPrepared = true
			eventer.dispatchEvent(events.prepare.event)

		} catch (error) {
			errorMessage(_i18n('cropper', 'Error'), _i18n('cropper', 'Ha ocurrido un error al configurar CropperAdapterComponent'))
			console.error(error)
		}

	}

	/**
	 * @function initialize
	 */
	function initialize(e) {

		if (typeof e.preventDefault == 'function') {
			e.preventDefault()
		}

		if (initWithImage || hasImage) {
			toEditStep()
			configCanvasDimensions()
		} else {
			toAddStep()
		}

		if (!initialized) {

			cropper = new Cropper(canvas.get(0), cropperOptions)

			//Verificar si tiene alguna imagen predeterminada
			if (initWithImage) {
				cropper.replace(canvasImage)
				lastImageBase64 = instance.crop()
				lastTitleSave = instance.getTitle()
			}

			if (typeof presetTitle == 'string' && presetTitle.trim().length > 0) {
				titleInput.val(presetTitle)
			} else {
				titleInput.val(title)
			}

			if (initWithImage) {
				enableElement(saveButton)
				startButton.html(changeImageText)
			}

			configEvents()

		} else { }

	}

	/**
	 * @function configTriggers
	 */
	function configEvents() {

		//Evento al terminar la carga de una imagen
		canvas.get(0).addEventListener('ready', function (e) {

			let isCropperEvent = this.cropper === cropper

			if (isCropperEvent) {

				adapterOptions.onReadyCropper(cropper, canvas)
				canvas.parent().removeAttr('style')

				if (!initialized) {
					adapterOptions.onInitialize(cropper, canvas)
					updateCropperData()
					initialized = true
				}

				limitCropBox()

			}

		})

		//Evento de corte

		canvas.get(0).addEventListener('crop', function (e) {

			let isCropperEvent = this.cropper === cropper

			if (isCropperEvent) {

				adapterOptions.onCrop(e)

				let detail = e.detail

				let realHeight = detail.height.toFixed(0)
				let realWidth = detail.width.toFixed(0)

				if (isOnEdit) {
					$(`[show-crop-dimensions]`).html(formatStr(
						_i18n('cropper', `Tamaño real de la máscara de corte %rx%r(px)`),
						[
							realWidth,
							realHeight,
						],
					))
				} else {
					$(`[show-crop-dimensions]`).html(``)
				}

			}

		})

		canvas.get(0).addEventListener('cropend', function (e) {

			let isCropperEvent = this.cropper === cropper

			if (isCropperEvent) {

				limitCropBox()

			}

		})

		canvas.get(0).addEventListener('cropmove', function (e) {

			let isCropperEvent = this.cropper === cropper

			if (isCropperEvent) {

				limitCropBox(true)

			}

		})

		//Evento al escribir un título
		titleInput.on('input', function (e) {
			isOnEdit = true
		})

		//Evento al cargar una imagen
		fileInput.on('change', function () {

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
								errorMessage(_i18n('cropper', 'Error'), formatStr(
									_i18n('cropper', `El ancho mínimo de la imagen debe ser: %rpx`),
									[
										adapterOptions.minWidth,
									]
								))
								return
							}

							toEditStep()
							configCanvasDimensions()

							cropper.replace(dataURIImg)
							hasImage = true
							wasChanged = true
							isOnEdit = true
							unSaveImage = true
							enableElement(saveButton)
							fileInput.val('')

						}

					}

				} else {

					errorMessage(_i18n('cropper', 'Error'), _i18n('cropper', 'Seleccione una imagen, por favor.'))

				}

			} else {

				errorMessage(_i18n('cropper', 'Error'), _i18n('cropper', 'No hay imágenes seleccionadas.'))

			}

		})

		//Evento al aplicar el recorte
		if (saveButton instanceof $) {

			saveButton.click(function (e) {

				e.preventDefault()

				let that = $(e.currentTarget)

				if ((initWithImage || hasImage) && !isDisable(that)) {

					wasChanged = true
					isOnEdit = false
					wasSaved = true
					unSaveImage = false

					let cutImage = new Image()
					cutImage.id = 'image'

					cutImage.addEventListener('load', () => {

						let imageElement = previewContainer.find('img')

						if (imageElement.length > 0) {
							imageElement.attr('src', cutImage.src)
						} else {
							previewContainer.append(cutImage)
						}

						lastSavedImage = new Image()
						lastSavedImage.src = cutImage.src

						startButton.html(changeImageText)

						if (fileInputIsRequired) {
							fileInput.removeAttr('required')
						}

						lastImageBase64 = instance.crop()
						lastTitleSave = instance.getTitle()
						updateCropperData()
						toPreview()

						events.save.data['b64'] = lastImageBase64
						eventer.dispatchEvent(events.save.event)

					})

					cutImage.src = instance.crop()

				}

				return false

			})

		}

		//Evento al revertir cualquier cambio hecho
		if (cancelButton instanceof $) {

			cancelButton.click(function (e) {

				e.preventDefault()

				let that = $(e.currentTarget)

				if (!isDisable(that)) {

					if (isOnEdit) {

						if (wasSaved) {

							if (unSaveImage) {

								cropper.destroy()
								cropper = new Cropper(canvas.get(0), cropperOptions)
								cropper.replace(lastSavedImage.src)
								unSaveImage = false
								wasChanged = false

							} else {

								restoreCropperData()

							}

						} else {

							if (initWithImage) {

								cropper.replace(canvasImage)
								wasChanged = false

							} else {

								cropper.destroy()
								cropper = new Cropper(canvas.get(0), cropperOptions)
								hasImage = false
								wasChanged = false

							}

						}

						isOnEdit = false
						toPreview()

					} else {

						toPreview()

					}

				}

				return false

			})

		}

		//Evento para lanzar la carga de imagen
		if (loadImageButton instanceof $) {

			loadImageButton.click(e => {

				e.preventDefault()
				e.stopPropagation()

				let that = e.currentTarget

				if (!isDisable(that)) {
					fileInput.click()
				}

			})

		}

		//Evento de los controles
		if (options instanceof $) {

			let option = options.find('.option[data-option]')

			option.click(function (e) {

				e.preventDefault()
				e.stopPropagation()

				let that = $(e.currentTarget)

				if (!isDisable(that)) {
					let name = that.attr('data-option')
					activateSubOptions(name)
				}

			})

		}

		//Evento para volver a las opciones
		if (backOptionsbutton instanceof $) {

			backOptionsbutton.click(e => {

				e.preventDefault()
				e.stopPropagation()

				let that = e.currentTarget

				if (!isDisable(that)) {
					deactivateSubOptions()
				}

			})

		}

		//Eventos de acciones de Cropper
		actionRotateLeft.click(e => {

			e.preventDefault()
			e.stopPropagation()

			if (initialized) {
				cropper.rotate(-90)
				isOnEdit = true
			}

		})

		actionRotateRight.click(e => {

			e.preventDefault()
			e.stopPropagation()

			if (initialized) {
				cropper.rotate(90)
				isOnEdit = true
			}

		})

		actionFlipHorizontal.click(e => {

			e.preventDefault()
			e.stopPropagation()

			if (initialized) {

				isOnEdit = true
				let data = cropper.getData()

				if (data.scaleX < 0) {
					cropper.scaleX(1)
				} else {
					cropper.scaleX(-1)
				}

			}

		})

		actionFlipVertical.click(e => {

			e.preventDefault()
			e.stopPropagation()

			if (initialized) {

				isOnEdit = true
				let data = cropper.getData()

				if (data.scaleY < 0) {
					cropper.scaleY(1)
				} else {
					cropper.scaleY(-1)
				}

			}

		})

		actionMoveUp.click(e => {

			e.preventDefault()
			e.stopPropagation()

			if (initialized) {
				cropper.move(0, adapterOptions.pxOnMove * -1)
				isOnEdit = true
			}

		})

		actionMoveDown.click(e => {

			e.preventDefault()
			e.stopPropagation()

			if (initialized) {
				cropper.move(0, adapterOptions.pxOnMove)
				isOnEdit = true
			}

		})

		actionMoveLeft.click(e => {

			e.preventDefault()
			e.stopPropagation()

			if (initialized) {
				cropper.move(adapterOptions.pxOnMove * -1, 0)
				isOnEdit = true
			}

		})

		actionMoveRight.click(e => {

			e.preventDefault()
			e.stopPropagation()

			if (initialized) {
				cropper.move(adapterOptions.pxOnMove, 0)
				isOnEdit = true
			}

		})

		actionZoomOut.click(e => {

			e.preventDefault()
			e.stopPropagation()

			if (initialized) {
				cropper.zoom(adapterOptions.zoomRatio * -1)
				isOnEdit = true
			}

		})

		actionZoomIn.click(e => {

			e.preventDefault()
			e.stopPropagation()

			if (initialized) {
				cropper.zoom(adapterOptions.zoomRatio)
				isOnEdit = true
			}

		})

	}

	/**
	 * @function limitCropBox
	 * @param {Boolean} isMove
	 */
	function limitCropBox(isMove = false) {

		isMove = isMove === true

		if (!adapterOptions.allowResizeCrop && initialized) {

			let outputWidth = adapterOptions.outputWidth
			let aspectRatio = adapterOptions.cropperOptions.aspectRatio

			let containerData = cropper.getContainerData()
			let containerWidth = containerData.width
			let cropWidth = outputWidth >= containerWidth ? outputWidth : containerWidth

			let calculatedHeight = instance.getHeightByAspectRatioFromWidth(cropWidth, aspectRatio)

			if (isMove) {

				cropper.setCropBoxData({
					width: cropWidth,
					height: calculatedHeight,
				})

			} else {

				cropper.setCropBoxData({
					top: 0,
					left: 0,
					width: cropWidth,
					height: calculatedHeight,
				})

			}

		}

	}

	/**
	 * @function updateCropperData
	 */
	function updateCropperData() {
		if (!isOnEdit) {
			cropperData = cropper.getData()
			cropperCanvasData = cropper.getCanvasData()
			cropperCropBoxData = cropper.getCropBoxData()
		}
	}

	/**
	 * @function restoreCropperData
	 */
	function restoreCropperData() {

		if (typeof cropperData !== 'undefined') {
			cropper.setData(cropperData)
		}

		if (typeof cropperCanvasData !== 'undefined') {
			cropper.setCanvasData(cropperCanvasData)
		}

		if (typeof cropperCropBoxData !== 'undefined') {
			cropper.setCropBoxData(cropperCropBoxData)
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
		//Ajustes en algunos valores
		if (adapterOptions.pxOnMove < 0) {
			adapterOptions.pxOnMove *= -1
		}
		if (adapterOptions.zoomRatio < 0) {
			adapterOptions.zoomRatio *= -1
		}


		//Establecer valores
		let containerSelector = adapterOptions.containerSelector
		container = document.querySelector(containerSelector)

		if (container instanceof HTMLElement) {

			container = $(container)

			//──── Asignación de valores y estados iniciales ─────────────────────────────────────────

			fileInput = document.querySelector(`${containerSelector} input[type="file"]`)

			if (fileInput instanceof HTMLElement) {

				fileInputIsRequired = fileInput.required
				fileInput = $(fileInput)

			} else {
				throw new Error(_i18n('cropper', 'No se ha encontrado ningún input de tipo file.'))
			}

			//──────────────────────────────────────────────────────────────────────────────────

			canvas = document.querySelector(`${containerSelector} canvas`)

			if (canvas instanceof HTMLElement) {
				canvas = $(canvas)
				canvasImage = canvas.attr('data-image')
			} else {
				throw new Error(_i18n('cropper', 'No se ha encontrado ningún canvas.'))
			}

			//──────────────────────────────────────────────────────────────────────────────────

			previewContainer = document.querySelector(`${containerSelector} > .preview`)

			if (previewContainer instanceof HTMLElement) {
				previewContainer = $(previewContainer)

				let w = parseInt(previewContainer.attr('w'))
				let h = parseInt(previewContainer.attr('h'))

				if (!isNaN(w) && !isNaN(h)) {
					previewContainer.css('width', `${w}px`)
					previewContainer.css('height', `${h}px`)
				}

				activateElement(previewContainer)
			} else {
				console.warn('No hay ningún contenedor de vista previa.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			loadImageButton = document.querySelectorAll(`${containerSelector} [load-image]`)

			if (loadImageButton.length > 0) {
				loadImageButton = $(loadImageButton)
			} else {
				console.warn('No hay ningún botón de carga de imagen.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			startButton = document.querySelector(`${containerSelector} [start]`)

			if (startButton instanceof HTMLElement) {
				startButton = $(startButton)
				startButton.click(initialize)
			} else {
				console.warn('No hay ningún botón de inicio.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			steps = document.querySelector(`${containerSelector} > .workspace > .steps`)

			if (steps instanceof HTMLElement) {

				steps = $(steps)
				addStep = steps.find('>.step.add')
				editStep = steps.find('>.step.edit')
				deactivateElement(addStep)
				deactivateElement(editStep)

				if (addStep.length < 1) {
					console.warn('No está el paso add.')
				}
				if (editStep.length < 1) {
					console.warn('No está el paso edit.')
				}

			} else {
				console.warn('No hay pasos.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			controls = document.querySelector(`${containerSelector} > .workspace > .controls`)

			if (controls instanceof HTMLElement) {

				controls = $(controls)
				options = controls.find('>.options')
				subOptions = controls.find('>.sub-options')

				deactivateElement(controls)
				deactivateElement(options)
				deactivateElement(subOptions)

				if (options.length < 1) {
					console.warn('No hay opciones.')
				}
				if (subOptions.length < 1) {
					console.warn('No hay subopciones')
				}

			} else {
				console.warn('No hay controles.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			backOptionsbutton = document.querySelectorAll(`${containerSelector} [back-options]`)

			if (backOptionsbutton.length > 0) {
				backOptionsbutton = $(backOptionsbutton)
			} else {
				console.warn('No hay ningún botón para volver a las opciones.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			actionRotateLeft = document.querySelectorAll(`${containerSelector} [action-rotate-left]`)

			if (actionRotateLeft.length > 0) {
				actionRotateLeft = $(actionRotateLeft)
			} else {
				console.warn('No hay ningún botón para rotar a la izquierda.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			actionRotateRight = document.querySelectorAll(`${containerSelector} [action-rotate-right]`)

			if (actionRotateRight.length > 0) {
				actionRotateRight = $(actionRotateRight)
			} else {
				console.warn('No hay ningún botón para rotar a la derecha.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			actionFlipHorizontal = document.querySelectorAll(`${containerSelector} [action-flip-horizontal]`)

			if (actionFlipHorizontal.length > 0) {
				actionFlipHorizontal = $(actionFlipHorizontal)
			} else {
				console.warn('No hay ningún botón para voltear horizontalmente.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			actionFlipVertical = document.querySelectorAll(`${containerSelector} [action-flip-vertical]`)

			if (actionFlipVertical.length > 0) {
				actionFlipVertical = $(actionFlipVertical)
			} else {
				console.warn('No hay ningún botón para voltear verticalmente.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			actionMoveUp = document.querySelectorAll(`${containerSelector} [action-move-up]`)

			if (actionMoveUp.length > 0) {
				actionMoveUp = $(actionMoveUp)
			} else {
				console.warn('No hay ningún botón para mover hacia arriba.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			actionMoveDown = document.querySelectorAll(`${containerSelector} [action-move-down]`)

			if (actionMoveDown.length > 0) {
				actionMoveDown = $(actionMoveDown)
			} else {
				console.warn('No hay ningún botón para mover hacia abajo.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			actionMoveLeft = document.querySelectorAll(`${containerSelector} [action-move-left]`)

			if (actionMoveLeft.length > 0) {
				actionMoveLeft = $(actionMoveLeft)
			} else {
				console.warn('No hay ningún botón para mover hacia la izquierda.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			actionMoveRight = document.querySelectorAll(`${containerSelector} [action-move-right]`)

			if (actionMoveRight.length > 0) {
				actionMoveRight = $(actionMoveRight)
			} else {
				console.warn('No hay ningún botón para mover hacia la derecha.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			actionZoomOut = document.querySelectorAll(`${containerSelector} [action-zoom-out]`)

			if (actionZoomOut.length > 0) {
				actionZoomOut = $(actionZoomOut)
			} else {
				console.warn('No hay ningún botón para reducir.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			actionZoomIn = document.querySelectorAll(`${containerSelector} [action-zoom-in]`)

			if (actionZoomIn.length > 0) {
				actionZoomIn = $(actionZoomIn)
			} else {
				console.warn('No hay ningún botón para aumentar.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			saveButton = document.querySelectorAll(`${containerSelector} [save]`)

			if (saveButton.length > 0) {
				saveButton = $(saveButton)
				disableElement(saveButton)
			} else {
				console.warn('No hay ningún botón de guardado (cortado).')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			cancelButton = document.querySelectorAll(`${containerSelector} [cancel]`)

			if (cancelButton.length > 0) {
				cancelButton = $(cancelButton)
			} else {
				console.warn('No hay ningún botón de cancelar.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			mainButtons = document.querySelector(`${containerSelector} .main-buttons`)

			if (mainButtons instanceof HTMLElement) {
				mainButtons = $(mainButtons)
				deactivateElement(mainButtons)
			} else {
				console.warn('No hay ningún contenedor de los botones de guardado (cortado) y cancelar.')
			}

			//──────────────────────────────────────────────────────────────────────────────────

			titleInput = document.querySelector(`${containerSelector} input[cropper-title-export]`)

			if (titleInput instanceof HTMLElement) {
				titleInput = $(titleInput)
			} else {
				console.warn('No hay ningún input para extraer el título.')
			}

			//──────────────────────────────────────────────────────────────────────────────────


		} else {
			throw new Error(formatStr(_i18n('cropper', 'No existe ningún elemento con el selector %r.', [containerSelector])))
		}


	}

	/**
	 * @function toAddStep
	 */
	function toAddStep() {
		activateElement(addStep)
		deactivateElement(previewContainer)
		deactivateElement(editStep)
		disableActions()
		disableOptions()

		activateElement(controls)
		activateElement(options)
		activateElement(mainButtons)
		disableElement(saveButton)
	}

	/**
	 * @function toEditStep
	 */
	function toEditStep() {
		activateElement(editStep)
		deactivateElement(previewContainer)
		deactivateElement(addStep)
		enableActions()
		enableOptions()

		activateElement(controls)
		activateElement(options)
		activateElement(mainButtons)
		enableElement(saveButton)

		limitCropBox()

		if (lastSavedImage instanceof Image && initialized) {
			cropper.replace(lastSavedImage.src)
		}

	}

	/**
	 * @function toPreview
	 */
	function toPreview() {

		activateElement(previewContainer)
		deactivateElement(addStep)
		deactivateElement(editStep)

		deactivateElement(controls)
		deactivateElement(options)
		deactivateElement(subOptions)
		deactivateElement(mainButtons)

		$([document.documentElement, document.body]).animate({
			scrollTop: previewContainer.offset().top
		}, 500)
	}

	/**
	 * @function disableElement
	 * @param {$|HTMLElement} element 
	 */
	function disableElement(element) {
		if (element instanceof HTMLElement) {
			element = $(element)
		}
		if (element instanceof $) {
			element.attr('disabled', 'disabled')
			element.addClass('disabled')
		}
	}

	/**
	 * @function enableElement
	 * @param {$|HTMLElement} element 
	 */
	function enableElement(element) {
		if (element instanceof HTMLElement) {
			element = $(element)
		}
		if (element instanceof $) {
			element.removeAttr('disabled')
			element.removeClass('disabled')
		}
	}

	/**
	 * @function isDisable
	 * @param {$|HTMLElement} element 
	 * @returns {Boolean}
	 */
	function isDisable(element) {

		let disabled = true

		if (element instanceof HTMLElement) {
			element = $(element)
		}
		if (element instanceof $) {
			disabled = element.hasClass('disabled') || element.attr('disabled') == 'disabled'
		}

		return disabled
	}

	/**
	 * @function deactiveElement
	 * @param {$|HTMLElement} element 
	 */
	function deactivateElement(element) {
		if (element instanceof HTMLElement) {
			element = $(element)
		}
		if (element instanceof $) {
			element.removeClass('active')
		}
	}

	/**
	 * @function activeElement
	 * @param {$|HTMLElement} element 
	 */
	function activateElement(element) {
		if (element instanceof HTMLElement) {
			element = $(element)
		}
		if (element instanceof $) {
			element.addClass('active')
		}
	}

	/**
	 * @function activateSubOptions
	 * @param {String} name 
	 */
	function activateSubOptions(name) {
		deactivateElement(options)
		activateElement(subOptions.filter(`[data-name="${name}"]`))
	}

	/**
	 * @function deactivateSubOptions
	 */
	function deactivateSubOptions() {
		deactivateElement(subOptions)
		activateElement(options)
	}

	/**
	 * @function enableOptions
	 * @param {String} name 
	 */
	function enableOptions() {
		enableElement(options.find('.option'))
	}

	/**
	 * @function disableOptions
	 */
	function disableOptions() {
		disableElement(options.find('.option'))
	}

	/**
	 * @function enableActions
	 * @param {String} name 
	 */
	function enableActions() {
		enableElement(actionRotateLeft)
		enableElement(actionRotateRight)
		enableElement(actionFlipHorizontal)
		enableElement(actionFlipVertical)
		enableElement(actionMoveUp)
		enableElement(actionMoveDown)
		enableElement(actionMoveLeft)
		enableElement(actionMoveRight)
		enableElement(actionZoomOut)
		enableElement(actionZoomIn)
	}

	/**
	 * @function disableActions
	 */
	function disableActions() {
		disableElement(actionRotateLeft)
		disableElement(actionRotateRight)
		disableElement(actionFlipHorizontal)
		disableElement(actionFlipVertical)
		disableElement(actionMoveUp)
		disableElement(actionMoveDown)
		disableElement(actionMoveLeft)
		disableElement(actionMoveRight)
		disableElement(actionZoomOut)
		disableElement(actionZoomIn)
	}

	/**
	 * @function isActive
	 * @param {$|HTMLElement} element 
	 * @returns {Boolean}
	 */
	function isActive(element) {

		let active = false

		if (element instanceof HTMLElement) {
			element = $(element)
		}
		if (element instanceof $) {
			active = element.hasClass('active')
		}

		return active
	}

	/**
	 * @function configCanvasDimensions
	 */
	function configCanvasDimensions() {

		canvas.removeAttr('style')

		let windowWidth = window.outerWidth
		let windowHeight = window.innerHeight * 0.75
		let aspectRatio = adapterOptions.cropperOptions.aspectRatio

		canvas.css('width', `${windowWidth}px`)
		canvas.css('max-width', `100%`)

		let parentWidth = canvas.parent().width()
		let canvasWidth = canvas.width()
		let reCalculateWidth = canvasWidth < parentWidth ? canvasWidth : parentWidth

		let height = (reCalculateWidth / (aspectRatio))

		if (height > windowHeight) {

			reCalculateWidth = ((aspectRatio) * windowHeight)
			height = (reCalculateWidth / (aspectRatio))

		}

		if (!isNaN(height)) {

			canvas.css('width', `${reCalculateWidth}px`)
			canvas.css('height', `${height}px`)
			if (reCalculateWidth < parentWidth) {
				canvas.parent().css('width', `${reCalculateWidth}px`)
			}

		}

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
	 * @function strReplace
	 * @param {string[]|string} search Elementos a buscar
	 * @param {strin[]|string} replace Elementos de reemplazo
	 * @param {string} subject Cadena de entrada
	 * @returns {string}
	 */
	function strReplace(search, replace, subject) {

		if (typeof search == 'string') {
			search = [search]
		} else if (!Array.isArray(search)) {
			return null
		}

		if (typeof replace != 'string' && !Array.isArray(replace)) {
			return null
		}

		if (typeof subject != 'string') {
			return null
		}

		let searchLength = search.length

		for (let i = 0; i < searchLength; i++) {

			let searchString = search[i]
			let replaceString = ''

			if (Array.isArray(replace)) {
				if (typeof replace[i] == 'string') {
					replaceString = replace[i]
				}
			} else {
				replaceString = replace
			}

			let replacedString = subject

			while (replacedString.indexOf(searchString) !== -1) {
				replacedString = replacedString.replace(searchString, replaceString)
			}

			subject = replacedString

		}

		return subject
	}

	/**
	 * @function friendlyURL
	 * @param {string} str Cadena para formatear
	 * @param {number} maxWords Cantidad máxima de palabras
	 * @returns {string} Cadena formateada
	 */
	function friendlyURL(str, maxWords) {

		if (typeof str != 'string') {
			return null
		}

		str = str.trim()

		let dictionary = [
			'á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä', 'Ã',
			'é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë',
			'í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î',
			'ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô',
			'ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü',
			'ñ', 'Ñ', 'ç', 'Ç',
			'  ', ' ',
		]

		let replace_dictionary = [
			'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A',
			'e', 'e', 'e', 'e', 'E', 'E', 'E', 'E',
			'i', 'i', 'i', 'i', 'I', 'I', 'I', 'I',
			'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O',
			'u', 'u', 'u', 'u', 'U', 'U', 'U', 'U',
			'nn', 'NN', 'c', 'C',
			' ', '-',
		]

		let other_characters = [
			"\\", "¨", "º", "~", '±',
			"#", "@", "|", "!", "\"",
			"·", "$", "%", "&", "/",
			"(", ")", "?", "'", "¡",
			"¿", "[", "^", "`", "]",
			"+", "}", "{", "¨", "´",
			">", "<", ";", ",", ":",
			".", 'º',
		]

		str = str.replace(/(\t|\r\n|\r|\n){1,}/gmi, '')
		str = str.replace(/(\u00a0){1,}/gmi, ' ')
		str = strReplace(dictionary, replace_dictionary, str)
		str = strReplace(other_characters, '', str)
		str = str.replace(/-{2,}/gmi, '')
		str = str.toLowerCase()

		if (typeof maxWords == 'number') {

			maxWords = parseInt(maxWords)

			let words = str.split('-')

			let wordsLimitied = []
			let countWords = words.length

			for (let $i = 0; $i < maxWords && $i < countWords; $i++) {
				let word = words[$i]
				wordsLimitied.push(word)
			}

			str = wordsLimitied.join('-')

		}

		return str
	}

	return this

}
