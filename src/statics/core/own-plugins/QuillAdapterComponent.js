/**
 * @function QuillAdapterComponent
 * 
 * @param {QuillAdapterOptions} quillAdapterOptions 
 * @param {Array} [toolbar=null] 
 * @param {Boolean} [silentError=true] 
 */
function QuillAdapterComponent(quillAdapterOptions = {}, toolbar = null, silentError = true) {
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

		try {

			configs(quillAdapterOptions)

			toolbarOptions = Array.isArray(toolbar) ? toolbar : toolbarDefault
			component = $(containerSelector)
			textareaTarget = $(textareaTargetSelector)

			//Estilos component
			component.css({
				position: 'relative',
				minHeight: '500px',
				maxHeight: '500px',
				overflowY: 'auto',
			})

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

			if (component.length !== 1 || textareaTarget.length < 1) {
				throw new Error(_i18n('quill', 'Falta(n) el componente o el textarea en el DOM.'))
			} else {
				if (QuillAdapterComponent.componentsSelectors.indexOf(containerSelector) === -1) {
					QuillAdapterComponent.componentsSelectors.push(containerSelector)
				} else {
					throw new Error(formatStr(
						_i18n('quill', 'El componente "%r" ya está en uso.'),
						[
							containerSelector,
						]
					))
				}
			}

			instantiateQuill()

		} catch (error) {

			if (silentError !== true) {
				if (typeof errorMessage == 'function') {
					errorMessage(_i18n('quill', 'Error en QuillAdapterComponent'), _i18n('quill', 'Ha ocurrido un error al instanciar.'))
				} else {
					alert(_i18n('quill', 'Ha ocurrido un error al instanciar QuillAdapterComponent.'))
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
			}
		})

		//Evento para rellenar el textarea
		quillInstance.on('editor-change', (delta, oldDelta, source) => {

			let html = component.get(0).children[0].innerHTML

			if (quillInstance.getText().trim().length > 0) {

				textareaTarget.val(html)

			} else {

				textareaTarget.val('')

			}

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

				errorMessage(_i18n('quill', 'Error'), _i18n('quill', 'Ha ocurrido un error al carga la imagen.'))
			} else {

				alert(_i18n('quill', 'Ha ocurrido un error al cargar la imagen.'))

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
					"wrap": 80,
					"markup": true,
					"output-xml": false,
					"numeric-entities": true,
					"quote-marks": true,
					"quote-nbsp": false,
					"show-body-only": true,
					"quote-ampersand": false,
					"break-before-br": false,
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

				let html = textarea.val().trim()
				html = html.replace(/(\r|\n|\t)*/gmi, '')

				quillInstance.pasteHTML(html.trim())

				modalEditor.hide(500, () => {
					modalEditor.remove()
					modalEditorExists = false
				})

			})

			return modalEditor
		}
	}

	instantiate(quillAdapterOptions)

	return this
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
		 * Instantiate the module given a quill instance and any options
		 * @param {Quill} quill
		 * @param {Object} options
		 */
		constructor(quill, options = {}) {
			// save the quill reference
			this.quill = quill;
			// save options
			this.options = options;
			// listen for drop and paste events
			this.quill
				.getModule('toolbar')
				.addHandler('image', this.selectLocalImage.bind(this));
		}

		/**
		 * Select local image
		 */
		selectLocalImage() {
			const input = document.createElement('input');
			input.setAttribute('type', 'file');
			input.click();

			// Listen upload local image and save to server
			input.onchange = () => {
				const file = input.files[0];

				// file type is only image.
				if (/^image\//.test(file.type)) {
					const checkBeforeSend =
						this.options.checkBeforeSend || this.checkBeforeSend.bind(this);
					checkBeforeSend(file, this.sendToServer.bind(this));
				} else {
					console.warn('You could only upload images.');
				}
			};
		}

		/**
		 * Check file before sending to the server
		 * @param {File} file
		 * @param {Function} next
		 */
		checkBeforeSend(file, next) {
			next(file);
		}

		/**
		 * Send to server
		 * @param {File} file
		 */
		sendToServer(file) {
			// Handle custom upload
			if (this.options.customUploader) {
				this.options.customUploader(file, dataUrl => {
					this.insert(dataUrl);
				});
			} else {
				const url = this.options.url,
					method = this.options.method || 'POST',
					name = this.options.name || 'image',
					headers = this.options.headers || {},
					callbackOK =
						this.options.callbackOK || this.uploadImageCallbackOK.bind(this),
					callbackKO =
						this.options.callbackKO || this.uploadImageCallbackKO.bind(this);

				if (url) {
					const fd = new FormData();

					fd.append(name, file);

					if (this.options.csrf) {
						// add CSRF
						fd.append(this.options.csrf.token, this.options.csrf.hash);
					}

					const xhr = new XMLHttpRequest();
					// init http query
					xhr.open(method, url, true);
					// add custom headers
					for (var index in headers) {
						xhr.setRequestHeader(index, headers[index]);
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
					};

					if (this.options.withCredentials) {
						xhr.withCredentials = true;
					}

					xhr.send(fd);
				} else {
					const reader = new FileReader();

					reader.onload = event => {
						callbackOK(event.target.result, this.insert.bind(this));
					};
					reader.readAsDataURL(file);
				}
			}
		}

		/**
		 * Insert the image into the document at the current cursor position
		 * @param {String} dataUrl  The base64-encoded image URI
		 */
		insert(dataUrl) {
			const index =
				(this.quill.getSelection() || {}).index || this.quill.getLength();

			this.quill.insertEmbed(index, 'image', {
				alt: 'Imagen de contenido',
				url: dataUrl,
			}, Quill.sources.USER);
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
			alert(error);
		}
	}

	return new QuillImageUpload(quill, options)

}

QuillAdapterComponent.BlotsConfig = function () {

	function imageBlot() {

		let BlockEmbed = Quill.import('blots/block/embed')

		class ImageBlot extends BlockEmbed {
			static create(value) {

				let node = super.create()
				node.setAttribute('alt', value.alt)
				node.setAttribute('src', value.url)
				return node

			}

			static value(node) {
				return {
					alt: node.getAttribute('alt'),
					url: node.getAttribute('src')
				}
			}

		}

		ImageBlot.blotName = 'image'
		ImageBlot.tagName = 'img'

		return ImageBlot
	}

	Quill.register(imageBlot())

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
