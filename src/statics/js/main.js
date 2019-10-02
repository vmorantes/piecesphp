window.addEventListener('load', (e) => {

	//──── QuillJS ───────────────────────────────────────────────────────────────────────────
	let quillAdapter = new QuillAdapterComponent({
		containerSelector: '[quill-adapter-component]',
		textareaTargetSelector: '[quill-adapter-component] + textarea[target]',
	})

})
