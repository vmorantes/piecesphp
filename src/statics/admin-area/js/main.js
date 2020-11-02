/// <reference path="../../core/js/helpers.js" />
/// <reference path="../../core/js/configurations.js" />
window.addEventListener('load', function (e) {
	timeOnPlatform()
	adminZoneSupportForm()
	adminSitemapUpdate()
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

		buttonSupport.on('click', (e) => {
			e.preventDefault()
			modalSupport.modal('show')
			return false
		})
	}

}

function adminSitemapUpdate() {

	let button = document.querySelector('[sitemap-update-trigger]')

	if (button !== null) {

		let formSitemap = document.createElement('form')
		formSitemap.method = 'POST'
		formSitemap.action = button.dataset.url
		formSitemap = $(formSitemap)

		genericFormHandler(formSitemap)

		button.addEventListener('click', function () {
			formSitemap.submit()
		})

	}

}
