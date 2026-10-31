'use strict'
//JS con el formateador de VS Code (typescript-language-features: la petición «format» de tsserver), pero con el
//TypeScript del PROYECTO, fijado en package.json (P236). Formatea en su sitio cada archivo que recibe.
//
//  node bin/tools/formato/js.js <archivo.js>…

const fs = require('fs')
const path = require('path')

const RAIZ = path.resolve(__dirname, '..', '..', '..')
const ts = require(path.join(RAIZ, 'node_modules', 'typescript'))
const fijada = require(path.join(RAIZ, 'package.json')).devDependencies.typescript

if (ts.version !== fijada) {
	console.error(`formato/js.js: node_modules/typescript es ${ts.version} y package.json fija ${fijada}: corre npm install.`)
	process.exit(2)
}

//Lo mismo que tsserver arma para VS Code: sus valores por omisión, encima los de getFormatOptions() de
//typescript-language-features (VS Code 1.143.1), encima los del proyecto. Los mismos van, explícitos, en .vscode/settings.json.
const AJUSTES = Object.assign(ts.getDefaultFormatCodeSettings('\n'), {
	//Del editor: la extensión EditorConfig aplica [*.js] de .editorconfig (indent_style = tab, indent_size = 4).
	tabSize: 4,
	indentSize: 4,
	convertTabsToSpaces: false,
	newLineCharacter: '\n',
	//js/ts.format.*: el valor por omisión de typescript-language-features.
	insertSpaceAfterCommaDelimiter: true,
	insertSpaceAfterConstructor: false,
	insertSpaceAfterSemicolonInForStatements: true,
	insertSpaceBeforeAndAfterBinaryOperators: true,
	insertSpaceAfterKeywordsInControlFlowStatements: true,
	insertSpaceAfterFunctionKeywordForAnonymousFunctions: true,
	insertSpaceBeforeFunctionParenthesis: false,
	insertSpaceAfterOpeningAndBeforeClosingNonemptyParenthesis: false,
	insertSpaceAfterOpeningAndBeforeClosingNonemptyBrackets: false,
	insertSpaceAfterOpeningAndBeforeClosingNonemptyBraces: true,
	insertSpaceAfterOpeningAndBeforeClosingEmptyBraces: true,
	insertSpaceAfterOpeningAndBeforeClosingTemplateStringBraces: false,
	insertSpaceAfterOpeningAndBeforeClosingJsxExpressionBraces: false,
	insertSpaceAfterTypeAssertion: false,
	placeOpenBraceOnNewLineForFunctions: false,
	placeOpenBraceOnNewLineForControlBlocks: false,
	indentSwitchCase: true,
	//Del proyecto: .vscode/settings.json, [javascript].
	semicolons: 'remove',
})

function formatear(texto, nombre) {
	const host = {
		getScriptFileNames: () => [nombre],
		getScriptVersion: () => '1',
		getScriptSnapshot: (archivo) => archivo === nombre ? ts.ScriptSnapshot.fromString(texto) : undefined,
		getCurrentDirectory: () => RAIZ,
		getCompilationSettings: () => ({ allowJs: true, noResolve: true, noLib: true }),
		getDefaultLibFileName: (opciones) => ts.getDefaultLibFilePath(opciones),
		fileExists: (archivo) => archivo === nombre,
		readFile: (archivo) => archivo === nombre ? texto : undefined,
	}
	const servicio = ts.createLanguageService(host, ts.createDocumentRegistry())
	const cambios = servicio.getFormattingEditsForDocument(nombre, AJUSTES)
	//Del final al principio: así las posiciones de los que quedan siguen valiendo.
	cambios.sort((a, b) => b.span.start - a.span.start)
	for (const cambio of cambios) {
		texto = texto.slice(0, cambio.span.start) + cambio.newText + texto.slice(cambio.span.start + cambio.span.length)
	}
	return texto
}

module.exports = { AJUSTES }

//Requerido (la suite compara las opciones con .vscode/settings.json), no formatea nada.
if (require.main === module) {
	let errores = 0
	for (const archivo of process.argv.slice(2)) {
		try {
			fs.writeFileSync(archivo, formatear(fs.readFileSync(archivo, 'utf8'), path.resolve(archivo).replace(/\.[^.]*$/, '') + '.js'))
		} catch (error) {
			console.error(`formato/js.js: ${archivo}: ${error.message}`)
			errores++
		}
	}
	process.exit(errores === 0 ? 0 : 1)
}
