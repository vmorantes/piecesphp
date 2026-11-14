//@ts-check
import { defineConfig } from 'astro/config'
import starlight from '@astrojs/starlight'

//El sitio público de la documentación (ADR 0076). La guía se lee EN SU SITIO, source-docs/project/docs/ (src/content.config.ts).
export default defineConfig({
	//Donde se publica: la carpeta docs/ del sitio (bin/docs desplegar). Los enlaces y los estáticos salen con /docs/ delante.
	site: 'https://piecesphp.bitbucket.io',
	base: '/docs',
	//La salida, dentro de esta carpeta e ignorada por git.
	outDir: './dist',
	integrations: [
		starlight({
			title: 'PiecesPHP',
			defaultLocale: 'root',
			locales: {
				root: { label: 'Español', lang: 'es' },
			},
			//Los avisos y las anclas de Starlight solo se aplican en su carpeta de contenido: la guía vive fuera.
			markdown: { processedDirs: ['../project/docs'] },
			//El 404 lo da src/pages/404.astro: la guía vive fuera de src/content/docs y no tiene una entrada «404».
			disable404Route: true,
		}),
	],
})
