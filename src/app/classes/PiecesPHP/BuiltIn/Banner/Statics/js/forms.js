showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteBuiltInBanner'))

	/* Traducciones */
	const langGroup = 'built-in-banner-lang'
	registerDynamicLocalizationMessages(langGroup)

	/* Selectores y elementos de interfaz */
	const formSelector = `.ui.form.built-in-banner`

	//Tarjetas de imagen: lo aplicado en el editor se envía al guardar, con el nombre de su campo.
	const editedImages = {}
	const imageEditor = new ContentImageEditor()
	imageEditor.onApply('desktop-image', (file) => editedImages.desktopImage = file)
	imageEditor.onApply('mobile-image', (file) => editedImages.mobileImage = file)

	//Editores de texto
	let richEditorAdapter = new RichEditorAdapterComponent({
		containerSelector: '[rich-editor-adapter-component]',
		textareaTargetSelector: "textarea[name='content']",
	}, {
		items: [
			'undo',
			'redo',
			'|',
			'fontBackgroundColor',
			'fontColor',
			'bold',
			'italic',
			'underline',
			'strikethrough',
			'link',
			'removeFormat',
			'|',
			'superscript',
			'subscript',
		]
	})

	/* Configuraciones iniciales */
	configFomanticDropdown('.ui.dropdown:not(.langs)') //Debe inciarse antes de genericFormHandler para la validación

	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {

			for (const field in editedImages) {
				formData.set(field, editedImages[field])
			}

			return formData
		},
	})

	//Tabs
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
		}
	})

	//Otros
	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.checkbox').checkbox()
	//Solo los del formulario: uno global reiniciaba el de la navegación del shell sin su selector.ignore del riel.
	form.find('.ui.accordion').accordion()

	configLangChange('.ui.dropdown.langs')

	function configLangChange(dropdownSelector) {

		let dropdown = $(dropdownSelector)

		dropdown.dropdown({
			/**
			 * 
			 * @param {Number|String} value 
			 * @param {String} innerText 
			 * @param {$} element 
			 */
			onChange: function (value, innerText, element) {
				showGenericLoader('redirect')
				window.location.href = value
			},
		})

	}

	removeGenericLoader('_CARGA_INICIAL_')

})


