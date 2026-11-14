//La puerta del sitio (ADR 0076): en lo compilado (dist/**/*.html) no puede quedar nada de MkDocs sin convertir. Falla con
//código 1, el archivo y una muestra si encuentra un enlace relativo a un .md, un aviso «!!!» o «???» como texto, o un
//fragmento «--8<--». Sin dependencias: solo Node.
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
console.log(`check-dist: ${files.length} página(s), ningún resto de MkDocs (enlaces a .md, avisos ni fragmentos).`)
