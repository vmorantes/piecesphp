window.addEventListener('load', function () {

	let requestURL = $('[built-in-categories-url]').attr('built-in-categories-url')

	let categoryManager = new BuiltInCategory({
		requestURL: requestURL,
		page: 1,
		perPage: 3,
		containerSelector: '[built-in-categories-items-js]',
		loadMoreTriggerSelector: '[built-in-categories-load-more-js]',
		onDraw: (item) => {//Debe devolver un HTMLElement o un $|JQuery
			console.log(item)
		},
	})

	categoryManager.loadItems()

})
