/// <reference path="../../js/helpers.js" />
window.addEventListener('load', () => {

	const view = document.querySelector('[data-routes-view]')
	if (view === null) {
		return
	}
	const search = view.querySelector('[data-routes-search]')
	const empty = view.querySelector('[data-routes-empty]')
	const accordion = view.querySelector('[data-routes-accordion]')
	const groups = Array.from(accordion.querySelectorAll('[data-routes-group]')).map((title) => {
		const content = title.nextElementSibling
		return {
			title,
			content,
			rows: Array.from(content.querySelectorAll('tr[data-search]')).map((row) => ({
				row,
				text: (row.getAttribute('data-search') || '').toLowerCase(),
			})),
		}
	})

	$(accordion).accordion({ exclusive: false })

	const filter = () => {
		const query = search.value.trim().toLowerCase()
		let matches = 0
		for (const group of groups) {
			let groupMatches = 0
			for (const entry of group.rows) {
				const visible = query === '' || entry.text.includes(query)
				entry.row.style.display = visible ? '' : 'none'
				if (visible) {
					groupMatches++
				}
			}
			matches += groupMatches
			//Sin texto, todo cerrado como al entrar; con texto, abierto lo que tiene alguna fila.
			const open = query !== '' && groupMatches > 0
			group.title.style.display = groupMatches > 0 ? '' : 'none'
			group.title.classList.toggle('active', open)
			group.content.classList.toggle('active', open)
		}
		empty.hidden = matches > 0
	}

	search.addEventListener('input', filter)

})
