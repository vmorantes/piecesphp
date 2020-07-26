window.addEventListener('load', function () {

	dataTablesServerProccesingOnCards('.table-to-cards', 10, {
		drawCallbackEnd: function (cards) {
			window.dispatchEvent(new Event('canDeleteHeroImageConfig'))
		},
	})

})
