function MessagesComponent(page, perPage) {

	this.page = 1;
	this.perPage = 3;
	this.route = '';
	this.userID = null;
	this.messagesComponent = null;
	this.templatesComponent = null;
	this.inbox = null;
	this.pagination = null;
	this.externalEditor = null;
	this.externalEditorURL = '';
	this.externalEditorMethod = 'GET';

	let readAttribute = 'read';
	let unreadAttribute = 'unread';

	let lastOpened = null;
	let reopenForReply = false;
	let reloadInterval = null;
	let _this = this;
	let _pages = 1;

	let selectorsMessagesTemplate = {
		selector: '[message]',
		preview: {
			selector: '>[preview]',
			subject: {
				selector: '>[subject]',
			},
			date: {
				selector: '>[date]',
			},
		},
		content: {
			selector: '>[content]',
			messageDetail: {
				selector: '>[message-detail]',
				author: {
					selector: '>[author]',
				},
				subject: {
					selector: '>[subject]',
				},
				date: {
					selector: '>[date]',
				},
				text: {
					selector: '>[text]',
				},
			},
			dialog: {
				selector: '>[dialog]',
			},
			replyForm: {
				selector: '>[reply]',
			},
		},
	};

	let selectorsSubMessagesTemplate = {
		selector: '[sub-message]',
		data: {
			selector: '>[data]',
			avatar: {
				selector: '>[avatar]',
			},
			author: {
				selector: '>[author]',
			},
			date: {
				selector: '>[date]',
			},
		},
		text: {
			selector: '>[text]',
		},
	};

	let selectorContentInbox = '>[content]';

	let selectorsMessageComponent = {
		selector: '[messages-component-container]',
		sendForm: {
			selector: '>[send-form]',
			toggleForm: {
				selector: '>[toggle-form]',
			},
			messagesComponentExternalEditor: {
				selector: '>[messages-component-external-editor]',
			},
		},
		messagesComponent: {
			selector: '>[messages-component]',
			inbox: {
				selector: '>[inbox]',
				content: {
					selector: selectorContentInbox,
					messages: (function (element, subElement) {
						element.content.dialog.subMessages = subElement;
						return element;
					})(selectorsMessagesTemplate, selectorsSubMessagesTemplate),
				}
			},
			pagination: {
				selector: '>[pagination]',
			},
		},
		messagesTemplatesComponent: {
			ignore: true,
			selector: '[messages-component-templates]',
			messages: selectorsMessagesTemplate,
			subMessages: selectorsSubMessagesTemplate,
		},
	};

	__constructor(page, perPage);


	this.loadMessages = (options) => {

		options = typeof options == 'object' ? options : {};

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
		};

		for (let option in allowedOptions) {
			let defaultOption = allowedOptions[option];
			if (!defaultOption.validate(options[option])) {
				options[option] = defaultOption.default;
			}
		}

		let onDone = options.onDone;
		let onRequest = options.onRequest;
		let onPreRequest = options.onPreRequest;
		let timeReload = options.timeReload;

		let form = $(document.createElement('form'));
		let page = `<input name="page" value="${this.page}"/>`;
		let perPage = `<input name="per_page" value="${this.perPage}"/>`;
		form.append(page).append(perPage);

		onPreRequest(form);

		let requestMessages = getRequest(this.route, form);

		onRequest(requestMessages);

		requestMessages.done((res) => {

			onDone(res);

			_pages = Number.isInteger(res.pages) ? res.pages : _pages;

			let contentInbox = _this.inbox.find(selectorContentInbox);
			let elements = contentInbox.find('*').toArray();

			for (let element of elements) {
				$(element).off();
				$(element).remove();
			}

			if (res.total > 0) {
				configPagination();
				messagesElements(res.messages);
			} else {
				let noHayH3 = $("<h3>No hay mensajes.</h3>");
				noHayH3.css({
					padding: '1rem',
				});
				contentInbox.html(noHayH3);
			}

			reopenForReply = false;
		});

		if (reloadInterval != null) {
			clearInterval(reloadInterval);
		}

		if (timeReload != null && typeof timeReload == 'number') {
			reloadInterval = setInterval(function () {

				let messagesContentElements = _this.inbox
					.find(selectorsMessageComponent.messagesComponent.inbox.content.messages.selector)
					.find(selectorsMessageComponent.messagesComponent.inbox.content.messages.content.selector);
				let hasOpened = messagesContentElements
					.is(':visible');

				if (!hasOpened) {
					_this.loadMessages(options);
				}

			}, timeReload);
		}

	};

	this.setPage = (page) => {
		this.page = Number.isInteger(page) ? page : this.page;
		this.loadMessages();
	}

	this.setPerPage = (page) => {
		this.perPage = Number.isInteger(perPage) ? perPage : this.perPage;
		this.loadMessages();
	}

	/**
	 * Constructor de uso interno
	 * @param {number} page 
	 * @param {number} perPage 
	 */
	function __constructor(page, perPage) {

		_this.page = Number.isInteger(page) ? page : _this.page;
		_this.perPage = Number.isInteger(perPage) ? perPage : _this.perPage;

		let componentContainer = $(selectorsMessageComponent.selector);

		let toggleForm = componentContainer
			.find(selectorsMessageComponent.sendForm.selector)
			.find(selectorsMessageComponent.sendForm.toggleForm.selector);

		_this.messagesComponent = componentContainer.find(selectorsMessageComponent.messagesComponent.selector);
		_this.templatesComponent = $(document.createElement('div'));

		let basicExists = _this.messagesComponent.length > 0 && $(selectorsMessageComponent.messagesTemplatesComponent.selector).length > 0;

		if (basicExists) {

			let templatesElement = $(selectorsMessageComponent.messagesTemplatesComponent.selector);

			_this.route = _this.messagesComponent.attr('route');
			_this.userID = _this.messagesComponent.attr('user');

			templatesElement.hide()
			_this.templatesComponent.html(templatesElement.html())
			templatesElement.remove()

			_this.inbox = _this.messagesComponent.find('[inbox]')
			_this.pagination = _this.messagesComponent.find('[pagination]')

			configExternalEditor();

			toggleForm.on('click', () => {
				_this.externalEditor.fadeIn(500);
			});
		}
	}

	/**
	 * Configura lo relativo al editor externo
	 */
	function configExternalEditor() {

		let element = $('[messages-component-external-editor]');

		_this.externalEditor = element;
		_this.externalEditorURL = element.attr('action');
		_this.externalEditorMethod = element.attr('method');

		_this.externalEditor.submit(function (e) {
			e.preventDefault();

			let sendRequest = null;
			let formData = new FormData(_this.externalEditor[0])

			if (_this.externalEditorMethod.toUpperCase() == 'POST') {
				sendRequest = postRequest(_this.externalEditorURL, formData);
			} else {
				sendRequest = getRequest(_this.externalEditorURL, formData);
			}

			sendRequest.done((res) => {
				if (res.success) {
					successMessage('Éxito', res.message);
					_this.externalEditor[0].reset();
					_this.externalEditor.find('.dropdown').dropdown('clear');
					_this.externalEditor.hide(500);
					_this.loadMessages();
				} else {
					errorMessage('Error', res.message);
				}
			});

			sendRequest.fail((res) => {
				errorMessage('Error', 'Ha ocurrido un error desconocido.');
				console.log(res);
			});

			return false;
		});

		return _this;
	}

	function configPagination() {
		let page = _this.page;
		let pages = _pages;

		if (pages > 1) {
			_this.pagination.html('');
			for (let i = 1; i <= pages; i++) {
				let item = $(document.createElement('a'));
				if (i == page) {
					item.addClass('active item');
				} else {
					item.addClass('item');
				}
				item.html(i);
				item.on('click', function () {
					let number = parseInt($(this).html())
					_this.setPage(number);
				})
				_this.pagination.append(item);
				_this.pagination.attr('class', 'ui pagination menu');
			}
		} else {
			_this.pagination.attr('class', '');
			_this.pagination.html('');
		}
	}

	function messagesElements(messages) {

		let messageTemplate = _this.templatesComponent.find(selectorsMessagesTemplate.selector);
		let messageResponseTemplate = _this.templatesComponent.find(selectorsSubMessagesTemplate.selector);
		let contentInbox = _this.inbox.find(selectorContentInbox);

		contentInbox.html('');

		for (let messageContent of messages) {

			let message = messageContent.message;
			let responses = messageContent.responses;

			let messageReaded = message.readed;
			let messageMainReaded = message.main_readed;

			let templateMessage = $(messageTemplate[0].outerHTML);
			let templateMessageInnerHTML = templateMessage.html();

			let is_same = message.message_from.id == _this.userID;
			let hasResponsesNotMine = (function (responses) {
				let has = false;
				for (let response of responses) {
					if (response.message_from.id != _this.userID && !response.readed) {
						has = true;
						break;
					}
				}
				return has;
			})(responses);

			if (!is_same) {

				if (messageMainReaded) {
					if (messageReaded) {
						templateMessage.attr(readAttribute, '');
					} else if (hasResponsesNotMine) {
						templateMessage.attr(unreadAttribute, message.mark_as_read_url);
					} else {
						templateMessage.attr(readAttribute, '');
					}
				} else {
					templateMessage.attr(unreadAttribute, message.mark_as_read_url);
				}
			} else {
				if (!messageReaded) {
					if (!hasResponsesNotMine) {
						templateMessage.attr(readAttribute, '');
					} else {
						templateMessage.attr(unreadAttribute, message.mark_as_read_url);
					}
				} else {
					templateMessage.attr(readAttribute, '');
				}
			}

			let replaceValuesMessage = {
				avatar: message.avatar != null ? `<img src="${message.avatar}"/>` : '',
				message_id: message.id,
				message_from: _this.userID,
				message_from_name: is_same ? 'Yo' : message.message_from.username,
				date: message.date,
				subject: message.subject,
				message: message.message,
				messages: '',
			};

			for (let response of responses) {

				let responseReaded = response.readed;

				let templateSubMessage = $(messageResponseTemplate[0].outerHTML);
				let templateSubMessageInnerHTML = templateSubMessage.html();

				let is_same = response.message_from.id == _this.userID;

				if (responseReaded || is_same) {
					templateSubMessage.attr(readAttribute, '');
				} else {
					templateSubMessage.attr(unreadAttribute, response.mark_as_read_url);
				}

				let replaceValuesMessageResponse = {
					avatar: response.avatar != null ? `<img src="${response.avatar}"/>` : '',
					message_from_name: is_same ? 'Yo' : response.message_from.username,
					date: response.date,
					message: response.message,
				};

				for (let name in replaceValuesMessageResponse) {
					let value = replaceValuesMessageResponse[name];
					templateSubMessageInnerHTML = templateSubMessageInnerHTML.replace(`{{${name}}}`, value);
				}

				templateSubMessage.html(templateSubMessageInnerHTML.trim());

				replaceValuesMessage.messages += templateSubMessage[0].outerHTML;

			}

			for (let name in replaceValuesMessage) {
				let value = replaceValuesMessage[name];
				let regex = new RegExp(`{{${name}}}`, 'gi');
				templateMessageInnerHTML = templateMessageInnerHTML.replace(regex, value);
			}

			templateMessage.html(templateMessageInnerHTML.trim());

			templateMessage.find('[avatar]').popup();

			let triggerOpenContent = templateMessage.find(selectorsMessagesTemplate.preview.selector);
			triggerOpenContent.css({
				cursor: 'pointer',
			});

			let contentMessages = templateMessage.find(selectorsMessagesTemplate.content.selector);
			contentMessages.hide();

			let markRead = function (e) {

				let artificialTriggered = typeof e == 'object' && typeof e.type == 'string' && e.type == 'markRread';

				if (hasAttr(templateMessage, unreadAttribute)) {

					if (templateMessage.attr(unreadAttribute).trim().length > 0) {

						let toMark = templateMessage.find(`[${unreadAttribute}]`).toArray();

						toMark.unshift(templateMessage[0]);

						let firstUnread = templateMessage.find(`[${unreadAttribute}]:first`);
						let urlFirst = firstUnread.attr(unreadAttribute);

						for (let msg of toMark) {

							let toMarkElement = $(msg);
							let urlMarkReaded = toMarkElement.attr(unreadAttribute);
							if (urlMarkReaded.trim().length > 0) {

								let markReadedRequest = postRequest(urlMarkReaded);

								markReadedRequest.done(function (res) {

									if (res.success) {

										if (urlFirst == urlMarkReaded) {

											if (firstUnread.length > 0 && !artificialTriggered) {

												$([document.documentElement, document.body]).animate({
													scrollTop: firstUnread.offset().top
												}, 500, () => {

													toMarkElement.removeAttr(unreadAttribute);

												});

											} else {

												toMarkElement.removeAttr(unreadAttribute);

											}

										} else {

											toMarkElement.removeAttr(unreadAttribute);

										}

									} else {

										console.warn(res.message);

									}

								});

							}

						}

					}
				}

			};
			contentMessages.on('markRread', markRead);

			triggerOpenContent.on('click', () => {

				let othersMessages = contentInbox
					.find(selectorsMessageComponent.messagesComponent.inbox.content.messages.selector).not(templateMessage);
				let othersPreview = othersMessages.find(selectorsMessagesTemplate.preview.selector);
				let othersContentsMessages = othersMessages.find(selectorsMessagesTemplate.content.selector);

				othersContentsMessages.hide();
				othersPreview.removeClass('active');

				if (contentMessages.is(':visible')) {
					contentMessages.hide(500);
					triggerOpenContent.removeClass('active');
				} else {
					contentMessages.fadeIn(500, markRead);
					triggerOpenContent.addClass('active');
					lastOpened = message.id;
				}

			});

			if (lastOpened == message.id && reopenForReply) {
				contentMessages.show();
				reopenForReply = false;
				triggerOpenContent.addClass('active');
				contentMessages.trigger('markRread');
			}

			let replyForm = contentMessages.find(selectorsMessagesTemplate.content.replyForm.selector);
			replyURL = replyForm.attr('action');
			replyMethod = replyForm.attr('method');

			replyForm.on('submit', function (e) {
				e.preventDefault();

				let sendResponseRequest = null;
				let formData = new FormData(replyForm[0])

				if (replyMethod.toUpperCase() == 'POST') {
					sendResponseRequest = postRequest(replyURL, formData);
				} else {
					sendResponseRequest = getRequest(replyURL, formData);
				}

				sendResponseRequest.done((res) => {
					if (res.success) {
						replyForm.find("[name='message']").val('');
						reopenForReply = true;
						_this.loadMessages();
					} else {
						errorMessage('Error', res.message);
					}
				});

				sendResponseRequest.fail((res) => {
					errorMessage('Error', 'Ha ocurrido un error desconocido.');
					console.log(res);
				});

				return false;
			});

			contentInbox.append(templateMessage);
		}
	}

	function hasAttr(element, attr) {
		let has = false;
		if (element instanceof $ && typeof attr == 'string') {
			attr = element.attr(attr);
			has = typeof attr != undefined && attr != null;
		}
		return has;
	}

	function toScss(object) {

		if (typeof object.ignore == 'boolean' && object.ignore) {
			return '';
		}

		let ignore = typeof object.ignore == 'boolean' && object.ignore;
		let scss = '';
		let selector = object.selector;
		let rule = `${selector}{*ELEMENTS*}`;

		for (let name in object) {

			let element = object[name];
			let subSelector = element.selector;
			let subIgnore = typeof element.ignore == 'boolean' && element.ignore;

			if (ignore || subIgnore) {
				continue;
			}

			if (typeof subSelector != 'undefined') {

				let subRule = `${subSelector}{*ELEMENTS*}`;
				let content = '';

				if (typeof element == 'object') {
					for (let subName in element) {
						let ignoreNames = ['selector', 'ignore'];
						if (ignoreNames.indexOf(subName) == -1 && element.hasOwnProperty(subName)) {
							let subElement = element[subName];
							content += toScss(subElement);
						}

					}
				}

				subRule = subRule.replace('*ELEMENTS*', content);
				scss += subRule;
			}

		}
		return rule.replace('*ELEMENTS*', scss);
	}
	return this;
}
