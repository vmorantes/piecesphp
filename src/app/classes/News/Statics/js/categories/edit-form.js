window.addEventListener('load', function () {
	NewsCategories.configNewsCategoryForm()
	window.dispatchEvent(new Event('canDeleteNewsCategory'))
})
