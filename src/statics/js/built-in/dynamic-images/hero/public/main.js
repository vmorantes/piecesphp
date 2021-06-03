window.addEventListener('load', function (e) {

	let slidershow = document.querySelector('.slideshow-main-home')
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

					if (imageURL.length > 0) {

						let item = hasLink ? document.createElement('a') : document.createElement('div')
						let imageElement = document.createElement('img')
						let withCaption = description.length > 0 || title.length > 0

						item.classList.add('item')

						if (hasLink) {
							item.href = link
							try {
								let url = new URL(link)
								let urlBase = new URL(document.baseURI)
								if (url.origin != urlBase.origin) {
									item.target = '_blank'
									item.rel = 'noreferrer'
								}
							} catch { }
						}

						imageElement.setAttribute('loading', 'lazy')
						imageElement.setAttribute('src', imageURL)
						imageElement.setAttribute('alt', imageURL.indexOf('/') !== -1 ? imageURL.split('/').reverse()[0] : imageURL)
						item.appendChild(imageElement)

						if (withCaption) {

							let caption = document.createElement('div')
							caption.classList.add('caption')

							if (title.length > 0) {
								let titleElement = document.createElement('div')
								caption.appendChild(titleElement)
								titleElement.innerHTML = title
								titleElement.classList.add('title')
								imageElement.setAttribute('alt', title)
							}

							if (description.length > 0) {
								let descriptionElement = document.createElement('div')
								descriptionElement.innerHTML = description
								caption.appendChild(descriptionElement)
								descriptionElement.classList.add('text')
							}

							item.appendChild(caption)

						}

						slidershow.appendChild(item)

					}

				}

				CustomNamespace.slideshow('.slideshow-main-home')

			}

		})

	}

})
