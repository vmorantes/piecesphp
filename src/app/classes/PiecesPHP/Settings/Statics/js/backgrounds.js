showGenericLoader('backgrounds')
window.addEventListener('load', () => {

	new ContentImageEditor()

	//La ruta guardada lleva la huella y solo la conoce el servidor: tras guardar un fondo se recarga, y cada tarjeta sale
	//con la ruta real que necesita Eliminar. El evento no burbujea: se escucha en cada tarjeta.
	const savedNoticeKey = 'pcsphp-backgrounds-saved'
	const editorTexts = document.querySelector('[data-image-editor]')
	//El aviso del editor se pierde con la recarga: se repite al volver, con el mismo texto (agregada o reemplazada).
	try {
		const kind = sessionStorage.getItem(savedNoticeKey)
		if (kind !== null) {
			sessionStorage.removeItem(savedNoticeKey)
			if (editorTexts !== null && (kind === 'added' || kind === 'replaced')) {
				successMessage(editorTexts.getAttribute(`data-text-${kind}`) || '', editorTexts.getAttribute(`data-text-${kind}-detail`) || '')
			}
		}
	} catch (error) {
		//Sin sessionStorage (privado o bloqueado) solo se pierde el aviso.
	}
	document.querySelectorAll('[data-image-card]').forEach((card) => {
		//Si estaba vacía al elegir el archivo, el aviso es «agregada»; si no, «reemplazada».
		const input = card.querySelector('input[type=file]')
		if (input !== null) {
			input.addEventListener('change', () => {
				card.dataset.wasEmpty = card.querySelector('.checkered.image img') === null ? 'yes' : 'no'
			})
		}
		card.addEventListener('image-card-replaced', () => {
			try {
				sessionStorage.setItem(savedNoticeKey, card.dataset.wasEmpty === 'yes' ? 'added' : 'replaced')
			} catch (error) {
				//Sin sessionStorage solo se pierde el aviso.
			}
			location.reload()
		})
	})

	//Fondos del inicio de sesión: de uno a cinco, las imágenes primero y las vacías detrás, como los pinta el servidor
	//(SettingsController::loginBackgroundCards()). Aquí se mantiene ese orden al eliminar, sin recargar.
	const list = document.querySelector('[data-login-backgrounds]')
	if (list !== null) {
		const text = (name) => list.getAttribute(`data-text-${name}`) || ''
		const cards = () => Array.from(list.querySelectorAll('[data-image-card]'))
		const hasImage = (card) => card.querySelector('.checkered.image img') !== null
		const allFields = cards().map((card) => card.dataset.field).sort()

		const layout = () => {
			const full = cards().filter(hasImage)
			const empty = cards().filter((card) => !hasImage(card))
			//Una tarjeta vacía sube con el primer nombre libre: bg<n> en el servidor.
			const used = full.map((card) => card.dataset.field)
			const free = allFields.filter((field) => !used.includes(field))
			empty.forEach((card, index) => {
				card.dataset.field = free[index]
			})
			full.concat(empty).forEach((card, index) => {
				list.appendChild(card)
				const title = list.getAttribute('data-title-template').replace('${n}', String(index + 1))
				card.dataset.title = title
				card.dataset.editLabel = list.getAttribute('data-edit-template').replace('${title}', title)
				card.querySelector('.header').textContent = title
				const edit = card.querySelector('[data-image-card-edit]')
				if (edit !== null) {
					edit.setAttribute('aria-label', card.dataset.editLabel)
					edit.querySelector('img').alt = title
				}
				const holder = card.querySelector('[data-image-card-delete-holder]')
				const button = holder.querySelector('[data-image-card-delete]')
				const onlyOne = full.length <= 1
				holder.hidden = !hasImage(card)
				button.disabled = onlyOne
				holder.setAttribute('data-tooltip', onlyOne ? text('only-one') : text('delete'))
			})
		}

		const empty = (card) => {
			card.querySelector('.checkered.image').replaceChildren()
			const download = card.querySelector('[data-image-card-download]')
			if (download !== null) {
				const template = document.createElement('template')
				template.setAttribute('data-image-card-download-template', '')
				download.setAttribute('href', '#')
				template.content.appendChild(download.cloneNode(true))
				download.replaceWith(template)
			}
			card.querySelector('[data-image-card-delete]').dataset.value = ''
		}


		list.addEventListener('click', (event) => {
			const button = event.target.closest('[data-image-card-delete]')
			if (button === null || button.disabled) {
				return
			}
			const card = button.closest('[data-image-card]')
			confirmAction({ message: $('<p></p>').text(text('confirm')).prop('outerHTML'), confirmText: text('delete'), confirmClass: 'red' }).then((confirmed) => {
				if (!confirmed) {
					return
				}
				const formData = new FormData()
				formData.set('background', button.dataset.value)
				button.classList.add('loading', 'disabled')
				postRequest(list.dataset.deleteUrl, formData).done((response) => {
					button.classList.remove('loading', 'disabled')
					if (response.success) {
						empty(card)
						layout()
						successMessage(text('deleted'), text('deleted-detail'))
					} else {
						errorMessage(text('error'), response.message)
					}
				}).fail(() => {
					button.classList.remove('loading', 'disabled')
					errorMessage(text('error'), '')
				})
			})
		})

		layout()
	}

	removeGenericLoader('backgrounds')

})
