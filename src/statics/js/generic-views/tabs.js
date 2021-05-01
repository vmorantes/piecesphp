/// <reference path="../CustomNamespace.js" />
CustomNamespace.loader()
window.addEventListener('load', function (e) {

	configTabs()

	CustomNamespace.loader(null, false)

	function configTabs() {

		let isInitial = true
		let tabs = $('.tabs-menu-items [data-tab-target]')

		if (tabs.length > 0) {
			verifyHash()
			window.addEventListener('hashchange', function () {
				verifyHash()
			})
			CustomNamespace.tabs('active')
		}

		function verifyHash() {

			let currentURL = new URL(window.location.href)
			let hashURL = currentURL.hash
			hashURL = typeof hashURL == 'string' && hashURL.replace('#', '').trim().length > 0 ? hashURL.replace('#', '').trim() : null

			if (hashURL !== null) {

				let tabToSelect = tabs.filter(`[data-tab-target="${hashURL}"]`)

				if (tabToSelect.length > 0) {

					if (isInitial) {
						tabs.attr('data-tab-active', 'no')
						tabToSelect.attr('data-tab-active', 'yes')
						isInitial = false
					} else {
						tabToSelect.trigger('click')
					}

				}

			}

		}

	}

})
