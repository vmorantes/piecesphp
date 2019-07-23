$(document).ready(function (e) {
	timeOnPlatform()
	adminZoneSupportForm()
})

function timeOnPlatform() {
	let attr = 'timer-platform-js'
	let selector = `[${attr}]`
	let timerElement = $(selector)
	if (timerElement.length > 0) {

		let data = timerElement.attr(attr)

		data = JSON.parse(atob(data))

		let url = data.url
		let userID = data.user_id
		let interval = 10000

		setInterval(req, interval)

		function req() {

			let formData = new FormData()
			formData.set('user_id', userID)
			formData.set('seconds', interval / 1000)
			let req = postRequest(url, formData)

			req.done(function (res) {
			})
			req.fail(function (res) {
				console.log(res)
			})
		}

	}
}

function adminZoneSupportForm() {

	let buttonSupportSelector = '[support-button-js]'
	let modalSupportSelector = '.ui.modal[support-js]'
	let formSupportSelector = modalSupportSelector + ' form'
	let modalSupport = $(modalSupportSelector)
	let buttonSupport = $(buttonSupportSelector)

	if (buttonSupport.length > 0) {
		genericFormHandler(formSupportSelector)

		buttonSupport.click((e) => {
			e.preventDefault()
			modalSupport.modal('show')
			return false
		})
	}

}
