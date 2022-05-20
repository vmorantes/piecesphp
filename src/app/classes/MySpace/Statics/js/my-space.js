/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../News/Statics/js/NewsAdapter.js" />	
showGenericLoader('my-space')
window.addEventListener('load', function () {
	removeGenericLoader('my-space')

	let requestNewsURL = $('[data-news-url]').attr('data-news-url')

	let newsManager = new NewsAdapter({
		requestURL: requestNewsURL,
		page: 1,
		perPage: 6,
		containerSelector: '[news-js] .content',
		loadMoreTriggerSelector: '[news-load-more-js]',
		onDraw: (item, parsed) => {
			const buttonMore = parsed.find('[see-more]')
			buttonMore.off('click')
			buttonMore.on('click', function (e) {
				e.preventDefault()
				$('body').modal({
					title: parsed.find('>.header').html(),
					class: 'modal-news-content',
					closeIcon: true,
					content: atob(parsed.data('content-b64')),
				}).modal('show');
			})
			return parsed
		},
		onEmpty: (container) => {
			const mainContainer = container.closest('.news-content')
			mainContainer.addClass('gradient non-results')
			mainContainer.find('>.title').hide()
		},
	})

	newsManager.loadItems()

})
