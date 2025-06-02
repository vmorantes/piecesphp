/// <reference path="../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../ApplicationCallAdapter.js" />
CustomNamespace.loader()
window.addEventListener('load', function (e) {

	CustomNamespace.loader(null, false)

	let requestURL = $('[data-application-call-url]').attr('data-application-call-url')

	if (requestURL.length > 0) {
		let articleManager = new ApplicationCallAdapter({
			requestURL: requestURL,
			page: 1,
			perPage: 10,
			containerSelector: '[application-calls-js]',
			loadMoreTriggerSelector: '[application-calls-load-more-js]',
			onDraw: (item, parsed) => {
				parsed.addClass('horizontal fluid centered')
				return parsed
			},
			onEmpty: (container) => {
				container.closest('.posts-list').html(`<h2>............</h2>`)
			},
		})

		articleManager.loadItems()
	}
})
