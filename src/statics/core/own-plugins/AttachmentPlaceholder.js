/// <reference path="./SimpleCropperAdapter.js" />
/**
 * @function AttachmentPlaceholder
 *  
 * @param {$} component
 */
function AttachmentPlaceholder(attachContainer) {

	const LANG_GROUP = 'AttachmentPlaceholder'

	AttachmentPlaceholder.registerDynamicMessages(LANG_GROUP)

	const instance = this
	let attachLabelElement = null
	let imagePreviewContainer = null
	let imageSetted = null
	let fileNoImageSetted = null
	let iconPlaceholderUpload = null
	let imagePreviewCaption = null
	let imagePreviewCaptionBaseText = null
	let onChangeText = null
	let textContainer = null
	let filenameContainer = null
	let inputFile = null
	let inputName = null
	let selectedFile = null
	let wasChange = false
	let id = null

	/**
	 * @param {AttachmentPlaceholder} instance
	 * @param {AttachmentPlaceholderElements} elements
	 * @param {Event} event
	 */
	let onClickCallback = (instance, elements, event) => { }
	/**
	 * @param {AttachmentPlaceholder} instance
	 * @param {File|null} selectedFile
	 */
	let onSelectedCallback = (instance, selectedFile) => { }

	/**
	 * Dispara inmediatamente el callback, se puede aprovechar para centralizar configuraciones en un ámbito reducido
	 * @param {{(instance:AttachmentPlaceholder, elements:AttachmentPlaceholderElements)}} callback 
	 * @returns {AttachmentPlaceholder}
	 */
	this.scopeAction = function (callback) {
		if (typeof callback == 'function') {
			callback(instance, instance.getElements())
		}
		return instance
	}

	/**
	 * @param {{(instance:AttachmentPlaceholder, elements:AttachmentPlaceholderElements, event:Event)}} callback 
	 * @returns {AttachmentPlaceholder}
	 */
	this.onClick = function (callback) {
		if (typeof callback == 'function') {
			onClickCallback = callback
		}
		return instance
	}

	/**
	 * @param {{(instance:AttachmentPlaceholder, selectedFile:File|null)}} callback 
	 * @returns {AttachmentPlaceholder}
	 */
	this.onSelected = function (callback) {
		if (typeof callback == 'function') {
			onSelectedCallback = callback
		}
		return instance
	}

	/**
	 * @returns {File|null}
	 */
	this.getSelectedFile = function () {
		return selectedFile
	}

	/**
	 * @returns {String}
	 */
	this.getName = function () {
		const nameOnInput = inputName.val()
		const nameIsValid = typeof nameOnInput == 'string' ? nameOnInput.trim().length > 0 : false
		if (!nameIsValid) {
			return inputName.attr('data-file-name')
		} else {
			return inputName.val()
		}
	}

	/**
	 * @returns {AttachmentPlaceholderElements}
	 */
	this.getElements = function () {
		return {
			attachContainer: attachContainer,
			attachLabelElement: attachLabelElement,
			imagePreviewContainer: imagePreviewContainer,
			imageSetted: imageSetted,
			fileNoImageSetted: fileNoImageSetted,
			imagePreviewCaption: imagePreviewCaption,
			imagePreviewCaptionBaseText: imagePreviewCaptionBaseText,
			textContainer: textContainer,
			filenameContainer: filenameContainer,
			inputFile: inputFile,
			inputName: inputName,
			selectedFile: selectedFile,
		}
	}

	/**
	 * @returns {Boolean}
	 */
	this.wasChange = function () {
		return wasChange
	}

	/**
	 * @param {File} file
	 * @returns {AttachmentPlaceholderElements}
	 */
	this.setFile = function (file) {

		if (!(file instanceof File)) {
			return instance
		}

		const loaderAttachmentName = generateUniqueID()
		const fileReader = new FileReader()
		let imagePreview = imagePreviewContainer.find('img')

		showGenericLoader(loaderAttachmentName)

		if (imagePreview.length == 0) {
			iconPlaceholderUpload.replaceWith($('<img src=""/>'))
			imagePreview = imagePreviewContainer.find('img')
		}

		fileReader.readAsArrayBuffer(file)

		fileReader.onloadend = function () {
			const mimeType = file.type
			const fileName = file.name
			const blob = new Blob([fileReader.result], {
				type: mimeType,
			})
			filenameContainer.html(fileName)
			imagePreview.attr('src', mimeType.indexOf('image/') != -1 ? URL.createObjectURL(blob) : 'statics/images/attachment-placeholder.png')
			imagePreview.on('load', function () {
				imagePreviewCaption.html(onChangeText)
				removeGenericLoader(loaderAttachmentName)
			})
			imagePreview.on('error', function () {
				removeGenericLoader(loaderAttachmentName)
			})
			selectedFile = file
			if (!attachContainer.hasClass('attached')) {
				attachContainer.addClass('attached')
			}
			inputFile.removeAttr('required')
			inputName.attr('data-file-name', fileName.split('.')[0])
			if (typeof inputName.val() == 'string' && inputName.val().trim().length == 0) {
				inputName.val(fileName.split('.')[0])
			}
			wasChange = true
			onSelectedCallback(instance, selectedFile)
		}

		fileReader.onerror = function () {
			removeGenericLoader(loaderAttachmentName)
		}

	}

	/**
	 * @param {String} name 
	 * @returns {AttachmentPlaceholderElements}
	 */
	this.setName = function (name) {
		inputName.val(name)
		return this
	}

	//──── Inicialización ────────────────────────────────────────────────────────────────────
	showGenericLoader('AttachmentPlaceholder')

	id = attachContainer.attr('data-attachment-component-id')

	if (typeof id != 'string' || id.trim().length == 0) {

		id = generateUniqueID()

		attachContainer.attr('data-attachment-component-id', id)
		attachLabelElement = attachContainer.find('label')
		imagePreviewContainer = attachLabelElement.find('>.image')
		iconPlaceholderUpload = imagePreviewContainer.find('i.icon')
		imageSetted = imagePreviewContainer.data('image')
		imageSetted = typeof imageSetted == 'string' && imageSetted.trim().length > 0 ? imageSetted.trim() : null
		fileNoImageSetted = imagePreviewContainer.data('file')
		fileNoImageSetted = typeof fileNoImageSetted == 'string' && fileNoImageSetted.trim().length > 0 ? fileNoImageSetted.trim() : null
		imagePreviewCaption = imagePreviewContainer.find('.caption')
		imagePreviewCaptionBaseText = imagePreviewCaption.html()
		onChangeText = imagePreviewContainer.attr('data-on-change-text')
		textContainer = attachLabelElement.find('>.text')
		filenameContainer = textContainer.find('>.filename')
		inputFile = attachContainer.find('input[type="file"]')
		inputName = attachContainer.find('input[attachment-name]')

		if (imageSetted !== null) {
			const initialImageLoaderName = generateUniqueID('initial_image')
			const urlSegments = imageSetted.split('/')
			const filename = urlSegments[urlSegments.length - 1]
			let mimeType = ''
			showGenericLoader(initialImageLoaderName)

			if (!attachContainer.hasClass('attached')) {
				attachContainer.addClass('attached')
			}
			imagePreviewCaption.html(onChangeText)

			fetch(imageSetted).then(function (response) {
				mimeType = response.headers.get("content-type")
				return response.blob()
			}).then(function (blob) {
				iconPlaceholderUpload.replaceWith($(`<img src="${imageSetted}"/>`))
				selectedFile = new File([blob], filename, { type: mimeType })
			}).finally(function () {
				removeGenericLoader(initialImageLoaderName)
			})
		} else if (fileNoImageSetted !== null) {
			const initialFileNoImageLoaderName = generateUniqueID('initial_file')
			const urlSegments = fileNoImageSetted.split('/')
			const filename = urlSegments[urlSegments.length - 1]
			let mimeType = ''
			showGenericLoader(initialFileNoImageLoaderName)

			if (!attachContainer.hasClass('attached')) {
				attachContainer.addClass('attached')
			}
			imagePreviewCaption.html(onChangeText)

			fetch(fileNoImageSetted).then(function (response) {
				mimeType = response.headers.get("content-type")
				return response.blob()
			}).then(function (blob) {
				filenameContainer.html(`<a target="_blank" href="${fileNoImageSetted}" see-file><i class="external alternate icon"></i></a>`)
				if (mimeType.indexOf('image/') != -1) {
					iconPlaceholderUpload.replaceWith($(`<img src="${fileNoImageSetted}"/>`))
				} else {
					iconPlaceholderUpload.replaceWith($(`<img src='statics/images/attachment-placeholder.png'/>`))
				}
				selectedFile = new File([blob], filename, { type: mimeType })
			}).finally(function () {
				removeGenericLoader(initialFileNoImageLoaderName)
			})
		}

		inputFile.on('change', function (e) {
			selectedFile = null
			imagePreviewCaption.html(imagePreviewCaptionBaseText)
			if (e.target.files.length > 0) {
				instance.setFile(e.target.files[0])
			}
		})

		attachLabelElement.on('click', function (e) {
			onClickCallback(instance, instance.getElements(), e)
		})

	}

	removeGenericLoader('AttachmentPlaceholder')

	return this
}
/**
 * @param {String} name 
 * @returns {void}
 */
AttachmentPlaceholder.registerDynamicMessages = function (name) {
	registerDynamicLocalizationMessages(name)
}
/**
 * @typedef AttachmentPlaceholderElements
 * @property {$|null} attachContainer Elemento principal
 * @property {$|null} attachLabelElement Label, contiene el resto de cosas
 * @property {$|null} imagePreviewContainer Contenedor de vista previa (columna izquierda)
 * @property {String|null} imageSetted Imagen inicial
 * @property {String|null} fileNoImageSetted Archivo (no imagen) inicial
 * @property {$|null} imagePreviewCaption Contenedor donde se muestra el texto de vista previa
 * @property {String|null} imagePreviewCaptionBaseText Texto base del segmendo de vista previa (la columna izquierda)
 * @property {$|null} textContainer Contenedor principal de los textos (la columna derecha)
 * @property {$|null} filenameContainer Contenedor donde se muestra el nombre del archivo cargado
 * @property {$|null} inputFile input:file
 * @property {$|null} inputName input:text
 * @property {File|null} selectedFile Último archivo seleccionado
 */
