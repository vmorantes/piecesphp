//El comprobador de CHECK.html (ADR 0084): PCSCheck.run(files, rules) → { problems, generated, changed }, sin DOM ni
//disco, para probarlo también con node; las marcas salen solo de site/.pcs/rules.json.
; (function (global) {
	'use strict'

	var VOID = 'area base br col embed hr img input link meta param source track wbr'.split(' ')
	var RAW = ['script', 'style', 'textarea', 'title']
	var BLOCK = 'address article aside blockquote div dl fieldset figure footer form h1 h2 h3 h4 h5 h6 header hr li main nav ol p pre section table ul'.split(' ')
	var PLACEHOLDER = /\{\{([^{}]*)\}\}/g
	//Rutas relativas a la raíz del paquete: el sitio en site/, su lector oculto en site/.pcs/ y el agente fuera.
	var SITE = 'site/'
	var READER = 'site/.pcs/'
	var RULES_FILE = READER + 'rules.json'
	var PARTS_FILE = SITE + 'parts/parts.js'
	var UNSAFE_FIX = 'Un dato no puede ir dentro de un evento, un estilo o un script; ponlo en el texto o en un atributo normal (class, href, src, alt…).'
	//Qué hacer cuando un valor no casa con su «pattern» de las reglas; el mismo sentido que MarkRules::PATTERN_HINTS.
	var PATTERN_FIX = {
		name: 'Usa solo minúsculas, números y guiones, sin espacios ni tildes.',
		fragment: 'Pon el id de la sección, sin «#»: letras, números, «-», «_», «:» o «.», empezando por una letra (por ejemplo, anchor="equipo").',
		classList: 'Pon uno o más nombres de clase separados por espacios (por ejemplo, current-class="w--current").',
		sizes: 'Escribe medidas como sizes="(max-width: 600px) 100vw, 50vw", sin comillas ni < >.',
	}
	//Los atributos de URL y la lista blanca de safeUrl() son los del servidor (MarkRegistry::safeUrl) y los de pcs.js.
	var URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'poster', 'srcset', 'xlink:href', 'data-src', 'data-srcset']

	function safeUrl(value) {
		var clean = String(value).replace(/^[\x00-\x20]+/, '').replace(/[\t\r\n]/g, '')
		if (/^(https?:\/\/|mailto:|tel:|\/|\.\/|\.\.\/|#|\?)/i.test(clean)) {
			return clean
		}
		var colon = clean.indexOf(':')
		var slash = clean.indexOf('/')
		return colon === -1 || (slash !== -1 && slash < colon) ? clean : ''
	}

	var HIDDEN = /(^|\/)(\.|Thumbs\.db$|desktop\.ini$)/i

	//──── SHA-256 en JavaScript puro: con doble clic (file://) no siempre hay crypto.subtle ────
	var K = [
		0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5, 0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3,
		0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174, 0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
		0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967, 0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13,
		0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85, 0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
		0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3, 0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208,
		0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2,
	]

	function sha256(bytes) {
		var length = bytes.length
		var total = ((length + 9 + 63) >> 6) << 6
		var data = new Uint8Array(total)
		data.set(bytes)
		data[length] = 0x80
		var bits = length * 8
		var view = new DataView(data.buffer)
		view.setUint32(total - 4, bits >>> 0)
		view.setUint32(total - 8, Math.floor(bits / 0x100000000))
		var h = [0x6a09e667, 0xbb67ae85, 0x3c6ef372, 0xa54ff53a, 0x510e527f, 0x9b05688c, 0x1f83d9ab, 0x5be0cd19]
		var w = new Uint32Array(64)
		for (var offset = 0; offset < total; offset += 64) {
			for (var i = 0; i < 16; i++) {
				w[i] = view.getUint32(offset + i * 4)
			}
			for (i = 16; i < 64; i++) {
				var a15 = w[i - 15]
				var a2 = w[i - 2]
				var s0 = ((a15 >>> 7) | (a15 << 25)) ^ ((a15 >>> 18) | (a15 << 14)) ^ (a15 >>> 3)
				var s1 = ((a2 >>> 17) | (a2 << 15)) ^ ((a2 >>> 19) | (a2 << 13)) ^ (a2 >>> 10)
				w[i] = (w[i - 16] + s0 + w[i - 7] + s1) | 0
			}
			var a = h[0], b = h[1], c = h[2], d = h[3], e = h[4], f = h[5], g = h[6], hh = h[7]
			for (i = 0; i < 64; i++) {
				var S1 = ((e >>> 6) | (e << 26)) ^ ((e >>> 11) | (e << 21)) ^ ((e >>> 25) | (e << 7))
				var t1 = (hh + S1 + ((e & f) ^ (~e & g)) + K[i] + w[i]) | 0
				var S0 = ((a >>> 2) | (a << 30)) ^ ((a >>> 13) | (a << 19)) ^ ((a >>> 22) | (a << 10))
				var t2 = (S0 + ((a & b) ^ (a & c) ^ (b & c))) | 0
				hh = g
				g = f
				f = e
				e = (d + t1) | 0
				d = c
				c = b
				b = a
				a = (t1 + t2) | 0
			}
			h[0] = (h[0] + a) | 0
			h[1] = (h[1] + b) | 0
			h[2] = (h[2] + c) | 0
			h[3] = (h[3] + d) | 0
			h[4] = (h[4] + e) | 0
			h[5] = (h[5] + f) | 0
			h[6] = (h[6] + g) | 0
			h[7] = (h[7] + hh) | 0
		}
		return h
			.map(function (value) {
				return ('00000000' + (value >>> 0).toString(16)).slice(-8)
			})
			.join('')
	}

	function utf8(text) {
		if (typeof TextEncoder !== 'undefined') {
			return new TextEncoder().encode(text)
		}
		return new Uint8Array(Buffer.from(text, 'utf8'))
	}

	//──── Un lector de HTML pequeño que recuerda la línea de cada etiqueta: el del navegador la pierde ────
	function lineFinder(text) {
		var starts = [0]
		for (var i = 0; i < text.length; i++) {
			if (text.charCodeAt(i) === 10) {
				starts.push(i + 1)
			}
		}
		return function (index) {
			var low = 0
			var high = starts.length - 1
			while (low < high) {
				var middle = (low + high + 1) >> 1
				if (starts[middle] <= index) {
					low = middle
				} else {
					high = middle - 1
				}
			}
			return low + 1
		}
	}

	function parseHtml(text) {
		var lineAt = lineFinder(text)
		var root = { tag: '#root', attrs: {}, attrList: [], children: [], parent: null, line: 1 }
		var current = root
		var position = 0
		var tagPattern = /<(\/?)([a-zA-Z][a-zA-Z0-9:-]*)((?:\s+[^\s"'>\/=]+(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s"'=<>`]+))?)*)\s*(\/?)>/g
		var attrPattern = /([^\s"'>\/=]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s"'=<>`]+)))?/g

		function addText(from, to) {
			if (to > from) {
				current.children.push({ tag: '#text', text: text.slice(from, to), line: lineAt(from), parent: current })
			}
		}

		while (position < text.length) {
			var lt = text.indexOf('<', position)
			if (lt === -1) {
				addText(position, text.length)
				break
			}
			if (text.startsWith('<!--', lt)) {
				addText(position, lt)
				var endComment = text.indexOf('-->', lt + 4)
				position = endComment === -1 ? text.length : endComment + 3
				continue
			}
			if (text.startsWith('<!', lt) || text.startsWith('<?', lt)) {
				addText(position, lt)
				var endDecl = text.indexOf('>', lt)
				position = endDecl === -1 ? text.length : endDecl + 1
				continue
			}
			tagPattern.lastIndex = lt
			var match = tagPattern.exec(text)
			if (!match || match.index !== lt) {
				addText(position, lt + 1)
				position = lt + 1
				continue
			}
			addText(position, lt)
			var closing = match[1] === '/'
			var tag = match[2].toLowerCase()
			position = tagPattern.lastIndex
			if (closing) {
				for (var up = current; up && up !== root; up = up.parent) {
					if (up.tag === tag) {
						current = up.parent
						break
					}
				}
				continue
			}
			var element = { tag: tag, attrs: {}, attrList: [], children: [], parent: current, line: lineAt(lt) }
			var attrMatch
			attrPattern.lastIndex = 0
			while ((attrMatch = attrPattern.exec(match[3]))) {
				var name = attrMatch[1].toLowerCase()
				var value = attrMatch[2] !== undefined ? attrMatch[2] : attrMatch[3] !== undefined ? attrMatch[3] : attrMatch[4] !== undefined ? attrMatch[4] : ''
				if (!(name in element.attrs)) {
					element.attrs[name] = decodeEntities(value)
					element.attrList.push(name)
				}
			}
			current.children.push(element)
			if (VOID.indexOf(tag) !== -1 || match[4] === '/' && tag.indexOf('-') === -1) {
				continue
			}
			if (RAW.indexOf(tag) !== -1) {
				//Sin toLowerCase() sobre el texto entero: una «İ» cambia de longitud y desplazaría el cierre.
				var rawClose = new RegExp('</' + tag, 'gi')
				rawClose.lastIndex = position
				var rawFound = rawClose.exec(text)
				var endRaw = rawFound ? rawFound.index : -1
				var stop = endRaw === -1 ? text.length : endRaw
				element.children.push({ tag: '#text', text: text.slice(position, stop), line: lineAt(position), parent: element, raw: true })
				var closeEnd = endRaw === -1 ? text.length : text.indexOf('>', endRaw)
				position = closeEnd === -1 ? text.length : closeEnd + 1
				continue
			}
			current = element
		}
		return root
	}

	function decodeEntities(value) {
		return value.replace(/&(amp|lt|gt|quot|#39|apos);/g, function (all, name) {
			return { amp: '&', lt: '<', gt: '>', quot: '"', '#39': "'", apos: "'" }[name]
		})
	}

	//─── Lo que finge, las URL ajenas y los bloques copiados (ADR 0087, puntos 4, 8 y 10). Las dos últimas, igual que
	//SiteLint.php en la importación: misma lista (rules.json), mismo recorrido por texto y blancos solo ASCII.
	var BUTTON_CLASSES = ['btn', 'button', 'w-button']
	var FIELD_CLASS = /^(search|field|input)(-|$)/i
	var LINT_URL_ATTRIBUTES = ['src', 'href', 'srcset', 'data-srcset', 'poster', 'data-src', 'data-bg', 'data-background', 'action', 'formaction']
	var RESOURCE_RELS = ['stylesheet', 'preload', 'modulepreload', 'prefetch', 'icon', 'apple-touch-icon', 'manifest']
	var SCRIPT_TYPES = ['', 'text/javascript', 'application/javascript', 'module']
	var NAMESPACE_HOSTS = ['www.w3.org']
	var BLANK = /[\t\n\f\r ]+/g
	var TEXT_URL = /[`'"(][\t\n\f\r ]*((?:https?:)?\/\/(?:localhost|\d{1,3}(?:\.\d{1,3}){3}|[A-Za-z0-9][A-Za-z0-9.-]*\.[A-Za-z]{2,})(?::\d+)?[^\t\n\f\r '"`()<>]*)/g
	var STYLE_URL = /url\([\t\n\f\r ]*['"]?((?:https?:)?\/\/[^'")\t\n\f\r ]+)/gi
	var BLOCK_MINIMUM = 300
	var BLOCK_PAGES = 3

	function lintFakes(path, tree, report) {
		walk(tree, function (node) {
			if (!node.attrs) {
				return
			}
			var classes = (node.attrs['class'] || '').toLowerCase().split(/\s+/).filter(Boolean)
			if (node.tag === 'a') {
				var href = node.attrs.href
				if (href === undefined) {
					//Un <a id> o <a name> sin href es un ancla de destino, no un enlace.
					if (!('id' in node.attrs) && !('name' in node.attrs)) {
						report('aviso', path, node.line, 'Un enlace sin href no lleva a ninguna parte.', 'Ponle su destino (otra página, una sección con #id o una dirección), o usa <button> si es una acción.')
					}
				} else if (/^(#?|javascript:.*)$/i.test(href.replace(/\s+/g, ''))) {
					report('aviso', path, node.line, 'Un enlace finge: href="' + href.trim() + '" no lleva a ninguna parte.', 'Ponle su destino de verdad (otra página, una sección con #id o una dirección), o usa <button> si es una acción.')
				}
			} else if (node.tag === 'img' && !('alt' in node.attrs)) {
				report('aviso', path, node.line, 'Una imagen sin alt.', 'Ponle un alt que diga qué muestra, o alt="" si es solo adorno.')
			} else if (node.tag === 'form') {
				var action = node.attrs.action
				var inPcsForm = false
				for (var up = node.parent; up; up = up.parent) {
					inPcsForm = inPcsForm || up.tag === 'pcs-form'
				}
				if (!inPcsForm && (action === undefined || action.trim() === '' || action.trim() === '#')) {
					report('aviso', path, node.line, 'Un formulario que no envía a ninguna parte.', 'Envuélvelo en <pcs-form action="…"> con la acción que te dé el equipo del proyecto.')
				}
			} else if (node.tag === 'div' || node.tag === 'span') {
				//Con role="button" y tabindex ya es un botón accesible (los menús de Webflow): no finge.
				var fakeButton = (node.attrs.role || '').toLowerCase() === 'button' ? !('tabindex' in node.attrs) : classes.some(function (name) { return BUTTON_CLASSES.indexOf(name) !== -1 })
				if (fakeButton) {
					report('aviso', path, node.line, 'Un <' + node.tag + '> finge ser un botón.', 'Usa <button> para una acción o <a> con su destino para ir a otra página.')
				} else if (classes.some(function (name) { return FIELD_CLASS.test(name) })) {
					var real = false
					walk(node, function (child) {
						real = real || child.tag === 'input' || child.tag === 'select' || child.tag === 'textarea'
					})
					if (!real) {
						report('aviso', path, node.line, 'Un <' + node.tag + '> parece un campo y no lo es.', 'Pon dentro un <input>, <select> o <textarea> de verdad (y su <label>).')
					}
				}
			}
		})
	}

	//Los comentarios, en blanco y con sus saltos (las líneas no se mueven): /* */ en CSS; en JS, además // fuera de cadenas
	//y de expresiones regulares (una / tras ( , = : [ ! & | ? { } ; o al empezar). Límite: no es un analizador de JS.
	function withoutComments(text, kind) {
		var out = ''
		var quote = ''
		var previous = ''
		var blank = function (piece) { return piece.replace(/[^\n]/g, ' ') }
		for (var i = 0; i < text.length; i++) {
			var ch = text[i]
			if (quote !== '') {
				out += ch
				if (ch === '\\') {
					out += i + 1 < text.length ? text[++i] : ''
				} else if (ch === quote) {
					quote = ''
				}
			} else if (ch === '"' || ch === "'" || (kind === 'js' && ch === '`')) {
				quote = ch
				out += ch
			} else if (kind === 'js' && ch === '/' && text[i + 1] !== '/' && text[i + 1] !== '*' && '(,=:[!&|?{};'.indexOf(previous) !== -1) {
				var inClass = false
				out += ch
				for (i++; i < text.length && text[i] !== '\n'; i++) {
					out += text[i]
					if (text[i] === '\\') {
						out += i + 1 < text.length ? text[++i] : ''
					} else if (text[i] === '[') {
						inClass = true
					} else if (text[i] === ']') {
						inClass = false
					} else if (text[i] === '/' && !inClass) {
						break
					}
				}
				previous = '/'
				continue
			} else if (ch === '/' && text[i + 1] === '*') {
				var close = text.indexOf('*/', i + 2)
				var stop = close === -1 ? text.length : close + 2
				out += blank(text.slice(i, stop))
				i = stop - 1
			} else if (kind === 'js' && ch === '/' && text[i + 1] === '/') {
				var newline = text.indexOf('\n', i)
				var end = newline === -1 ? text.length : newline
				out += blank(text.slice(i, end))
				i = end - 1
			} else {
				out += ch
			}
			if (!/[\t\n\f\r ]/.test(ch)) {
				previous = ch
			}
		}
		return out
	}

	function knownHostList(rules) {
		var known = []
		var groups = rules && rules.knownHosts && typeof rules.knownHosts === 'object' ? rules.knownHosts : {}
		Object.keys(groups).forEach(function (group) {
			(Array.isArray(groups[group]) ? groups[group] : []).forEach(function (host) {
				known.push(String(host).toLowerCase())
			})
		})
		return known
	}

	//URL absolutas a hosts fuera de la lista; path, el archivo del paquete con su nombre (exact: con el final de su ruta),
	//lo único que cuenta en la navegación. inputs: [{ file, text, kind: 'html' | 'css' | 'js' }]; packagePaths: [ruta en site/].
	function externalUrls(inputs, knownHosts, packagePaths) {
		var found = []
		function consider(file, line, value, navigation) {
			var url = String(value).replace(/^[\t\n\f\r ]+|[\t\n\f\r ]+$/g, '')
			var match = /^(?:https?:)?\/\/([^/?#]*)([^?#]*)/i.exec(url)
			if (!match) {
				return
			}
			var host = match[1].replace(/^.*@/, '').replace(/:\d*$/, '').toLowerCase()
			if (host === '' || NAMESPACE_HOSTS.indexOf(host) !== -1 || knownHosts.indexOf(host) !== -1) {
				return
			}
			var urlPath = match[2]
			var last = urlPath.split('/').pop()
			var path = ''
			var exact = false
			if (last !== '') {
				packagePaths.forEach(function (candidate) {
					var tails = [candidate].concat(candidate.indexOf('assets/') === 0 ? [candidate.slice(7)] : [])
					//Exacta solo si coincide al menos una carpeta: «…/style.css» de otro dominio es solo el nombre.
					var isExact = tails.some(function (tail) { return tail.indexOf('/') !== -1 && urlPath.slice(-tail.length - 1) === '/' + tail })
					if (isExact && !exact) {
						path = candidate
						exact = true
					} else if (!exact && path === '' && candidate.split('/').pop() === last) {
						path = candidate
					}
				})
			}
			if (path !== '' || !navigation) {
				found.push({ file: file, line: line, url: url, host: host, path: path, exact: exact })
			}
		}
		function scanText(file, text, firstLine, kind) {
			text = withoutComments(text, kind)
			var lineAt = lineFinder(text)
			var match
			TEXT_URL.lastIndex = 0
			while ((match = TEXT_URL.exec(text))) {
				consider(file, firstLine + lineAt(match.index) - 1, match[1], false)
			}
		}
		inputs.forEach(function (input) {
			if (input.kind !== 'html') {
				scanText(input.file, input.text, 1, input.kind)
				return
			}
			walk(parseHtml(input.text), function (node) {
				if (node.tag === '#text') {
					var parent = node.parent
					if (node.raw && parent && (parent.tag === 'style' || parent.tag === 'script' && !('src' in parent.attrs) && SCRIPT_TYPES.indexOf((parent.attrs.type || '').trim().toLowerCase()) !== -1)) {
						scanText(input.file, node.text, node.line, parent.tag === 'style' ? 'css' : 'js')
					}
					return
				}
				var rels = (node.attrs.rel || '').toLowerCase().split(/\s+/)
				var navigation = node.tag === 'a' || node.tag === 'link' && !rels.some(function (rel) { return RESOURCE_RELS.indexOf(rel) !== -1 })
				LINT_URL_ATTRIBUTES.forEach(function (name) {
					if (!(name in node.attrs)) {
						return
					}
					var values = name === 'srcset' || name === 'data-srcset' ? node.attrs[name].split(',').map(function (candidate) { return candidate.replace(/^[\t\n\f\r ]+/, '').split(/[\t\n\f\r ]+/)[0] }) : [node.attrs[name]]
					values.forEach(function (value) {
						consider(input.file, node.line, value, navigation && name === 'href')
					})
				})
				if ('style' in node.attrs) {
					var match
					STYLE_URL.lastIndex = 0
					while ((match = STYLE_URL.exec(node.attrs.style))) {
						consider(input.file, node.line, match[1], false)
					}
				}
			})
		})
		return found
	}

	//Bloques de 300+ caracteres sin blancos, idénticos en 3+ páginas y fuera de <pcs-part>: uno por bloque, el más externo,
	//en la primera página. Recorrido propio por texto, el mismo de SiteLint.php. pages: { 'site/x.html': texto }.
	function repeatedBlocks(pages) {
		var tagPattern = /<(\/?)([a-zA-Z][a-zA-Z0-9:-]*)((?:\s+[^\s"'>\/=]+(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s"'=<>`]+))?)*)\s*(\/?)>/g
		var perPage = {}
		var pagesOf = {}
		Object.keys(pages).sort().forEach(function (file) {
			var text = pages[file]
			var lineAt = lineFinder(text)
			var elements = []
			var stack = []
			var position = 0
			while (position < text.length) {
				var lt = text.indexOf('<', position)
				if (lt === -1) {
					break
				}
				if (text.startsWith('<!--', lt)) {
					var endComment = text.indexOf('-->', lt + 4)
					position = endComment === -1 ? text.length : endComment + 3
					continue
				}
				if (text.startsWith('<!', lt) || text.startsWith('<?', lt)) {
					var endDecl = text.indexOf('>', lt)
					position = endDecl === -1 ? text.length : endDecl + 1
					continue
				}
				tagPattern.lastIndex = lt
				var match = tagPattern.exec(text)
				if (!match || match.index !== lt) {
					position = lt + 1
					continue
				}
				var tag = match[2].toLowerCase()
				position = tagPattern.lastIndex
				if (match[1] === '/') {
					for (var index = stack.length - 1; index >= 0; index--) {
						if (stack[index].tag === tag) {
							stack[index].end = position
							stack.length = index
							break
						}
					}
					continue
				}
				if (VOID.indexOf(tag) !== -1 || match[4] === '/' && tag.indexOf('-') === -1) {
					continue
				}
				var element = { tag: tag, start: lt, end: -1, parent: stack.length ? stack[stack.length - 1] : null }
				elements.push(element)
				if (RAW.indexOf(tag) !== -1) {
					//El cierre se busca sin pasar el texto a minúsculas: toLowerCase() cambia longitudes (İ) y desplaza.
					var closing = new RegExp('</' + tag, 'gi')
					closing.lastIndex = position
					var found = closing.exec(text)
					var closeEnd = found ? text.indexOf('>', found.index) : -1
					element.end = closeEnd === -1 ? -1 : closeEnd + 1
					position = closeEnd === -1 ? text.length : closeEnd + 1
					continue
				}
				stack.push(element)
			}
			elements.forEach(function (element) {
				var inPart = false
				for (var up = element; up; up = up.parent) {
					inPart = inPart || up.tag === 'pcs-part'
				}
				element.norm = element.end === -1 || inPart || ['html', 'head', 'body'].indexOf(element.tag) !== -1 ? null : text.slice(element.start, element.end).replace(BLANK, '')
				if (element.norm !== null && Array.from(element.norm).length >= BLOCK_MINIMUM) {
					pagesOf[element.norm] = pagesOf[element.norm] || []
					if (pagesOf[element.norm].indexOf(file) === -1) {
						pagesOf[element.norm].push(file)
					}
				} else {
					element.norm = null
				}
				element.line = lineAt(element.start)
			})
			perPage[file] = elements
		})
		var result = []
		var reported = {}
		var qualifies = function (candidate) {
			return candidate.norm !== null && pagesOf[candidate.norm].length >= BLOCK_PAGES
		}
		Object.keys(perPage).sort().forEach(function (file) {
			perPage[file].forEach(function (element) {
				if (!qualifies(element) || reported[element.norm]) {
					return
				}
				for (var up = element.parent; up; up = up.parent) {
					if (qualifies(up)) {
						return
					}
				}
				reported[element.norm] = true
				result.push({ file: file, line: element.line, pages: pagesOf[element.norm].slice() })
			})
		})
		return result
	}

	function walk(node, visit, inTemplate) {
		node.children.forEach(function (child) {
			visit(child, inTemplate)
			if (child.children) {
				walk(child, visit, inTemplate || child.tag === 'template')
			}
		})
	}

	//Lo mismo que Compiler::isUnsafeFieldAttribute(): el href de MathML, <meta http-equiv=refresh> y las animaciones SVG.
	var ANIMATION_ELEMENTS = ['set', 'animate', 'animatetransform', 'animatemotion']
	var ANIMATION_VALUE_ATTRIBUTES = ['to', 'from', 'values', 'by']

	function unsafeElsewhere(node, attribute) {
		if (attribute === 'content' && node.tag === 'meta' && (node.attrs['http-equiv'] || '').trim().toLowerCase() === 'refresh') {
			return true
		}
		if (ANIMATION_VALUE_ATTRIBUTES.indexOf(attribute) !== -1 && ANIMATION_ELEMENTS.indexOf(node.tag) !== -1) {
			return true
		}
		return (attribute === 'href' || attribute === 'xlink:href') && (node.tag === 'math' || closest(node, 'math') !== null)
	}

	function closest(node, tag) {
		for (var up = node.parent; up; up = up.parent) {
			if (up.tag === tag) {
				return up
			}
		}
		return null
	}

	function textOf(node) {
		if (node.tag === '#text') {
			return node.text
		}
		return (node.children || []).map(textOf).join('')
	}

	//──── Los datos de ejemplo: data/<recurso>.js se ejecuta con una ventana de mentira, sin tocar la página ────
	function evaluateData(text) {
		var fake = { PCS_DATA: {} }
		new Function('window', 'document', 'globalThis', '"use strict";\n' + text)(fake, undefined, fake)
		return fake.PCS_DATA || {}
	}

	function evaluateParts(text) {
		var fake = {}
		new Function('window', 'document', 'globalThis', '"use strict";\n' + text)(fake, undefined, fake)
		return fake
	}

	function quote(value) {
		return JSON.stringify(value)
	}

	//──── Lo generado: siempre igual para la misma carpeta, para que volver a comprobar no cambie nada ────
	//Escrito como lo deja bin/formatear (tabuladores, listas en una línea): regenerar no cambia el archivo del repositorio.
	function literal(value, depth) {
		if (Array.isArray(value)) {
			return '[' + value.map(function (item) {
				return literal(item, depth)
			}).join(', ') + ']'
		}
		if (value && typeof value === 'object') {
			var keys = Object.keys(value)
			if (keys.length === 0) {
				return '{}'
			}
			var inner = '\t'.repeat(depth + 1)
			return '{\n' + keys.map(function (key) {
				return inner + quote(key) + ': ' + literal(value[key], depth + 1)
			}).join(',\n') + '\n' + '\t'.repeat(depth) + '}'
		}
		return quote(value)
	}

	function buildRulesJs(rules) {
		return '/* Generado desde pcs/rules.json por CHECK.html (o por la exportación). No se edita a mano. */\n' + 'window.PCS_RULES = ' + literal(rules, 0) + '\n'
	}

	//PCS_RESOURCES, los recursos con data/<r>.js: la vista previa los carga en <head>, antes que los scripts del diseñador.
	function buildPartsJs(parts, pages, formatVersion, resources) {
		var names = Object.keys(parts).sort()
		var lines = ['/* Generado por CHECK.html desde la carpeta parts/. No se edita a mano: se cambia parts/<nombre>.html y se vuelve a comprobar. */']
		lines.push('window.PCS_PARTS = {')
		names.forEach(function (name, index) {
			lines.push('\t' + quote(name) + ': ' + quote(parts[name]) + (index < names.length - 1 ? ',' : ''))
		})
		lines.push('}')
		lines.push('window.PCS_PAGES = ' + literal(pages.slice().sort(), 0))
		lines.push('window.PCS_RESOURCES = ' + literal((resources || []).slice().sort(), 0))
		lines.push('window.PCS_PARTS_INFO = { "formatVersion": ' + quote(formatVersion) + ' }')
		return lines.join('\n') + '\n'
	}

	function fingerprints(files) {
		var result = {}
		Object.keys(files)
			.sort()
			.forEach(function (path) {
				if (path.indexOf(SITE) !== 0 || HIDDEN.test(path.slice(SITE.length))) {
					return
				}
				var file = files[path]
				result[path] = sha256(file.bytes || utf8(file.text || ''))
			})
		return result
	}

	function run(inputFiles, givenRules) {
		var problems = []
		var files = {}
		Object.keys(inputFiles).forEach(function (path) {
			files[path.replace(/\\/g, '/').replace(/^\.\//, '')] = inputFiles[path]
		})

		function report(level, file, line, what, fix) {
			problems.push({ level: level, file: file, line: line || 0, message: what, fix: fix || '' })
		}

		var rules = givenRules
		if (!rules && files[RULES_FILE]) {
			try {
				rules = JSON.parse(files[RULES_FILE].text)
			} catch (error) {
				report('error', RULES_FILE, 0, 'El archivo de reglas está dañado.', 'No se toca la carpeta site/.pcs/: vuelve a pedir el paquete.')
			}
		}
		if (!rules || !rules.marks) {
			report('error', RULES_FILE, 0, 'No encuentro las reglas de las marcas.', 'Elige la carpeta del paquete entera (la que tiene CHECK.html y la carpeta site/).')
			return { problems: problems, generated: {}, changed: [] }
		}
		var namePattern = new RegExp(rules.namePattern)
		var fieldPattern = new RegExp(rules.fieldPattern)
		var markNames = Object.keys(rules.marks)
		//Si las reglas declaran atributos comunes (class, id, data-*…), valen en cualquier marca; si no, ninguno.
		var globalAttributes = Array.isArray(rules.globalAttributes) ? rules.globalAttributes : []
		function isGlobalAttribute(name) {
			return globalAttributes.some(function (allowed) {
				return allowed.slice(-1) === '*' ? name.indexOf(allowed.slice(0, -1)) === 0 : name === allowed
			})
		}

		var pages = []
		var parts = {}
		Object.keys(files).forEach(function (path) {
			var page = /^site\/([^/]+)\.html$/i.exec(path)
			var part = /^site\/parts\/([^/]+)\.html$/i.exec(path)
			if (page) {
				if (!namePattern.test(page[1])) {
					report('error', path, 0, 'El nombre de la página «' + page[1] + '» no vale.', 'Renómbrala solo con minúsculas, números y guiones (por ejemplo, «sobre-nosotros.html»).')
				}
				pages.push(page[1])
			} else if (part) {
				if (!namePattern.test(part[1])) {
					report('error', path, 0, 'El nombre de la parte «' + part[1] + '» no vale.', 'Renómbrala solo con minúsculas, números y guiones (por ejemplo, «menu-principal.html»).')
				}
				parts[part[1]] = files[path].text
			} else if (/^site\/parts\/.+\/.+\.html$/i.test(path)) {
				report('aviso', path, 0, 'Las partes van sueltas en site/parts/, no en subcarpetas.', 'Muévela directamente a site/parts/.')
			} else if (/^site\/(?!assets\/|data\/|parts\/|\.).+\/[^/]+\.html$/i.test(path)) {
				report('aviso', path, 0, 'Las páginas van sueltas en site/, no en subcarpetas: esta no se publicará.', 'Muévela directamente a site/.')
			}
		})
		if (pages.length === 0) {
			report('error', 'site/', 0, 'No hay ninguna página en site/.', 'Comprueba que elegiste la carpeta del paquete (la que tiene CHECK.html) y que tus páginas están en site/ con extensión .html.')
		}

		var data = {}
		var examples = {}
		function examplesOf(resource, field) {
			return examples[resource].map(function (item) {
				var value = item && item[field] !== undefined && item[field] !== null ? item[field] : (data[resource][field] || {}).example
				return value === undefined || value === null ? '' : String(value)
			})
		}
		Object.keys(files).forEach(function (path) {
			var match = /^site\/data\/([^/]+)\.js$/i.exec(path)
			if (!match) {
				return
			}
			var resource = match[1]
			var loaded
			try {
				loaded = evaluateData(files[path].text)
			} catch (error) {
				report('error', path, 0, 'Los datos de ejemplo tienen un error de escritura: ' + error.message + '.', 'Si lo tocaste, deshaz el cambio; si no, pide el archivo de nuevo.')
				return
			}
			var set = loaded[resource]
			if (!set || typeof set !== 'object') {
				report('error', path, 0, 'Este archivo no define los datos de «' + resource + '».', 'El archivo site/data/' + resource + '.js debe empezar por window.PCS_DATA[\'' + resource + '\'] = {…}.')
				return
			}
			var fields = set.fields && typeof set.fields === 'object' ? set.fields : {}
			Object.keys(fields).forEach(function (field) {
				if (!fieldPattern.test(field)) {
					report('error', path, 0, 'El campo «' + field + '» tiene un nombre que no vale.', 'Usa letras, números y guion bajo, empezando por una letra.')
				}
			})
				; (Array.isArray(set.items) ? set.items : []).forEach(function (item, index) {
					Object.keys(item || {}).forEach(function (key) {
						if (!Object.prototype.hasOwnProperty.call(fields, key)) {
							report('aviso', path, 0, 'El ejemplo n.º ' + (index + 1) + ' trae «' + key + '», que no es un campo de «' + resource + '».', 'Ese dato no existe en el sistema: no lo uses en el diseño.')
						}
					})
				})
			data[resource] = fields
			examples[resource] = (Array.isArray(set.items) ? set.items : []).concat([{}])
		})

		var partUses = {}

		function checkLocalReference(path, element, attribute, fromPart) {
			var value = (element.attrs[attribute] || '').trim()
			if (!value || /^([a-z][a-z0-9+.-]*:|\/\/|#|\{\{|\?)/i.test(value)) {
				return
			}
			var clean = value.split('#')[0].split('?')[0]
			if (!clean) {
				return
			}
			var target = resolve(SITE, clean)
			//El lector (site/.pcs/) no es del diseñador: lo vigila checkIntegrity con `protected`, también donde no se ve.
			if (target.indexOf(READER) === 0) {
				return
			}
			if (/\.html?$/i.test(clean) && element.tag === 'a' && /^site\/[^/]+\.html?$/i.test(target)) {
				report('error', path, element.line, 'El enlace <a href="' + value + '"> va a otra página con un enlace normal, que no funcionará en el sitio real.', 'Cámbialo por <pcs-link page="' + target.replace(/^site\//, '').replace(/\.html?$/i, '') + '">…</pcs-link>.')
				return
			}
			if (!files[target]) {
				var where = fromPart ? ' (en una parte, las rutas se escriben como desde site/, porque la parte se pinta dentro de la página)' : ''
				report('error', path, element.line, 'El archivo «' + value + '» no existe' + where + '.', 'Corrige la ruta o copia el archivo a site/assets/.')
			}
		}

		function checkFile(path, isPart) {
			var text = files[path].text || ''
			var tree = parseHtml(text)
			var hasScript = false
			var scriptBefore = false
			var hasTitle = false
			var hasMeta = false
			walk(tree, function (node, inTemplate) {
				if (node.tag === '#text') {
					if (!node.raw) {
						checkPlaceholders(path, node.line, node.text, node, inTemplate, null)
					} else if (inTemplate && node.parent && (node.parent.tag === 'script' || node.parent.tag === 'style') && node.text.indexOf('{{') !== -1) {
						report('error', path, node.line, 'Hay un {{campo}} dentro de <' + node.parent.tag + '> en el molde de una lista.', UNSAFE_FIX)
					}
					return
				}
				if (node.tag === 'script' && /(^|\/)\.pcs\/pcs\.js$/.test(node.attrs.src || '')) {
					if (!isPart && !hasScript && ('async' in node.attrs || 'defer' in node.attrs || (node.attrs.type || '').trim().toLowerCase() === 'module')) {
						report('aviso', path, node.line, 'La vista previa se carga con async, defer o type="module": tus scripts no verán las partes ni las marcas pintadas.', 'Escríbelo tal cual, en <head>: <script src=".pcs/pcs.js"></script>, sin async, defer ni type.')
					}
					hasScript = true
				} else if (node.tag === 'script' && !isPart && !inTemplate && !hasScript && !scriptBefore && /^(|text\/javascript|application\/javascript|module)$/i.test((node.attrs.type || '').trim())) {
					scriptBefore = true
					report('aviso', path, node.line, 'Hay un <script> antes de la vista previa: si busca algo de tus partes o de tus marcas, no lo encontrará.', 'Pon <script src=".pcs/pcs.js"></script> en <head>, antes de cualquier otro <script>.')
				}
				if (node.tag === 'title' && !inTemplate) {
					hasTitle = true
				}
				node.attrList.forEach(function (attribute) {
					var value = node.attrs[attribute]
					if (value.indexOf('{{') !== -1) {
						checkPlaceholders(path, node.line, value, node, inTemplate, attribute)
					}
				})
				if (node.tag === 'img' || node.tag === 'script' || node.tag === 'source' || node.tag === 'video' || node.tag === 'audio') {
					checkLocalReference(path, node, 'src', isPart)
				}
				if (node.tag === 'link' || node.tag === 'a') {
					checkLocalReference(path, node, 'href', isPart)
				}
				if (node.tag === 'form' && closest(node, 'pcs-form')) {
					report('error', path, node.line, 'Hay un <form> dentro de <pcs-form>.', 'Quita el <form>: <pcs-form> ya es el formulario.')
				}
				if (node.tag === 'base') {
					report('error', path, node.line, 'Tu página trae su propio <base>.', 'Quita tu <base>; el sitio pone el suyo.')
				}
				if (node.tag.indexOf('pcs-') !== 0) {
					return
				}
				if (inTemplate) {
					report('error', path, node.line, 'Dentro del molde de una lista no van marcas: <' + node.tag + '> no se resolvería en el sitio real.', 'Sácala de la <template>; dentro solo van HTML y {{campos}}.')
				}
				if (node.tag === 'pcs-meta') {
					hasMeta = true
				}
				checkMark(path, node, isPart)
			})
			if (!isPart) {
				if (!hasScript) {
					report('aviso', path, 1, 'La página no carga la vista previa.', 'Pon <script src=".pcs/pcs.js"></script> dentro de <head>: sin eso las marcas no se ven al abrirla.')
				}
				if (hasMeta && !hasTitle) {
					report('aviso', path, 0, 'La página no tiene <title>.', 'Pon <title>Nombre de la página</title> en <head>: es el título que usará el sitio real.')
				}
			}
		}

		function checkPlaceholders(path, line, text, node, inTemplate, attribute) {
			PLACEHOLDER.lastIndex = 0
			var match
			while ((match = PLACEHOLDER.exec(text))) {
				var field = match[1].trim()
				var list = inTemplate ? closest(closest(node, 'template') || node, 'pcs-list') : null
				if (!inTemplate || !list) {
					report('aviso', path, line, '{{' + field + '}} está fuera de una lista: se verá tal cual.', 'Los {{campos}} solo se rellenan dentro de la <template> de una <pcs-list>.')
					continue
				}
				if (attribute && (/^on/i.test(attribute) || attribute === 'style' || attribute === 'srcdoc' || (attribute === 'data' && node.tag === 'object') || unsafeElsewhere(node, attribute))) {
					report('error', path, line, '{{' + field + '}} está dentro del atributo ' + attribute + '.', UNSAFE_FIX)
					continue
				}
				if (!fieldPattern.test(field)) {
					report('error', path, line, '{{' + field + '}} no es un nombre de campo válido.', 'Escribe el nombre tal cual aparece en site/data/' + (list.attrs.resource || '<recurso>') + '.js, sin espacios raros ni símbolos.')
					continue
				}
				var resource = list.attrs.resource
				if (data[resource] && !Object.prototype.hasOwnProperty.call(data[resource], field)) {
					report('error', path, line, 'El campo {{' + field + '}} no existe en «' + resource + '».', 'Los campos que hay son: ' + (Object.keys(data[resource]).join(', ') || 'ninguno') + '. Pedir uno nuevo es trabajo de programación.')
				} else if (data[resource] && attribute && URL_ATTRIBUTES.indexOf(attribute) !== -1) {
					var refused = examplesOf(resource, field).filter(function (value) {
						return value !== '' && safeUrl(value) === ''
					})
					if (refused.length) {
						report('aviso', path, line, 'Un dato de ejemplo de {{' + field + '}} («' + refused[0] + '») no es una dirección que se pueda usar en ' + attribute + ': en el sitio saldrá vacío.', 'Si el dato real fuera así, no enlazaría a nada; avisa al equipo del sistema si lo esperabas.')
					}
				}
			}
		}

		function checkMark(path, node, isPart) {
			var rule = rules.marks[node.tag]
			if (!rule) {
				report('error', path, node.line, 'La marca <' + node.tag + '> no existe.', 'Las que existen son: ' + markNames.map(function (name) { return '<' + name + '>' }).join(', ') + '. Revisa cómo está escrita.')
				return
			}
			var attributes = rule.attributes || {}
			Object.keys(attributes).forEach(function (name) {
				var spec = attributes[name]
				var value = node.attrs[name]
				if (value === undefined || value === '') {
					if (spec.required) {
						report('error', path, node.line, 'A <' + node.tag + '> le falta el atributo ' + name + '.', 'Escríbelo así: <' + node.tag + ' ' + name + '="…">.')
					}
					return
				}
				if (spec.pattern) {
					var source = rules[spec.pattern + 'Pattern'] || spec.pattern
					if (!new RegExp(source).test(value)) {
						report('error', path, node.line, 'El valor ' + name + '="' + value + '" de <' + node.tag + '> no vale.', PATTERN_FIX[spec.pattern] || 'Corrígelo como indica GUIDE.html.')
					}
				}
				if (spec.integer) {
					var number = Number(value)
					if (!/^\d+$/.test(value) || number < spec.integer.min || number > spec.integer.max) {
						report('error', path, node.line, 'El valor ' + name + '="' + value + '" de <' + node.tag + '> no vale.', 'Pon un número entero entre ' + spec.integer.min + ' y ' + spec.integer.max + '.')
					}
				}
				if (Array.isArray(spec.values) && spec.values.indexOf(value) === -1) {
					report('error', path, node.line, 'El valor ' + name + '="' + value + '" de <' + node.tag + '> no existe.', 'Usa uno de estos: ' + spec.values.join(', ') + '.')
				}
			})
			var dropped = []
			node.attrList.forEach(function (name) {
				if (!Object.prototype.hasOwnProperty.call(attributes, name) && !isGlobalAttribute(name)) {
					report('aviso', path, node.line, '<' + node.tag + '> no usa el atributo ' + name + '.', 'Quítalo, o pásalo al elemento de dentro si es para estilo.')
				} else if (rule.passesGlobalAttributes === false && !Object.prototype.hasOwnProperty.call(attributes, name)) {
					dropped.push(name)
				}
			})
			if (dropped.length) {
				report('aviso', path, node.line, '<' + node.tag + '> lleva ' + dropped.join(', ') + ': en el sitio real no llegan.', 'Pon esos atributos en un elemento que envuelva la imagen (por ejemplo, un <span>), o quítalos.')
			}
			var elements = node.children.filter(function (child) {
				return child.tag !== '#text'
			})
			var text = node.children
				.filter(function (child) {
					return child.tag === '#text'
				})
				.map(textOf)
				.join('')
				.trim()
			if (rule.content === 'empty' && (elements.length > 0 || text !== '')) {
				report('aviso', path, node.line, 'Lo que hay dentro de <' + node.tag + '> se pierde.', 'Déjala vacía: <' + node.tag + ' …></' + node.tag + '>.')
			}
			if (rule.content === 'text' && elements.length > 0) {
				report('error', path, node.line, '<' + node.tag + '> solo admite texto, y tiene etiquetas dentro.', 'Pon las etiquetas fuera: <strong><pcs-text key="…">texto</pcs-text></strong>.')
			}
			if (rule.content === 'template') {
				var templates = elements.filter(function (child) {
					return child.tag === 'template'
				})
				if (templates.length !== 1) {
					report('error', path, node.line, '<' + node.tag + '> necesita una sola <template> dentro, y tiene ' + templates.length + '.', 'Pon dentro <template> con el HTML de UN elemento de la lista; se repite solo.')
				} else if (elements.length > 1 || text !== '') {
					report('aviso', path, node.line, 'Lo que hay en <' + node.tag + '> fuera de la <template> se pierde.', 'Mételo dentro de la <template> o sácalo de la lista.')
				}
			}
			if (rule.content === 'inline') {
				walk(node, function (child) {
					if (BLOCK.indexOf(child.tag) !== -1) {
						report('aviso', path, child.line, '<' + child.tag + '> dentro de <' + node.tag + '>: un enlace no debería llevar bloques.', 'Deja dentro solo texto, imágenes o etiquetas de línea (span, strong, img…).')
					}
				})
			}
			if (node.tag === 'pcs-part' && node.attrs.name && namePattern.test(node.attrs.name)) {
				if (!files[SITE + 'parts/' + node.attrs.name + '.html']) {
					report('error', path, node.line, 'La parte «' + node.attrs.name + '» no existe.', 'Crea site/parts/' + node.attrs.name + '.html o corrige el nombre.')
				}
				if (isPart) {
					var from = path.replace(/^site\/parts\//, '').replace(/\.html$/i, '')
						; (partUses[from] = partUses[from] || []).push({ name: node.attrs.name, line: node.line })
				}
			}
			if (node.tag === 'pcs-link' && node.attrs.page && namePattern.test(node.attrs.page) && !files[SITE + node.attrs.page + '.html']) {
				report('error', path, node.line, 'El enlace va a la página «' + node.attrs.page + '», que no existe.', 'Crea site/' + node.attrs.page + '.html o corrige el nombre.')
			}
			if (node.tag === 'pcs-list' && node.attrs.resource && namePattern.test(node.attrs.resource) && !data[node.attrs.resource]) {
				var known = Object.keys(data)
				report('error', path, node.line, 'No hay datos de ejemplo para «' + node.attrs.resource + '».', known.length ? 'Los recursos que hay son: ' + known.join(', ') + '. Si necesitas otro, pídeselo al equipo del proyecto: lo registra en el sistema y te manda su site/data/<recurso>.js.' : 'Falta site/data/' + node.attrs.resource + '.js: pídeselo al equipo del proyecto, que registra el recurso en el sistema y te manda ese archivo.')
			}
		}

		Object.keys(files)
			.sort()
			.forEach(function (path) {
				if (/^site\/[^/]+\.html$/i.test(path)) {
					checkFile(path, false)
				} else if (/^site\/parts\/[^/]+\.html$/i.test(path)) {
					checkFile(path, true)
				}
			})

		//Una parte que se incluye a sí misma, directa o por otra, colgaría la página.
		Object.keys(partUses).forEach(function (start) {
			var seen = {}
			var stack = [start]
			while (stack.length) {
				var name = stack.pop()
					; (partUses[name] || []).forEach(function (use) {
						if (use.name === start) {
							report('error', SITE + 'parts/' + name + '.html', use.line, 'La parte «' + start + '» acaba incluyéndose a sí misma.', 'Quita la <pcs-part> que cierra el círculo.')
						} else if (!seen[use.name]) {
							seen[use.name] = true
							stack.push(use.name)
						}
					})
			}
		})

		//──── Lo generado y lo que ha cambiado ────
		var generated = {}
		var changed = []
		var formatVersion = rules.formatVersion || 1
		var resources = Object.keys(files)
			.map(function (path) {
				var match = /^site\/data\/([^/]+)\.js$/i.exec(path)
				return match && namePattern.test(match[1]) ? match[1] : ''
			})
			.filter(Boolean)
		generated[PARTS_FILE] = buildPartsJs(parts, pages, formatVersion, resources)

		var previousParts = null
		if (files[PARTS_FILE]) {
			try {
				previousParts = evaluateParts(files[PARTS_FILE].text).PCS_PARTS || {}
			} catch (error) {
				previousParts = {}
			}
		}
		var stale = Object.keys(parts).filter(function (name) {
			return !previousParts || previousParts[name] !== parts[name]
		})
		if (previousParts) {
			Object.keys(previousParts).forEach(function (name) {
				if (!Object.prototype.hasOwnProperty.call(parts, name)) {
					stale.push(name)
				}
			})
		}
		if (stale.length) {
			report('aviso', PARTS_FILE, 0, 'El paquete de partes no estaba al día (' + stale.sort().join(', ') + ').', 'Pulsa «Actualizar el paquete»: la vista previa usa el paquete, no los archivos de site/parts/.')
		}

		//Lo que finge, las URL ajenas y los bloques copiados: avisos (la ruta local disfrazada, error).
		var htmlPaths = Object.keys(files).filter(function (path) {
			return /^site\/([^/]+|parts\/[^/]+)\.html$/i.test(path)
		}).sort()
		htmlPaths.forEach(function (path) {
			lintFakes(path, parseHtml(files[path].text || ''), report)
		})
		var packagePaths = Object.keys(files).filter(function (path) {
			return path.indexOf(SITE) === 0 && path.indexOf(READER) !== 0
		}).map(function (path) {
			return path.slice(SITE.length)
		}).sort()
		var urlInputs = htmlPaths.map(function (path) {
			return { file: path, text: files[path].text || '', kind: 'html' }
		}).concat(Object.keys(files).filter(function (path) {
			return /^site\/assets\/.+\.(css|js)$/i.test(path)
		}).sort().map(function (path) {
			return { file: path, text: files[path].text || '', kind: /\.js$/i.test(path) ? 'js' : 'css' }
		}))
		externalUrls(urlInputs, knownHostList(rules), packagePaths).forEach(function (url) {
			if (url.exact) {
				report('error', url.file, url.line, 'Es una ruta local disfrazada: «' + url.url + '» apunta a un archivo que está en el paquete (' + url.path + ').', 'Enlázalo con su ruta relativa dentro del paquete, la de site/' + url.path + '.')
			} else if (url.path !== '') {
				report('aviso', url.file, url.line, 'Quizá una ruta local disfrazada: «' + url.url + '» se llama como un archivo del paquete (' + url.path + ').', 'Si es ese archivo, enlázalo con su ruta relativa (site/' + url.path + '); si es otro de fuera, déjalo.')
			} else {
				report('aviso', url.file, url.line, 'Una dirección absoluta a un servicio que el proyecto no conoce (' + url.host + ').', 'Si el archivo es tuyo, ponlo en el paquete y enlázalo con su ruta relativa; si es un servicio de fuera, pídele al equipo del proyecto que lo añada a su lista.')
			}
		})
		var pageTexts = {}
		htmlPaths.forEach(function (path) {
			if (/^site\/[^/]+\.html$/i.test(path)) {
				pageTexts[path] = files[path].text || ''
			}
		})
		repeatedBlocks(pageTexts).forEach(function (block) {
			report('aviso', block.file, block.line, 'Este bloque aparece igual en ' + block.pages.length + ' páginas (' + block.pages.map(function (path) { return path.slice(SITE.length) }).join(', ') + '): parece una parte.', 'Pásalo a site/parts/<nombre>.html y ponlo en cada página con <pcs-part name="<nombre>"></pcs-part>.')
		})

		var manifest = { name: '', formatVersion: formatVersion, files: {} }
		var protectedFiles = null
		if (files['pcs.json']) {
			try {
				var previous = JSON.parse(files['pcs.json'].text)
				manifest.name = typeof previous.name === 'string' ? previous.name : ''
				protectedFiles = previous.protected && typeof previous.protected === 'object' ? previous.protected : null
				if (previous.formatVersion !== formatVersion) {
					report('aviso', 'pcs.json', 0, 'El sitio se hizo con otra versión del formato (' + previous.formatVersion + '; ahora ' + formatVersion + ').', 'Pulsa «Actualizar el paquete» y vuelve a comprobar.')
				}
			} catch (error) {
				report('aviso', 'pcs.json', 0, 'El manifiesto pcs.json está dañado.', 'Pulsa «Actualizar el paquete»: se vuelve a escribir.')
			}
		}
		var after = {}
		Object.keys(files).forEach(function (path) {
			after[path] = files[path]
		})
		Object.keys(generated).forEach(function (path) {
			after[path] = { text: generated[path] }
		})
		manifest.files = fingerprints(after)
		if (protectedFiles) {
			manifest.protected = protectedFiles
			checkIntegrity(files, protectedFiles, report)
		} else {
			report('aviso', 'pcs.json', 0, 'El paquete no trae las huellas de las piezas que no se tocan: no se puede comprobar que estén intactas.', 'Pide el paquete de nuevo al equipo del sistema.')
		}
		generated['pcs.json'] = JSON.stringify(manifest, null, 4) + '\n'

		Object.keys(generated).forEach(function (path) {
			if (!files[path] || files[path].text !== generated[path]) {
				changed.push(path)
			}
		})

		return { problems: problems, generated: generated, changed: changed }
	}

	//`protected` lo escribe la exportación y aquí solo se comprueba: el lector y los archivos del agente, intactos.
	function checkIntegrity(files, protectedFiles, report) {
		var paths = Object.keys(protectedFiles).sort()
		var missing = paths.filter(function (path) {
			return !files[path]
		})
		if (paths.length > 0 && missing.length === paths.length) {
			report('aviso', 'site/.pcs/', 0, 'No veo las carpetas ocultas (site/.pcs/, .claude/…): no se pudo comprobar que estén intactas.', 'Usa Chrome o Edge, que dejan ver la carpeta entera, o pide al equipo que lo compruebe.')
			return
		}
		paths.forEach(function (path) {
			if (!files[path]) {
				report('error', path, 0, 'Falta una pieza que no se toca.', 'Vuelve a pedir el paquete o restaura ' + path + ' desde el original.')
			} else if (sha256(files[path].bytes || utf8(files[path].text || '')) !== protectedFiles[path]) {
				report('error', path, 0, 'Se cambió una pieza que no se toca.', 'Vuelve a pedir el paquete o restaura ' + path + ' desde el original.')
			}
		})
		Object.keys(files).forEach(function (path) {
			if (path.indexOf(READER) === 0 && !Object.prototype.hasOwnProperty.call(protectedFiles, path) && !HIDDEN.test(path.slice(READER.length))) {
				report('error', path, 0, 'Hay un archivo que no es del paquete dentro de site/.pcs/.', 'Bórralo: en site/.pcs/ solo va lo que trae el paquete.')
			}
		})
	}

	function resolve(base, relative) {
		var segments = (base + relative).split('/')
		var out = []
		segments.forEach(function (segment) {
			if (segment === '..') {
				out.pop()
			} else if (segment !== '.' && segment !== '') {
				out.push(segment)
			}
		})
		return decodeURIComponentSafe(out.join('/'))
	}

	function decodeURIComponentSafe(value) {
		try {
			return decodeURIComponent(value)
		} catch (error) {
			return value
		}
	}

	var api = { run: run, safeUrl: safeUrl, parseHtml: parseHtml, externalUrls: externalUrls, repeatedBlocks: repeatedBlocks, knownHostList: knownHostList, sha256: sha256, utf8: utf8, buildRulesJs: buildRulesJs, buildPartsJs: buildPartsJs }
	if (typeof module !== 'undefined' && module.exports) {
		module.exports = api
	} else {
		global.PCSCheck = api
	}
})(typeof window !== 'undefined' ? window : this)
