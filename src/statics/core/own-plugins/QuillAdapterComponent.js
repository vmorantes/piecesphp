/// <reference path="../../core/js/helpers.js" />
/**
 * @function QuillAdapterComponent
 * 
 * @param {QuillAdapterOptions} quillAdapterOptions 
 * @param {Array} [toolbar=null] 
 * @param {Boolean} [silentError=true] 
 */
function QuillAdapterComponent(quillAdapterOptions = {}, toolbar = null, silentError = true) {

	const LANG_GROUP = 'quill'

	QuillAdapterComponent.registerDynamicMessages(LANG_GROUP)

	//──── Types ─────────────────────────────────────────────────────────────────────────────
	/**
	 * @typedef QuillAdapterOptions
	 * @property {String} [containerSelector=[quill-adapter-component]]
	 * @property {String} [textareaTargetSelector=[quill-adapter-component] + textarea[target]]
	 * @property {String} [urlProcessImage]
	 * @property {String} [nameOnRequest]
	 */
	quillAdapterOptions;

	//──── Variables ─────────────────────────────────────────────────────────────────────────
	/**
	 * @property {QuillAdapterComponent} instance
	 */
	let instance = this;

	//──── Misc. vars ──────────────────────────────────────────────────────────────────────

	/**
	 * @property {Array} toolbarDefault
	 */
	let toolbarDefault = [
		[
			'bold',
			'italic',
			'underline',
			'strike',
			'link',
		],
		[
			{
				'list': 'ordered'
			},
			{
				'list': 'bullet'
			},
			'blockquote'
		],
		[
			{
				'header': [
					1,
					2,
					3,
					4,
					5,
					6,
					false
				]
			},
		],
		[
			{
				'script': 'sub'
			},
			{
				'script': 'super'
			}
		],
		[
			{
				'color': []
			}, {
				'background': []
			}
		],
		[
			{
				'align': []
			}
		],
		[
			'image',
			'video',
			'clean',
			'show-source',
		]
	]

	/**
	 * @property {String} containerSelector
	 */
	let containerSelector = '[quill-adapter-component]'

	/**
	 * @property {String} textareaTargetSelector
	 */
	let textareaTargetSelector = `${containerSelector} + textarea[target]`

	/**
	 * @property {String} urlProcessImage
	 */
	let urlProcessImage = ''

	/**
	 * @property {String} nameOnRequest
	 */
	let nameOnRequest = ''

	//──── Components vars ───────────────────────────────────────────────────────────────────

	/**
	 * @property {Array} toolbarOptions
	 */
	let toolbarOptions = []

	/**
	 * @property {Quill} quillInstance
	 */
	let quillInstance = null

	/**
	 * @property {$} component
	 */
	let component = null

	/**
	 * @property {$} textareaTarget
	 */
	let textareaTarget = null

	//──── Methods ───────────────────────────────────────────────────────────────────────────


	//──── Functions ─────────────────────────────────────────────────────────────────────────

	/**
	 * @function instantiate
	 * @param {QuillAdapterOptions} quillAdapterOptions 
	 */
	function instantiate(quillAdapterOptions) {

		showGenericLoader('QuillAdapterComponent')

		try {

			configs(quillAdapterOptions)

			toolbarOptions = Array.isArray(toolbar) ? toolbar : toolbarDefault
			component = $(containerSelector)
			textareaTarget = $(textareaTargetSelector)

			//Ocultar textarea
			textareaTarget.css({
				height: '0px',
				minHeight: '0px',
				maxHeight: '0px',
				outline: 'none',
				cursor: 'default',
				width: '0px',
				opacity: '0',
			})

			//Verificar que el selector no esté en uso por otra instancia de Quill
			if (component.length !== 1 || textareaTarget.length < 1) {
				throw new Error(_i18n(LANG_GROUP, 'Falta(n) el componente o el textarea en el DOM.'))
			} else {
				if (QuillAdapterComponent.componentsSelectors.indexOf(containerSelector) === -1) {
					QuillAdapterComponent.componentsSelectors.push(containerSelector)
				} else {
					throw new Error(formatStr(
						_i18n(LANG_GROUP, 'El componente "%r" ya está en uso.'),
						[
							containerSelector,
						]
					))
				}
			}

			instantiateQuill()

			removeGenericLoader('QuillAdapterComponent')

		} catch (error) {

			if (silentError !== true) {
				if (typeof errorMessage == 'function') {
					errorMessage(_i18n(LANG_GROUP, 'Error en QuillAdapterComponent'), _i18n(LANG_GROUP, 'Ha ocurrido un error al instanciar.'))
				} else {
					alert(_i18n(LANG_GROUP, 'Ha ocurrido un error al instanciar QuillAdapterComponent.'))
				}
			}

			console.error(error)
		}

	}

	/**
	 * @function configs
	 * @param {QuillAdapterOptions} quillAdapterOptions 
	 */
	function configs(quillAdapterOptions) {

		if (typeof quillAdapterOptions.containerSelector == 'string' && quillAdapterOptions.containerSelector.length > 0) {
			containerSelector = quillAdapterOptions.containerSelector
		}

		if (typeof quillAdapterOptions.textareaTargetSelector == 'string' && quillAdapterOptions.textareaTargetSelector.length > 0) {
			textareaTargetSelector = quillAdapterOptions.textareaTargetSelector
		}

		if (typeof quillAdapterOptions.urlProcessImage == 'string' && quillAdapterOptions.urlProcessImage.length > 0) {
			urlProcessImage = quillAdapterOptions.urlProcessImage
		}

		if (typeof quillAdapterOptions.nameOnRequest == 'string' && quillAdapterOptions.nameOnRequest.length > 0) {
			nameOnRequest = quillAdapterOptions.nameOnRequest
		}

		if (QuillAdapterComponent.initialized !== true) {

			Quill.debug('error')
			Quill.register('modules/imageUpload', QuillAdapterComponent.imageUploadModule)
			QuillAdapterComponent.BlotsConfig()
			QuillAdapterComponent.AttributtorsConfig()
			QuillAdapterComponent.initialized = true

		}

	}

	/**
	 * @function instantiateQuill
	 */
	function instantiateQuill() {

		quillInstance = new Quill(component.get(0), {
			theme: 'snow',
			modules: {
				toolbar: toolbarOptions,
				imageUpload: imageUploadModuleOptions(),
				imageResize: imageResizeModuleOptions(),
				videoResize: videoResizeModuleOptions(),
			}
		})

		let delta = quillInstance.clipboard.convert(textareaTarget.val())
		quillInstance.setContents(delta, 'silent')

		//Evento para rellenar el textarea
		quillInstance.on('editor-change', (delta, oldDelta, source) => {

			let html = component.get(0).children[0].innerHTML

			if (quillInstance.getText().trim().length > 0) {

				textareaTarget.val(html)

			} else {

				textareaTarget.val('')

			}

		})

		//Aplicar estilos de tamaño al contenedor editable
		let qlEditor = component.find('.ql-editor')
		qlEditor.css({
			minHeight: '500px',
			maxHeight: '500px',
		})

		let toolbarModule = quillInstance.getModule('toolbar')
		toolbarModule.addHandler('show-source', showSourceHandler(quillInstance, component))

	}

	/**
	 * @function imageUploadModuleOptions
	 */
	function imageUploadModuleOptions() {

		let imageUploadModule = {
			method: 'POST',
			callbackOK: onSuccess,
			callbackKO: onError,
			customUploader: false,
		}

		if (urlProcessImage.length > 0 && urlProcessImage.length > 0) {
			imageUploadModule.url = urlProcessImage
			imageUploadModule.name = nameOnRequest
		} else {
			imageUploadModule = {}
		}

		function onSuccess(serverResponse, next) {

			if (serverResponse.success) {

				next(serverResponse.values.path)

			}

		}

		function onError(serverError) {

			console.error(serverError)

			if (typeof errorMessage == 'function') {

				errorMessage(_i18n(LANG_GROUP, 'Error'), _i18n(LANG_GROUP, 'Ha ocurrido un error al carga la imagen.'))
			} else {

				alert(_i18n(LANG_GROUP, 'Ha ocurrido un error al cargar la imagen.'))

			}

		}

		return imageUploadModule
	}

	/**
	 * @function imageResizeModuleOptions
	 */
	function imageResizeModuleOptions() {

		let imageResizeModule = {}

		return imageResizeModule

	}

	/**
	 * @function videoResizeModuleOptions
	 */
	function videoResizeModuleOptions() {

		let videoResize = {}

		return videoResize

	}

	/**
	 * @function showSourceHandler
	 * @param {Quill} quillInstance 
	 * @param {$} component 
	 */
	function showSourceHandler(quillInstance, component) {

		let editor = component.get(0)
		let customButton = quillInstance.getModule('toolbar').container.querySelector('.ql-show-source')

		let textareaModal = null
		let textAreaSourceEditor = null
		let modalEditorExists = false

		customButton.innerHTML = `<i class="code icon"></i>`

		customButton.addEventListener('click', function () {

			if (!modalEditorExists) {

				textareaModal = getEditorHTML()
				textAreaSourceEditor = textareaModal.find('textarea')
				modalEditorExists = true

				let html = quillInstance.root.innerHTML

				let formatOptions = {
					"indent": "auto",
					"indent-spaces": 4,
					"wrap": 100,
					"markup": true,
					"output-xml": false,
					"numeric-entities": true,
					"quote-marks": true,
					"quote-nbsp": false,
					"show-body-only": true,
					"quote-ampersand": false,
					"break-before-br": true,
					"uppercase-tags": false,
					"uppercase-attributes": false,
					"drop-font-tags": false,
					"tidy-mark": false
				}

				html = tidy_html5(html, formatOptions)

				textAreaSourceEditor.val(html)

				textareaModal.show(500)

			}

		})

		function getEditorHTML() {

			let modalEditor = document.createElement('div')
			let textarea = document.createElement('textarea')
			let buttonFinish = document.createElement('button')

			let css1 = `
				display:none;
				width: 100%;
				height: 100%;
				position: fixed;
				top: 50%;
				left: 50%;
				transform: translate(-50%, -50%);
				background-color: rgba(0, 0, 0, 0.5);
				text-align: center;
				padding: 3rem;
				max-height: 100%;
				overflow: auto;
			`
			let css2 = `    
				display: block;
				width: 90%;
				padding: 20px;
				line-height: 24px;
				background: rgb(29, 29, 29);
				color: rgb(255, 168, 40);
				font-family: consola;
				font-size: 22px;
				min-height: 500px;
				height: 80%;
				resize: none;
				margin: 0 auto;
				max-width: 1000px;
			`

			modalEditor.style.cssText = css1
			textarea.style.cssText = css2

			modalEditor = $(modalEditor)
			textarea = $(textarea)
			buttonFinish = $(buttonFinish)

			buttonFinish.addClass('ui button green')
			buttonFinish.html('Terminar edición')

			modalEditor.append("<h1 style='color:white;'>Editor de código</h1>")
			modalEditor.append(textarea)
			modalEditor.append("<br><br>")
			modalEditor.append(buttonFinish)
			$('body').append(modalEditor)

			buttonFinish.on('click', function () {

				let html = textarea.val()

				quillInstance.pasteHTML(html)

				modalEditor.hide(500, () => {
					modalEditor.remove()
					modalEditorExists = false
				})

			})

			return modalEditor
		}
	}

	instantiate(quillAdapterOptions)

	return instance
}

QuillAdapterComponent.componentsSelectors = []
QuillAdapterComponent.initialized = false

/**
 * @function imageUploadModule
 * @param {Quill} quill
 * @param {Object} options
 * @returns {QuillImageUpload}
 */
QuillAdapterComponent.imageUploadModule = function (quill, options = {}) {

	class QuillImageUpload {

		/**
		 * Crear instancia del módulo a partir de las opciones y la instancia de QuillJS
		 * @param {Quill} quill
		 * @param {Object} options
		 */
		constructor(quill, options = {}) {

			// Referencia a QuillJS
			this.quill = quill

			// Opciones
			this.options = Object.assign({}, options)
			this.options.checkBeforeSend = typeof options.checkBeforeSend == 'function' ? options.checkBeforeSend : this.checkBeforeSend.bind(this)
			this.options.customUploader = typeof options.customUploader == 'function' ? options.customUploader : false
			this.options.method = typeof options.method == 'string' ? options.method : 'POST'
			this.options.name = typeof options.name == 'string' ? options.name : 'image'
			this.options.headers = typeof options.headers == 'object' ? options.headers : {}
			this.options.callbackOK = typeof options.callbackOK == 'function' ? options.callbackOK : this.uploadImageCallbackOK.bind(this)
			this.options.callbackKO = typeof options.callbackKO == 'function' ? options.callbackKO : this.uploadImageCallbackKO.bind(this)


			// Asignar manejador a toolbar
			let toolbar = this.quill.getModule('toolbar')
			toolbar.addHandler('image', this.selectLocalImage.bind(this))
		}

		/**
		 * Seleccionar imagen desde el sistema local de ficheros
		 */
		selectLocalImage() {

			const input = document.createElement('input')
			input.setAttribute('type', 'file')
			input.click()

			// Evento de subida de archivo
			input.onchange = () => {

				const file = input.files[0];

				// Solo adminitr imágenes
				if (/^image\//.test(file.type)) {

					const checkBeforeSend = this.options.checkBeforeSend
					checkBeforeSend(file, this.sendToServer.bind(this))

				} else {

					console.warn('You could only upload images.')

				}

			}

		}

		/**
		 * Verificar archivo antes de enviarlo al servidor
		 * @param {File} file
		 * @param {Function} next
		 */
		checkBeforeSend(file, next) {
			next(file)
		}

		/**
		 * Enviar archivo al servidor
		 * @param {File} file
		 */
		sendToServer(file) {

			// Manejador personalizado de subida
			if (this.options.customUploader) {

				this.options.customUploader(file, (dataUrl) => {
					this.insert(dataUrl)
				})

			} else {

				const url = this.options.url
				const method = this.options.method
				const name = this.options.name
				const headers = this.options.headers
				const callbackOK = this.options.callbackOK
				const callbackKO = this.options.callbackKO

				if (url) {

					const fd = new FormData();

					fd.append(name, file);

					if (this.options.csrf) {
						// Agregar CSRF
						fd.append(this.options.csrf.token, this.options.csrf.hash)
					}

					const xhr = new XMLHttpRequest()

					// init http query
					xhr.open(method, url, true)

					// add custom headers
					for (var index in headers) {
						xhr.setRequestHeader(index, headers[index])
					}

					// listen callback
					xhr.onload = () => {
						if (xhr.status === 200) {
							callbackOK(JSON.parse(xhr.responseText), this.insert.bind(this));
						} else {
							callbackKO({
								code: xhr.status,
								type: xhr.statusText,
								body: xhr.responseText
							});
						}
					}

					if (this.options.withCredentials) {
						xhr.withCredentials = true
					}

					xhr.send(fd)

				} else {

					const reader = new FileReader()

					reader.onload = event => {
						callbackOK(event.target.result, this.insert.bind(this))
					}

					reader.readAsDataURL(file)
				}
			}
		}

		/**
		 * Insert the image into the document at the current cursor position
		 * @param {String} dataUrl  The base64-encoded image URI
		 */
		insert(dataUrl) {

			const index =
				(this.quill.getSelection() || {}).index || this.quill.getLength()

			this.quill.insertEmbed(index, 'image', {
				alt: 'Imagen de contenido',
				url: dataUrl,
			}, Quill.sources.USER)

		}

		/**
		 * callback on image upload succesfull
		 * @param {Any} response http response
		 */
		uploadImageCallbackOK(response, next) {
			next(response);
		}

		/**
		 * callback on image upload failed
		 * @param {Any} error http error
		 */
		uploadImageCallbackKO(error) {
			alert(error)
		}
	}

	return new QuillImageUpload(quill, options)

}

QuillAdapterComponent.BlotsConfig = function () {

	const BlotsBlockEmbed = Quill.import('blots/block/embed')

	function imageBlot() {

		class ImageBlot extends BlotsBlockEmbed {
			static create(value) {

				let node = super.create()
				node.setAttribute('alt', value.alt)
				node.setAttribute('src', value.url)
				node.setAttribute('width', value.width)
				node.setAttribute('style', value.style)
				return node

			}

			static value(node) {
				return {
					alt: node.getAttribute('alt'),
					url: node.getAttribute('src'),
					width: node.getAttribute('width'),
					style: node.getAttribute('style'),
				}
			}

		}

		ImageBlot.blotName = 'image'
		ImageBlot.tagName = 'img'

		return ImageBlot
	}


	function iframeBlot() {

		class VideoBlot extends BlotsBlockEmbed {
			static create(value) {

				let node = super.create(value)

				node.setAttribute('frameborder', '0')
				node.setAttribute('allowfullscreen', true)

				if (typeof value == 'string') {
					node.setAttribute('src', value)
				} else {
					node.setAttribute('src', value.src)
					node.setAttribute('width', value.width)
					node.setAttribute('height', value.height)
					node.setAttribute('style', value.style)
				}

				return node
			}

			static value(node) {
				return {
					src: node.getAttribute('src'),
					width: node.getAttribute('width'),
					height: node.getAttribute('height'),
					style: node.getAttribute('style'),
				}
			}
		}

		VideoBlot.blotName = 'video'
		VideoBlot.className = 'ql-video'
		VideoBlot.tagName = 'iframe'

		return VideoBlot
	}

	Quill.register(imageBlot())
	Quill.register('formats/video', iframeBlot())

}

QuillAdapterComponent.AttributtorsConfig = function () {

	function alignAttributtorCustom() {

		const Parchment = Quill.import('parchment')

		class Attributtor extends Parchment.Attributor.Style {
			add(node, value) {

				if (node instanceof HTMLElement) {

					if (value == 'center') {
						node.style.textAlign = 'center'
					} else if (value == 'justify') {
						node.style.textAlign = 'justify'
					} else if (value == 'right') {
						node.style.textAlign = 'right'
					}

					return true

				}

			}

			remove(node) {

				if (node instanceof HTMLElement) {

					node.style.textAlign = ''

					return true

				}

			}
		}

		let format = Quill.import('formats/align')

		let Style = new Attributtor(format.attrName, format.keyName, {
			scope: format.scope,
			whitelist: format.whitelist
		})

		return Style

	}

	Quill.register(alignAttributtorCustom(), true)

}

/**
 * @param {String} name 
 * @returns {void}
 */
QuillAdapterComponent.registerDynamicMessages = function (name) {

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
		'Error en QuillAdapterComponent': 'Error in QuillAdapterComponent',
		'Ha ocurrido un error al instanciar.': 'An error occurred while instantiating.',
		'Ha ocurrido un error al instanciar QuillAdapterComponent.': 'An error occurred while instantiating QuillAdapterComponent.',
		'El componente "%r" ya está en uso.': 'The component "%r" is already in use.',
		'Error': 'Error',
		'Ha ocurrido un error al carga la imagen.': 'An error has occurred while loading the image.',
		'Ha ocurrido un error al cargar la imagen.': 'An error occurred while loading the image.',
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
