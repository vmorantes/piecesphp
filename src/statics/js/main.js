window.addEventListener('load', (e) => {

	//──── QuillJS ───────────────────────────────────────────────────────────────────────────
	let quillAdapter = new QuillAdapterComponent({
		containerSelector: '[quill-adapter-component]',
		textareaTargetSelector: '[quill-adapter-component] + textarea[target]',
	})

	//──── CropperJS ─────────────────────────────────────────────────────────────────────────
	let cropperAdapterWithoutImage = new CropperAdapterComponent({
		containerSelector: '.ui.form.cropper-adapter.without-image',
		outputWidth: 900,
		minWidth: 160,
		cropperOptions: {
			aspectRatio: 16 / 9,
			viewMode: 3,
		},
	})
	let cropperAdapterWithImage = new CropperAdapterComponent({
		containerSelector: '.ui.form.cropper-adapter.with-image',
		outputWidth: 900,
		minWidth: 90,
		cropperOptions: {
			aspectRatio: 9 / 16,
			viewMode: 3,
		},
	})

	$('.cropper-test-1.change').click(e => console.log(cropperAdapterWithoutImage.wasChanged()))
	$('.cropper-test-1.init').click(e => console.log(cropperAdapterWithoutImage.initWithImage()))
	$('.cropper-test-1.image').click(e => console.log(cropperAdapterWithoutImage.hasImage()))
	$('.cropper-test-1.file').click(e => console.log(cropperAdapterWithoutImage.getFile()))
	$('.cropper-test-1.title').click(e => console.log(cropperAdapterWithoutImage.getTitle()))
	$('.cropper-test-1.crop').click(e => console.log(cropperAdapterWithoutImage.crop()))

	$('.cropper-test-2.change').click(e => console.log(cropperAdapterWithImage.wasChanged()))
	$('.cropper-test-2.init').click(e => console.log(cropperAdapterWithImage.initWithImage()))
	$('.cropper-test-2.image').click(e => console.log(cropperAdapterWithImage.hasImage()))
	$('.cropper-test-2.file').click(e => console.log(cropperAdapterWithImage.getFile()))
	$('.cropper-test-2.title').click(e => console.log(cropperAdapterWithImage.getTitle()))
	$('.cropper-test-2.crop').click(e => console.log(cropperAdapterWithImage.crop()))

	document.body.style.minHeight = '100vh'
	let dialog = new DialogPCS('.dialog-pcs', 'body', true)
	$('.open-dialog').click(function (e) {
		e.preventDefault()
		dialog.open()
	})

	//Tabs
	let tabsItemMenu = $('.tabular.menu .item')
	let tabs = $(`[data-tab]`)
	let activeTab = 'DialogPCS'

	tabsItemMenu.tab()
	tabsItemMenu.filter(`[data-tab="${activeTab}"]`).addClass('active')
	tabs.filter(`[data-tab="${activeTab}"]`).addClass('active')

})
