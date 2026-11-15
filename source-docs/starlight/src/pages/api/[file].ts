import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'

//La descarga de la API, en /api/openapi.json como en MkDocs, byte a byte la fuente: source-docs/api/openapi.json. Solo en el
//sitio «guia» (DOCS_SITE, astro.config.mjs): las guías de entorno no tienen API. Por eso es una ruta con getStaticPaths.
const SOURCE = fileURLToPath(new URL('../../../../api/openapi.json', import.meta.url))

export const prerender = true

export function getStaticPaths(): { params: { file: string } }[] {
	return (process.env.DOCS_SITE || 'guia') === 'guia' ? [{ params: { file: 'openapi.json' } }] : []
}

export function GET(): Response {
	return new Response(readFileSync(SOURCE), { headers: { 'Content-Type': 'application/json' } })
}
