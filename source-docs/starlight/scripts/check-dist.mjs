//La puerta del sitio (ADR 0076): en lo compilado (dist/**/*.html) no puede quedar nada de MkDocs sin convertir. Falla con
//código 1, el archivo y una muestra si encuentra un enlace relativo a un .md, un aviso «!!!» o «???» como texto, un
//fragmento «--8<--» o atributos de MkDocs («{ .clase }», «{: … }») en el texto. Y mira que cada página de la guía y de la
//API tenga su HTML con tantos avisos como su fuente, y que la descarga de openapi.json sea la fuente. Solo Node.
import { createHash } from 'node:crypto'
import { existsSync, readdirSync, readFileSync } from 'node:fs'
import { join, relative, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

//El sitio que se compiló (DOCS_SITE, DOCS_BASE y DOCS_OUT_DIR, como astro.config.mjs): sus fuentes, su base y su salida.
const SITES = {
	guia: { sources: [{ dir: 'project/docs', prefix: '' }, { dir: 'api/docs', prefix: 'api/' }], base: '/docs', outDir: './dist', openapi: true },
	entornos: { sources: [{ dir: 'entornos/docs', prefix: '' }], base: '/docs-entornos', outDir: '../../docs-entornos', openapi: false },
}
const SITE_NAME = process.env.DOCS_SITE || 'guia'
const SITE = SITES[SITE_NAME]
if (SITE === undefined) {
	console.error(`check-dist: DOCS_SITE=«${SITE_NAME}» no es ningún sitio (guia o entornos).`)
	process.exit(1)
}
const starlightRoot = fileURLToPath(new URL('../', import.meta.url))
const dist = resolve(starlightRoot, process.env.DOCS_OUT_DIR || SITE.outDir) + '/'
const sourcesRoot = fileURLToPath(new URL('../../', import.meta.url))
const SOURCES = SITE.sources
//La descarga de la API, solo en la guía: api/openapi.json, byte a byte source-docs/api/openapi.json.
const OPENAPI_SOURCE = join(sourcesRoot, 'api/openapi.json')
const OPENAPI_DIST = join(dist, 'api/openapi.json')

//Los avisos de una fuente: los «!!!» y «???» sin sangría y fuera de bloques de código, los que convierte el cargador.
const sourceAsides = (markdown) => {
	let fence = null
	let count = 0
	for (const line of markdown.replace(/\r\n/g, '\n').split('\n')) {
		const fenceMatch = /^[ \t]*(```+|~~~+)/.exec(line)
		if (fenceMatch) {
			fence = fence === null ? fenceMatch[1] : line.trim().startsWith(fence) ? null : fence
			continue
		}
		if (fence === null && /^(?:!!!|\?\?\?\+?)[ \t]+[a-z]+/.test(line)) {
			count++
		}
	}
	return count
}
//Los avisos pintados en el contenido de una página (no en el menú, que también usa <details>).
const paintedAsides = (html) => {
	const start = html.indexOf('sl-markdown-content')
	const content = start === -1 ? '' : html.slice(start, html.indexOf('</main>', start) === -1 ? undefined : html.indexOf('</main>', start))
	return (content.match(/class="starlight-aside[ "]/g) ?? []).length + (content.match(/<details[\s>]/g) ?? []).length
}
const sha1 = (buffer) => createHash('sha1').update(buffer).digest('hex')
//El favicon que nombra una página (<link rel="icon" href="…">), relativo a la salida: se le quita la base del sitio.
const SITE_BASE = (process.env.DOCS_BASE || SITE.base).replace(/\/?$/, '/')
const faviconOf = (html) => {
	const link = /<link\b[^>]*\brel="(?:shortcut )?icon"[^>]*>/i.exec(html)?.[0] ?? ''
	const href = /\bhref="([^"]+)"/i.exec(link)?.[1] ?? null
	return href !== null && href.startsWith(SITE_BASE) ? href.slice(SITE_BASE.length) : href
}
//Los canarios de las reglas nuevas: si dejan de distinguir, la puerta no miraría nada.
if (sourceAsides('!!! note "a"\n    x\n```\n!!! tip\n```\n??? info "b"\n    y\n    !!! warning\n') !== 2) {
	console.error('check-dist: el contador de avisos de la fuente no cuenta bien su canario: la puerta no miraría nada.')
	process.exit(1)
}
if (paintedAsides('<nav><details></details></nav><div class="sl-markdown-content"><aside class="starlight-aside starlight-aside--note"></aside><details><summary>x</summary></details></div></main>') !== 2) {
	console.error('check-dist: el contador de avisos pintados no cuenta bien su canario (o cuenta los del menú): la puerta no miraría nada.')
	process.exit(1)
}
if (faviconOf(`<head><link rel="icon" href="${SITE_BASE}favicon.png" type="image/png"></head>`) !== 'favicon.png' || faviconOf('<head></head>') !== null) {
	console.error('check-dist: la regla del favicon no lee bien su canario: la puerta no miraría nada.')
	process.exit(1)
}
if (sha1(Buffer.from('zz')) === sha1(Buffer.from('zz '))) {
	console.error('check-dist: la comparación de openapi.json no distingue dos contenidos distintos.')
	process.exit(1)
}

const checks = [
	//Un href relativo (sin esquema ni / delante) que acaba en .md, con o sin ancla.
	['enlace a un .md', /href="(?![a-z]+:|\/|#)[^"]*\.md(?:#[^"]*)?"/i],
	//Un aviso de MkDocs que no se convirtió: al principio de un párrafo o de una línea.
	['aviso de MkDocs como texto', /(?:<p>|^|\n)\s*(?:!!!|\?\?\?\+?) [a-z]+/],
	//Un fragmento sin resolver, en claro o escapado.
	['fragmento --8<--', /--8(?:<|&lt;)--/],
]

//Atributos de MkDocs (attr_list) que quedaron en el TEXTO: se mira sin etiquetas, sin código y sin scripts ni estilos.
const attributes = /\{:[^}\n]*\}|\{[ \t]*[.#][A-Za-z][\w-]*(?:[ \t]+[.#][A-Za-z][\w-]*|[ \t]+[\w-]+="[^"]*")*[ \t]*\}/
//El HTML sin bloques ni trozos de código (<pre>, <code>), donde un ejemplo de MkDocs es texto y no un resto.
const withoutCode = (html) => html.replace(/<(pre|code)\b[\s\S]*?<\/\1>/gi, ' ')
const textOf = (html) =>
	html
		.replace(/<(script|style|pre|code)\b[\s\S]*?<\/\1>/gi, ' ')
		.replace(/<[^>]*>/g, ' ')
		.replace(/&quot;/g, '"')
//El canario de las reglas de restos sobre el HTML sin código: el resto en un párrafo se ve; en <code>, no.
if (!checks[0][1].test(withoutCode('<p><a href="otra.md">x</a></p>')) || checks[0][1].test(withoutCode('<p><code>href="otra.md"</code></p>'))
	|| !checks[2][1].test(withoutCode('<p>--8&lt;-- "a"</p>')) || checks[2][1].test(withoutCode('<td><code>--8&lt;-- "a"</code></td>'))) {
	console.error('check-dist: las reglas de restos no distinguen un resto de un ejemplo en <code>: la puerta no miraría nada o daría falsos.')
	process.exit(1)
}
//El canario: si la regla deja de ver los dos casos de la guía, la puerta no mira nada y falla.
for (const sample of ['<p><a href="/x/">Despliegue</a>{ .md-button }</p>', '<h2>Título {: #ancla }</h2>']) {
	if (!attributes.test(textOf(sample))) {
		console.error(`check-dist: la regla de atributos de MkDocs no ve su canario «${sample}»: la puerta no miraría nada.`)
		process.exit(1)
	}
}
if (attributes.test(textOf('<pre><code>{ .md-button }</code></pre><p>const a = { ...b }</p>'))) {
	console.error('check-dist: la regla de atributos de MkDocs caza código o un «{ ...b }»: daría falsos.')
	process.exit(1)
}

const files = []
const walk = (directory) => {
	for (const entry of readdirSync(directory, { withFileTypes: true })) {
		const path = join(directory, entry.name)
		if (entry.isDirectory()) {
			walk(path)
		} else if (entry.name.endsWith('.html')) {
			files.push(path)
		}
	}
}

try {
	walk(dist)
} catch (error) {
	console.error(`check-dist: no se puede leer ${dist}: ${error.message}`)
	process.exit(1)
}

const failures = []
//Cada página de cada fuente tiene su HTML, con tantos avisos como la fuente.
let sourcePages = 0
for (const source of SOURCES) {
	const walkSource = (directory) => {
		for (const entry of readdirSync(directory, { withFileTypes: true })) {
			const path = join(directory, entry.name)
			if (entry.isDirectory()) {
				walkSource(path)
				continue
			}
			if (!entry.name.endsWith('.md') || entry.name.startsWith('_')) {
				continue
			}
			sourcePages++
			const fromSource = relative(join(sourcesRoot, source.dir), path)
			const slug = (source.prefix + fromSource.replace(/\.md$/, '')).replace(/(^|\/)index$/, '').toLowerCase()
			const page = join(dist, slug, 'index.html')
			const where = `source-docs/${source.dir}/${fromSource}`
			if (!existsSync(page)) {
				failures.push(`${where}: no tiene su página en dist/${slug === '' ? '' : slug + '/'}index.html`)
				continue
			}
			const expected = sourceAsides(readFileSync(path, 'utf8'))
			const painted = paintedAsides(readFileSync(page, 'utf8'))
			if (expected !== painted) {
				failures.push(`${where}: ${expected} aviso(s) en la fuente y ${painted} pintado(s) en dist/${slug === '' ? '' : slug + '/'}index.html`)
			}
		}
	}
	walkSource(join(sourcesRoot, source.dir))
}
if (!SITE.openapi) {
	if (existsSync(OPENAPI_DIST)) {
		failures.push('api/openapi.json está en la salida de un sitio sin API.')
	}
} else if (!existsSync(OPENAPI_DIST)) {
	failures.push('dist/api/openapi.json no existe: la descarga de la API no está en el sitio.')
} else if (sha1(readFileSync(OPENAPI_DIST)) !== sha1(readFileSync(OPENAPI_SOURCE))) {
	failures.push(`dist/api/openapi.json (${sha1(readFileSync(OPENAPI_DIST))}) no es source-docs/api/openapi.json (${sha1(readFileSync(OPENAPI_SOURCE))}).`)
}
//El favicon: cada página nombra uno, y está en la salida.
const favicons = new Set(files.map((file) => faviconOf(readFileSync(file, 'utf8'))))
for (const favicon of favicons) {
	if (favicon === null) {
		failures.push('hay páginas sin <link rel="icon">: el navegador pediría /favicon.ico.')
	} else if (/^[a-z]+:/i.test(favicon) || favicon.startsWith('/') || !existsSync(join(dist, favicon))) {
		failures.push(`el favicon que nombran las páginas, «${favicon}», no está en dist/.`)
	}
}
for (const file of files) {
	const html = readFileSync(file, 'utf8')
	//Sin el código: una página que EXPLICA la sintaxis de MkDocs la enseña en <code>, y eso no es un resto sin convertir.
	const outsideCode = withoutCode(html)
	for (const [name, pattern] of checks) {
		const match = pattern.exec(outsideCode)
		if (match) {
			const start = Math.max(0, match.index - 40)
			failures.push(`${relative(dist, file)}: ${name}: …${outsideCode.slice(start, match.index + match[0].length + 40).replace(/\s+/g, ' ')}…`)
		}
	}
	const text = textOf(html)
	const attribute = attributes.exec(text)
	if (attribute) {
		const start = Math.max(0, attribute.index - 40)
		failures.push(`${relative(dist, file)}: atributos de MkDocs: …${text.slice(start, attribute.index + attribute[0].length + 40).replace(/\s+/g, ' ')}…`)
	}
}

if (files.length === 0) {
	console.error('check-dist: dist/ no tiene ningún .html: la puerta no miró nada.')
	process.exit(1)
}
if (failures.length > 0) {
	console.error(`check-dist: ${failures.length} fallo(s) en ${files.length} página(s):`)
	for (const failure of failures) {
		console.error(`  ${failure}`)
	}
	process.exit(1)
}
console.log(`check-dist (${SITE_NAME}, ${relative(process.cwd(), dist) || '.'}/): ${files.length} página(s), ningún resto de MkDocs (enlaces a .md, avisos, fragmentos ni atributos); las ${sourcePages} de sus fuentes con su HTML y sus avisos; ${SITE.openapi ? 'openapi.json igual a su fuente' : 'sin openapi.json, como debe'}; el favicon (${[...favicons].join(', ')}) en la salida.`)
