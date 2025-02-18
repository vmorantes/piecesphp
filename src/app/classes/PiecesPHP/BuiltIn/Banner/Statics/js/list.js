/// <reference path="../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	//Tablas	
	const imageModalID = generateUniqueID()
	const tables = [
		{
			selector: 'table[url].all',
			ajaxURLAttribute: 'url',
			table: null,
			dataTable: null,
			length: 20,
			options: {
				responsive: false,
				autoWidth: false,
				drawCallback: function () {
					window.dispatchEvent(new Event('canDeleteBuiltInBanner'))
					$('[data-image-preview]').click(function (e) {
						e.preventDefault()
						const currentTarget = $(e.currentTarget)
						const src = currentTarget.attr('data-image-preview')
						openImageModal(src, imageModalID)
					})
				},
				initComplete: function () {
					configMirrorScrollX('namespace.mirror-scroll-x.all', '.mirror-scroll-x.all')
				},
			},
		},
	]

	for (const tableConfig of tables) {
		const selector = tableConfig.selector
		const ajaxURLAttribute = tableConfig.ajaxURLAttribute
		const length = tableConfig.length
		const options = tableConfig.options
		tableConfig.table = $(selector)
		let ajaxURL = tableConfig.table.attr(ajaxURLAttribute)
		tableConfig.dataTable = dataTableServerProccesing(tableConfig.table, ajaxURL, length, options).DataTable()
	}

	//Tabs
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
			for (const tableConfig of tables) {
				tableConfig.dataTable.draw()
			}
		}
	})

	function openImageModal(src, imageModalID) {
		$(`#${imageModalID}`).remove()
		let modalHtml = `<div id="${imageModalID}" class="ui modal">
							<i class="close icon"></i>
							<div class="content">
								<img src="${src}" class="ui centered image fluid">
							</div>
						</div>`
		$('body').append(modalHtml)
		$(`#${imageModalID}`).modal({
			onHidden: function () {
				$(`#${imageModalID}`).remove()
			}
		}).modal('show')
	}
})
