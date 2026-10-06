/*
* Dependencias
*/
const { src, watch, task, series, parallel } = require('gulp')
const gulp = require('gulp')
// const pug = require('gulp-pug') // Pug default view template
const sassCore = require('sass')
const sourcemaps = require('gulp-sourcemaps')
const rename = require('gulp-rename')
const concat = require('gulp-concat')
const uglifyJS = require('gulp-uglify')
const typescript = require('gulp-typescript')
const exec = require('child_process').exec
const fs = require('fs')
const path = require('path')
const { Transform } = require('stream')
const { fileURLToPath } = require('url')
const removeCacheEvent = 'remove-cache'
const removeCacheFinishEvent = 'remove-cache-finish'
let cleanCacheVerbose = false

//Compila con la API moderna de Sass. Una hoja con error se imprime con su archivo y línea, y las demás siguen compilando.
function sassCompileAdapter() {
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
				file.path = cssPath
				return callback(null, file)
			}
			let result
			try {
				result = sassCore.compile(file.path, {
					style: 'compressed',
					sourceMap: true,
					sourceMapIncludeSources: true,
				})
			} catch (error) {
				adapter.failures.push(file.path)
				process.stderr.write(`Error de SASS en ${path.relative(process.cwd(), file.path)}\n${error.message}\n`)
				return callback()
			}
			adapter.compiled++
			const sourceMap = result.sourceMap
			sourceMap.sources = sourceMap.sources.map((source) => source.startsWith('file:') ? path.relative(file.base, fileURLToPath(source)) : source)
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
	const adapter = sassCompileAdapter()
	let stream = sources
		.pipe(sourcemaps.init())
		.pipe(adapter)
		.pipe(sourcemaps.write('./'))
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

function tsTask() {
	const sources = sourcesOf(compilePiecesPHPTS.base)
	if (sources === null) {
		return Promise.resolve()
	}
	return sources
		.pipe(sourcemaps.init())
		.pipe(typescript({
			target: 'es5',
			//Opciones importantes
			skipLibCheck: true, //No buscar librerías .d.ts.
			typeRoots: [], //No buscar tipos automáticamente en node_modules.
		}))
		.pipe(sourcemaps.write('./'))
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
		.pipe(sourcemaps.init())
		.pipe(concat('configurations.min.js'))
		.pipe(uglifyJS())
		.pipe(sourcemaps.write('./'))
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
	users: [
		'./statics/login-and-recovery/sass/**/*.scss',
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
	users: [
		'./statics/login-and-recovery/sass/**/*.scss',
	],
}

var destsPiecesPHP = {
	users: './statics/login-and-recovery/css',
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

//Compilación usuarios
function sassCompileUsers() {
	return sassBuild(compilePiecesPHPSassFiles.users, destsPiecesPHP.users)
}

//Tareas de compilación
task("sass-compile-own-plugins", sassCompileOwnPlugins)
task("sass-compile-general", sassCompileGeneral)
task("sass-compile-users", sassCompileUsers)

//Tareas de observación
task("sass-vendor:watch", (done) => {
	watch(watchingPiecesPHPSassFiles.ownPlugins, series("sass-compile-own-plugins"))
	watch(watchingPiecesPHPSassFiles.general, series("sass-compile-general"))
	watch(watchingPiecesPHPSassFiles.users, series("sass-compile-users"))
	done()
})

//Tarea inicial
task("sass-vendor:init", () => Promise.all([
	sassCompileOwnPlugins(),
	sassCompileGeneral(),
	sassCompileUsers(),
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
	sassCompileUsers(),
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
