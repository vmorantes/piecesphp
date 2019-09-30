window.addEventListener('load', function(){
	
	let requestURL = $('[built-in-articles-url]').attr('built-in-articles-url')
	let singleURL = $('[built-in-articles-single-url]').attr('built-in-articles-single-url')

	let articleManager = new BuiltInArticle({
		requestURL: requestURL,
		singleURL: singleURL,
		page:1,
		perPage:1,
		containerSelector: '[built-in-articles-items-js]',
		loadMoreTriggerSelector: '[built-in-articles-load-more-js]',
		onDraw: (item) => {//Debe devolver un HTMLElement o un $|JQuery
			console.log(item)
		},
	})

	articleManager.loadItems()

})
