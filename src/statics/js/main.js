/// <reference path="./CustomNamespace.js" />
CustomNamespace.loader()
window.addEventListener('loadApp', function (e) {

	CustomNamespace.loader(null, false)

	let homeArticleManager = new BuiltInArticle({
		requestURL: $('[home-articles-url]').attr('home-articles-url'),
		page: 1,
		perPage: 3,
		containerSelector: '[home-articles-items-js]',
		loadMoreTriggerSelector: '[home-articles-load-more-js]',
		onDraw: (item) => CustomNamespace.itemArticleElement(item, true),
		onEmpty: (container) => {
			container.closest('[home-articles-container]').remove()
		},
	})

	homeArticleManager.loadItems()
		.then(function () {
			$(window).trigger('resize')
		})

})
