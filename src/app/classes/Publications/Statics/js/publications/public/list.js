/// <reference path="../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../PublicationsAdapter.js" />
CustomNamespace.loader()
window.addEventListener('load', function (e) {

	CustomNamespace.loader(null, false)

	let requestURL = $('[data-publication-url]').attr('data-publication-url')

	if (requestURL.length > 0) {
		let articleManager = new PublicationsAdapter({
			scrollToOnLoadMore: true,
			requestURL: requestURL,
			page: 1,
			perPage: 10,
			containerSelector: '[publications-js]',
			loadMoreTriggerSelector: '[publications-load-more-js]',
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
