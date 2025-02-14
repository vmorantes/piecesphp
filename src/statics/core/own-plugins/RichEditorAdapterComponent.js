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
	 * @property {String} [fileManagerURL]
	 * @property {String} [fileManagerBaseURLStatics]
	 */
	adapterOptions;

	//──── Variables ─────────────────────────────────────────────────────────────────────────
	/**
	 * @property {RichEditorAdapterComponent}
	 */
	let instance = this;

	/**
	 * @property {String}
	 */
	let langGroup = 'richEditor'

	RichEditorAdapterComponent.registerDynamicMessages(langGroup)

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
			'sourceEditing',
			//'codeBlock'
		]
	}

	/**
	 * @property {String}
	 */
	let containerSelector = '[rich-editor-adapter-component]'

	/**
	 * @property {String}
	 */
	let textareaTargetSelector = `${containerSelector} + textarea[target]`

	/**
	 * @property {String}
	 */
	let fileManagerURL = ''

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
				language: 'es',
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
				})

				const CKFinder = editor.commands.get('ckfinder')
				const fileRepository = editorInstance.plugins.get('FileRepository')

				CKFinder.execute = () => {

					let fileManagerPromise = fileManagerHandler()

					fileManagerPromise.then(fileManager => {
						verifyImgCmd = false
						fileManager.getUI().dialogelfinder('open')
					})

				}

				fileRepository.createUploadAdapter = loader => {
					return new UploadAdapter(loader)
				}

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

		if (typeof adapterOptions.containerSelector == 'string' && adapterOptions.containerSelector.length > 0) {
			containerSelector = adapterOptions.containerSelector
		}

		if (typeof adapterOptions.textareaTargetSelector == 'string' && adapterOptions.textareaTargetSelector.length > 0) {
			textareaTargetSelector = adapterOptions.textareaTargetSelector
		}

		if (typeof adapterOptions.fileManagerURL == 'string' && adapterOptions.fileManagerURL.length > 0) {
			fileManagerURL = adapterOptions.fileManagerURL
		} else {
			const relativeAdminPath = pcsphpGlobals.adminURLConfig.url.length > 0 ? `${pcsphpGlobals.adminURLConfig.url}/` : ''
			fileManagerURL = `${relativeAdminPath}filemanager/rich-editor/configuration/`
		}

		if (typeof adapterOptions.fileManagerBaseURLStatics == 'string' && adapterOptions.fileManagerBaseURLStatics.length > 0) {
			fileManagerBaseURLStatics = adapterOptions.fileManagerBaseURLStatics
		} else {
			fileManagerBaseURLStatics = `${pcsphpGlobals.baseURL.replace(/\/$/, '')}/statics/plugins/elfinder`
		}

	}

	/**
	 * @param {String} target
	 * @returns {Promise}
	 */
	function fileManagerHandler(target = null) {

		return new Promise((resolve, reject) => {

			const done = () => {

				if (target) {

					if (!Object.keys(fileManager.files()).length) {

						fileManager.one('open', () => {
							fileManager.file(target) ? resolve(fileManager) : reject(fileManager, 'errFolderNotFound');
						})

					} else {

						new Promise((res, rej) => {

							if (fileManager.file(target)) {

								res()

							} else {

								fileManager.request({ cmd: 'parents', target: target }).done(e => {

									fileManager.file(target) ? res() : rej()

								}).fail(() => {
									rej()
								})
							}

						}).then(() => {

							fileManager.exec('open', target).done(() => {
								resolve(fileManager)
							}).fail(err => {
								reject(fileManager, err ? err : 'errFolderNotFound')
							})

						}).catch((err) => {
							reject(fileManager, err ? err : 'errFolderNotFound')
						})

					}

				} else {
					resolve(fileManager);
				}

			}

			if (fileManager != null) {
				done()
			} else {

				fileManager = $('<div/>').dialogelfinder({
					title: 'Seleccionar imagen',
					url: fileManagerURL,
					baseUrl: fileManagerBaseURLStatics + '/',
					lang: 'es',
					useBrowserHistory: false,
					autoOpen: false,
					width: 'auto',
					commandsOptions: {
						getfile: {
							oncomplete: 'close',
							multiple: true
						}
					},
					getFileCallback: (files, instance) => {

						let imagesSrc = []

						instance.getUI('cwd').trigger('unselectall')

						$.each(files, function (index, file) {

							if (typeof file == 'object' && typeof file.url == 'string' && file.url.length > 0) {

								let baseURL = new URL($('head base').attr('href'))
								let fileURL = file.url.replace(baseURL.href, '').replace(/(^\/|\/$)/g, '')

								if (file.mime.match(/^image\//i)) {

									imagesSrc.push(fileURL)

								} else {
									errorMessage(_i18n(langGroup, 'Error'), _i18n(langGroup, 'Debe seleccionar una imagen'))
								}

							}

						})

						if (imagesSrc.length > 0) {
							insertImages(imagesSrc)
						}

					}

				}).elfinder('instance')

				done()

			}

		})

	}

	/**
	 * @param {Object} loader 
	 */
	function UploadAdapter(loader) {

		let upload = function (file, resolve, reject) {

			fileManagerHandler(uploadTargetHash).then(instance => {

				let fmNode = instance.getUI()

				verifyImgCmd = true
				fmNode.dialogelfinder('open')

				instance.exec('upload', { files: [file], target: uploadTargetHash }, void (0), uploadTargetHash)
					.done(data => {

						if (data.added && data.added.length) {

							instance.url(data.added[0].hash, { async: true }).done(function (url) {

								let baseURL = new URL($('head base').attr('href'))
								let fileURL = url.replace(baseURL.href, '').replace(/(^\/|\/$)/g, '')

								resolve({
									'default': fileURL,
								})

								fmNode.dialogelfinder('close')

							}).fail(function () {

								reject('errFileNotFound')

							})

						} else {

							reject(instance.i18n(data.error ? data.error : 'errUpload'))
							fmNode.dialogelfinder('close')

						}

					})
					.fail(err => {
						const error = instance.parseError(err)
						reject(instance.i18n(error ? (error === 'userabort' ? 'errAbort' : error) : 'errUploadNoFiles'))
					})

			}).catch((instance, err) => {
				console.error(instance)
				console.error(err)
				const error = instance.parseError(err)
				reject(instance.i18n(error ? (error === 'userabort' ? 'errAbort' : error) : 'errUploadNoFiles'))
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
			fileManager && fileManager.getUI().trigger('uploadabort')
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

	instantiate(adapterOptions)

	return instance
}

RichEditorAdapterComponent.componentsSelectors = []/**

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
