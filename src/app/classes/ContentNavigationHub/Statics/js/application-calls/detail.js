/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function (e) {
	const loaderName = generateUniqueID()
	showGenericLoader(loaderName)

	handleDownloableElements()
	handleOpenableLinksElements()
	handleShareAction()

	removeGenericLoader(loaderName)

	function handleDownloableElements() {
		const attrDataSelector = 'trigger-download-link'
		const element = $(`[data-${attrDataSelector}]`)
		element.on('click', function (e) {
			e.preventDefault()
			const link = $(e.currentTarget).data(attrDataSelector)
			if (typeof link == 'string' && link.trim().length > 0) {
				const a = document.createElement('a')
				a.download = ""
				a.href = link
				a.target = '_blank'
				a.click()
				a.remove()
			}
		})
	}
	function handleOpenableLinksElements() {
		const attrDataSelector = 'trigger-open-link'
		const element = $(`[data-${attrDataSelector}]`)
		element.on('click', function (e) {
			e.preventDefault()
			const link = $(e.currentTarget).data(attrDataSelector)
			if (typeof link == 'string' && link.trim().length > 0) {
				const a = document.createElement('a')
				a.href = link
				a.target = '_blank'
				a.click()
				a.remove()
			}
		})
	}

	function handleShareAction() {
		$('[share-action]').off('click').on('click', function (e) {
			e.preventDefault()
			const element = $(e.currentTarget)
			console.log(element)
			shareLinkContent({
				title: element.data('title'),
				text: element.data('text'),
				url: element.data('url'),
				onCopy: (url) => console.log('Copiado:', url),
				onShare: (shareData) => console.log('Compartido:', shareData)
			})
		})
	}
})
