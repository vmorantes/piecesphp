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
		//La ruta llega del atributo ya decodificada: va por attr(), nunca dentro de una cadena de HTML.
		const modal = $('<div class="ui modal"><i class="close icon"></i><div class="content"></div></div>').attr('id', imageModalID)
		modal.find('.content').append($('<img class="ui centered image fluid">').attr('src', src))
		$('body').append(modal)
		$(`#${imageModalID}`).modal({
			onHidden: function () {
				$(`#${imageModalID}`).remove()
			}
		}).modal('show')
	}
})
