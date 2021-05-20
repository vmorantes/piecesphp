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
			onDraw: (item, parsed) => {
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

	//Configurar barra de navegación
	configurateNavigation()

	//Configurar botón de "ir arriba"
	let toTopButton = $('button.to-top')
	toTopButton.on('click', function () {
		$("html, body").animate({ scrollTop: 0 }, 350);
	})

	CustomNamespace.loader(null, false)

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
})

function configurateNavigation() {

	let body = $('body')
	let openNavButton = $('.navigation .open-nav')
	let nav = $('body > .navigation')
	let navSubMenus = nav.find('.items .item.menu')

	let navH = nav.height()

	body.css('padding-top', `${navH}px`)

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
			} else {
				that.removeClass('active')
			}

		}

	})

	body.on('click', function (e) {
		toggleNav(true)
	})

}
