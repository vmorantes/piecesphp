/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../News/Statics/js/NewsAdapter.js" />	
showGenericLoader('my-space')
window.addEventListener('load', function () {
	removeGenericLoader('my-space')

	let newsMainContainerSelector = '.news-content'
	const mainContainer = document.querySelector(newsMainContainerSelector)

	if (mainContainer !== null) {

		let requestNewsURL = mainContainer.dataset.url
		let loadMoreTriggerSelector = '[news-load-more-js]'
		let containerElementsSelector = `${newsMainContainerSelector} .content`

		const newsManager = new NewsAdapter({
			requestURL: requestNewsURL,
			page: 1,
			perPage: 6,
			containerSelector: containerElementsSelector,
			loadMoreTriggerSelector: loadMoreTriggerSelector,
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
					}).modal('show')

				})

				return parsed
			},
			onEmpty: (container) => {
				mainContainer.addClass('gradient non-results')
				mainContainer.find('>.title').hide()
			},
		})

		const resizeObserver = new ResizeObserver(/** @type {ResizeObserverEntry[]} */function (entries) {

			const width = mainContainer.offsetWidth

			if (width <= pcsphpGlobals.responsive.sizes.rsTablet) {
				mainContainer.classList.add(pcsphpGlobals.responsive.class.rsTablet)
			} else {
				mainContainer.classList.remove(pcsphpGlobals.responsive.class.rsTablet)
			}
		})
		resizeObserver.observe(mainContainer)

		newsManager.loadItems()

	}

})
