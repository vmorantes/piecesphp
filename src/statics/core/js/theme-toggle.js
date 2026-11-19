/**
 * El botón luna/sol de las pantallas sin sesión con `<html>` propio (theme-toggle.php): pasa `data-theme` de <html> a claro
 * u oscuro, lo recuerda en el navegador y lo aplica antes de pintar. Sin jQuery ni Fomantic: la página de error no los carga.
 * Corre dos veces (en línea en el <head> y como recurso al final, por si la CSP prohíbe lo en línea): la segunda no hace nada.
 */
(function () {
	if (window.pcsThemeToggle === true) {
		return
	}
	window.pcsThemeToggle = true
	const storageKey = 'pcsphp-access-theme'
	const root = document.documentElement
	let stored = null
	//Sin almacenamiento (navegación privada, bloqueado) se queda el modo del sitio.
	try {
		stored = window.localStorage.getItem(storageKey)
	} catch (error) {
		stored = null
	}
	if (stored === 'light' || stored === 'dark') {
		root.setAttribute('data-theme', stored)
	}
	const current = function () {
		const theme = root.getAttribute('data-theme')
		if (theme === 'light' || theme === 'dark') {
			return theme
		}
		return typeof window.matchMedia === 'function' && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
	}
	//El botón dice a qué modo pasa: su texto, en `data-label-dark` y `data-label-light`; su icono, por `data-target-theme`.
	const paint = function () {
		const target = current() === 'dark' ? 'light' : 'dark'
		document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
			const label = button.getAttribute(target === 'dark' ? 'data-label-dark' : 'data-label-light') || ''
			button.setAttribute('aria-label', label)
			button.setAttribute('title', label)
			button.setAttribute('data-target-theme', target)
			button.hidden = false
		})
	}
	document.addEventListener('click', function (event) {
		const button = event.target instanceof Element ? event.target.closest('[data-theme-toggle]') : null
		if (button === null) {
			return
		}
		const next = current() === 'dark' ? 'light' : 'dark'
		root.setAttribute('data-theme', next)
		try {
			window.localStorage.setItem(storageKey, next)
		} catch (error) {
			//Sin almacenamiento cambia igual; solo no se recuerda.
		}
		paint()
	})
	if (typeof window.matchMedia === 'function') {
		const scheme = window.matchMedia('(prefers-color-scheme: dark)')
		if (typeof scheme.addEventListener === 'function') {
			scheme.addEventListener('change', paint)
		}
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', paint)
	} else {
		paint()
	}
})()
