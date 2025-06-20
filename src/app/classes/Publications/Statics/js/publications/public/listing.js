//Referencias para ayuda del editor
/// <reference path="../../../../../../../statics/js/CustomNamespace.js" />
/// <reference path="../../PublicationsAdapter.js" />
CustomNamespace.loader()
window.addEventListener(pcsphpGlobals.events.configurationsAndWindowLoad, function (e) {

	//Slugs para omitir en la carga del listado regular
	const showedSlugsNews = []

	//Configurar carga de bloque de tres
	let publicationsSpecialBlockSectionSelector = `[publications-special-block-section]`
	let publicationsSpecialBlockContainerSelector = `${publicationsSpecialBlockSectionSelector} [publications-special-block]`
	let publicationsSpecialBlockLoadMoreTriggerSelector = `${publicationsSpecialBlockContainerSelector} [publications-special-block-load-more]`
	let publicationsSpecialBlockSection = $(publicationsSpecialBlockSectionSelector)
	if (publicationsSpecialBlockSection.length > 0) {

		//Configurar manejador de bloque
		let publicationsSpecialBlockContainer = $(publicationsSpecialBlockContainerSelector)
		let publicationsSpecialBlockURL = new URL(publicationsSpecialBlockContainer.data('url'))
		publicationsSpecialBlockURL.searchParams.set('random', 'yes')

		let publicationsSpecialBlockHandler = new PublicationsAdapter({
			requestURL: publicationsSpecialBlockURL.href,
			page: 1,
			perPage: 5,
			containerSelector: publicationsSpecialBlockContainerSelector,
			loadMoreTriggerSelector: publicationsSpecialBlockLoadMoreTriggerSelector,
			onDraw: (item, parsed) => {
				//Recibo el item y devuelvo el HTML o el item para que se pueda usar en el componente
				return item
			},
			onEmpty: (container) => {
				//Si no hay artículos elimino la sección
				container.closest('.content').remove()
			},
		})

		//Obtener plantilla de bloque		
		let publicationsSpecialBlockComponentesURL = new URL(publicationsSpecialBlockContainer.data('components-provider-url'))
		let elementsToAdd = []
		let containerElementsToAdd = null

		publicationsSpecialBlockHandler.loadItems(false).then(function (resolution) {
			const response = resolution.response
			containerElementsToAdd = resolution.container
			elementsToAdd = response.elements
			elementsToAdd.forEach((e) => {
				publicationsSpecialBlockComponentesURL.searchParams.append('slugs[]', e.preferSlug)
			})
		}).then(function () {

			//Número de elementos renderizados (útil para saber si se ha cargado el elemento principal o secundario)
			let publicationsRendered = 0

			getRequest(publicationsSpecialBlockComponentesURL).then(function (response) {

				const components = $(response)

				//Cargar
				let publicationsSecondaryContainerAdded = false
				let publicationsSecondaryContainer = null
				publicationsSpecialBlockHandler.onDraw(function (item, parsed, container) {
					return item
				})
				const container = containerElementsToAdd
				const elements = elementsToAdd
				const totalElements = elementsToAdd.length

				if (totalElements > 0) {

					elements.forEach(function (element) {

						const componentSecondaryItemsContainerHTML = components.find(`component[name="special-block-container"]`).get(0).innerHTML
						let componentMainHTML = components.find(`component[name="special-main-item"]`).filter(`[data-slug="${element.preferSlug}"]`)
						let componentSecondaryHTML = components.find(`component[name="special-secondary-item"]`).filter(`[data-slug="${element.preferSlug}"]`)

						if (componentMainHTML.length > 0 || componentSecondaryHTML.length > 0) {

							componentMainHTML = componentMainHTML.get(0).innerHTML
							componentSecondaryHTML = componentSecondaryHTML.get(0).innerHTML

							if (publicationsRendered == 0) {
								//Cargar elemento principal
								showedSlugsNews.push(element.slug)
								container.append($(componentMainHTML))
							} else {
								//Cargar elementos secundarios
								if (!publicationsSecondaryContainerAdded) {
									publicationsSecondaryContainer = $(componentSecondaryItemsContainerHTML)
									container.append(publicationsSecondaryContainer)
									publicationsSecondaryContainerAdded = true
								}
								publicationsSecondaryContainer.append($(componentSecondaryHTML))
							}
							publicationsRendered++

						}
					})

				} else {
					publicationsSpecialBlockSection.remove()
				}

			})
		})
	}

	//Configurar carga de listado regular
	let publicationsRegularSectionSelector = `[publications-regular-section]`
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
				parsed.addClass('horizontal fluid centered')
				return parsed
			},
			onEmpty: (container) => {
				//Si no hay artículos elimino la sección
				publicationsRegularSection.remove()
			},
		})

		//Cargar listado		
		publicationsRegularHandler.loadItems().then(function (resolution) { })

	}

	CustomNamespace.loader(null, false)
})