window.addEventListener('load', function () {

	let requestURL = $('[built-in-articles-url]').attr('built-in-articles-url')

	let articleManager = new BuiltInArticle({
		requestURL: requestURL,
		page: 1,
		perPage: 1,
		containerSelector: '[built-in-articles-items-js]',
		loadMoreTriggerSelector: '[built-in-articles-load-more-js]',
		onDraw: (item) => {//Debe devolver un HTMLElement o un $|JQuery
			console.log(item)
			console.log(item.preferDate.date)
			console.log(articleManager.processDate(item.preferDate.date))
		},
		onEmpty: (container) => {
			container.html(`<h2>${_i18n('articles', 'No hay artículos.')}</h2>`)
		},
	})

	articleManager.loadItems()

})
