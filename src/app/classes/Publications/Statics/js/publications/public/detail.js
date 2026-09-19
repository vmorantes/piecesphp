//Referencias para ayuda del editor
/// <reference path="../../../../../../../statics/js/CustomNamespace.js" />
/// <reference path="../../PublicationsAdapter.js" />
CustomNamespace.loader()
window.addEventListener(pcsphpGlobals.events.configurationsAndWindowLoad, function (e) {

	//Slugs para omitir en la carga del listado regular
	const showedSlugsNews = [
		getVariableFromHTML('preferSlug')
	]

	//Configurar carga de listado regular
	let publicationsRegularSectionSelector = `[publications-regular-section-detail]`
	let publicationsRegularContainerSelector = `${publicationsRegularSectionSelector} [publications-regular-block]`
	let publicationsRegularLoadMoreTriggerSelector = `${publicationsRegularSectionSelector} [publications-regular-block-load-more]`
	let publicationsRegularSection = $(publicationsRegularSectionSelector)
	if (publicationsRegularSection.length > 0) {

		//Configurar manejador de listado
		let publicationsRegularContainer = $(publicationsRegularContainerSelector)
		let publicationsRegularURL = new URL(publicationsRegularContainer.data('url'))
		for (const slug of showedSlugsNews) {
			publicationsRegularURL.searchParams.append('ignoreSlugs[]', slug)
		}

		let publicationsRegularHandler = new PublicationsAdapter({
			requestURL: publicationsRegularURL.href,
			page: 1,
			perPage: 3,
			containerSelector: publicationsRegularContainerSelector,
			loadMoreTriggerSelector: publicationsRegularLoadMoreTriggerSelector,
			onDraw: (item, parsed) => {
				//Recibo el item y devuelvo el HTML
				return item
			},
			onEmpty: (container) => {
				//Si no hay artículos elimino la sección
				publicationsRegularSection.remove()
			},
		})

		//Cargar listado		
		publicationsRegularHandler.loadItems().then(function (resolution) { console.log(resolution) })

	}

	CustomNamespace.loader(null, false)
})