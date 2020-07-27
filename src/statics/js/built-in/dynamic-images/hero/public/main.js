window.addEventListener('loadApp', function (e) {

	let slidershow = document.querySelector('.vm-slideshow')
	let sliderAjaxURL = slidershow !== null ? slidershow.dataset.url : null

	if (sliderAjaxURL !== null) {

		getRequest(sliderAjaxURL).done(function (images) {

			if (Array.isArray(images) && images.length > 0) {

				for (let image of images) {

					let title = typeof image.title == 'string' ? image.title.trim() : ''
					let imageURL = typeof image.image == 'string' ? image.image.trim() : ''
					let description = typeof image.description == 'string' ? image.description.trim() : ''
					let link = typeof image.link == 'string' ? image.link.trim() : ''
					let hasLink = link.length

					if (title.length > 0 && imageURL.length > 0) {

						let item = hasLink ? document.createElement('a') : document.createElement('div')
						let imageElement = document.createElement('img')
						let caption = document.createElement('div')
						let titleElement = document.createElement('div')

						item.appendChild(imageElement)
						item.appendChild(caption)
						caption.appendChild(titleElement)
						titleElement.innerHTML = title
						if (description.length > 0) {
							let descriptionElement = document.createElement('div')
							descriptionElement.innerHTML = description
							caption.appendChild(descriptionElement)
							descriptionElement.classList.add('text')
						}

						item.classList.add('item')

						if (hasLink) {
							item.href = link
							try {
								let url = new URL(link)
								let urlBase = new URL(document.baseURI)
								if (url.origin != urlBase.origin) {
									item.target = '_blank'
								}
							} catch{ }
						}

						imageElement.setAttribute('loading', 'lazy')
						imageElement.setAttribute('src', imageURL)
						caption.classList.add('caption')
						titleElement.classList.add('title')

						slidershow.appendChild(item)

					}

				}
				
				CustomNamespace.slideshow('.vm-slideshow')

			}

		})

	}

})
