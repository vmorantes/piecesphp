showGenericLoader('colors')
window.addEventListener('load', () => {

	const LANG_GROUP = 'appConfig'
	registerDynamicLocalizationMessages(LANG_GROUP)

	//Del escaparate del tema: todo se ve en vivo en <html> y nada se guarda hasta pulsar Guardar.
	const root = document.documentElement
	const DARK = '#1F2430'
	const form = $('form[colors-form]')
	const accentInput = form.find('#colors-accent')
	const seriesInputs = form.find('input[data-series]')
	const tokenInputs = form.find('input[data-token]')
	const fontSelect = form.find('[name="theme_font"]')
	const fontSizeSelect = form.find('[name="theme_font_size"]')
	const radiusSelect = form.find('[name="theme_radius"]')
	const barInputs = form.find('[name="browser_bar"]')

	let accent = normal(accentInput.val())
	let initial = null

	form.find('.ui.checkbox').checkbox()
	form.find('.ui.progress').progress()
	form.find('select[colors-select]').dropdown()

	function normal(value) {
		value = (value || '').trim()
		if (value[0] !== '#') {
			value = '#' + value
		}
		return /^#[0-9a-f]{6}$/i.test(value) ? value.toUpperCase() : null
	}
	function rgb(hex) {
		return [1, 3, 5].map((i) => parseInt(hex.substr(i, 2), 16))
	}
	function toHex(channels) {
		return '#' + channels.map((channel) => {
			const value = Math.max(0, Math.min(255, Math.round(channel)))
			return (value < 16 ? '0' : '') + value.toString(16)
		}).join('').toUpperCase()
	}
	function luminance(hex) {
		const channels = rgb(hex).map((value) => {
			value /= 255
			return value <= 0.03928 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4)
		})
		return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2]
	}
	function ratio(hex) {
		return 1.05 / (luminance(hex) + 0.05)
	}
	function formatRatio(value) {
		return value.toFixed(1).replace('.', ',') + ':1'
	}
	function lab(hex) {
		const channels = rgb(hex).map((value) => {
			value /= 255
			return value <= 0.04045 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4)
		})
		const x = (channels[0] * 0.4124 + channels[1] * 0.3576 + channels[2] * 0.1805) / 0.95047
		const y = channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722
		const z = (channels[0] * 0.0193 + channels[1] * 0.1192 + channels[2] * 0.9505) / 1.08883
		const f = (t) => t > 0.008856 ? Math.cbrt(t) : 7.787 * t + 16 / 116
		return [116 * f(y) - 16, 500 * (f(x) - f(y)), 200 * (f(y) - f(z))]
	}
	function distance(a, b) {
		const p = lab(a)
		const q = lab(b)
		return Math.sqrt(Math.pow(p[0] - q[0], 2) + Math.pow(p[1] - q[1], 2) + Math.pow(p[2] - q[2], 2))
	}
	function toHsl(hex) {
		const channels = rgb(hex).map((value) => value / 255)
		const max = Math.max.apply(0, channels)
		const min = Math.min.apply(0, channels)
		const lightness = (max + min) / 2
		const delta = max - min
		let saturation = 0
		let hue = 0
		if (delta) {
			saturation = delta / (1 - Math.abs(2 * lightness - 1))
			hue = max === channels[0] ? ((channels[1] - channels[2]) / delta) % 6 : max === channels[1] ? (channels[2] - channels[0]) / delta + 2 : (channels[0] - channels[1]) / delta + 4
			hue *= 60
			if (hue < 0) {
				hue += 360
			}
		}
		return [hue, saturation, lightness]
	}
	function fromHsl(hue, saturation, lightness) {
		hue = ((hue % 360) + 360) % 360
		const chroma = (1 - Math.abs(2 * lightness - 1)) * saturation
		const x = chroma * (1 - Math.abs((hue / 60) % 2 - 1))
		const m = lightness - chroma / 2
		const sector = [[chroma, x, 0], [x, chroma, 0], [0, chroma, x], [0, x, chroma], [x, 0, chroma], [chroma, 0, x]][Math.floor(hue / 60)]
		return toHex(sector.map((value) => (value + m) * 255))
	}

	function setField(input, hex) {
		input.val(hex.toLowerCase())
	}

	function setBrowserBar() {
		const color = barInputs.filter(':checked').val() === 'dark' ? DARK : accent
		form.find('[colors-phone-bar]').attr('fill', color === DARK ? DARK : 'var(--accent)')
		form.find('[colors-bar-hex]').text(color)
		let meta = $('meta[name="theme-color"]')
		if (meta.length === 0) {
			meta = $('<meta name="theme-color">').appendTo('head')
		}
		meta.attr('content', color)
	}

	//Avisos: series muy parecidas o con poco contraste sobre blanco (solo informativos)
	function reviewPalette() {
		const colors = [accent].concat(seriesInputs.map(function () { return normal(this.value) }).get())
		const warnings = []
		for (let i = 0; i < colors.length; i++) {
			for (let j = i + 1; j < colors.length; j++) {
				if (colors[i] && colors[j] && distance(colors[i], colors[j]) < 12) {
					warnings.push(formatStr(_i18n(LANG_GROUP, 'Serie %r y Serie %r son muy parecidas: en una gráfica no se distinguirán.'), [i + 1, j + 1]))
				}
			}
		}
		colors.forEach((color, index) => {
			if (color && ratio(color) < 3) {
				warnings.push(formatStr(_i18n(LANG_GROUP, 'Serie %r tiene poco contraste sobre fondo blanco (%r; mínimo 3:1).'), [index + 1, formatRatio(ratio(color))]))
			}
		})
		form.find('[colors-palette-warnings]').empty().append(warnings.map((warning) => $('<span class="ui yellow basic label"></span>').text(warning)))
	}

	//Contraste contra texto blanco: solo informativo, no se corrige ni se sugiere otro tono
	function setAccent(hex) {
		accent = hex
		root.style.setProperty('--accent', hex)
		setField(accentInput, hex)
		setField(form.find('#colors-series-1'), hex)
		form.find('[colors-accent-hero]').text(hex)
		const value = ratio(hex)
		const contrast = form.find('#colors-accent-contrast')
		const help = form.find('[colors-accent-help]')
		if (value >= 4.5) {
			contrast.attr('class', 'ui green basic label').text(formatStr(_i18n(LANG_GROUP, 'Cumple AA · %r'), [formatRatio(value)]))
			help.text(_i18n(LANG_GROUP, 'Con texto blanco encima (botones, etiquetas).'))
		} else {
			contrast.attr('class', 'ui yellow basic label').text(_i18n(LANG_GROUP, 'Contraste bajo - por favor ajuste a un valor AA'))
			help.text(formatStr(_i18n(LANG_GROUP, 'Texto blanco sobre el acento: %r (mínimo 4,5:1).'), [formatRatio(value)]))
		}
		setBrowserBar()
		reviewPalette()
	}

	//El valor de fábrica no se fija: la serie 5 del tema es light-dark(), y se apagaría en oscuro.
	function setSeries(input, hex) {
		const property = `--data-${input.data('series')}`
		if (hex === input.data('factory')) {
			root.style.removeProperty(property)
		} else {
			root.style.setProperty(property, hex)
		}
		setField(input, hex)
	}

	function setToken(input, hex) {
		root.style.setProperty(input.data('token'), hex)
		setField(input, hex)
	}

	function setFont() {
		const stack = fontSelect.find('option:selected').data('fontStack')
		root.style.setProperty('--font-body', stack)
		root.style.setProperty('--font-heading', stack)
	}

	function setFontSize() {
		root.style.setProperty('--font-size-site', `${fontSizeSelect.val()}px`)
	}

	//Las mismas proporciones que SettingsController::themeTokens()
	function setRadius() {
		const radius = parseInt(radiusSelect.val(), 10)
		root.style.setProperty('--r-xs', `${Math.round(radius / 2)}px`)
		root.style.setProperty('--r-sm', `${Math.round(radius * 3 / 4)}px`)
		root.style.setProperty('--r-md', `${radius}px`)
		root.style.setProperty('--r-lg', `${Math.round(radius * 3 / 2)}px`)
	}

	function setCustomHarmony() {
		form.find('#colors-harmony-custom').parent().checkbox('set checked')
	}

	//Armonías: desplazamientos de tono desde el acento, apartados ±18° de los tonos de estado
	const STATES = [4, 33, 150, 191]
	function avoidStates(hue) {
		hue = ((hue % 360) + 360) % 360
		STATES.forEach((state) => {
			const offset = ((hue - state + 540) % 360) - 180
			if (Math.abs(offset) < 18) {
				hue = state + (offset < 0 ? -18 : 18)
			}
		})
		return hue
	}
	const HARMONIES = {
		ana: [[30, 0], [-30, 0], [60, -0.06], [-60, 0.06]],
		tri: [[120, 0], [240, 0], [120, 0.12], [240, -0.1]],
		comp: [[180, 0], [150, 0], [210, 0], [180, 0.16]],
	}

	/** @returns {Object} Lo que hay en el formulario: a esto vuelve Cancelar */
	function currentValues() {
		return {
			accent: accent,
			series: seriesInputs.map(function () { return normal(this.value) }).get(),
			tokens: tokenInputs.map(function () { return normal(this.value) }).get(),
			bar: barInputs.filter(':checked').val(),
			font: fontSelect.val(),
			fontSize: fontSizeSelect.val(),
			radius: radiusSelect.val(),
		}
	}

	function restore(values) {
		seriesInputs.each(function (index) {
			setSeries($(this), values.series[index])
		})
		tokenInputs.each(function (index) {
			setToken($(this), values.tokens[index])
		})
		setCustomHarmony()
		barInputs.filter(`[value="${values.bar}"]`).parent().checkbox('set checked')
		fontSelect.dropdown('set selected', values.font)
		fontSizeSelect.dropdown('set selected', values.fontSize)
		radiusSelect.dropdown('set selected', values.radius)
		setFont()
		setFontSize()
		setRadius()
		setAccent(values.accent)
	}

	accentInput.on('input', function () {
		const hex = normal(this.value)
		if (hex) {
			setAccent(hex)
		}
	})
	seriesInputs.on('input', function () {
		const hex = normal(this.value)
		if (hex) {
			setSeries($(this), hex)
		}
		setCustomHarmony()
		reviewPalette()
	})
	tokenInputs.on('input', function () {
		const hex = normal(this.value)
		if (hex) {
			setToken($(this), hex)
		}
	})
	form.find('[name="harmony"]').on('change', function () {
		if (!this.checked || !HARMONIES[this.value]) {
			return
		}
		const hsl = toHsl(accent)
		HARMONIES[this.value].forEach((harmony, index) => {
			const lightness = Math.max(0.32, Math.min(0.5, hsl[2] + harmony[1]))
			setSeries(seriesInputs.eq(index), fromHsl(avoidStates(hsl[0] + harmony[0]), Math.max(0.45, hsl[1]), lightness))
		})
		reviewPalette()
	})
	barInputs.on('change', setBrowserBar)
	fontSelect.on('change', setFont)
	fontSizeSelect.on('change', setFontSize)
	radiusSelect.on('change', setRadius)

	form.find('[colors-cancel]').on('click', function () {
		restore(initial)
	})

	genericFormHandler(form, {
		ignoreRedirection: true,
		ignoreReload: true,
		onSuccess: function () {
			initial = currentValues()
		},
	})

	initial = currentValues()
	setAccent(accent)

	removeGenericLoader('colors')

})
