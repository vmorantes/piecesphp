//Referencias para ayuda del editor
/// <reference path="./CustomNamespace.js" />
/// <reference path="../../app/classes/Publications/Statics/js/PublicationsAdapter.js" />
CustomNamespace.loader()
window.addEventListener('load', function (e) {

	//Configurar carga de artículos del blog
	let articlesContainerSelector = '[articles-container]'
	let articlesContainer = $(articlesContainerSelector)
	if (articlesContainer.length > 0) {

		let homeArticleManager = new PublicationsAdapter({
			requestURL: articlesContainer.data('route'),
			page: 1,
			perPage: 3,
			containerSelector: articlesContainerSelector,
			loadMoreTriggerSelector: '[load-more-articles]',
			onDraw: (item, parsed, container) => {
				//Recibo el item y devuelvo el HTML
				return item
			},
			onEmpty: (container) => {
				//Si no hay artículos elimino la sección
				container.closest('.content').remove()
			},
		})

		//Cargar items
		homeArticleManager.loadItems()
			.then(function () {
				console.log('Items cargados')
			})
	}

	//Configurar botón de "ir arriba"
	let toTopButton = $('button.to-top')
	toTopButton.on('click', function () {
		$("html, body").animate({ scrollTop: 0 }, 350);
	})

	//Formulario de suscriptor
	let formSuscriber = $('.ui.form.add-suscriber')
	if (formSuscriber.length > 0) {
		genericFormHandler(formSuscriber, {
			onSuccess: function () {
				formSuscriber.toArray().map(e => e.reset())
				formSuscriber.find('button').toArray().map(e => e.blur())
			}
		})
	}

	//Activar ejemplo de slideshow estático
	CustomNamespace.slideshow('.slideshow-static')

	//Configurar barra de navegación
	configurateNavigation()

	//Cambio de hash URL	
	$('[smooth-to-trigger]').on('click', function (e) {
		e.preventDefault()
		const href = e.currentTarget.href
		if (typeof href == 'string' && href.length > 0) {
			try {
				const hash = new URL(href).hash
				const target = $(`[smooth-scroll]${hash}`)
				CustomNamespace.scrollTo(target)
			} catch {

			}
		}
	})

	CustomNamespace.loader(null, false)
})

function configurateNavigation() {

	let body = $('body')
	let openNavButton = $('.navigation .open-nav')
	let nav = $('body > .navigation')
	let navSubMenus = nav.find('.items .item.menu')

	body.css('padding-top', `70px`)
	let repeatedNumber = 0
	let navHInterval = setInterval(function () {
		repeatedNumber++
		let navH = nav.height()
		body.css('padding-top', `${navH}px`)
		if (repeatedNumber >= 4) {
			clearInterval(navHInterval)
		}
	}, 1000)

	let toggleNav = function (onlyClose = false) {
		if (!nav.hasClass('active') && !onlyClose) {
			nav.addClass('active')
		} else {
			nav.removeClass('active')
			navSubMenus.removeClass('active')
		}
	}

	openNavButton.on('click', function (e) {
		e.stopPropagation()
		e.preventDefault()
		toggleNav()
	})

	navSubMenus.on('click', function (e) {

		let that = $(e.currentTarget)
		let originalTarget = e.target
		let continueNormalBehavior = false

		if (originalTarget instanceof HTMLElement && originalTarget.tagName.toLocaleLowerCase() == 'a') {
			continueNormalBehavior = true
		}

		if (!continueNormalBehavior) {

			e.stopPropagation()
			e.preventDefault()

			if (!that.hasClass('active')) {
				that.addClass('active')
				navSubMenus.not(that).removeClass('active')
			} else {
				that.removeClass('active')
			}

			let subContainer = that.find('.subitems')

			//Verificar si el submenú se desborda
			if (subContainer.length > 0) {
				/**
				 * @property {HTMLElement}
				 */
				const element = subContainer.get(0)
				const bounding = element.getBoundingClientRect() // {top: Number, left: Number, right: Number, bottom: Number}
				const viewportMeasuresH = (window.innerHeight || document.documentElement.clientHeight)
				const viewportMeasuresW = (window.innerWidth || document.documentElement.clientWidth)
				console.log(bounding)

				if (bounding.top < 0) {
					//Se sale por arriba
				}

				if (bounding.left < 0) {
					//Se sale por la izquierda
				}

				if (bounding.bottom > viewportMeasuresH) {
					//Se sale por abajo
				}

				if (bounding.right > viewportMeasuresW) {
					//Se sale por la derecha
					subContainer.css({
						left: 'initial',
						right: '0',
					})
				} else {
					setTimeout(() => {
						if (subContainer.closest('.item').not('.active').length > 0) {
							subContainer.removeAttr('style')
						}
					}, 750)
				}
			}

		}

	})

	body.on('click', function (e) {
		toggleNav(true)
	})

}
