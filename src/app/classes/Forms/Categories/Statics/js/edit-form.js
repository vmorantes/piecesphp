window.addEventListener('load', function () {
	Categories.configCategoryForm()
	window.dispatchEvent(new Event('canDeleteCategory'))
})
