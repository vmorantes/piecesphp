window.addEventListener("load", function () {

	let toggleMessageForm = $("[toggle-form]")
	let modalNewMessage = $('.message-component-new-message-modal')

	$(".ui.dropdown").dropdown()

	toggleMessageForm.click(function (e) {
		modalNewMessage.modal("show")
	})

	//Configuración de mensajes
	let templates = document.querySelector("template[messenger]")

	if (typeof MessagesComponent == "function" && templates !== null) {

		templates = $(templates.content)
		sendRoute = templates.find("[send-route]").attr("send-route")
		loadRoute = templates.find("[load-route]").attr("load-route")
		idSearcher = parseInt(templates.find("[user-id]").attr("user-id"))

		responseRoute = templates
			.find("[response-route]")
			.attr("response-route")

		let messenger = new MessagesComponent(1, 20, {
			fromID: idSearcher,
			defaultToID: null,
			sessionToken: pcsphp.authenticator.getJWT(),
			loadMessagesRoute: loadRoute,
			sendMessageRoute: sendRoute,
			sendResponseRoute: responseRoute,
			sendFormSelector: "[messages-component-external-editor]",
			container: "[messages-component-container] [messages-component]",
			loadMoreButtonSelector: "[message-component-load-more]",
			closeOnOutsideClick: false,
			messagePreviewTemplate: {
				template: templates.find("[preview]")
			},
			messageTemplate: {
				template: templates.find("[main-message-body]")
			},
			subMessageTemplate: {
				template: templates.find("[sub-message]")
			},
			responseFormTemplate: {
				template: templates.find("[response-message-form]")
			},
			onToggle: function (preview, conversation, id) {

				let isOpen = messenger.conversationIsOpened(id)
				let windowWidth = window.innerWidth

				if (windowWidth <= 1220) {

					if (isOpen) {

						messenger.getPreviewsContainer().hide()
						messenger.getLoadMoreButton().hide()

					} else {

						messenger.getPreviewsContainer().show()
						messenger.getLoadMoreButton().show()

					}

				}

			},
			onWindowResize: function (previewsContainer, conversationsContainer, lastOpened, event) {

				let loadMoraButton = messenger.getLoadMoreButton()
				let windowWidth = window.innerWidth

				if (windowWidth > 1220) {

					if (!previewsContainer.is(':visible')) {
						previewsContainer.show()
					}

					if (!loadMoraButton.is(':visible')) {
						loadMoraButton.show()
					}

				}

			},
		})

		messenger.loadMessages({
			timeReload: null
		})

		let refreshButton = $("[refresh-messages]")

		refreshButton.on("click", () => {
			messenger.loadMessages()
		})
	}
})
