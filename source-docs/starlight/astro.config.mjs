//@ts-check
import { existsSync, readFileSync } from 'node:fs'
import { dirname, relative, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig } from 'astro/config'
import starlight from '@astrojs/starlight'

const here = dirname(fileURLToPath(import.meta.url))
const MKDOCS_YML = resolve(here, '../project/mkdocs.yml')
const DOCS_ROOT = resolve(here, '../project/docs')
//La API: su nav lo escribe bin/api-docs en su mkdocs.yml, y sus páginas van en /api/ (src/content.config.ts).
const API_MKDOCS_YML = resolve(here, '../api/mkdocs.yml')
const API_DOCS_ROOT = resolve(here, '../api/docs')

/**
 * El menú sale del `nav:` de un mkdocs.yml, la única fuente: mismos títulos, secciones y orden. Solo se lee ese bloque (el
 * resto lleva etiquetas !!python/), sin dependencias. Una ruta que no existe rompe la compilación con su línea.
 *
 * @param {string} mkdocsYml El mkdocs.yml
 * @param {string} docsRoot La carpeta de sus páginas
 * @param {string} prefix Lo que va delante de sus rutas ('' la guía, 'api/' la API)
 * @returns {any[]}
 */
function sidebarFromMkdocsNav(mkdocsYml, docsRoot, prefix) {
	const lines = readFileSync(mkdocsYml, 'utf8').replace(/\r\n/g, '\n').split('\n')
	const start = lines.findIndex((line) => /^nav:\s*$/.test(line))
	if (start === -1) {
		throw new Error(`${mkdocsYml}: no tiene un bloque «nav:».`)
	}
	const yml = relative(resolve(here, '..'), mkdocsYml)
	/** @type {{ indent: number, items: any[], group?: any }[]} */
	const stack = [{ indent: -1, items: [] }]
	for (let i = start + 1; i < lines.length; i++) {
		const line = lines[i]
		if (line.trim() === '' || /^\s*#/.test(line)) {
			continue
		}
		//La primera clave sin sangría cierra el bloque.
		if (!/^\s/.test(line)) {
			break
		}
		const where = `${yml}:${i + 1} «${line.trim()}»`
		const item = /^(\s*)-\s+(.*?)\s*$/.exec(line)
		if (!item) {
			throw new Error(`${where}: el nav solo admite elementos de lista («- Título: ruta.md», «- Título:» o «- ruta.md»).`)
		}
		const indent = item[1].length
		while (stack.length > 1 && indent <= stack[stack.length - 1].indent) {
			stack.pop()
		}
		const parent = stack[stack.length - 1]
		const pair = /^(.*?):(?:\s+(.*))?$/.exec(item[2])
		const title = pair ? pair[1].trim().replace(/^(['"])(.*)\1$/, '$2') : null
		const target = pair ? (pair[2] ?? '').trim().replace(/^(['"])(.*)\1$/, '$2') : item[2]
		if (title !== null && target === '') {
			const group = { label: title, collapsed: true, items: [] }
			parent.items.push(group)
			stack.push({ indent, items: group.items, group })
			continue
		}
		if (/^https?:\/\//.test(target)) {
			if (title === null) {
				throw new Error(`${where}: un enlace externo necesita su título.`)
			}
			parent.items.push({ label: title, link: target })
			continue
		}
		if (!target.endsWith('.md') || !existsSync(resolve(docsRoot, target))) {
			throw new Error(`${where}: «${target}» no es ninguna página de source-docs/${relative(resolve(here, '..'), docsRoot)}.`)
		}
		//El mismo slug que da el cargador (src/content.config.ts): sin .md, sin «index» final y en minúsculas.
		const slug = (prefix + target.replace(/\.md$/, '')).replace(/(^|\/)index$/, '').toLowerCase() || 'index'
		parent.items.push(title === null ? { slug } : { label: title, slug })
	}
	return stack[0].items
}

//El sitio público de la documentación (ADR 0076). La guía y la API se leen EN SU SITIO, source-docs/project/docs/ y
//source-docs/api/docs/ (src/content.config.ts).
export default defineConfig({
	//Donde se publica: la carpeta docs/ del sitio (bin/docs desplegar). Los enlaces y los estáticos salen con /docs/ delante.
	site: 'https://piecesphp.bitbucket.io',
	base: '/docs',
	//La salida, dentro de esta carpeta e ignorada por git.
	outDir: './dist',
	integrations: [
		starlight({
			title: 'PiecesPHP',
			//El del panel (src/statics/images/favicon.png), copiado en public/: sin él, Starlight pide /favicon.svg y da 404.
			favicon: '/favicon.png',
			defaultLocale: 'root',
			locales: {
				root: { label: 'Español', lang: 'es' },
			},
			//El acento del panel y el aspecto de los botones que vienen de MkDocs.
			customCss: ['./src/styles/site.css'],
			//Los grupos, plegados; Starlight abre siempre el que contiene la página actual.
			//La API, al final y en su grupo, desde el nav que escribe bin/api-docs.
			sidebar: [...sidebarFromMkdocsNav(MKDOCS_YML, DOCS_ROOT, ''), { label: 'API', collapsed: true, items: sidebarFromMkdocsNav(API_MKDOCS_YML, API_DOCS_ROOT, 'api/') }],
			//Los avisos y las anclas de Starlight solo se aplican en su carpeta de contenido: la guía vive fuera.
			markdown: { processedDirs: ['../project/docs', '../api/docs'] },
			//El 404 lo da src/pages/404.astro: la guía vive fuera de src/content/docs y no tiene una entrada «404».
			disable404Route: true,
		}),
	],
})
