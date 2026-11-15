import { existsSync, readFileSync } from 'node:fs'
import { readFile } from 'node:fs/promises'
import { dirname, relative, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineCollection } from 'astro:content'
import { glob } from 'astro/loaders'
import { docsSchema, i18nSchema } from '@astrojs/starlight/schema'
import { i18nLoader } from '@astrojs/starlight/loaders'

//El Markdown se lee EN SU SITIO y se convierte AL COMPILAR, sin tocar los .md (ADR 0076). Un solo glob para todas las
//fuentes de un sitio: dos sobre la misma colección se borrarían las entradas uno al otro.
const SOURCES_BASE = '..'
//Dos sitios en este proyecto, elegidos al compilar con DOCS_SITE (astro.config.mjs): «guia», la guía y la API (la API va en
///api/), y «entornos», las guías de entorno, que no se publican. Cada fuente, su carpeta (relativa a source-docs/) y el
//prefijo de sus rutas.
const SITES: Record<string, { dir: string; prefix: string }[]> = {
	guia: [
		{ dir: 'project/docs', prefix: '' },
		{ dir: 'api/docs', prefix: 'api/' },
	],
	entornos: [{ dir: 'entornos/docs', prefix: '' }],
}
const SITE = process.env.DOCS_SITE || 'guia'
if (SITES[SITE] === undefined) {
	throw new Error(`DOCS_SITE=«${SITE}» no es ningún sitio (guia o entornos).`)
}
const SOURCES = SITES[SITE]
//La base de los fragmentos `--8<--`: la raíz del repositorio. Los fragmentos se escriben con rutas desde ella
//(«--8<-- "src/app/…"»); la heredaron de MkDocs (pymdownx.snippets, base_path) y es la única.
const SNIPPETS_BASE = '../..'

//El primer «# Título» de un .md: las páginas de MkDocs no llevan frontmatter y Starlight pide `title` y lo pinta él.
const firstHeading = /^#[ \t]+(.+?)[ \t]*#*[ \t]*$/m

//De los avisos de MkDocs a los de Starlight (note, tip, caution, danger): los cinco tipos que usa la guía.
const ASIDE_TYPES: Record<string, string> = { note: 'note', info: 'note', tip: 'tip', warning: 'caution', danger: 'danger' }

const siteRoot = dirname(fileURLToPath(import.meta.url)) + '/..'
const sourcesRoot = resolve(siteRoot, SOURCES_BASE)
const snippetsRoot = resolve(siteRoot, SNIPPETS_BASE)

/**
 * La ruta de una página: la de su fuente, sin .md, sin «index» final y en minúsculas (lo mismo que espera el menú de
 * astro.config.mjs). null si el archivo no es de ninguna fuente.
 */
function slugOf(absolute: string): string | null {
	for (const source of SOURCES) {
		const fromSource = relative(resolve(sourcesRoot, source.dir), absolute)
		if (!fromSource.startsWith('..')) {
			const slug = (source.prefix + fromSource.replace(/\.md$/, '')).replace(/(^|\/)index$/, '').toLowerCase()
			return slug === '' ? 'index' : slug
		}
	}
	return null
}

/**
 * `--8<-- "archivo"` o `--8<-- "archivo:sección"`: el archivo entero, o lo que hay entre sus marcas
 * `--8<-- [start:sección]` y `--8<-- [end:sección]`. Uno que falte rompe la compilación, como en MkDocs (check_paths).
 */
function resolveSnippets(text: string, filePath: string): string {
	return text.replace(/^([ \t]*)--8<--[ \t]+"([^"]+)"[ \t]*$/gm, (_match, indent: string, target: string) => {
		const [file, section] = target.split(':')
		const path = resolve(snippetsRoot, file)
		if (!existsSync(path)) {
			throw new Error(`${filePath}: el fragmento «--8<-- "${target}"» apunta a ${file}, que no existe (base: la raíz del repositorio).`)
		}
		let lines = readFileSync(path, 'utf8').replace(/\r\n/g, '\n').split('\n')
		if (section !== undefined) {
			const start = lines.findIndex((line) => line.includes(`--8<-- [start:${section}]`))
			const end = lines.findIndex((line) => line.includes(`--8<-- [end:${section}]`))
			if (start === -1 || end === -1 || end < start) {
				throw new Error(`${filePath}: el fragmento «--8<-- "${target}"» pide la sección «${section}», que ${file} no marca.`)
			}
			lines = lines.slice(start + 1, end)
		}
		return lines.map((line) => (line === '' ? '' : indent + line)).join('\n')
	})
}

/**
 * `!!! tipo "Título"` (y `??? tipo`, plegable) con su contenido sangrado cuatro espacios. Los de `!!!`, avisos de Starlight
 * (`:::tipo[Título]`); los de `???`, un <details>, porque los avisos de Starlight no se pliegan.
 */
function convertAdmonitions(text: string, filePath: string): string {
	const lines = text.split('\n')
	const out: string[] = []
	let fence: string | null = null
	for (let i = 0; i < lines.length; i++) {
		const line = lines[i]
		const fenceMatch = /^[ \t]*(```+|~~~+)/.exec(line)
		if (fenceMatch) {
			fence = fence === null ? fenceMatch[1] : line.trim().startsWith(fence) ? null : fence
		}
		const header = fence === null ? /^(!!!|\?\?\?\+?)[ \t]+([a-z]+)(?:[ \t]+"([^"]*)")?[ \t]*$/.exec(line) : null
		if (!header) {
			out.push(line)
			continue
		}
		const [, marker, type, title] = header
		const body: string[] = []
		while (i + 1 < lines.length && (lines[i + 1].startsWith('    ') || lines[i + 1].trim() === '')) {
			body.push(lines[i + 1].startsWith('    ') ? lines[i + 1].slice(4) : '')
			i++
		}
		while (body.length > 0 && body[body.length - 1] === '') {
			body.pop()
		}
		if (marker === '!!!') {
			const aside = ASIDE_TYPES[type]
			if (aside === undefined) {
				throw new Error(`${filePath}: el aviso «!!! ${type}» no tiene equivalente en Starlight (ASIDE_TYPES).`)
			}
			out.push(`:::${aside}${title !== undefined && title !== '' ? `[${title}]` : ''}`, ...body, ':::', '')
		} else {
			out.push(`<details${marker === '???+' ? ' open' : ''}>`, `<summary>${title ?? type}</summary>`, '', ...body, '', '</details>', '')
		}
	}
	return out.join('\n')
}

/**
 * Los enlaces relativos a otra página .md, con o sin «#ancla», a su ruta en Starlight. Uno a un .md que no existe rompe
 * la compilación, con su archivo y su línea.
 */
function convertLinks(text: string, filePath: string, base: string): string {
	const pageDir = dirname(filePath)
	return outsideCode(text, (part, index) =>
		part.replace(/\]\((?!https?:|mailto:|#|\/)([^)#\s]+\.md)(#[^)\s]*)?\)/g, (_match, target: string, anchor: string | undefined) => {
			const absolute = resolve(pageDir, target)
			const slug = existsSync(absolute) ? slugOf(absolute) : null
			if (slug === null) {
				throw new Error(`${relative(siteRoot, filePath)}:${index + 1}: el enlace a «${target}» no lleva a ninguna página de la documentación.`)
			}
			return `](${base}${slug === 'index' ? '' : slug + '/'}${anchor ?? ''})`
		}),
	)
}

/**
 * Aplica una conversión solo FUERA del código: ni en los bloques ``` o ~~~ ni en el código en línea (`…`), donde un
 * «](ruta.md)» es un ejemplo y no un enlace. La función recibe cada trozo de texto y el número de su línea (desde 0).
 */
function outsideCode(text: string, convert: (part: string, lineIndex: number) => string): string {
	let fence: string | null = null
	return text
		.split('\n')
		.map((line, index) => {
			const fenceMatch = /^[ \t]*(```+|~~~+)/.exec(line)
			if (fenceMatch) {
				fence = fence === null ? fenceMatch[1] : line.trim().startsWith(fence) ? null : fence
				return line
			}
			if (fence !== null) {
				return line
			}
			//Los trozos impares del split son código en línea (con sus comillas): se dejan como están.
			return line
				.split(/(`+[^`]*?`+)/)
				.map((part, i) => (i % 2 === 1 ? part : convert(part, index)))
				.join('')
		})
		.join('\n')
}

/**
 * `[texto](url){ .md-button }` (y `{ .md-button .md-button--primary }`) de MkDocs al botón de enlace de Starlight:
 * `sl-link-button not-content`, secundario o primario. Su aspecto lo da src/styles/site.css: el CSS del componente es local.
 */
function convertButtons(text: string): string {
	return outsideCode(text, (part) => part.replace(/\[([^\]\n]+)\]\(([^)\s]+)\)\{[ \t]*\.md-button([ \t]+\.md-button--primary)?[ \t]*\}/g, (_match, label: string, href: string, primary: string | undefined) => {
		const escape = (value: string) => value.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;')
		return `<a class="sl-link-button not-content ${primary ? 'primary' : 'secondary'}" href="${escape(href)}">${escape(label)}</a>`
	}))
}

/**
 * La guía y la API de MkDocs leídas en su sitio, sin mover ni tocar los .md: el título sale del primer «#» al cargar; y antes de
 * pintar cada página, su texto se convierte (fragmentos, avisos, enlaces, botones) y pierde ese «#» para que no salga
 * dos veces.
 */
function mkdocsLoader() {
	const loader = glob({
		base: SOURCES_BASE,
		pattern: SOURCES.map((source) => `${source.dir}/**/[^_]*.md`),
		generateId: ({ entry }) => {
			const slug = slugOf(resolve(sourcesRoot, entry))
			if (slug === null) {
				throw new Error(`${entry}: no es de ninguna fuente de la documentación.`)
			}
			return slug
		},
	})
	return {
		name: 'piecesphp-mkdocs-loader',
		load: async (context) => {
			const parseData = async (props) => {
				if (typeof props.data.title !== 'string' || props.data.title === '') {
					const source = props.filePath ? await readFile(props.filePath, 'utf8') : ''
					const title = firstHeading.exec(source)?.[1] ?? props.id
					props = { ...props, data: { ...props.data, title } }
				}
				return context.parseData(props)
			}
			//El glob pinta cada .md con la función de su tipo de entrada: se envuelve para convertir el TEXTO antes.
			const entryTypes = new Map(context.entryTypes)
			const markdown = entryTypes.get('.md')
			if (markdown?.getRenderFunction) {
				entryTypes.set('.md', {
					...markdown,
					getRenderFunction: async (config) => {
						const render = await markdown.getRenderFunction(config)
						const base = (config.base ?? '/').replace(/\/?$/, '/')
						return (props) => {
							const filePath = resolve(fileURLToPath(config.root), props.filePath)
							let body = props.body.replace(firstHeading, '')
							body = resolveSnippets(body, filePath)
							body = convertAdmonitions(body, filePath)
							body = convertLinks(body, filePath, base)
							body = convertButtons(body)
							return render({ ...props, body })
						}
					},
				})
			}
			return loader.load({ ...context, parseData, entryTypes })
		},
	}
}

export const collections = {
	docs: defineCollection({ loader: mkdocsLoader(), schema: docsSchema() }),
	i18n: defineCollection({ loader: i18nLoader(), schema: i18nSchema() }),
}
