window.addEventListener('load', function () {
	const example = $('[example-steps]')
	//El cuarto parámetro marca el ui steps: active el actual y completed los anteriores.
	new GenericStepsViewHandler(example.find('[example-step]'), example.find('[data-to-step]'), 1, {
		indicators: example.find('[example-steps-indicators]'),
	})
})
