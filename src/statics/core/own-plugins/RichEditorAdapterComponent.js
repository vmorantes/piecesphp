/// <reference path="../../core/js/helpers.js" />
/**
 * @param {RichEditorAdapterOptions} adapterOptions 
 * @param {Array} [toolbar=null] 
 * @param {Boolean} [silentError=true]
 */
function RichEditorAdapterComponent(adapterOptions = {}, toolbar = null, silentError = true) {
	//──── Types ─────────────────────────────────────────────────────────────────────────────
	/**
	 * @typedef RichEditorAdapterOptions
	 * @property {String} [containerSelector=[rich-editor-adapter-component]]
	 * @property {String} [textareaTargetSelector=[rich-editor-adapter-component] + textarea[target]]
	 * @property {String} [fileManagerConfigURL]
	 * @property {String} [fileManagerUIURL]
	 * @property {String} [fileManagerBaseURLStatics]
	 * @property {Function} [onChange]
	 */
	adapterOptions;

	//──── Variables ─────────────────────────────────────────────────────────────────────────
	/**
	 * @property {RichEditorAdapterComponent}
	 */
	let instance = this;

	/**
	 * @property {HTMLElement}
	 */
	let eventer = document.createElement('div')

	/**
	 * @property {String}
	 */
	let langGroup = 'richEditor'
	let langGroupFileManager = 'filemanager-lang'

	RichEditorAdapterComponent.registerDynamicMessages(langGroup)
	registerDynamicLocalizationMessages(langGroupFileManager)

	//──── Misc. vars ──────────────────────────────────────────────────────────────────────

	/**
	 * @property {Array}
	 */
	let toolbarDefault = {
		items: [
			'heading',
			'|',
			'undo',
			'redo',
			'|',
			'fontBackgroundColor',
			'fontColor',
			'fontFamily',
			'|',
			'bold',
			'italic',
			'underline',
			'strikethrough',
			'link',
			'removeFormat',
			'|',
			'alignment',
			'blockQuote',
			'bulletedList',
			'numberedList',
			'outdent',
			'indent',
			'|',
			'CKFinder',
			'imageUpload',
			'mediaEmbed',
			'insertTable',
			'|',
			'superscript',
			'subscript',
			'htmlEmbed',
			'horizontalLine',
			//'codeBlock'
		]
	}

	/**
	 * @property {String}
	 */
	let containerSelector = '[rich-editor-adapter-component]'

	/**
	 * @property {Function}
	 */
	let onChange = () => { }
	/**
	 * @property {String}
	 */
	let textareaTargetSelector = `${containerSelector} + textarea[target]`

	/**
	 * Modal de selector de archivos
	 * @property {$|null} 
	 */
	let fileManagerModal = null

	/**
	 * @property {String}
	 */
	let fileManagerConfigURL = ''

	/**
	 * @property {String}
	 */
	let fileManagerUIURL = ''

	/**
	 * @property {String}
	 */
	let fileManagerBaseURLStatics = ''

	/**
	 * @property {Object}
	 */
	let fileManager = null

	/**
	 * @property {String}
	 */
	let uploadTargetHash = 'l1_Lw'

	/**
	 * Esto se usa para saber si ya se puede insertar la imagen seleccionada desde el explorador de imágenes
	 * @property {Boolean}
	 */
	let verifyImgCmd = false

	/**
	 * @property {String}
	 */
	let idComponent = 'componentID_' + generateUniqueID()

	//──── Components vars ───────────────────────────────────────────────────────────────────

	/**
	 * @property {Array}
	 */
	let toolbarOptions = {}

	/**
	 * @property {Quill}
	 */
	let editorInstance = null

	/**
	 * @property {$}
	 */
	let component = null

	/**
	 * @property {$}
	 */
	let textareaTarget = null

	//──── Methods ───────────────────────────────────────────────────────────────────────────

	this.on = function (event, callback) {
		eventer.addEventListener(event, callback)
	}

	this.off = function (event, callback) {
		eventer.removeEventListener(event, callback)
	}

	//──── Functions ─────────────────────────────────────────────────────────────────────────

	/**
	 * @function instantiate
	 * @param {RichEditorAdapterOptions} adapterOptions 
	 */
	function instantiate(adapterOptions) {

		showGenericLoader('RichEditorAdapterComponent')

		try {

			configs(adapterOptions)

			toolbarOptions = toolbar !== null && typeof toolbar == 'object' && Array.isArray(toolbar.items) ? toolbar : toolbarDefault
			component = $(containerSelector)
			textareaTarget = $(textareaTargetSelector)

			//Verificar que el selector no esté en uso por otra instancia del Editor
			if (textareaTarget.length < 1) {
				throw new Error(_i18n(langGroup, 'Falta(n) el componente o el textarea en el DOM.'))
			} else {
				if (RichEditorAdapterComponent.componentsSelectors.indexOf(containerSelector) === -1 && RichEditorAdapterComponent.componentsSelectors.indexOf(textareaTargetSelector) === -1) {
					RichEditorAdapterComponent.componentsSelectors.push(containerSelector)
					RichEditorAdapterComponent.componentsSelectors.push(textareaTargetSelector)
				} else {
					throw new Error(formatStr(
						_i18n(langGroup, 'El componente "%r" ya está en uso.'),
						[
							containerSelector,
						]
					))
				}
			}

			instantiateEditor()

			removeGenericLoader('RichEditorAdapterComponent')

		} catch (error) {

			if (silentError !== true) {
				if (typeof errorMessage == 'function') {
					errorMessage(_i18n(langGroup, 'Error en RichEditorAdapterComponent'), _i18n(langGroup, 'Ha ocurrido un error al instanciar.'))
				} else {
					alert(_i18n(langGroup, 'Ha ocurrido un error al instanciar RichEditorAdapterComponent.'))
				}
			}

			console.error(error)
		}

	}

	/**
	 * Instancia el editor de texto
	 */
	function instantiateEditor() {

		component.remove()
		textareaTarget.attr('id-component-rich-editor', idComponent)

		ClassicEditor
			.create(document.querySelector(`[id-component-rich-editor="${idComponent}"]`), {
				toolbar: toolbarOptions,
				language: typeof pcsphpGlobals !== 'undefined' ? pcsphpGlobals.lang : 'es',
				image: {
					toolbar: [
						'imageTextAlternative',
						'imageStyle:alignLeft',
						'imageStyle:alignCenter',
						'imageStyle:alignRight',
						'imageStyle:full',
						'linkImage',
						'resizeImage:original',
					],
					resizeUnit: 'px',
					resizeOptions: [
						{
							name: 'resizeImage:original',
							value: null,
							icon: 'original'
						},
					],
					styles: [
						'full',
						'alignLeft',
						'alignCenter',
						'alignRight',
					],
				},
				table: {
					contentToolbar: [
						'tableColumn',
						'tableRow',
						'mergeTableCells',
						'tableCellProperties',
						'tableProperties'
					]
				},
				mediaEmbed: {
					previewsInData: true,
				},
				licenseKey: '',
			})
			.then(editor => {
				editorInstance = editor
				editorInstance.model.document.on('change:data', () => {
					textareaTarget.val(editorInstance.getData())
					onChange(instance, editorInstance.getData())
				})

				const CKFinder = editor.commands.get('ckfinder')
				const fileRepository = editorInstance.plugins.get('FileRepository')

				textareaTarget.get(0).updateRichEditor = function (value) {
					editorInstance.setData(value)
				}

				textareaTarget.get(0).getRichEditorData = function () {
					return editorInstance.getData()
				}

				textareaTarget.get(0).onChangeRichEditor = function (callback) {
					editorInstance.model.document.on('change:data', () => {
						callback(instance, editorInstance.getData())
					})
				}

				CKFinder.execute = () => {
					explorerHandler(uploadTargetHash)
				}
				fileRepository.createUploadAdapter = loader => {
					return new UploadAdapter(loader)
				}

				eventer.dispatchEvent(new Event(RichEditorAdapterComponent.events.instanceReady))

			})
			.catch(error => {
				console.error('Oops, something went wrong!');
				console.error('Please, report the following error on https://github.com/ckeditor/ckeditor5/issues with the build id and the error stack trace:');
				console.warn('Build id: jphltcuj0rzv-bt20ajtolrmd');
				console.error(error);
			})

	}

	/**
	 * @function configs
	 * @param {RichEditorAdapterOptions} adapterOptions 
	 */
	function configs(adapterOptions) {

		if (typeof adapterOptions.onChange == 'function') {
			onChange = adapterOptions.onChange
		}

		if (typeof adapterOptions.containerSelector == 'string' && adapterOptions.containerSelector.length > 0) {
			containerSelector = adapterOptions.containerSelector
		}

		if (typeof adapterOptions.textareaTargetSelector == 'string' && adapterOptions.textareaTargetSelector.length > 0) {
			textareaTargetSelector = adapterOptions.textareaTargetSelector
		}

		if (typeof adapterOptions.fileManagerConfigURL == 'string' && adapterOptions.fileManagerConfigURL.length > 0) {
			fileManagerConfigURL = adapterOptions.fileManagerConfigURL
		} else {
			const relativeAdminPath = pcsphpGlobals.adminURLConfig.url.length > 0 ? `${pcsphpGlobals.adminURLConfig.url}/` : ''
			fileManagerConfigURL = `${relativeAdminPath}filemanager/rich-editor/configuration/`
		}

		if (typeof adapterOptions.fileManagerUIURL == 'string' && adapterOptions.fileManagerUIURL.length > 0) {
			fileManagerUIURL = adapterOptions.fileManagerUIURL
		} else {
			const relativeAdminPath = pcsphpGlobals.adminURLConfig.url.length > 0 ? `${pcsphpGlobals.adminURLConfig.url}/` : ''
			fileManagerUIURL = `${relativeAdminPath}filemanager/rich-editor/`
		}

		if (typeof adapterOptions.fileManagerBaseURLStatics == 'string' && adapterOptions.fileManagerBaseURLStatics.length > 0) {
			fileManagerBaseURLStatics = adapterOptions.fileManagerBaseURLStatics
		} else {
			fileManagerBaseURLStatics = `${pcsphpGlobals.baseURL.replace(/\/$/, '')}/statics/plugins/elfinder`
		}

	}

	/**
	 * Abre el explorador de archivos y maneja las inserciones
	 * @param {String} target
	 * @returns {Promise}
	 */
	function explorerHandler(target = null) {
		return new Promise((resolve) => {

			const explorerURL = new URL(fileManagerUIURL, pcsphpGlobals.baseURL)
			explorerURL.searchParams.set('target', target)

			fileManagerModalCreate(explorerURL.href)
			verifyImgCmd = false

			//Observar selección de imagen externa
			window.addEventListener('message', function (event) {
				if (typeof event.data.ckeditorSelection !== 'undefined' && event.data.ckeditorSelection) {
					const fileURL = event.data.fileURL
					const fileType = fileURL.split('.').pop()
					const imagesTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'ico', 'webp', 'svg']
					const isImage = imagesTypes.includes(fileType.toLowerCase())
					if (isImage) {
						insertImages(fileURL)
					} else {
						insertLink(fileURL)
					}
					fileManagerModalDestroy()
				}
			}, false)

			resolve()

		})
	}

	/**
	 * Crea el modal del explorador de archivos
	 * @param {String} url 
	 */
	function fileManagerModalCreate(url) {
		const $modal = $(`<div id="fileManagerModal" class="ui modal">
			<div class="content">
				<iframe id="fileManagerIframe" src="${url}" style="width: 100%; height: 500px; border: none;"></iframe>
			</div>
		</div>`)
		$('body').append($modal)
		$modal.modal({
			closable: true,
			onHidden: () => { }
		}).modal('show')
		fileManagerModal = $modal
	}

	/**
	 * Destruye el modal del explorador de archivos
	 * @param {String} url 
	 */
	function fileManagerModalDestroy() {
		if (fileManagerModal !== null) {
			fileManagerModal.modal('hide')
			fileManagerModal.remove()
		}
	}

	/**
	 * @param {Object} loader 
	 */
	function UploadAdapter(loader) {

		let upload = function (file, resolve, reject) {

			const formUploadData = new FormData()

			const uploadURL = new URL(fileManagerConfigURL, pcsphpGlobals.baseURL)
			uploadURL.searchParams.set('cmd', 'upload')
			uploadURL.searchParams.set('target', uploadTargetHash)
			uploadURL.searchParams.set('overwrite', 0)
			formUploadData.append('upload[]', file)

			const handleError = function (errorData) {
				const isString = typeof errorData == 'string'
				if (!isString) {
					let responseJSON = typeof errorData.responseJSON == 'object' ? errorData.responseJSON : errorData
					responseJSON = typeof responseJSON == 'object' ? responseJSON : {}
					const message = typeof responseJSON == 'object' && typeof responseJSON.message == 'string' ? responseJSON.message : JSON.stringify(errorData)
					errorMessage(message)
				} else {
					errorMessage(errorData)
				}
				reject()
			}

			postRequest(uploadURL, formUploadData)
				.done(data => {
					if (Array.isArray(data.added) && data.added.length > 0) {
						const uploadedElement = data.added[0]
						const uploadedElementHash = uploadedElement.hash
						const uploadedElementURL = uploadedElement.url
						const fileURL = uploadedElementURL.replace(pcsphpGlobals.baseURL, '').replace(/(^\/|\/$)/g, '')
						resolve({
							'default': fileURL,
						})
					} else {
						let errorStr = []
						let error = Array.isArray(data.error) ? data.error : data
						for (const str of error) {
							if (typeof str == 'string' && str.length > 0) {
								errorStr.push(_i18n(langGroupFileManager, str))
							}
						}
						errorStr = errorStr.join("<br>")
						error = errorStr.length > 0 ? errorStr : error
						handleError(error)
					}
				})
				.fail((errorData) => {
					handleError(errorData)
				})

		}

		this.upload = function () {

			return new Promise(function (resolve, reject) {

				if (loader.file instanceof Promise || (loader.file && typeof loader.file.then === 'function')) {

					loader.file.then(function (file) {
						upload(file, resolve, reject)
					})

				} else {
					upload(loader.file, resolve, reject)
				}

			})

		}

		this.abort = function () {
			fileManagerModalDestroy()
		}

	}

	/**
	 * @param {String[]|String} url 
	 * @returns {void}
	 */
	function insertImages(url) {

		const imgCmd = editorInstance.commands.get('imageUpload')

		if (verifyImgCmd === true) {

			if (!imgCmd.isEnabled) {
				editorInstance.execute('imageInsert', { source: url })
			} else {
				errorMessage(_i18n(langGroup, 'Error'), _i18n(langGroup, 'No se puede insertar la imagen en esa posición.'))
			}

		} else {
			editorInstance.execute('imageInsert', { source: url })
		}

	}

	/**
	 * @param {String} url 
	 * @param {Object} attributes 
	 */
	function insertLink(url, attributes = {}) {
		let linkName = decodeURIComponent(url).split('/').pop()
		linkName = typeof linkName == 'string' && linkName.length > 0 ? linkName : url
		editorInstance.model.enqueueChange('transparent', writer => {
			const textNode = writer.createText(linkName, {
				...attributes,
				linkHref: url
			})
			const position = editorInstance.model.document.selection.getFirstPosition()
			editorInstance.model.insertContent(textNode, position)
		})
	}

	instantiate(adapterOptions)

	return instance
}

RichEditorAdapterComponent.events = {
	instanceReady: 'instanceReady',
}

RichEditorAdapterComponent.componentsSelectors = []

/**
* @param {String} name 
* @returns {void}
*/
RichEditorAdapterComponent.registerDynamicMessages = function (name) {

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
		'Falta(n) el componente o el textarea en el DOM.': 'The component or textarea is missing in the DOM.',
		'Error en RichEditorAdapterComponent': 'Error in RichEditorAdapterComponent',
		'Ha ocurrido un error al instanciar.': 'An error occurred while instantiating.',
		'Ha ocurrido un error al instanciar RichEditorAdapterComponent.': 'An error occurred while instantiating RichEditorAdapterComponent.',
		'El componente "%r" ya está en uso.': 'The component "%r" is already in use.',
		'Error': 'Error',
		'No se puede insertar la imagen en esa posición.': 'You cannot insert the image in that position.',
		'Debe seleccionar una imagen': 'You must select an image',
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
