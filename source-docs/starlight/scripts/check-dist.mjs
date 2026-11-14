//La puerta del sitio (ADR 0076): en lo compilado (dist/**/*.html) no puede quedar nada de MkDocs sin convertir. Falla con
//código 1, el archivo y una muestra si encuentra un enlace relativo a un .md, un aviso «!!!» o «???» como texto, un
//fragmento «--8<--» o atributos de MkDocs («{ .clase }», «{: … }») en el texto. Sin dependencias: solo Node.
import { readdirSync, readFileSync } from 'node:fs'
import { join, relative } from 'node:path'
import { fileURLToPath } from 'node:url'

const dist = fileURLToPath(new URL('../dist/', import.meta.url))

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
const textOf = (html) =>
	html
		.replace(/<(script|style|pre|code)\b[\s\S]*?<\/\1>/gi, ' ')
		.replace(/<[^>]*>/g, ' ')
		.replace(/&quot;/g, '"')
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
for (const file of files) {
	const html = readFileSync(file, 'utf8')
	for (const [name, pattern] of checks) {
		const match = pattern.exec(html)
		if (match) {
			const start = Math.max(0, match.index - 40)
			failures.push(`${relative(dist, file)}: ${name}: …${html.slice(start, match.index + match[0].length + 40).replace(/\s+/g, ' ')}…`)
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
	console.error(`check-dist: ${failures.length} resto(s) de MkDocs en ${files.length} página(s):`)
	for (const failure of failures) {
		console.error(`  ${failure}`)
	}
	process.exit(1)
}
console.log(`check-dist: ${files.length} página(s), ningún resto de MkDocs (enlaces a .md, avisos, fragmentos ni atributos).`)
