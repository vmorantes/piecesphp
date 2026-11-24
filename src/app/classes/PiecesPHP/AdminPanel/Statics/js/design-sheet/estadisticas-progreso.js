window.addEventListener('load', function () {
	//El círculo del tema: $.fn.progress lee data-percent y pone --progress-percent (la vista no lleva style=).
	if (typeof $ === 'undefined' || typeof $.fn.progress !== 'function') {
		return
	}
	$('[example-progress]').progress()
})
