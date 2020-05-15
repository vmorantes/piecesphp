/// <reference path="../../../../../core/js/configurations.js" />
/// <reference path="../../../../../core/js/helpers.js" />
/// <reference path="../../../../../core/own-plugins/CropperAdapterComponent.js" />
showGenericLoader('_CARGA_INICIAL_')

window.addEventListener('load', function () {

	let isEdit = false
	let formSelector = `.ui.form.shop-products`
	let langGroup = 'biShopProducts'

	let currentImages = new Map()
	let imagesToAdd = new Map()
	let imagesToDelete = new Set()

	let cropperAdapter = new CropperAdapterComponent({
		containerSelector: '[cropper-main-image]',
		minWidth: 400,
		outputWidth: 400,
		cropperOptions: {
			aspectRatio: 400 / 300,
		},
	})

	let form = genericFormHandler(formSelector, {
		/**
		 * 
		 * @param {FormData} formData 
		 */
		onSetFormData: function (formData) {

			formData.set('has_warranty', warrantyCheckbox.is(':checked') ? 1 : 0)

			if (isEdit) {
				formData.set('mainImage', cropperAdapter.getFile(null, null, null, null, true))
			} else {
				formData.set('mainImage', cropperAdapter.getFile())
			}

			let imagesToAddValues = Array.from(imagesToAdd.values())
			let imagesToDeleteValues = Array.from(imagesToDelete.values())

			formData = addObjectToFormData(formData, imagesToDeleteValues, 'images_to_delete')

			for (let imageToAdd of imagesToAddValues) {
				formData.append('images_to_add[]', imageToAdd)
			}

			return formData

		},
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let field = jElement.parents('.field')
			let nameOnLabel = field.find('label').html()

			errorMessage(`${nameOnLabel}: ${validationMessage}`)

			event.preventDefault()

		}
	})

	let warrantyCheckbox = form.find(`[name="has_warranty"]`)

	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.ui.dropdown.autodropdown').dropdown()
	$('.tabular.menu .item').tab()

	isEdit = form.find(`[name="id"]`).length > 0

	configWarranty()
	configSelects()
	configDynamicImages(form)

	removeGenericLoader('_CARGA_INICIAL_')

	function configSelects() {

		let selectCategory = form.find(`select[name="category"]`)
		let selectSubcategory = form.find(`select[name="subcategory"]`)
		let selectSubcategoryHTML = selectSubcategory.get(0).outerHTML
		let subcategoriesURL = selectSubcategory.attr('url')

		let categorySelected = selectCategory.val()
		let subcategorySelected = selectSubcategory.attr('current')
		subcategorySelected = parseInt(subcategorySelected.trim())
		subcategorySelected = !isNaN(subcategorySelected) ? subcategorySelected : -1

		if (categorySelected.trim().length > 0) {
			categorySelected = parseInt(categorySelected.trim())
			categorySelected = !isNaN(categorySelected) ? categorySelected : -1
		} else {
			categorySelected = -1
		}

		selectCategory.dropdown()
		selectSubcategory.dropdown()

		selectCategory.change(function (e) {
			getSubcategories($(e.currentTarget).val(), -1)
		})

		getSubcategories(categorySelected, subcategorySelected)

		function getSubcategories(category, subcategorySelected) {

			getRequest(subcategoriesURL + `?category=${category}`).done(function (res) {

				selectSubcategory.dropdown('destroy')
				selectSubcategory.closest('.ui.dropdown').replaceWith($(selectSubcategoryHTML))
				selectSubcategory = form.find(`select[name="subcategory"]`)

				if (Array.isArray(res)) {

					for (let subcategory of res) {

						let option = $(`<option value="${subcategory.id}">${subcategory.name}</option>`)

						if (subcategorySelected == subcategory.id) {
							option.attr('selected', true)
						}

						selectSubcategory.append(option)

					}

				}

				selectSubcategory.dropdown()

			})

		}

	}

	function configWarranty() {

		let warrantyDuration = form.find(`[name="warranty_duration"]`)
		let warrantyMeasure = form.find(`[name="warranty_measure"]`)

		warrantyCheckbox.change(function () {
			updateWarranty()
		})

		updateWarranty()

		function updateWarranty() {

			if (!warrantyCheckbox.is(':checked')) {
				warrantyDuration.closest('.field').addClass('disabled')
				warrantyMeasure.closest('.field').addClass('disabled')
			} else {
				warrantyDuration.closest('.field').removeClass('disabled')
				warrantyMeasure.closest('.field').removeClass('disabled')
			}

		}

	}

	function configDynamicImages(parent) {

		let trigger = parent.find('[images-multiple-trigger-add]')
		let containerItems = parent.find('[images-multiple-container]')
		let editorContainer = parent.find('[images-multiple-editor]')
		let editorTemplate = editorContainer.find('.cropper-adapter').get(0).outerHTML

		let scriptTagTemplate = $('[template-item-images-multiple]')
		let templateItem = scriptTagTemplate.get(0).innerHTML

		scriptTagTemplate.remove()
		editorContainer.find('.cropper-adapter').remove()

		readCurrents()

		trigger.click(function (e) {

			e.preventDefault()

			let uniqueID = generateUniqueID('cropper-')

			let hasEditor = editorContainer.find('.cropper-adapter').length > 0

			if (!hasEditor) {

				let editor = $(editorTemplate)
				editor.attr('data-id', uniqueID)

				editorContainer.append(editor)

				let adapter = new CropperAdapterComponent({
					containerSelector: `[data-id="${uniqueID}"]`,
					minWidth: 400,
					outputWidth: 400,
					cropperOptions: {
						aspectRatio: 400 / 300,
					},
				}, false)

				adapter.on('prepare', function () {
					adapter.forceInitialize()
				})

				adapter.on('save', function (e, data) {
					toAdd(uniqueID, adapter.getFile(), data.b64)
					adapter.destroy()
				})

				adapter.prepare()

			}

		})

		function readCurrents() {

			let currentItems = containerItems.find('> [item]').toArray()

			for (let current of currentItems) {

				current = $(current)

				let id = current.attr('data-id')

				currentImages.set(id, current.find('img').attr('src'))

				let deleteItemButton = current.find('button[delete]')
				deleteItemButton.click(deleteEventHandler)

			}

		}

		function toAdd(id, file, b64) {

			imagesToAdd.set(id, file)

			let item = $(templateItem)

			item.attr('data-id', id)
			item.find('img').attr('src', b64)

			if (countItems() == 0) {
				containerItems.html('')
			}

			containerItems.append(item)

			let deleteItemButton = item.find('button[delete]')

			deleteItemButton.click(deleteEventHandler)

		}

		function toDelete(id) {

			let itemToDelete = containerItems.find(`[item][data-id="${id}"]`)

			imagesToAdd.delete(id)

			if (currentImages.has(id)) {
				imagesToDelete.add(currentImages.get(id))
			}

			itemToDelete.remove()

		}

		function deleteEventHandler(e) {

			e.preventDefault()

			let that = $(e.currentTarget)
			let parent = that.closest('[item]')
			let uniqueID = parent.attr('data-id')

			iziToast.question({
				timeout: 20000,
				close: false,
				overlay: true,
				displayMode: 'once',
				id: 'question',
				zindex: 999,
				title: _i18n(langGroup, 'Confirmación'),
				message: _i18n(langGroup, '¿Quiere eliminar la imagen?'),
				position: 'center',
				buttons: [
					['<button><b>' + _i18n(langGroup, 'Sí') + '</b></button>', function (instance, toast) {
						toDelete(uniqueID)
						instance.hide({ transitionOut: 'fadeOut' }, toast, 'button')
					}, true],
					['<button>' + _i18n(langGroup, 'No') + '</button>', function (instance, toast) {
						instance.hide({ transitionOut: 'fadeOut' }, toast, 'button')
					}],
				]
			})

		}

		function countItems() {
			return containerItems.find('>[item]').length
		}

	}

})


