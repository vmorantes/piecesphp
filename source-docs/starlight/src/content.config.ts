import { existsSync, readFileSync } from 'node:fs'
import { readFile } from 'node:fs/promises'
import { dirname, relative, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineCollection } from 'astro:content'
import { glob } from 'astro/loaders'
import { docsSchema, i18nSchema } from '@astrojs/starlight/schema'
import { i18nLoader } from '@astrojs/starlight/loaders'

//La guía de MkDocs se lee EN SU SITIO y se convierte AL COMPILAR, sin tocar los .md (ADR 0076): MkDocs la sigue compilando.
const DOCS_BASE = '../project/docs'
//La base de los fragmentos `--8<--`: la raíz del repositorio (source-docs/project/mkdocs.yml, pymdownx.snippets, base_path).
const SNIPPETS_BASE = '../..'

//El primer «# Título» de un .md: las páginas de MkDocs no llevan frontmatter y Starlight pide `title` y lo pinta él.
const firstHeading = /^#[ \t]+(.+?)[ \t]*#*[ \t]*$/m

//De los avisos de MkDocs a los de Starlight (note, tip, caution, danger): los cinco tipos que usa la guía.
const ASIDE_TYPES: Record<string, string> = { note: 'note', info: 'note', tip: 'tip', warning: 'caution', danger: 'danger' }

const siteRoot = dirname(fileURLToPath(import.meta.url)) + '/..'
const docsRoot = resolve(siteRoot, DOCS_BASE)
const snippetsRoot = resolve(siteRoot, SNIPPETS_BASE)

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
	return text
		.split('\n')
		.map((line, index) =>
			line.replace(/\]\((?!https?:|mailto:|#|\/)([^)#\s]+\.md)(#[^)\s]*)?\)/g, (_match, target: string, anchor: string | undefined) => {
				const absolute = resolve(pageDir, target)
				const fromDocs = relative(docsRoot, absolute)
				if (!existsSync(absolute) || fromDocs.startsWith('..')) {
					throw new Error(`${relative(siteRoot, filePath)}:${index + 1}: el enlace a «${target}» no lleva a ninguna página de la guía.`)
				}
				const slug = fromDocs.replace(/\.md$/, '').replace(/(^|\/)index$/, '').toLowerCase()
				return `](${base}${slug === '' ? '' : slug + '/'}${anchor ?? ''})`
			}),
		)
		.join('\n')
}

/**
 * La guía de MkDocs leída en su sitio, sin mover ni tocar los .md: el título sale del primer «#» al cargar; y antes de
 * pintar cada página, su texto se convierte (fragmentos, avisos, enlaces) y pierde ese «#» para que no salga dos veces.
 */
function mkdocsLoader() {
	const loader = glob({ base: DOCS_BASE, pattern: '**/[^_]*.md' })
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
