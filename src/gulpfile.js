/*
* Dependencias
*/
const { src, watch, task, series, parallel } = require('gulp')
const gulp = require('gulp')
// const pug = require('gulp-pug') // Pug default view template
const sassCore = require('sass')
const rename = require('gulp-rename')
const concat = require('gulp-concat')
const uglifyJS = require('gulp-uglify')
const ts = require('typescript')
const exec = require('child_process').exec
const fs = require('fs')
const crypto = require('crypto')
const path = require('path')
const { Transform } = require('stream')
const { fileURLToPath } = require('url')
const removeCacheEvent = 'remove-cache'
const removeCacheFinishEvent = 'remove-cache-finish'
let cleanCacheVerbose = false

//Compila con la API moderna de Sass. Una hoja con error se imprime con su archivo y línea, y las demás siguen compilando.
//Las fuentes del .map van relativas a la carpeta donde acaba el .map (la de destino, tras renameFile), no a la de la hoja.
function sassCompileAdapter(folder, renameFile) {
	const mapDirectory = (file) => {
		let dirname = path.dirname(path.relative(file.base, file.path))
		if (renameFile) {
			dirname = renameFile({ dirname, basename: '', extname: '' }).dirname
		}
		return path.resolve(folder, dirname)
	}
	const adapter = new Transform({
		objectMode: true,
		transform(file, encoding, callback) {
			if (file.isNull()) {
				return callback(null, file)
			}
			if (path.basename(file.path).startsWith('_')) {
				return callback()
			}
			const cssPath = file.path.replace(/\.scss$/, '.css')
			if (!file.contents.length) {
				if (file.sourceMap) {
					file.sourceMap.sources = [path.relative(mapDirectory(file), file.path)]
				}
				file.path = cssPath
				return callback(null, file)
			}
			//static-url(): la ruta, relativa a la carpeta de destino, con la huella del contenido de su archivo (ADR 0067).
			const staticURL = (args) => {
				const requested = args[0].assertString('path').text
				if (/^(https?:|\/\/|data:)/i.test(requested)) {
					return new sassCore.SassString(requested, { quotes: true })
				}
				const [, filePart, query, fragment] = requested.match(/^([^?#]*)(\?[^#]*)?(#.*)?$/)
				const resolved = path.resolve(mapDirectory(file), filePart)
				if (!resolved.startsWith(__dirname + path.sep) || !fs.existsSync(resolved) || !fs.statSync(resolved).isFile()) {
					throw new Error(`static-url('${requested}'): ${path.relative(__dirname, resolved)} no existe o está fuera de src/`)
				}
				const fingerprint = crypto.createHash('sha1').update(fs.readFileSync(resolved)).digest('hex').slice(0, 16)
				return new sassCore.SassString(`${filePart}${query ? query + '&' : '?'}cacheStamp=${fingerprint}${fragment || ''}`, { quotes: true })
			}
			let result
			try {
				result = sassCore.compile(file.path, {
					style: 'compressed',
					sourceMap: true,
					sourceMapIncludeSources: true,
					functions: { 'static-url($path)': staticURL },
				})
			} catch (error) {
				adapter.failures.push(file.path)
				process.stderr.write(`Error de SASS en ${path.relative(process.cwd(), file.path)}\n${error.message}\n`)
				return callback()
			}
			adapter.compiled++
			const sourceMap = result.sourceMap
			sourceMap.sources = sourceMap.sources.map((source) => source.startsWith('file:') ? path.relative(mapDirectory(file), fileURLToPath(source)) : source)
			sourceMap.file = path.relative(file.base, cssPath)
			file.sourceMap = sourceMap
			file.contents = Buffer.from(result.css)
			file.path = cssPath
			callback(null, file)
		},
	})
	adapter.failures = []
	adapter.compiled = 0
	return adapter
}

//Los globs cuya carpeta base existe. src() de gulp 5 revienta con ENOENT si falta la base de uno, y una carpeta vacía o ausente no es un error.
function existingGlobs(globs) {
	const present = globs.filter((glob) => {
		if (glob.startsWith('!')) {
			return true
		}
		const parts = glob.split('/')
		const firstWild = parts.findIndex((part) => /[*?[\]{}]/.test(part))
		const base = (firstWild === -1 ? parts.slice(0, -1) : parts.slice(0, firstWild)).join('/') || '.'
		return fs.existsSync(base)
	})
	return present.some((glob) => !glob.startsWith('!')) ? present : []
}

//El origen de una tarea, o null si no queda ninguno: entonces la tarea lo dice y termina bien.
function sourcesOf(globs) {
	const present = existingGlobs(globs)
	if (present.length === 0) {
		console.log(`0 archivos en ${globs.filter((glob) => !glob.startsWith('!')).join(', ')}`)
		return null
	}
	return src(present, { allowEmpty: true })
}

//Termina cuando todo está escrito, y falla si alguna hoja no compiló.
function sassBuild(globs, folder, renameFile) {
	const sources = sourcesOf(globs)
	if (sources === null) {
		return Promise.resolve()
	}
	const adapter = sassCompileAdapter(folder, renameFile)
	let stream = sources
		.pipe(initSourceMaps())
		.pipe(adapter)
		.pipe(writeSourceMaps())
	if (renameFile) {
		stream = stream.pipe(rename(renameFile))
	}
	const writer = stream.pipe(writeIfChanged(folder))
	return new Promise((resolve, reject) => {
		writer.on('error', reject)
		writer.on('finish', () => {
			if (adapter.failures.length > 0) {
				reject(new Error(`SASS: no compilaron ${adapter.failures.length} hoja(s): ${adapter.failures.map((file) => path.relative(process.cwd(), file)).join(', ')}`))
			} else {
				if (adapter.compiled === 0) {
					console.log(`0 hojas en ${globs.filter((glob) => !glob.startsWith('!')).join(', ')}`)
				}
				resolve()
			}
		})
	})
}

//Escribe solo lo que cambió: un .css que no se reescribe conserva su fecha, y con ella su versión por archivo (ADR 0034).
function writeIfChanged(folder) {
	return new Transform({
		objectMode: true,
		transform(file, encoding, callback) {
			const target = path.join(folder, file.relative)
			const current = fs.existsSync(target) ? fs.readFileSync(target) : null
			if (current === null || !current.equals(file.contents)) {
				fs.mkdirSync(path.dirname(target), { recursive: true })
				fs.writeFileSync(target, file.contents)
			}
			callback()
		},
	})
}

//El mapa vacío de cada fuente, como gulp-sourcemaps.init(): sin leer el que ya enlace (src() con sourcemaps carga el de UtilPieces.js).
function initSourceMaps() {
	return new Transform({
		objectMode: true,
		transform(file, encoding, callback) {
			if (file.isNull() || file.sourceMap) {
				return callback(null, file)
			}
			const relative = file.relative.split(path.sep).join('/')
			file.sourceMap = { version: 3, names: [], mappings: '', sources: [relative], sourcesContent: [file.contents.toString()], file: relative }
			callback(null, file)
		},
	})
}

//Cada archivo con mapa sale con su `<archivo>.map` al lado y el comentario que lo enlaza, como los dejaba gulp-sourcemaps:
//el mapa en JSON sin espacios, `file` relativo al .map, y el contenido de las fuentes que falte, leído del disco.
function writeSourceMaps() {
	return new Transform({
		objectMode: true,
		transform(file, encoding, callback) {
			if (file.isNull() || !file.sourceMap) {
				return callback(null, file)
			}
			const sourceMap = file.sourceMap
			sourceMap.sourcesContent = sourceMap.sourcesContent || []
			sourceMap.sources.forEach((source, i) => {
				if (!sourceMap.sourcesContent[i]) {
					try {
						sourceMap.sourcesContent[i] = fs.readFileSync(path.resolve(file.base, source), 'utf8').replace(/^\uFEFF/, '')
					} catch (error) {
						//Sin la fuente en disco, el mapa va sin su contenido, como con gulp-sourcemaps.
					}
				}
			})
			const mapPath = file.path + '.map'
			//Sin raíz de fuentes: gulp-sourcemaps la dejaba sin definir, y así no sale en el JSON (Sass la trae vacía).
			sourceMap.sourceRoot = undefined
			sourceMap.file = path.basename(file.path)
			const mapFile = file.clone({ deep: false, contents: false })
			mapFile.path = mapPath
			mapFile.contents = Buffer.from(JSON.stringify(sourceMap))
			this.push(mapFile)
			const text = file.contents.toString()
			const newline = (text.match(/\r\n/g) || []).length > (text.match(/(?<!\r)\n/g) || []).length ? '\r\n' : '\n'
			const url = path.basename(mapPath)
			const comment = file.extname === '.css' ? `/*# sourceMappingURL=${url} */` : file.extname === '.js' ? `//# sourceMappingURL=${url}` : null
			if (comment !== null) {
				file.contents = Buffer.concat([file.contents, Buffer.from(newline + comment + newline)])
			}
			callback(null, file)
		},
	})
}

//Ordena y deduplica los segmentos como lo hacía source-map dentro de gulp-typescript: TypeScript los emite en otro orden.
//Base64 VLQ de la especificación v3.
function normalizeMappings(mappings) {
	const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'
	const decode = (segment) => {
		const values = []
		let value = 0
		let shift = 0
		for (const char of segment) {
			const digit = alphabet.indexOf(char)
			value += (digit & 31) << shift
			if (digit & 32) {
				shift += 5
			} else {
				values.push(value & 1 ? -(value >>> 1) : value >>> 1)
				value = 0
				shift = 0
			}
		}
		return values
	}
	const encode = (number) => {
		let vlq = number < 0 ? (-number << 1) | 1 : number << 1
		let out = ''
		do {
			let digit = vlq & 31
			vlq >>>= 5
			if (vlq > 0) {
				digit |= 32
			}
			out += alphabet[digit]
		} while (vlq > 0)
		return out
	}
	const previous = [0, 0, 0, 0]
	const lines = mappings.split(';').map((line) => {
		let generated = 0
		return line.split(',').filter(Boolean).map((segment) => {
			const values = decode(segment)
			generated += values[0]
			const absolute = { column: generated }
			if (values.length > 1) {
				previous[0] += values[1]
				previous[1] += values[2]
				previous[2] += values[3]
				absolute.source = previous[0]
				absolute.line = previous[1]
				absolute.originalColumn = previous[2]
				if (values.length > 4) {
					previous[3] += values[4]
					absolute.name = previous[3]
				}
			}
			return absolute
		})
	})
	//Lo que no tiene fuente o nombre va detrás de lo que sí, como el strcmp de source-map con null.
	const nullLast = (a, b) => (a === b ? 0 : a === undefined ? 1 : b === undefined ? -1 : a - b)
	const compare = (a, b) => (a.column - b.column) || nullLast(a.source, b.source) || ((a.line || 0) - (b.line || 0))
		|| ((a.originalColumn || 0) - (b.originalColumn || 0)) || nullLast(a.name, b.name)
	const state = { source: 0, line: 0, originalColumn: 0, name: 0 }
	return lines.map((segments) => {
		let column = 0
		return segments.slice().sort(compare).filter((segment, i, sorted) => i === 0 || compare(segment, sorted[i - 1]) !== 0).map((segment) => {
			let out = encode(segment.column - column)
			column = segment.column
			if (segment.source !== undefined) {
				out += encode(segment.source - state.source) + encode(segment.line - state.line) + encode(segment.originalColumn - state.originalColumn)
				state.source = segment.source
				state.line = segment.line
				state.originalColumn = segment.originalColumn
				if (segment.name !== undefined) {
					out += encode(segment.name - state.name)
					state.name = segment.name
				}
			}
			return out
		}).join(',')
	}).join(';')
}

//TypeScript, archivo a archivo, con el paquete `typescript`: el .js, y su mapa con la fuente relativa a la base y su contenido.
function typescriptCompile(compilerOptions) {
	return new Transform({
		objectMode: true,
		transform(file, encoding, callback) {
			if (file.isNull()) {
				return callback(null, file)
			}
			const source = file.contents.toString()
			const result = ts.transpileModule(source, { fileName: file.relative, compilerOptions: { ...compilerOptions, sourceMap: true } })
			const emitted = JSON.parse(result.sourceMapText)
			file.contents = Buffer.from(result.outputText.replace(/\/\/# sourceMappingURL=.*$/, ''))
			file.path = file.path.replace(/\.ts$/, '.js')
			file.sourceMap = {
				version: 3,
				sources: [file.relative.replace(/\.js$/, '.ts').split(path.sep).join('/')],
				names: emitted.names,
				mappings: normalizeMappings(emitted.mappings),
				file: path.basename(file.path),
				sourcesContent: [source],
			}
			callback(null, file)
		},
	})
}

//--------TS PiecesPHP

//Archivos que se observar
var watchingPiecesPHPTS = {
	base: [
		'./statics/core/ts/**/*.ts',
	],
}
//Archivos que se compilan
var compilePiecesPHPTS = {
	base: [
		'./statics/core/ts/**/*.ts',
	],
}

var destsPiecesPHPTS = {
	base: './statics/core/js',
}

//---------Funciones de compilación

/**
 * Las opciones de compilación de un tsconfig.json: el mismo archivo que lee el editor, para que no haya dos fuentes.
 * Si no se puede leer o trae una opción desconocida, la tarea falla en vez de compilar con otras.
 *
 * @param {string} file
 * @returns {import('typescript').CompilerOptions}
 */
function tsCompilerOptions(file) {
	const read = ts.readConfigFile(file, ts.sys.readFile)
	if (read.error) {
		throw new Error(`${file}: ${ts.flattenDiagnosticMessageText(read.error.messageText, ' ')}`)
	}
	const converted = ts.convertCompilerOptionsFromJson(read.config.compilerOptions || {}, path.dirname(path.resolve(file)))
	if (converted.errors.length > 0) {
		throw new Error(`${file}: ${converted.errors.map((error) => ts.flattenDiagnosticMessageText(error.messageText, ' ')).join('; ')}`)
	}
	return converted.options
}

function tsTask() {
	const sources = sourcesOf(compilePiecesPHPTS.base)
	if (sources === null) {
		return Promise.resolve()
	}
	return sources
		.pipe(initSourceMaps())
		.pipe(typescriptCompile(tsCompilerOptions('./statics/core/ts/tsconfig.json')))
		.pipe(writeSourceMaps())
		.pipe(writeIfChanged(destsPiecesPHPTS.base))
}

//Tareas de compilación
task("ts-vendor", tsTask)

//Tareas de observación
task("ts-vendor:watch", (done) => {
	watch(watchingPiecesPHPTS.base, series("ts-vendor"))
	done()
})
//--------JS PiecesPHP

//Archivos que se observar
var watchingPiecesPHPJS = {
	base: [
		'./statics/core/js/helpers-lib/*.js',
		'./statics/core/js/translations/*.js',
		'./statics/core/js/configurations.js',
		'./statics/core/js/helpers.js',
	],
}
//Archivos que se compilan
var compilePiecesPHPJS = {
	base: [
		'./statics/core/js/helpers-lib/*.js',
		'./statics/core/js/translations/*.js',
		'./statics/core/js/configurations.js',
		'./statics/core/js/helpers.js',
	],
}

var destsPiecesPHPJS = {
	base: './statics/core/js',
}

//---------Funciones de compilación

function jsTask() {
	const sources = sourcesOf(compilePiecesPHPJS.base)
	if (sources === null) {
		return Promise.resolve()
	}
	return sources
		.pipe(initSourceMaps())
		.pipe(concat('configurations.min.js'))
		.pipe(uglifyJS())
		.pipe(writeSourceMaps())
		.pipe(writeIfChanged(destsPiecesPHPJS.base))
}

//Tareas de compilación
task("js-vendor", jsTask)

//Tareas de observación
task("js-vendor:watch", (done) => {
	watch(watchingPiecesPHPJS.base, series("js-vendor"))
	done()
})

//--------SASS PiecesPHP

//Archivos que se observar
var watchingPiecesPHPSassFiles = {
	ownPlugins: [
		'./statics/core/own-plugins/sass/**/*.scss',
	],
	general: [
		'./statics/core/sass/**/*.scss',
	],
}

//Archivos que se compilan
var compilePiecesPHPSassFiles = {
	ownPlugins: [
		'./statics/core/own-plugins/sass/**/*.scss',
	],
	general: [
		'./statics/core/sass/**/*.scss',
	],
}

var destsPiecesPHP = {
	ownPlugins: './statics/core/own-plugins/css',
	general: './statics/core/css',
}
//---------Funciones de compilación

//Compilación plugins propios
function sassCompileOwnPlugins() {
	return sassBuild(compilePiecesPHPSassFiles.ownPlugins, destsPiecesPHP.ownPlugins)
}

//Compilación generales
function sassCompileGeneral() {
	return sassBuild(compilePiecesPHPSassFiles.general, destsPiecesPHP.general)
}

//Tareas de compilación
task("sass-compile-own-plugins", sassCompileOwnPlugins)
task("sass-compile-general", sassCompileGeneral)

//Tareas de observación
task("sass-vendor:watch", (done) => {
	watch(watchingPiecesPHPSassFiles.ownPlugins, series("sass-compile-own-plugins"))
	watch(watchingPiecesPHPSassFiles.general, series("sass-compile-general"))
	done()
})

//Tarea inicial
task("sass-vendor:init", () => Promise.all([
	sassCompileOwnPlugins(),
	sassCompileGeneral(),
]))

//SASS others
var watchingSassFiles = [
	'./statics/sass/**/*.scss',
]
var compileSassFiles = [
	'./statics/sass/**/*.scss',
	'!./statics/sass/imports/**/*.scss',
]
var cssDest = './statics/css'

function sassCompileGeneric() {
	return sassBuild(compileSassFiles, cssDest)
}

task("sass", sassCompileGeneric)
task("sass:watch", (done) => {
	watch(watchingSassFiles, series("sass"))
	done()
})
task("sass:init", sassCompileGeneric)

//Modules
var watchingModulesSassFiles = [
	'./app/classes/**/sass/**/*.scss',
	'./statics/core/sass/includes/**/*.scss',
	'./statics/core/sass/admin_app_base.scss',
]
var compileMonulesSassFiles = [
	'./app/classes/**/sass/**/*.scss',
]
var cssModulesDest = './app/classes'

function sassCompileModules() {
	return sassBuild(compileMonulesSassFiles, cssModulesDest, function (path) {
		return {
			dirname: path.dirname.replace('sass', 'css'),
			basename: path.basename,
			extname: path.extname,
		}
	})
}

task("sass-modules", sassCompileModules)
task("sass-modules:watch", (done) => {
	watch(watchingModulesSassFiles, series("sass-modules"))
	done()
})
task("sass-modules:init", sassCompileModules)

//Compilar todo
task("sass-all", () => Promise.all([
	sassCompileOwnPlugins(),
	sassCompileGeneral(),
	sassCompileGeneric(),
	sassCompileModules(),
]))
task("sass-all:watch", (done) => {
	parallel(
		"sass-all",
		"sass:watch",
		"sass-modules:watch",
		"sass-vendor:watch",
	)()
	done()
})

//General
task("init-project", parallel(
	"ts-vendor",
	"js-vendor",
	"sass-all",
))
task("init-project:watch", (done) => {
	parallel(
		"init-project",
		"sass-all",
		"sass-all:watch",
		"js-vendor:watch",
	)()
	done()
})

//Compilar documentación de api
task("api-build", (done) => {
	//En estructura normal debe subir solo un directorio
	exec('cd ../source-docs/api && mkdocs build --clean', (error, stdout, stderr) => {
		done(error)
	})
})

//Remover cache
task("clean-cache", (done) => {
	cleanCacheVerbose = true
	gulp.emit(removeCacheEvent)
	gulp.on(removeCacheFinishEvent, function () {
		done()
	})
})
//Solo la dispara la tarea clean-cache: compilar ya no renueva la marca global (ADR 0034), porque lo compilado cambia de versión solo.
gulp.on(removeCacheEvent, () => {
	if (cleanCacheVerbose) {
		console.log('Limpiando memoria caché..')
	}
	exec('../bin/cli clean-cache', (error, stdout, stderr) => {
		if (cleanCacheVerbose) {
			console.log('Memoria caché limpiada')
		}
		cleanCacheVerbose = false
		gulp.emit(removeCacheFinishEvent)
	})
})
