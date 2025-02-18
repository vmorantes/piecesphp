/// <reference path="../../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../../statics/core/js/helpers.js" />
/// <reference path="../BuiltInBannerAdapter.js" />
CustomNamespace.loader()
window.addEventListener('load', function (e) {

	CustomNamespace.loader(null, false)

	let containerSelector = '[built-in-banner-js]'
	let slideshow = $(containerSelector)
	let bluredParent = slideshow.closest('.blured')
	let requestURL = slideshow.data('url')

	if (requestURL.length > 0) {
		let bannerManager = new BuiltInBannerAdapter({
			requestURL: requestURL,
			page: 1,
			perPage: 10,
			containerSelector: containerSelector,
			onDraw: (item, parsed) => {
				const isLink = parsed.tagName.toLowerCase() == 'a'
				if (isLink) {
					try {
						let url = new URL(parsed.href)
						let urlBase = new URL(document.baseURI)
						if (url.origin != urlBase.origin) {
							parsed.target = '_blank'
							parsed.rel = 'noreferrer'
						}
					} catch { }
				}
				return parsed
			},
			onEmpty: (container) => { },
		})

		bannerManager.loadItems().then(function () {
			CustomNamespace.slideshow(containerSelector, 5, function (srcDesktop, srcMobile, currentItem) {
				if (bluredParent.length > 0) {
					bluredParent.get(0).style.backgroundImage = `url("${$(currentItem).find('img').filter(':visible').attr('src')}")`
				}
			})
		})
	}
})
