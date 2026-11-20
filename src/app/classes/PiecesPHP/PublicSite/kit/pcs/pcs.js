//Vista previa de las marcas <pcs-*> (ADR 0084): desde site/.pcs/ carga rules.js, parts/parts.js y data/ con <script>, nunca fetch
//(file:// no deja leer), y sustituye cada marca sin Shadow DOM; los datos entran como texto, nunca como HTML.
(function () {
	'use strict'

	if (window.PCS_PREVIEW_LOADED) {
		return
	}
	window.PCS_PREVIEW_LOADED = true

	var script = document.currentScript
	var pcsBase = script && script.src ? new URL('./', script.src) : new URL('.pcs/', document.baseURI)
	var siteRoot = new URL('../', pcsBase)
	var MAX_DEPTH = 10

	var notices = []
	var loadedScripts = {}

	//Los comportamientos de vista previa, uno por marca. Las reglas (qué marcas existen y sus atributos) vienen de rules.js.
	var behaviors = {
		'pcs-part': renderPart,
		'pcs-link': renderLink,
		'pcs-meta': renderMeta,
		'pcs-image': renderImage,
		'pcs-text': renderText,
		'pcs-list': renderList,
		'pcs-form': renderForm,
	}

	function siteUrl(path) {
		return new URL(path, siteRoot).href
	}

	function loadScript(path) {
		var url = siteUrl(path)
		if (loadedScripts[url]) {
			return loadedScripts[url]
		}
		loadedScripts[url] = new Promise(function (resolve) {
			var tag = document.createElement('script')
			tag.src = url
			tag.onload = function () {
				resolve(true)
			}
			tag.onerror = function () {
				resolve(false)
			}
			document.head.appendChild(tag)
		})
		return loadedScripts[url]
	}

	function notice(text) {
		if (notices.indexOf(text) === -1) {
			notices.push(text)
		}
	}

	function rules() {
		return window.PCS_RULES && window.PCS_RULES.marks ? window.PCS_RULES : null
	}

	function pageName() {
		var file = decodeURIComponent(location.pathname.split('/').pop() || '')
		return file.replace(/\.html?$/i, '') || 'inicio'
	}

	//Un recuadro visible en lugar de una marca que no se puede pintar: el diseñador lo ve sin abrir la consola.
	function problemBox(element, text) {
		var box = document.createElement('span')
		box.className = 'pcs-preview-problem'
		box.setAttribute(
			'style',
			'display:inline-block;margin:2px;padding:4px 8px;border:2px dashed #c0392b;background:#fdecea;color:#7b1d12;' +
			'font:13px/1.4 system-ui,sans-serif;border-radius:4px'
		)
		box.textContent = text
		element.replaceWith(box)
		notice(text)
	}

	function copyAttributes(from, to, skip) {
		for (var i = 0; i < from.attributes.length; i++) {
			var attribute = from.attributes[i]
			if (skip.indexOf(attribute.name) === -1) {
				to.setAttribute(attribute.name, attribute.value)
			}
		}
	}

	function moveChildren(from, to) {
		while (from.firstChild) {
			to.appendChild(from.firstChild)
		}
	}

	//Comprueba los atributos obligatorios y sus patrones con las reglas compartidas; devuelve el problema o ''.
	function attributeProblem(element) {
		var all = rules()
		if (!all) {
			return ''
		}
		var tag = element.localName
		var rule = all.marks[tag]
		if (!rule) {
			return 'Marca desconocida <' + tag + '>: abre CHECK.html para ver qué hacer.'
		}
		var attributes = rule.attributes || {}
		for (var name in attributes) {
			var spec = attributes[name]
			var value = element.getAttribute(name)
			if (value === null || value === '') {
				if (spec.required) {
					return 'A <' + tag + '> le falta el atributo ' + name + '.'
				}
				continue
			}
			if (spec.pattern) {
				var source = all[spec.pattern + 'Pattern'] || spec.pattern
				if (!new RegExp(source).test(value)) {
					return 'El atributo ' + name + '="' + value + '" de <' + tag + '> no vale: solo minúsculas, números y guiones.'
				}
			}
			if (spec.integer) {
				var number = Number(value)
				if (!/^\d+$/.test(value) || number < spec.integer.min || number > spec.integer.max) {
					return 'El atributo ' + name + '="' + value + '" de <' + tag + '> debe ser un número entre ' + spec.integer.min + ' y ' + spec.integer.max + '.'
				}
			}
			if (Array.isArray(spec.values) && spec.values.indexOf(value) === -1) {
				return 'El atributo ' + name + '="' + value + '" de <' + tag + '> no existe: usa uno de estos: ' + spec.values.join(', ') + '.'
			}
		}
		return ''
	}

	function renderPart(element, depth) {
		var name = element.getAttribute('name')
		var parts = window.PCS_PARTS || {}
		if (!Object.prototype.hasOwnProperty.call(parts, name)) {
			problemBox(element, 'Falta la parte «' + name + '» en el paquete: crea parts/' + name + '.html y abre CHECK.html.')
			return
		}
		var holder = document.createElement('template')
		holder.innerHTML = parts[name]
		var fragment = holder.content
		//Los <script> de una parte no corren al insertarse con innerHTML: se recrean para que corran como en el servidor.
		var scripts = fragment.querySelectorAll('script')
		for (var i = 0; i < scripts.length; i++) {
			var fresh = document.createElement('script')
			copyAttributes(scripts[i], fresh, [])
			fresh.text = scripts[i].text
			scripts[i].replaceWith(fresh)
		}
		var nodes = Array.prototype.slice.call(fragment.childNodes)
		element.replaceWith(fragment)
		checkPartFreshness(name, parts[name])
		return { nodes: nodes, depth: depth + 1 }
	}

	function renderLink(element) {
		var link = document.createElement('a')
		copyAttributes(element, link, ['page', 'href'])
		link.setAttribute('href', siteUrl(element.getAttribute('page') + '.html'))
		moveChildren(element, link)
		element.replaceWith(link)
		return { nodes: [link] }
	}

	//El <title> y la descripción del diseñador se respetan (el servidor también los usa); si faltan, unos de ejemplo.
	function renderMeta(element) {
		var page = pageName()
		if (!document.querySelector('head > title') || document.title.trim() === '') {
			document.title = 'Título de ejemplo · ' + page
		}
		var description = document.querySelector('meta[name="description"]')
		if (!description) {
			description = document.createElement('meta')
			description.setAttribute('name', 'description')
			description.setAttribute('content', 'Descripción de ejemplo de la página «' + page + '». En el sitio real la pone el sistema.')
			document.head.appendChild(description)
		}
		element.remove()
	}

	//La imagen de ejemplo de una imagen de marca: un dibujo con su nombre, para cualquier marca, sin archivos aparte.
	function brandPlaceholder(brand) {
		var svg =
			'<svg xmlns="http://www.w3.org/2000/svg" width="160" height="48" viewBox="0 0 160 48">' +
			'<rect width="160" height="48" rx="6" fill="#e8eef7" stroke="#7a8fb3" stroke-dasharray="4 3"/>' +
			'<text x="80" y="30" font-family="system-ui,sans-serif" font-size="16" text-anchor="middle" fill="#2c3e66"></text></svg>'
		var doc = new DOMParser().parseFromString(svg, 'image/svg+xml')
		doc.querySelector('text').textContent = brand
		return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(new XMLSerializer().serializeToString(doc))
	}

	function renderImage(element) {
		var brand = element.getAttribute('brand')
		var image = document.createElement('img')
		copyAttributes(element, image, ['brand', 'src'])
		if (!image.hasAttribute('alt')) {
			image.setAttribute('alt', brand)
		}
		image.setAttribute('src', brandPlaceholder(brand))
		element.replaceWith(image)
	}

	function renderText(element) {
		element.replaceWith(document.createTextNode(element.textContent))
	}

	function exampleItems(resource, count) {
		var data = (window.PCS_DATA || {})[resource]
		if (!data) {
			return null
		}
		var fields = data.fields || {}
		var items = Array.isArray(data.items) && data.items.length > 0 ? data.items : null
		if (!items) {
			var example = {}
			for (var field in fields) {
				example[field] = fields[field] && 'example' in fields[field] ? fields[field].example : ''
			}
			items = [example]
		}
		//Siempre count elementos (el servidor pide count filas): si hay menos ejemplos, se repiten en orden.
		var repeated = []
		for (var i = 0; i < count; i++) {
			repeated.push(items[i % items.length])
		}
		return {
			fields: fields,
			items: repeated,
		}
	}

	function valueOf(item, fields, field) {
		if (item && Object.prototype.hasOwnProperty.call(item, field) && item[field] !== null && item[field] !== undefined) {
			return String(item[field])
		}
		if (fields[field] && 'example' in fields[field]) {
			return String(fields[field].example)
		}
		return ''
	}

	var PLACEHOLDER = /\{\{\s*([A-Za-z][A-Za-z0-9_]*)\s*\}\}/g

	//Los atributos de URL y la lista blanca de safeUrl() son los del servidor (MarkRegistry::safeUrl): misma salida.
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

	function fill(text, item, fields, resource, asUrl) {
		return text.replace(PLACEHOLDER, function (all, field) {
			if (!Object.prototype.hasOwnProperty.call(fields, field)) {
				notice('El campo {{' + field + '}} no existe en el recurso «' + resource + '»: abre CHECK.html.')
			}
			var value = valueOf(item, fields, field)
			var type = fields[field] && fields[field].type
			return asUrl || type === 'url' || type === 'image' ? safeUrl(value) : value
		})
	}

	//Un {{campo}} en on*, style, <script> o <style> no se sustituye: ahí el escapado no protege, y el servidor tampoco.
	function unsafePlaceholder(content) {
		var elements = content.querySelectorAll('*')
		for (var i = 0; i < elements.length; i++) {
			var element = elements[i]
			if ((element.localName === 'script' || element.localName === 'style') && element.textContent.indexOf('{{') !== -1) {
				return '<' + element.localName + '>'
			}
			for (var k = 0; k < element.attributes.length; k++) {
				var name = element.attributes[k].name.toLowerCase()
				if ((name.indexOf('on') === 0 || name === 'style') && element.attributes[k].value.indexOf('{{') !== -1) {
					return name
				}
			}
		}
		return ''
	}

	//Sustituye {{campo}} SIEMPRE como texto: nodeValue y setAttribute, nunca innerHTML. Un dato con <script> sale escrito.
	function fillNode(root, item, fields, resource) {
		var walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT)
		var node = walker.currentNode
		while (node) {
			if (node.nodeType === 3) {
				if (node.nodeValue.indexOf('{{') !== -1) {
					node.textContent = fill(node.nodeValue, item, fields, resource)
				}
			} else if (node.nodeType === 1) {
				for (var i = 0; i < node.attributes.length; i++) {
					var attribute = node.attributes[i]
					if (attribute.value.indexOf('{{') === -1) {
						continue
					}
					var value = fill(attribute.value, item, fields, resource, URL_ATTRIBUTES.indexOf(attribute.name.toLowerCase()) !== -1)
					node.setAttribute(attribute.name, value)
				}
			}
			node = walker.nextNode()
		}
	}

	//Una marca en el molde de una lista no se pinta, como en el servidor: se queda su recuadro rojo.
	function markInTemplate(copy) {
		var inner = copy.querySelectorAll('*')
		for (var i = 0; i < inner.length; i++) {
			if (inner[i].localName.indexOf('pcs-') === 0 && copy.contains(inner[i])) {
				problemBox(inner[i], 'Dentro del molde de una lista no van marcas: <' + inner[i].localName + '> no se pinta. Sácala de la <template>.')
			}
		}
	}

	function renderList(element, depth) {
		var resource = element.getAttribute('resource')
		var template = null
		for (var i = 0; i < element.children.length; i++) {
			if (element.children[i].localName === 'template') {
				template = element.children[i]
				break
			}
		}
		if (!template) {
			problemBox(element, 'La lista «' + resource + '» no tiene <template> dentro: es el molde que se repite.')
			return
		}
		var unsafe = unsafePlaceholder(template.content)
		if (unsafe) {
			problemBox(element, 'La lista «' + resource + '» pone un dato en ' + unsafe + ': un dato no puede ir dentro de un evento, un estilo o un script; ponlo en el texto o en un atributo normal.')
			return
		}
		var all = rules()
		var count = parseInt(element.getAttribute('count') || '0', 10) || (all ? all.marks['pcs-list'].attributes.count.default : 0)
		var data = exampleItems(resource, count)
		if (!data) {
			problemBox(element, 'No hay datos de ejemplo para «' + resource + '»: falta data/' + resource + '.js.')
			return
		}
		var fragment = document.createDocumentFragment()
		for (var k = 0; k < data.items.length; k++) {
			var copy = document.importNode(template.content, true)
			markInTemplate(copy)
			fillNode(copy, data.items[k], data.fields, resource)
			fragment.appendChild(copy)
		}
		var nodes = Array.prototype.slice.call(fragment.childNodes)
		element.replaceWith(fragment)
		return { nodes: nodes, depth: depth + 1 }
	}

	function renderForm(element) {
		var form = document.createElement('form')
		copyAttributes(element, form, ['action'])
		form.setAttribute('data-pcs-action', element.getAttribute('action'))
		form.setAttribute('action', '#')
		moveChildren(element, form)
		form.addEventListener('submit', function (event) {
			event.preventDefault()
			showToast('Vista previa: el formulario «' + element.getAttribute('action') + '» no se envía. En el sitio real, sí.')
		})
		element.replaceWith(form)
		return { nodes: [form] }
	}

	//Las marcas que quedan dentro de unos nodos, en orden de documento; las de dentro de un <template> esperan a la copia.
	function marksIn(nodes) {
		var found = []
		nodes.forEach(function (node) {
			if (node.nodeType !== 1) {
				return
			}
			if (node.localName.indexOf('pcs-') === 0) {
				found.push(node)
			}
			var inner = node.getElementsByTagName('*')
			for (var i = 0; i < inner.length; i++) {
				if (inner[i].localName.indexOf('pcs-') === 0) {
					found.push(inner[i])
				}
			}
		})
		return found
	}

	//Lo que hay que cargar antes de pintar unos nodos: las partes, si hay alguna, y los datos de cada lista.
	function loadFor(marks) {
		var waits = []
		marks.forEach(function (mark) {
			if (mark.localName === 'pcs-part' && !window.PCS_PARTS) {
				waits.push(loadScript('parts/parts.js'))
			}
			if (mark.localName === 'pcs-list') {
				var resource = mark.getAttribute('resource') || ''
				if (/^[a-z0-9]+(-[a-z0-9]+)*$/.test(resource) && !(window.PCS_DATA && window.PCS_DATA[resource])) {
					waits.push(loadScript('data/' + resource + '.js'))
				}
			}
		})
		return Promise.all(waits)
	}

	function renderNodes(nodes, depth) {
		var marks = marksIn(nodes)
		if (marks.length === 0) {
			return Promise.resolve()
		}
		return loadFor(marks).then(function () {
			var next = []
			marks.forEach(function (mark) {
				if (!mark.isConnected) {
					return
				}
				if (depth > MAX_DEPTH) {
					problemBox(mark, 'Demasiadas partes una dentro de otra (¿una parte se incluye a sí misma?).')
					return
				}
				var problem = attributeProblem(mark)
				if (problem) {
					problemBox(mark, problem)
					return
				}
				var behavior = behaviors[mark.localName]
				if (!behavior) {
					problemBox(mark, 'La marca <' + mark.localName + '> todavía no tiene vista previa.')
					return
				}
				var result = behavior(mark, depth)
				if (result && result.nodes) {
					next.push({ nodes: result.nodes, depth: result.depth || depth })
				}
			})
			return next.reduce(function (chain, item) {
				return chain.then(function () {
					return renderNodes(item.nodes, item.depth)
				})
			}, Promise.resolve())
		})
	}

	//Solo servido por http(s): con doble clic ningún navegador por defecto deja leer la parte, y lo dice CHECK.html.
	//Ni se intenta con file://: Chrome y Firefox llenarían su consola de errores.
	function checkPartFreshness(name, packaged) {
		if (!window.fetch || !/^https?:$/.test(location.protocol)) {
			return
		}
		fetch(siteUrl('parts/' + name + '.html'), { cache: 'no-store' })
			.then(function (response) {
				return response.ok ? response.text() : null
			})
			.then(function (current) {
				if (current !== null && current !== packaged) {
					notice('La parte «' + name + '» cambió y el paquete no: abre CHECK.html y pulsa «Actualizar el paquete».')
					showNotices()
				}
			})
			.catch(function () { })
	}

	//parts.js dice con qué versión del formato se generó; si no es la de las reglas, el paquete es viejo.
	function packageNotices() {
		var info = window.PCS_PARTS_INFO
		if (info && info.formatVersion && rules() && info.formatVersion !== rules().formatVersion) {
			notice('El paquete se hizo con otra versión del formato: abre CHECK.html y pulsa «Actualizar el paquete».')
		}
	}

	function showToast(text) {
		var toast = document.createElement('div')
		toast.setAttribute(
			'style',
			'position:fixed;left:50%;top:16px;transform:translateX(-50%);z-index:2147483647;padding:10px 16px;' +
			'background:#1f2d3d;color:#fff;font:14px/1.4 system-ui,sans-serif;border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,.3)'
		)
		toast.textContent = text
		document.body.appendChild(toast)
		setTimeout(function () {
			toast.remove()
		}, 3500)
	}

	function showNotices() {
		if (notices.length === 0) {
			return
		}
		var bar = document.getElementById('pcs-preview-notice')
		if (!bar) {
			bar = document.createElement('div')
			bar.id = 'pcs-preview-notice'
			bar.setAttribute('role', 'status')
			bar.setAttribute(
				'style',
				'position:fixed;left:0;right:0;bottom:0;z-index:2147483647;max-height:40vh;overflow:auto;padding:10px 44px 10px 16px;' +
				'background:#fff4d6;color:#4a3800;border-top:3px solid #e0a800;font:14px/1.45 system-ui,sans-serif'
			)
			document.body.appendChild(bar)
		}
		bar.textContent = ''
		var title = document.createElement('strong')
		title.textContent = 'Aviso de la vista previa:'
		bar.appendChild(title)
		var list = document.createElement('ul')
		list.setAttribute('style', 'margin:4px 0 0;padding-left:20px')
		notices.forEach(function (text) {
			var item = document.createElement('li')
			item.textContent = text
			list.appendChild(item)
		})
		bar.appendChild(list)
		var close = document.createElement('button')
		close.type = 'button'
		close.textContent = '×'
		close.setAttribute('aria-label', 'Cerrar el aviso')
		close.setAttribute('style', 'position:absolute;right:10px;top:6px;border:0;background:none;font-size:22px;cursor:pointer;color:inherit')
		close.addEventListener('click', function () {
			bar.remove()
		})
		bar.appendChild(close)
	}

	//Las marcas quedan registradas como elementos propios, sin Shadow DOM; lo que pintan se decide al cargar la página.
	function defineMarks(names) {
		if (!window.customElements) {
			return
		}
		names.forEach(function (name) {
			if (!customElements.get(name)) {
				customElements.define(name, class extends HTMLElement { })
			}
		})
	}

	function start() {
		var style = document.createElement('style')
		style.textContent = 'pcs-part,pcs-list,pcs-meta{display:none}'
		document.head.appendChild(style)
		loadScript('.pcs/rules.js')
			.then(function (ok) {
				if (!ok || !rules()) {
					notice('No se pudo cargar .pcs/rules.js: la carpeta oculta .pcs/ de site/ no se mueve ni se borra.')
				}
				defineMarks(Object.keys(rules() ? rules().marks : behaviors))
				packageNotices()
				return renderNodes([document.documentElement], 0)
			})
			.then(function () {
				document.documentElement.setAttribute('data-pcs-preview', 'ready')
				showNotices()
			})
	}

	window.PCSPreview = {
		behaviors: Object.keys(behaviors),
		notices: notices,
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start)
	} else {
		start()
	}
})()
