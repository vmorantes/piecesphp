import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'

//La descarga de la API, en /api/openapi.json como en MkDocs, byte a byte la fuente: source-docs/api/openapi.json.
const SOURCE = fileURLToPath(new URL('../../../../api/openapi.json', import.meta.url))

export const prerender = true

export function GET(): Response {
	return new Response(readFileSync(SOURCE), { headers: { 'Content-Type': 'application/json' } })
}
