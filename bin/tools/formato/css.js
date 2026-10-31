'use strict'
//SCSS y CSS con el formateador del editor del PO: la extensión michelemelluso.code-beautifier 2.3.3, que es el CSS de
//js-beautify 1.10.1 (copiado en bin/tools/js-beautify/, MIT). Formatea en su sitio cada archivo que recibe.
//
//  node bin/tools/formato/css.js <archivo.scss>…

const fs = require('fs')
const path = require('path')

const cssBeautify = require(path.join(__dirname, '..', 'js-beautify', 'beautify-css.js')).css_beautify

//La extensión llama css_beautify(texto, {indent_size: tabSize del editor, ...beautify.options}). El tabSize lo pone la
//extensión EditorConfig desde [*] de .editorconfig (4); lo demás es el valor por omisión de js-beautify 1.10.1, escrito
//explícito. Los mismos van en beautify.options de .vscode/settings.json.
const OPCIONES = {
	indent_size: 4,
	indent_char: ' ',
	indent_with_tabs: false,
	indent_level: 0,
	indent_empty_lines: false,
	eol: 'auto',
	end_with_newline: false,
	preserve_newlines: true,
	max_preserve_newlines: 32786,
	wrap_line_length: 0,
	selector_separator_newline: true,
	newline_between_rules: true,
	space_around_combinator: false,
	//«.a > .b» se queda con sus espacios (B6): sin esto, js-beautify los quita alrededor de >, + y ~.
	space_around_selector_separator: true,
}

//js-beautify pega el «as», el «with», el «show» o el «hide» a la cadena del módulo: «@use 'x'as *;» (B5). Se devuelve el
//espacio tras la primera cadena de cada @use y @forward, recorriendo la línea carácter a carácter. El editor no lo hace:
//la tarea «Formatear este archivo» de .vscode/tasks.json corre bin/formatear.
function espacioTrasElModulo(texto) {
	return texto.split('\n').map((linea) => {
		const sangria = linea.length - linea.trimStart().length
		const resto = linea.slice(sangria)
		if (!resto.startsWith('@use') && !resto.startsWith('@forward')) {
			return linea
		}
		const inicio = sangria + resto.search(/['"]/)
		if (inicio < sangria) {
			return linea
		}
		const comilla = linea[inicio]
		let fin = inicio + 1
		while (fin < linea.length && linea[fin] !== comilla) {
			fin += linea[fin] === '\\' ? 2 : 1
		}
		const siguiente = linea[fin + 1]
		return siguiente !== undefined && /[A-Za-z(]/.test(siguiente) ? linea.slice(0, fin + 1) + ' ' + linea.slice(fin + 1) : linea
	}).join('\n')
}

module.exports = { OPCIONES, espacioTrasElModulo }

//Requerido (la suite compara las opciones con .vscode/settings.json), no formatea nada.
if (require.main === module) {
	let errores = 0
	for (const archivo of process.argv.slice(2)) {
		try {
			fs.writeFileSync(archivo, espacioTrasElModulo(cssBeautify(fs.readFileSync(archivo, 'utf8'), OPCIONES)))
		} catch (error) {
			console.error(`formato/css.js: ${archivo}: ${error.message}`)
			errores++
		}
	}
	process.exit(errores === 0 ? 0 : 1)
}
