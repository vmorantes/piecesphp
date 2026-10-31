/**
 * @function ContentImageEditor
 *
 * La tarjeta de imagen y su editor de recorte (design.md §3.18 y §3.19): toma el `[data-image-editor]` de la página y
 * todas las `[data-image-card]`. El recuadro es FIJO. El flujo es siempre archivo → editor → aplicar: con `data-action`
 * lo envía y el modal no se cierra hasta que el servidor lo acepta; sin ella, lo entrega a onApply().
 * Pide Cropper (`import_cropper()`). Los textos salen de los `data-text-*` de la pieza.
 *
 * @param {String} [scope] El alcance de la pieza y sus tarjetas (`scope` en PHP): el menú de usuario está en todas las
 * páginas y lleva el suyo, sin chocar con los de la página. Sin él, solo las piezas sin alcance.
 */
function ContentImageEditor(scope = '') {

	const instance = this
	const $editor = $(`[data-image-editor="${scope}"]`)
	const editor = $editor.get(0)
	const MIN_ZOOM = 10
	const MAX_ZOOM = 300
	const PREVIEW_MAX_WIDTH = 240
	const PREVIEW_MAX_HEIGHT = 150
	const NEW_FILE_CROP_AREA = 0.8
	const extensionsByFormat = {
		'image/png': 'png',
		'image/jpeg': 'jpg',
	}

	/** @type {Object.<string, function(File, string): void>} */
	const applyCallbacks = {}

	let cropper = null
	let card = null
	let trigger = null
	/** @type {String[]} */
	let imageURLs = []
	let base = 1
	let scaleX = 1
	let scaleY = 1
	let busy = false
	let openCropArea = NEW_FILE_CROP_AREA

	/**
	 * @param {String} cardId
	 * @param {function(File, string): void} callback El archivo recortado y una URL local para pintarlo.
	 * @returns {ContentImageEditor}
	 */
	this.onApply = function (cardId, callback) {
		applyCallbacks[cardId] = callback
		return instance
	}

	if (editor === undefined) {
		return
	}

	const stage = editor.querySelector('[data-image-editor-stage]')
	const preview = editor.querySelector('[data-image-editor-preview]')
	const fileInput = editor.querySelector('[data-image-editor-file]')
	const $zoom = $editor.find('[data-image-editor-zoom]')
	const $apply = $editor.find('.approve.button')
	const text = (name) => editor.getAttribute(`data-text-${name}`)
	const sizeOf = (element) => `${element.dataset.width} × ${element.dataset.height} px`

	/**
	 * El zoom de Cropper manda: el deslizador lo refleja sin volver a llamarlo.
	 */
	const syncZoom = function () {
		if (cropper === null) {
			return
		}
		const imageData = cropper.getImageData()
		const value = Math.round(imageData.width / imageData.naturalWidth / base * 100)
		$zoom.slider('set value', Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, value)), false)
	}

	/**
	 * La caja del resultado, con la proporción de la tarjeta y sin pasar de su tamaño fijo.
	 */
	const fitPreview = function () {
		const ratio = Number(card.dataset.width) / Number(card.dataset.height)
		const width = Math.min(PREVIEW_MAX_WIDTH, PREVIEW_MAX_HEIGHT * ratio)
		preview.style.width = `${Math.round(width)}px`
		preview.style.height = `${Math.round(width / ratio)}px`
	}

	/**
	 * @param {Number} cropArea Lo que ocupa el recuadro: una imagen ya recortada se abre entera, y Aplicar no la acerca.
	 */
	const buildCropper = function (cropArea) {
		scaleX = 1
		scaleY = 1
		cropper = new Cropper(stage.querySelector('img'), {
			aspectRatio: Number(card.dataset.width) / Number(card.dataset.height),
			viewMode: 1,
			dragMode: 'move',
			autoCropArea: cropArea,
			cropBoxResizable: false,
			cropBoxMovable: false,
			toggleDragModeOnDblclick: false,
			background: false,
			guides: true,
			center: true,
			preview: preview,
			ready: () => {
				const imageData = cropper.getImageData()
				base = imageData.width / imageData.naturalWidth
				syncZoom()
			},
			zoom: () => setTimeout(syncZoom, 0),
		})
	}

	const destroyCropper = function () {
		if (cropper !== null) {
			cropper.destroy()
			cropper = null
		}
	}

	//Al abrir otra, nunca al cerrar: al destruirse, Cropper vuelve a poner la URL original de un `replace()`, y la pide después.
	const releaseImageURLs = function () {
		imageURLs.forEach((url) => URL.revokeObjectURL(url))
		imageURLs = []
	}

	/**
	 * Un archivo que el navegador no decodifica no llega al editor.
	 * @param {File} file
	 * @returns {Promise<String>} La URL local de la imagen.
	 */
	const decode = function (file) {
		return new Promise((resolve, reject) => {
			const url = URL.createObjectURL(file)
			const image = new Image()
			image.onload = () => {
				if (image.naturalWidth > 0) {
					resolve(url)
				} else {
					URL.revokeObjectURL(url)
					reject(new Error('unreadable'))
				}
			}
			image.onerror = () => {
				URL.revokeObjectURL(url)
				reject(new Error('unreadable'))
			}
			image.src = url
		})
	}

	const showEditor = function (url, element, button) {
		card = element
		trigger = button
		editor.querySelector('[data-image-editor-name]').textContent = card.dataset.title
		editor.querySelector('[data-image-editor-size]').textContent = sizeOf(card)
		editor.querySelector('[data-image-editor-note]').textContent = text('export').replace('${size}', sizeOf(card))
		fileInput.accept = card.dataset.accept
		fitPreview()
		const image = document.createElement('img')
		image.alt = ''
		image.src = url
		stage.replaceChildren(image)
		$editor.modal('show')
	}

	const openWith = function (file, element, button) {
		decode(file).then((url) => {
			releaseImageURLs()
			imageURLs.push(url)
			openCropArea = NEW_FILE_CROP_AREA
			showEditor(url, element, button)
		}).catch(() => errorMessage(text('unreadable'), text('unreadable-detail')))
	}

	//La URL es de la tarjeta: se queda en su vista previa y nunca se revoca.
	const openCurrent = function (image, element, button) {
		releaseImageURLs()
		openCropArea = 1
		showEditor(image.src, element, button)
	}

	const updateCard = function (file, url) {
		const holder = card.querySelector('.checkered.image')
		let image = holder.querySelector('img')
		if (image === null) {
			const edit = document.createElement('button')
			edit.type = 'button'
			edit.className = 'content-image-card-edit'
			edit.setAttribute('aria-label', card.dataset.editLabel)
			edit.dataset.imageCardEdit = ''
			image = document.createElement('img')
			image.className = 'ui small image'
			image.alt = card.dataset.title
			edit.appendChild(image)
			holder.appendChild(edit)
		}
		image.src = url
		card.querySelector('input[type=file]').required = false
		let download = card.querySelector('[data-image-card-download]')
		if (download === null) {
			const template = card.querySelector('[data-image-card-download-template]')
			download = template.content.firstElementChild.cloneNode(true)
			template.replaceWith(download)
		}
		download.href = url
		download.setAttribute('download', file.name)
		card.dispatchEvent(new CustomEvent('image-card-replaced'))
	}

	const finishApply = function () {
		busy = false
		$apply.removeClass('loading disabled')
	}

	const send = function (file, url) {
		const formData = new FormData()
		formData.set(card.dataset.field, file)
		postRequest(card.dataset.action, formData).done((response) => {
			finishApply()
			if (response.success) {
				updateCard(file, url)
				$editor.modal('hide')
				successMessage(text('replaced'), text('replaced-detail'))
			} else {
				errorMessage(text('error'), response.message)
			}
		}).fail(() => {
			finishApply()
			errorMessage(text('error'), text('error-detail'))
		})
	}

	const apply = function () {
		if (busy || cropper === null) {
			return false
		}
		busy = true
		$apply.addClass('loading disabled')
		const format = card.dataset.format
		const canvas = cropper.getCroppedCanvas({
			width: Number(card.dataset.width),
			height: Number(card.dataset.height),
			fillColor: card.dataset.fillColor,
			imageSmoothingQuality: 'high',
		})
		canvas.toBlob((blob) => {
			if (blob === null) {
				finishApply()
				errorMessage(text('error'), text('error-detail'))
				return
			}
			const file = new File([blob], `${card.dataset.field}.${extensionsByFormat[format]}`, { type: format })
			const url = URL.createObjectURL(file)
			if (card.dataset.action !== '') {
				send(file, url)
			} else {
				finishApply()
				updateCard(file, url)
				$editor.modal('hide')
				if (typeof applyCallbacks[card.id] == 'function') {
					applyCallbacks[card.id](file, url)
				}
			}
		}, format, Number(card.dataset.quality))
		//El modal lo cierra quien termina: con un fallo del servidor se queda abierto.
		return false
	}

	const actions = {
		'zoom-in': () => cropper.zoom(0.1),
		'zoom-out': () => cropper.zoom(-0.1),
		'rotate-left': () => cropper.rotate(-90),
		'rotate-right': () => cropper.rotate(90),
		'flip-horizontal': () => {
			scaleX = -scaleX
			cropper.scaleX(scaleX)
		},
		'flip-vertical': () => {
			scaleY = -scaleY
			cropper.scaleY(scaleY)
		},
		'reset': () => {
			scaleX = 1
			scaleY = 1
			cropper.reset()
			syncZoom()
		},
	}

	$zoom.slider({
		min: MIN_ZOOM,
		max: MAX_ZOOM,
		step: 1,
		start: 100,
		onMove: (value) => {
			if (cropper !== null) {
				cropper.zoomTo(base * value / 100)
			}
		},
	})

	$editor.modal({
		//Cropper mide su caja: en un modal aún oculto mediría cero.
		onVisible: () => buildCropper(openCropArea),
		onHide: () => !busy,
		onApprove: apply,
		onHidden: () => {
			destroyCropper()
			stage.replaceChildren()
			if (trigger !== null) {
				trigger.focus()
			}
		},
	})

	$editor.on('click', '[data-image-editor-action]', function () {
		if (cropper !== null) {
			actions[this.dataset.imageEditorAction]()
		}
	})

	$editor.on('click', '[data-image-editor-other]', () => fileInput.click())

	fileInput.addEventListener('change', () => {
		const file = fileInput.files[0]
		fileInput.value = ''
		if (file === undefined) {
			return
		}
		decode(file).then((url) => {
			imageURLs.push(url)
			scaleX = 1
			scaleY = 1
			cropper.replace(url)
		}).catch(() => errorMessage(text('unreadable'), text('unreadable-detail')))
	})

	document.querySelectorAll(`[data-image-card="${scope}"]`).forEach((element) => {
		const input = element.querySelector('input[type=file]')
		const button = element.querySelector('[data-input]')
		button.addEventListener('click', () => input.click())
		//En la tarjeta y no en el botón: el de una tarjeta vacía nace al aplicar.
		element.addEventListener('click', (event) => {
			const edit = event.target.closest('[data-image-card-edit]')
			if (edit !== null) {
				openCurrent(edit.querySelector('img'), element, edit)
			}
		})
		input.addEventListener('change', () => {
			const file = input.files[0]
			input.value = ''
			if (file !== undefined) {
				openWith(file, element, button)
			}
		})
	})

}
