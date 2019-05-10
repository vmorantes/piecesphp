$(document).ready(function () {
	let unreadAttribute = 'unread-threads'
	let unreadMenu = $(`[${unreadAttribute}]`)

	if (unreadMenu.length > 0) {
		let urlRequest = unreadMenu.attr(unreadAttribute)

		if (typeof urlRequest == 'string' && urlRequest.trim().length > 0) {

			handlerUnreadVerify()

			setInterval(handlerUnreadVerify, 5000)

			function handlerUnreadVerify() {
				let request = postRequest(urlRequest)

				request.done(function (res) {

					let status = res.statusMessages

					if (status.hasUnread) {

						let numSmall = $(`<span number-unread class="floating ui red label">${status.unreaded}</span>`)
						numSmall.css({
							left: '80%',
						})
						numSmall = ` ${numSmall[0].outerHTML} `
						unreadMenu.find('.title-group span').css({
							position:'relative'
						})
						unreadMenu.find('.title-group span [number-unread]').remove()
						unreadMenu.find('.title-group span').prepend(numSmall)

					}

				})

				request.fail(function (res) {
					console.log(res)
				})
			}
		}
	}
})
