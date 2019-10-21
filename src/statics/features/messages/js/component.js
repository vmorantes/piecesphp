/**
 * @function MessagesComponent
 * @param {Number} [page=1]
 * @param {Number} [perPage=10]
 * @param {Configuration} configuration
 */
function MessagesComponent(page = 1, perPage = 10, configuration = {}) {

	/**
	 * @typedef Configuration
	 * @property {$|HTMLElement|String} [container=[messenger-component-container]]
	 * @property {Number} [fromID]
	 * @property {Number} [defaultToID]
	 * @property {String} sessionToken
	 * @property {String} loadMessagesRoute
	 * @property {String} sendMessageRoute
	 * @property {String} sendResponseRoute
	 * @property {String} [sendFormSelector=[send-message-form]]
	 * @property {String} [notificationElementSelector]
	 * @property {String} [loadMoreButtonSelector=[message-component-load-more]]
	 * @property {String} [closeButtonSelector=[message-component-close-conversation]]
	 * @property {MessagePreviewTemplate} messagePreviewTemplate
	 * @property {MessageTemplate} messageTemplate
	 * @property {SubMessageTemplate} subMessageTemplate
	 * @property {ResponseFormTemplate} responseFormTemplate
	 * @property {Function} [onToggle]
	 * @property {Function} [onWindowResize]
	 * @property {Boolean} [closeOnOutsideClick=true]
	 */
	configuration
	/**
	 * @typedef MessagePreviewTemplate
	 * @property {$|HTMLElement|String} template HTML de la plantilla
	 * @property {String} [avatarSelector=[avatar]] Selector del la etiqueta img del avatar dentro de la plantilla
	 * @property {String} [dateSelector=[date]] Selector del contenedor de la fecha dentro de la plantilla
	 * @property {String} [nameSelector=[name]] Selector del contenedor del nombre dentro de la plantilla
	 * @property {String} [subjectSelector=[subject]] Selector del contenedor del asunto dentro de la plantilla
	 * @property {String} [userTypeSelector=[user-type]] Selector del contenedor del tipo de usuario dentro de la plantilla
	 */
	let messagePreviewTemplate = {
		template: '',
		avatarSelector: '[avatar]',
		dateSelector: '[date]',
		nameSelector: '[name]',
		subjectSelector: '[subject]',
		userTypeSelector: '[user-type]',
	}
	/**
	 * @typedef MessageTemplate
	 * @property {$|HTMLElement|String} template HTML de la plantilla
	 * @property {String} [avatarSelector=[avatar]] Selector del la etiqueta img del avatar dentro de la plantilla
	 * @property {String} [dateSelector=[date]] Selector del contenedor de la fecha dentro de la plantilla
	 * @property {String} [nameSelector=[name]] Selector del contenedor del nombre dentro de la plantilla
	 * @property {String} [subjectSelector=[subject]] Selector del contenedor del asunto dentro de la plantilla
	 * @property {String} [textSelector=[text]] Selector del contenedor del mensaje dentro de la plantilla
	 * @property {String} [conversationSelector=[conversation]] Selector del contenedor de los submensajes dentro de la plantilla
	 */
	let messageTemplate = {
		template: '',
		avatarSelector: '[avatar]',
		dateSelector: '[date]',
		nameSelector: '[name]',
		subjectSelector: '[subject]',
		textSelector: '[text]',
		conversationSelector: '[conversation]',
	}
	/**
	 * @typedef SubMessageTemplate
	 * @property {$|HTMLElement|String} template HTML de la plantilla
	 * @property {String} [avatarSelector=[avatar]] Selector del la etiqueta img del avatar dentro de la plantilla
	 * @property {String} [textSelector=[text]] Selector del contenedor del mensaje
	 */
	let subMessageTemplate = {
		template: '',
		avatarSelector: '[avatar]',
		textSelector: '[text]',
	}
	/**
	 * @typedef ResponseFormTemplate
	 * @property {$|HTMLElement|String} template HTML de la plantilla
	 */
	let responseFormTemplate = {
		template: '',
	}

	/**
	 * @property {String} sessionToken
	 */
	let sessionToken

	/**
	 * @property {$|HTMLElement|String|null} container
	 */
	let container = '[messenger-component-container]'
	/**
	 * @property {$} previewsContainerElement
	 */
	let previewsContainerElement
	/**
	 * @property {$} conversationsContainerElement
	 */
	let conversationsContainerElement
	/**
	 * @property {String} loadMoreButtonSelector
	 */
	let loadMoreButtonSelector = '[message-component-load-more]'
	/**
	 * @property {String} closeButtonSelector
	 */
	let closeButtonSelector = '[message-component-close-conversation]'
	/**
	 * @property {$} loadMoreButton
	 */
	let loadMoreButton
	/**
	 * @property {$} closeButton
	 */
	let closeButton
	/**
	 * @property {String} notificationElementSelector
	 */
	let notificationElementSelector = '[message-component-notification]'
	/**
	 * @property {$} notificationElement
	 */
	let notificationElement
	/**
	 * @property {String} sendFormSelector
	 */
	let sendFormSelector = '[send-message-form]'
	/**
	 * @property {$} sendForm
	 */
	let sendForm
	/**
	 * @property {MessagesComponent} instance
	 */
	let instance = this
	/**
	 * @property {String} loadMessagesRoute
	 */
	let loadMessagesRoute = ''
	/**
	 * @property {String} sendMessageRoute
	 */
	let sendMessageRoute
	/**
	 * @property {String} sendResponseRoute
	 */
	let sendResponseRoute = ''
	/**
	 * @property {Number} currentPage
	 */
	let currentPage = 1
	/**
	 * @property {Number} currentPerPage
	 */
	let currentPerPage = 10
	/**
	 * @property {Number} paginationStep
	 */
	let paginationStep = currentPerPage > 1 ? currentPerPage : 2
	/**
	 * @property {Number} totalPages
	 */
	let totalPages = 0
	/**
	 * @property {Number} totalConversations
	 */
	let totalConversations = 0
	/**
	 * @property {Array} conversations
	 */
	let conversations = []

	/**
	 * @property {Boolean} isOpened
	 */
	let isOpened = false
	/**
	 * @property {Number} reloadInterval
	 */
	let reloadInterval = -1
	/**
	 * @property {Number} lastConversationOpen
	 */
	let lastConversationOpen = -1
	/**
	 * @property {String} unreadAttributte
	 */
	let unreadAttribute = 'unread'
	/**
	 * @property {String} readAttribute
	 */
	let readAttribute = 'read'
	/**
	 * @property {String} closedAttribute
	 */
	let closedAttribute = 'closed'
	/**
	 * @property {String} openedAttribute
	 */
	let openedAttribute = 'opened'
	/**
	 * @property {String} markReadRouteAttribute
	 */
	let markReadRouteAttribute = 'mark-read-route'
	/**
	 * @property {Boolean} hideComponent
	 */
	let hideComponents = false

	/**
	 * @property {Number} fromID
	 */
	let fromID = -1
	/**
	 * @property {Number} defaultToID
	 */
	let defaultToID = -1

	init(page, perPage, configuration)

	/**
	 * @function init
	 * @param {Number} page
	 * @param {Number} perPage
	 * @param {Configuration} configuration
	 */
	function init(page, perPage, configuration = {}) {

		//Asignación de variables
		if (typeof configuration.sendMessageRoute == 'string') {
			sendMessageRoute = configuration.sendMessageRoute
		}
		if (typeof configuration.loadMessagesRoute == 'string') {
			loadMessagesRoute = configuration.loadMessagesRoute
		}
		if (typeof configuration.sendResponseRoute == 'string') {
			sendResponseRoute = configuration.sendResponseRoute
		}
		if (typeof page == 'number') {
			currentPage = page
		}
		if (typeof perPage == 'number') {
			currentPerPage = perPage
			paginationStep = currentPerPage > 1 ? currentPerPage : 2
		}
		if (typeof configuration.messagePreviewTemplate == 'object' || typeof configuration.messagePreviewTemplate == 'string') {
			messagePreviewTemplate = processByDefaultValues(messagePreviewTemplate, configuration.messagePreviewTemplate)
		}
		if (typeof configuration.messageTemplate == 'object' || typeof configuration.messageTemplate == 'string') {
			messageTemplate = processByDefaultValues(messageTemplate, configuration.messageTemplate)
		}
		if (typeof configuration.subMessageTemplate == 'object' || typeof configuration.subMessageTemplate == 'string') {
			subMessageTemplate = processByDefaultValues(subMessageTemplate, configuration.subMessageTemplate)
		}
		if (typeof configuration.responseFormTemplate == 'object' || typeof configuration.responseFormTemplate == 'string') {
			responseFormTemplate = processByDefaultValues(responseFormTemplate, configuration.responseFormTemplate)
		}
		if (typeof configuration.container == 'object' || typeof configuration.container == 'string') {
			container = configuration.container
		}
		if (typeof configuration.sendFormSelector == 'string') {
			sendFormSelector = configuration.sendFormSelector
		}
		if (typeof configuration.notificationElementSelector == 'string') {
			notificationElementSelector = configuration.notificationElementSelector
		}
		if (typeof configuration.loadMoreButtonSelector == 'string') {
			loadMoreButtonSelector = configuration.loadMoreButtonSelector
		}
		if (typeof configuration.closeButtonSelector == 'string') {
			closeButtonSelector = configuration.closeButtonSelector
		}
		if (typeof configuration.fromID == 'number') {
			fromID = configuration.fromID
		}
		if (typeof configuration.defaultToID == 'number') {
			defaultToID = configuration.defaultToID
		}
		if (typeof configuration.sessionToken == 'string') {
			sessionToken = configuration.sessionToken
		}
		if (typeof configuration.onToggle != 'function') {
			configuration.onToggle = () => { }
		}
		if (typeof configuration.onWindowResize != 'function') {
			configuration.onWindowResize = () => { }
		}
		if (typeof configuration.closeOnOutsideClick != 'boolean') {
			configuration.closeOnOutsideClick = true
		}

		//Configuraciones iniciales
		container = processToHTML(container)
		notificationElement = $(notificationElementSelector)
		loadMoreButton = $(loadMoreButtonSelector)

		previewsContainerElement = container.find('[previews]')
		conversationsContainerElement = container.find('[conversations]')

		loadMoreButton.click(function (e) {
			e.preventDefault()
			e.stopPropagation()
			paginate(false)
		})

		//Formulario de envío
		sendForm = $(sendFormSelector)

		let from = sendForm.find(`[name="from"]`)
		let to = sendForm.find(`[name="to"]`)
		let subject = sendForm.find(`[name="subject"]`)
		let message = sendForm.find(`[name="message"]`)
		let submitButton = sendForm.find(`[type="submit"]`)

		from.val(fromID)
		if (to.length > 0 && to.val().trim().length == 0) {
			to.val(defaultToID)
		}

		sendForm.attr('method', 'POST')
		sendForm.attr('action', sendMessageRoute)

		sendForm.submit(function (e) {

			e.preventDefault()

			let actionURL = new URL($(e.target).attr('action'))

			let request = postRequest(actionURL, new FormData(sendForm.get(0)), {
				'JWTAuth': sessionToken,
			})

			disabledAttrAdOrRemove([
				from,
				to,
				subject,
				message,
				submitButton,
			], false)

			request.done(function (response) {

				if (typeof response.success !== 'undefined' && response.success === true) {

					successMessage(_i18n('messenger', '¡Listo!'), response.message)
					message.val('')
					instance.loadMessages()

				} else {

					errorMessage(_i18n('messenger', 'Error'), response.message)

				}

			}).fail(function (error) {

				console.error(error)
				errorMessage(_i18n('messenger', 'Error'), _i18n('messenger', 'Ha ocurrido un error desconocido.'))

			}).always(function () {

				disabledAttrAdOrRemove([
					from,
					to,
					subject,
					message,
					submitButton,
				], true)

			})

		})

		//Eventos generales
		window.addEventListener('click', function (e) {

			if (configuration.closeOnOutsideClick) {

				toggleConversation(lastConversationOpen)

			}

		})

		window.addEventListener('resize', function (e) {
			configuration.onWindowResize(previewsContainerElement, conversationsContainerElement, lastConversationOpen, e)
		})

	}

	/**
	 * @method loadMessages
	 * @param {Object} options
	 * @param {Function} options.onDone
	 * @param {Function} options.onRequest
	 * @param {Function} options.onPreRequest
	 * @param {Number} options.timeReload
	 */
	this.loadMessages = (options) => {

		options = typeof options == 'object' ? options : {}

		let allowedOptions = {
			'onDone': {
				validate: (value) => typeof value == 'function',
				default: () => { },
			},
			'onRequest': {
				validate: (value) => typeof value == 'function',
				default: () => { },
			},
			'onPreRequest': {
				validate: (value) => typeof value == 'function',
				default: () => { },
			},
			'timeReload': {
				validate: (value) => value == null || !isNaN(value),
				default: 5000,
			},
		}

		options = processByStructure(allowedOptions, options)

		let onDone = options.onDone
		let onRequest = options.onRequest
		let onPreRequest = options.onPreRequest
		let timeReload = options.timeReload

		let form = $(document.createElement('form'))

		onPreRequest(form)

		let urlRequest = new URL(loadMessagesRoute)
		urlRequest.searchParams.set('page', currentPage)
		urlRequest.searchParams.set('per_page', currentPerPage)

		let requestMessages = getRequest(urlRequest, form, {
			'JWTAuth': sessionToken,
		})

		onRequest(requestMessages)

		requestMessages.done((res) => {

			onDone(res)

			//Limpiar contenedores
			if (previewsContainerElement instanceof $) {
				previewsContainerElement.html('')
			}

			if (conversationsContainerElement instanceof $) {
				conversationsContainerElement.html('')
			}

			totalPages = Number.isInteger(res.pages) ? res.pages : totalPages
			totalConversations = Number.isInteger(res.total) ? res.total : totalConversations
			conversations = []

			paginate(true)

			if (Array.isArray(res.messages)) {

				for (let conversation of res.messages) {

					let mainMessage = conversation.message
					let responsesThread = conversation.responses
					let isMine = false

					if (mainMessage.message_from.id == fromID) {
						isMine = true
					}

					for (let index in responsesThread) {

						let message = responsesThread[index]
						if (message.message_from.id == fromID) {
							responsesThread[index].isMine = true
						}

					}

					mainMessage.isMine = isMine

					conversations.push({
						isMine: isMine,
						message: mainMessage,
						thread: responsesThread,
					})

				}

			}

			for (let conversation of conversations) {
				addConversationHTML(conversation, container)
			}

			//Cambiar estado a cerrado
			isOpened = false

			if (lastConversationOpen !== -1) {
				toggleConversation(lastConversationOpen)
			}

			if (conversations.length > 0) {

				let hasUnreadMessages = previewsContainerElement.find(`[${unreadAttribute}]`).length > 0 || conversationsContainerElement.find(`[${unreadAttribute}]`).length > 0

				if (hasUnreadMessages) {
					notificationElement.attr('has-unread', '')
				}

			}

			instance.updateVisibility()

		})

		if (reloadInterval != null) {
			clearInterval(reloadInterval)
		}

		if (timeReload != null && typeof timeReload == 'number') {

			reloadInterval = setInterval(function () {

				if (!isOpened) {
					instance.loadMessages(options)
				}

			}, timeReload)

		}

	}

	/**
	 * @method getPreviewsContainer
	 * @returns {$}
	 */
	this.getPreviewsContainer = () => {

		return previewsContainerElement

	}

	/**
	 * @method getLoadMoreButton
	 * @returns {$}
	 */
	this.getLoadMoreButton = () => {

		return loadMoreButton

	}

	/**
	 * @method conversationIsOpened
	 * @param {Number} conversationID
	 * @returns {Boolean}
	 */
	this.conversationIsOpened = (conversationID) => {

		let result = false

		if (typeof conversationID == 'number') {

			let conversationElement = conversationsContainerElement.find(`[conversation-id="${conversationID}"]`)
			let previewElement = previewsContainerElement.find(`[conversation-id="${conversationID}"]`)

			if (conversationElement.length > 0 && previewElement.length > 0) {

				if (hasAttr(previewElement, openedAttribute)) {

					result = true

				}

			}
		}

		return result

	}

	/**
	 * @method isHiddenComponents
	 * @returns {Boolean}
	 */
	this.isHiddenComponents = () => {
		return hideComponents
	}

	/**
	 * @method setHiddenComponents
	 * @param {Boolean} hidden
	 * @returns {void}
	 */
	this.setHiddenComponents = (hidden) => {
		hideComponents = hidden === true
	}

	/**
	 * @method updateVisibility
	 * @returns {void}
	 */
	this.updateVisibility = () => {
		if (hideComponents) {
			conversationsContainerElement.hide()
			previewsContainerElement.hide()
		} else {
			conversationsContainerElement.show()
			previewsContainerElement.show()
		}
	}

	/**
	 * @function addMessageHTML
	 * @param {Object} conversation 
	 * @param {$} container 
	 */
	function addConversationHTML(conversation, container) {

		let html = createConversationHTML(conversation)

		previewsContainerElement.off('click')
		conversationsContainerElement.off('click')

		previewsContainerElement.on('click', function (e) {
			e.stopPropagation()
		})
		conversationsContainerElement.on('click', function (e) {
			e.stopPropagation()
		})

		previewsContainerElement.append(html.previews)
		conversationsContainerElement.append(html.conversations)

	}

	/**
	 * @function createConversationHTML
	 * @param {Object} conversation
	 * @param {Object} conversation.message
	 * @param {Object[]} conversation.thread
	 * @returns {{previews:$,conversations:$}}
	 */
	function createConversationHTML(conversation) {

		let isMine = conversation.message
		let message = conversation.message
		let thread = conversation.thread

		let messagePreviewElement = createMessagePreviewElement(message, isMine)
		let messageMainElement = createMessageElement(message, thread, isMine)

		if (messageMainElement.find(`[${unreadAttribute}]`).not(`[is-mine]`).length == 0) {
			messagePreviewElement.removeAttr(`${unreadAttribute}`).attr(`${readAttribute}`, '')
		}

		return {
			previews: messagePreviewElement,
			conversations: messageMainElement,
		}

		/**
		 * @function createMessagePreviewHTML
		 * @param {Object} message
		 * @param {Boolean} isMine
		 * @returns {$}
		 */
		function createMessagePreviewElement(message, isMine) {

			let element = $(processToHTML(messagePreviewTemplate.template).get(0).outerHTML)

			let avatarElement = element.find(messagePreviewTemplate.avatarSelector)
			let dateElement = element.find(messagePreviewTemplate.dateSelector)
			let nameElement = element.find(messagePreviewTemplate.nameSelector)
			let subjectElement = element.find(messagePreviewTemplate.subjectSelector)
			let userTypeElement = element.find(messagePreviewTemplate.userTypeSelector)

			//Verificar si fue leído
			if (message.readed && (message.main_readed || message.isMine)) {
				element.attr(readAttribute, '')
			} else {
				element.attr(unreadAttribute, '')
			}

			//ID del mensaje principal
			element.attr('conversation-id', message.id)
			if (isMine) {
				element.attr('is-mine', '')
			}

			//Rellenar datos
			let fullname = [
				typeof message.message_from.firstname == 'string' ? message.message_from.firstname.trim() : '',
				typeof message.message_from.secondname == 'string' ? message.message_from.secondname.trim() : '',
				typeof message.message_from.first_lastname == 'string' ? message.message_from.first_lastname.trim() : '',
				typeof message.message_from.second_lastname == 'string' ? message.message_from.second_lastname.trim() : '',
			]

			avatarElement.attr('src', message.avatar)
			dateElement.html(message.date)
			nameElement.html(fullname.join(' '))
			subjectElement.html(message.subject)
			userTypeElement.html(message.rol)

			//Evento para mostrar la conversación
			element.css('cursor', 'pointer')
			element.click(function (e) {

				e.preventDefault()
				e.stopPropagation()

				let conversationID = element.attr('conversation-id')

				toggleConversation(conversationID)

			})

			return element

		}

		/**
		 * @function createMessageElement
		 * @param {Object} message
		 * @param {Object[]} [thread]
		 * @param {Boolean} isMine
		 * @returns {$}
		 */
		function createMessageElement(message, thread = null, isMine) {

			let element = $(processToHTML(messageTemplate.template).get(0).outerHTML)

			let avatarElement = element.find(messageTemplate.avatarSelector)
			let dateElement = element.find(messageTemplate.dateSelector)
			let nameElement = element.find(messageTemplate.nameSelector)
			let subjectElement = element.find(messageTemplate.subjectSelector)
			let textElement = element.find(messageTemplate.textSelector)
			let conversationContainerElement = element.find(messageTemplate.conversationSelector)

			//Verificar si fue leído
			if (message.readed || isMine) {
				element.attr(readAttribute, '')
			} else {
				element.attr(unreadAttribute, '')
			}
			if (isMine) {
				element.attr('is-mine', '')
			}

			//Ruta de marcar como leído
			element.attr(markReadRouteAttribute, message.mark_as_read_url)
			//ID del usuario al que se responderá
			element.attr('to', message.message_from.id)
			//ID del mensaje principal
			element.attr('conversation-id', message.id)

			//Rellenar datos
			let fullname = [
				typeof message.message_from.firstname == 'string' ? message.message_from.firstname.trim() : '',
				typeof message.message_from.secondname == 'string' ? message.message_from.secondname.trim() : '',
				typeof message.message_from.first_lastname == 'string' ? message.message_from.first_lastname.trim() : '',
				typeof message.message_from.second_lastname == 'string' ? message.message_from.second_lastname.trim() : '',
			]

			avatarElement.attr('src', message.avatar)
			dateElement.html(message.date)
			nameElement.html(fullname.join(' '))
			subjectElement.html(message.subject)
			textElement.html(message.message)

			if (Array.isArray(thread)) {
				for (let subMessage of thread) {
					conversationContainerElement.append(createSubMessageElement(subMessage, subMessage.isMine))
				}
			}

			conversationContainerElement.append(createResponseFormElement(message.id))

			//Ocultar elemento
			element.hide()

			return element

		}

		/**
		 * @function createSubMessageElement
		 * @param {Object} message 
		 * @param {Boolean} message 
		 * @returns {$} 
		 */
		function createSubMessageElement(message, isMine) {

			let element = $(processToHTML(subMessageTemplate.template).get(0).outerHTML)

			let avatarElement = element.find(subMessageTemplate.avatarSelector)
			let textElement = element.find(subMessageTemplate.textSelector)

			//Verificar si fue leído
			if (message.readed || isMine) {
				element.attr(readAttribute, '')
			} else {
				element.attr(unreadAttribute, '')
			}
			if (isMine) {
				element.attr('is-mine', '')
			}

			//Ruta de marcar como leído
			element.attr(markReadRouteAttribute, message.mark_as_read_url)
			element.attr('to', message.message_from.id)

			avatarElement.attr('src', message.avatar)
			textElement.html(message.message)

			//Ocultar elemento
			element.hide()

			return element

		}

		/**
		 * @function createResponseFormElement
		 * @param {Number} conversationID 
		 * @returns {$} 
		 */
		function createResponseFormElement(conversationID) {

			let element = $(processToHTML(responseFormTemplate.template).get(0).outerHTML)
			let message = element.find(`[name='message']`)
			let idMessage = element.find(`[name='message_id']`)
			let idFrom = element.find(`[name='message_from']`)
			let submitButton = element.find(`[type='submit']`)

			element.attr('method', 'POST')
			element.attr('action', sendResponseRoute)

			idFrom.val(fromID)
			idMessage.val(conversationID)

			element.submit(function (e) {

				e.preventDefault()

				let actionURL = new URL($(e.target).attr('action'))

				let request = postRequest(actionURL, new FormData(element.get(0)), {
					'JWTAuth': sessionToken,
				})

				disabledAttrAdOrRemove([
					message,
					idMessage,
					idFrom,
					submitButton,
				], false)

				request.done(function (response) {

					if (typeof response.success !== 'undefined' && response.success === true) {

						successMessage(_i18n('messenger', '¡Listo!'), response.message)
						instance.loadMessages()
						message.val('')

					} else {

						errorMessage(_i18n('messenger', 'Error'), response.message)

					}

				}).fail(function (error) {

					console.error(error)
					errorMessage(_i18n('messenger', 'Error'), _i18n('messenger', 'Ha ocurrido un error desconocido.'))

				}).always(function () {

					disabledAttrAdOrRemove([
						message,
						idMessage,
						idFrom,
						submitButton,
					], true)

				})

			})

			return element

		}

	}

	/**
	 * @function toggleConversation
	 * @param {Number} [id] 
	 * @returns {Promise}
	 */
	function toggleConversation(id) {

		return new Promise(function (resolve, reject) {

			id = parseInt(id)

			if (!Number.isInteger(id)) {
				id = lastConversationOpen
			}

			let conversationElement = conversationsContainerElement.find(`[conversation-id="${id}"]`)
			let previewElement = previewsContainerElement.find(`[conversation-id="${id}"]`)

			if (conversationElement.length > 0 && previewElement.length > 0) {

				let promise = Promise.resolve()

				if (lastConversationOpen != -1 && lastConversationOpen != id) {
					promise = toggleConversation(lastConversationOpen)
				}

				promise.then(function () {

					//Evento de botón general de cierre
					closeButton = $(closeButtonSelector)
					closeButton.off('click')
					closeButton.on('click', function (e) {
						toggleConversation(lastConversationOpen)
					})

					if (!isOpened) {

						lastConversationOpen = id
						isOpened = true

						let subMessages = conversationElement.find(messageTemplate.conversationSelector).find('>*')

						previewElement.attr(openedAttribute, '')
						previewElement.removeAttr(closedAttribute, '')
						conversationElement.show()
						subMessages.show()

						let unreadSubMessages = subMessages.filter(`[${unreadAttribute}]`).toArray()

						unreadSubMessages.unshift(conversationElement.get(0))

						let firstUnread = $(unreadSubMessages).filter(`[${unreadAttribute}]`).filter(':first')
						let urlFirst = firstUnread.attr(markReadRouteAttribute)

						for (let messageElement of unreadSubMessages) {

							let toMarkElement = $(messageElement)
							let urlMarkReaded = toMarkElement.attr(markReadRouteAttribute)
							let isMine = hasAttr(toMarkElement, 'is-mine')

							if (!isMine && urlMarkReaded.trim().length > 0) {

								let markReadedRequest = postRequest(urlMarkReaded, null, {
									'JWTAuth': sessionToken,
								})

								markReadedRequest.done(function (res) {

									if (res.success) {

										if (urlFirst == urlMarkReaded) {

											if (firstUnread.length > 0) {

												$([document.documentElement, document.body]).animate({
													scrollTop: firstUnread.offset().top
												}, 500, () => {

													setTimeout(function () {
														toMarkElement.removeAttr(unreadAttribute)
													}, 500)

												})

											} else {

												toMarkElement.removeAttr(unreadAttribute)

											}

										} else {

											toMarkElement.removeAttr(unreadAttribute)

										}

									} else {

										console.warn(res.message)

									}

								})

							}

						}

						previewElement.removeAttr(unreadAttribute)
						conversationElement.removeAttr(unreadAttribute)

						configuration.onToggle(previewElement, conversationElement, id)
						resolve(previewElement, conversationElement, id)

					} else {

						if (lastConversationOpen != -1) {

							lastConversationOpen = -1
							isOpened = false

							let subMessages = conversationElement.find(messageTemplate.conversationSelector).find('>*')

							subMessages.hide()
							conversationElement.hide()
							previewElement.attr(closedAttribute, '')
							previewElement.removeAttr(openedAttribute, '')

							configuration.onToggle(previewElement, conversationElement, id)
							resolve(previewElement, conversationElement, id)

						}


					}

				})

			} else {
				lastConversationOpen = -1
				configuration.onToggle(null, null, id)
				resolve(null, null, id)
			}

		})

	}

	/**
	 * @function paginate
	 * @param {Boolean} verify
	 */
	function paginate(verify) {

		if (totalConversations > currentPerPage) {

			if (!verify) {
				currentPerPage += paginationStep
				instance.loadMessages()
			}

		}

		if (totalConversations <= currentPerPage) {
			loadMoreButton.remove()
		}

	}

	/**
	 * @function processByStructure
	 * @param {Object} structure 
	 * @param {Object} data 
	 * @returns {Object}
	 */
	function processByStructure(structure, data) {

		for (let option in structure) {
			let defaultOption = structure[option]
			if (!defaultOption.validate(data[option])) {
				data[option] = defaultOption.default
			}
		}

		return data

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
			}
		}

		return data

	}

	/**
	 * @function proccessToHTML
	 * @param {$|HTMLElement|String} value 
	 * @returns {$|null}
	 */
	function processToHTML(value) {

		if (typeof value == 'string' || value instanceof HTMLElement) {

			value = $(value)

			if (value.length < 1) {
				value = null
			}

		} else if (!(value instanceof $)) {
			value = null
		}

		return value

	}

	/**
	 * @function hasAttr
	 * @param {$} element 
	 * @param {String} attr 
	 */
	function hasAttr(element, attr) {
		let has = false
		if (element instanceof $ && typeof attr == 'string') {
			attr = element.attr(attr)
			has = typeof attr != undefined && attr != null
		}
		return has
	}

	/**
	 * @function disabledAttrAdOrRemove
	 * @param {$|Array} elements 
	 * @param {Boolean} enable 
	 */
	function disabledAttrAdOrRemove(elements, enable) {

		elements = Array.isArray(elements) ? elements : []
		enable = typeof enable == 'boolean' ? enable : true

		for (let i = 0; i < elements.length; i++) {

			let element = elements[i] instanceof HTMLElement ? $(elements[i]) : elements[i]

			if (element instanceof $) {
				element.attr('disabled', enable ? false : true)
			}

		}

	}

	/**
	 * postRequest
	 * 
	 * Realiza una petición AJAX POST (JQuery.ajax) y devuelve el objeto jqXHR
	 * que es un objeto Deferred, por lo que tiene los métodos:
	 * done(data, textStatus, jqXHR),
	 * fail(jqXHR, textStatus, errorThrown) y
	 * always(data|jqXHR, textStatus, jqXHR|errorThrown)
	 * 
	 * @param {string} url URL que se consultará
	 * @param {FormData|Object} [data] Información enviada
	 * @param {Object} [headers] Cabeceras
	 * @returns {jqXHR}
	 */
	function postRequest(url, data, headers = {}) {

		let options = {
			url: url,
			method: 'POST',
		}

		if (data instanceof FormData) {

			options.processData = false
			options.enctype = "multipart/form-data"
			options.contentType = false
			options.cache = false
			options.data = data

		} else if (typeof data == 'object') {

			options.data = data

		}

		let parsedHeaders = parseHeaders(headers)

		if (parsedHeaders.size > 0) {

			options.beforeSend = function (request) {

				for (let key of parsedHeaders.keys()) {
					let value = parsedHeaders.get(key)
					request.setRequestHeader(key, value)
				}

			}

		}

		function parseHeaders(headers = {}) {

			let mapHeaders = new Map()

			if (typeof headers == 'object') {

				for (let name in headers) {

					let value = headers[name]
					let valueString = ''

					if (Array.isArray(value)) {

						let length = value.length
						let lastIndexValue = 0

						if (length == 1) {
							lastIndexValue = 0
						} else if (length > 1) {
							lastIndexValue = length - 1
						}

						for (let i = 0; i < length; i++) {
							if (i == lastIndexValue) {
								valueString += value[i]
							} else {
								valueString += value[i] + "\r\n"
							}
						}

					} else if (typeof value == 'string') {
						valueString = value
					}

					mapHeaders.set(name, valueString)

				}

			}

			return mapHeaders

		}

		return this.$.ajax(options)

	}

	/**
	 * getRequest
	 * 
	 * Realiza una petición AJAX GET (JQuery.ajax) y devuelve el objeto jqXHR
	 * que es un objeto Deferred, por lo que tiene los métodos:
	 * done(data, textStatus, jqXHR),
	 * fail(jqXHR, textStatus, errorThrown) y
	 * always(data|jqXHR, textStatus, jqXHR|errorThrown)
	 * 
	 * @param {String} url URL que se consultará
	 * @param {String|HTMLElement|JQuery} [data] Formulario
	 * @param {Object} [headers] Cabeceras
	 * @returns {jqXHR}
	 */
	function getRequest(url, data, headers = {}) {

		let options = {
			url: url,
			method: 'GET',
			enctype: "application/x-www-form-urlencoded",
		}

		if (data instanceof HTMLFormElement) {

			options.data = $(data).serialize()

		} else if (data instanceof $) {

			options.data = data.serialize()

		} else if (typeof data == 'string') {

			options.data = data

		}

		let parsedHeaders = parseHeaders(headers)

		if (parsedHeaders.size > 0) {

			options.beforeSend = function (request) {

				for (let key of parsedHeaders.keys()) {
					let value = parsedHeaders.get(key)
					request.setRequestHeader(key, value)
				}

			}

		}

		function parseHeaders(headers = {}) {

			let mapHeaders = new Map()

			if (typeof headers == 'object') {

				for (let name in headers) {

					let value = headers[name]
					let valueString = ''

					if (Array.isArray(value)) {

						let length = value.length
						let lastIndexValue = 0

						if (length == 1) {
							lastIndexValue = 0
						} else if (length > 1) {
							lastIndexValue = length - 1
						}

						for (let i = 0; i < length; i++) {
							if (i == lastIndexValue) {
								valueString += value[i]
							} else {
								valueString += value[i] + "\r\n"
							}
						}

					} else if (typeof value == 'string') {
						valueString = value
					}

					mapHeaders.set(name, valueString)

				}

			}

			return mapHeaders

		}

		return this.$.ajax(options)

	}

}
