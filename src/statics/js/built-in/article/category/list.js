///<reference path="./BuiltInCategory.js"/>
window.addEventListener('load', function () {

	let requestURL = $('[built-in-categories-url]').attr('built-in-categories-url')

	let categoryManager = new BuiltInCategory({
		requestURL: requestURL,
		page: 1,
		perPage: 3,
		containerSelector: '[built-in-categories-items-js]',
		loadMoreTriggerSelector: '[built-in-categories-load-more-js]',
		onDraw: (item) => {//Debe devolver un HTMLElement o un $|JQuery

			let li = document.createElement('li')
			let a = document.createElement('a')

			a.href = item.link
			a.innerHTML = `<strong>${item.name}</strong>` + (item.description.trim().length > 0 ? item.description : '')

			li.appendChild(a)

			return li

		},
		onEmpty: (container) => {
			container.html(`<h2>${_i18n('articles', 'No hay categorías.')}</h2>`)
		},
	})

	categoryManager.loadItems()

})
