
CustomNamespace.loader()
window.addEventListener('loadApp', function (e) {

	CustomNamespace.loader(null, false)

	let requestURL = $('[built-in-articles-url]').attr('built-in-articles-url')

	let articleManager = new BuiltInArticle({
		requestURL: requestURL,
		page: 1,
		perPage: 6,
		containerSelector: '[built-in-articles-items-js]',
		loadMoreTriggerSelector: '[built-in-articles-load-more-js]',
		onDraw: (item) => CustomNamespace.itemArticleElement(item),
		onEmpty: (container) => {
			container.parent().html(`<h2>${_i18n('articles', 'No hay artículos.')}</h2>`)
		},
	})

	articleManager.loadItems()
})
