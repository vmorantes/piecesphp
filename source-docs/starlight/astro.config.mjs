//@ts-check
import { existsSync, readFileSync } from 'node:fs'
import { dirname, relative, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig } from 'astro/config'
import starlight from '@astrojs/starlight'

const here = dirname(fileURLToPath(import.meta.url))
const NAV_YML = resolve(here, '../project/nav.yml')
const DOCS_ROOT = resolve(here, '../project/docs')
//La API: su nav.yml lo escribe bin/api-docs, y sus páginas van en /api/ (src/content.config.ts).
const API_NAV_YML = resolve(here, '../api/nav.yml')
const API_DOCS_ROOT = resolve(here, '../api/docs')
//Las guías de entorno: el segundo sitio, que no se publica.
const ENTORNOS_NAV_YML = resolve(here, '../entornos/nav.yml')
const ENTORNOS_DOCS_ROOT = resolve(here, '../entornos/docs')

/**
 * El menú sale del `nav:` de un nav.yml (el bloque que antes vivía en mkdocs.yml), la única fuente: mismos títulos,
 * secciones y orden, sin dependencias. Una ruta que no existe rompe la compilación con la línea del nav.yml.
 *
 * @param {string} navYml El nav.yml
 * @param {string} docsRoot La carpeta de sus páginas
 * @param {string} prefix Lo que va delante de sus rutas ('' la guía, 'api/' la API)
 * @returns {any[]}
 */
function sidebarFromNav(navYml, docsRoot, prefix) {
	const lines = readFileSync(navYml, 'utf8').replace(/\r\n/g, '\n').split('\n')
	const start = lines.findIndex((line) => /^nav:\s*$/.test(line))
	if (start === -1) {
		throw new Error(`${navYml}: no tiene un bloque «nav:».`)
	}
	const yml = relative(resolve(here, '..'), navYml)
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

/**
 * Dos sitios en este proyecto, elegidos al compilar con DOCS_SITE (bin/docs): «guia», la guía y la API, que se publica; y
 * «entornos», las guías de entorno, que no. Cada uno, su título, su menú, su base y su salida por omisión; DOCS_BASE y
 * DOCS_OUT_DIR las cambian (bin/docs compila la guía también como copia local, con la base de Apache).
 */
const SITES = {
	guia: {
		title: 'PiecesPHP',
		base: '/docs',
		outDir: './dist',
		//La API, al final y en su grupo, desde el nav.yml que escribe bin/api-docs.
		sidebar: () => [...sidebarFromNav(NAV_YML, DOCS_ROOT, ''), { label: 'API', collapsed: true, items: sidebarFromNav(API_NAV_YML, API_DOCS_ROOT, 'api/') }],
		processedDirs: ['../project/docs', '../api/docs'],
	},
	entornos: {
		title: 'Entornos y herramientas',
		base: '/docs-entornos',
		outDir: '../../docs-entornos',
		sidebar: () => sidebarFromNav(ENTORNOS_NAV_YML, ENTORNOS_DOCS_ROOT, ''),
		processedDirs: ['../entornos/docs'],
	},
}
const SITE_NAME = process.env.DOCS_SITE || 'guia'
/** @type {(typeof SITES)['guia']} */
const SITE = SITES[/** @type {'guia' | 'entornos'} */ (SITE_NAME)]
if (SITE === undefined) {
	throw new Error(`DOCS_SITE=«${SITE_NAME}» no es ningún sitio (guia o entornos).`)
}

//La documentación (ADR 0076). La guía y la API se leen EN SU SITIO, source-docs/project/docs/ y source-docs/api/docs/; las
//guías de entorno, en source-docs/entornos/docs/ (src/content.config.ts).
export default defineConfig({
	//Donde se publica la guía: la carpeta docs/ del sitio (bin/docs desplegar). Los enlaces y los estáticos salen con la base delante.
	site: 'https://piecesphp.bitbucket.io',
	base: process.env.DOCS_BASE || SITE.base,
	//La salida: dist/ de esta carpeta o la de DOCS_OUT_DIR, siempre una carpeta que git ignora.
	outDir: process.env.DOCS_OUT_DIR || SITE.outDir,
	//La caché de compilación: bin/docs preparar la lleva a su temporal, para no escribir en los node_modules que toma prestados.
	...(process.env.DOCS_CACHE_DIR ? { cacheDir: process.env.DOCS_CACHE_DIR } : {}),
	integrations: [
		starlight({
			title: SITE.title,
			//El del panel (src/statics/images/favicon.png), copiado en public/: sin él, Starlight pide /favicon.svg y da 404.
			favicon: '/favicon.png',
			defaultLocale: 'root',
			locales: {
				root: { label: 'Español', lang: 'es' },
			},
			//El acento del panel y el aspecto de los botones que vienen de MkDocs.
			customCss: ['./src/styles/site.css'],
			//Los grupos, plegados; Starlight abre siempre el que contiene la página actual.
			sidebar: SITE.sidebar(),
			//Los avisos y las anclas de Starlight solo se aplican en su carpeta de contenido: el Markdown vive fuera.
			markdown: { processedDirs: SITE.processedDirs },
			//El 404 lo da src/pages/404.astro: el contenido vive fuera de src/content/docs y no tiene una entrada «404».
			disable404Route: true,
		}),
	],
})
