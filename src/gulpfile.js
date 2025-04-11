/*
* Dependencias
*/
const { src, dest, watch, task, series, parallel } = require('gulp')
const gulp = require('gulp')
// const pug = require('gulp-pug') // Pug default view template
const sassCore = require('sass')
const sass = require('gulp-sass')(sassCore) // Actualiza esta línea
const sourcemaps = require('gulp-sourcemaps')
const rename = require('gulp-rename')
const concat = require('gulp-concat')
const replace = require('gulp-replace')
const uglifyJS = require('gulp-uglify')
const typescript = require('gulp-typescript')
const exec = require('child_process').exec
const removeCacheEvent = 'remove-cache'
const removeCacheFinishEvent = 'remove-cache-finish'
let cleanCacheVerbose = false
let sassCompileAdapter = function (options) {
	options = typeof options == 'object' ? options : {}
	const baseOptions = {
		outputStyle: 'compressed',
		silenceDeprecations: [
			'legacy-js-api',
			'color-functions',
			'mixed-decls',
			'import',
			'global-builtin',
		],		
		quietDeps: true,
	}
	const finalOptions = Object.assign({}, baseOptions)

	for (const option in options) {
		finalOptions.option = options[option]
	}

	return sass(finalOptions)
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
	return src(compilePiecesPHPTS.base)
		.pipe(sourcemaps.init())
		.pipe(typescript({
			target: 'es5',
		}))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(destsPiecesPHPTS.base))
}

//Tareas de compilación
task("ts-vendor", (done) => {
	tsTask()
	gulp.emit(removeCacheEvent)
	done()
})

//Tareas de observación
task("ts-vendor:watch", (done) => {
	watch(watchingPiecesPHPTS.base, series("ts-vendor"))
	done()
})
//--------JS PiecesPHP

//Archivos que se observar
var watchingPiecesPHPJS = {
	base: [
		'./statics/core/js/UtilPieces.js',
		'./statics/core/js/translations/*.js',
		'./statics/core/js/configurations.js',
		'./statics/core/js/helpers.js',
	],
}
//Archivos que se compilan
var compilePiecesPHPJS = {
	base: [
		'./statics/core/js/UtilPieces.js',
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
	return src(compilePiecesPHPJS.base)
		.pipe(sourcemaps.init())
		.pipe(concat('configurations.min.js'))
		.pipe(uglifyJS())
		.pipe(sourcemaps.write('./'))
		.pipe(dest(destsPiecesPHPJS.base))
}

//Tareas de compilación
task("js-vendor", (done) => {
	jsTask()
	gulp.emit(removeCacheEvent)
	done()
})

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
	users2: [
		'./statics/admin-area/sass/**/*.scss',
	],
	avatars: [
		'./statics/features/avatars/sass/**/*.scss',
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
	users2: [
		'./statics/admin-area/sass/**/*.scss',
	],
	avatars: [
		'./statics/features/avatars/sass/**/*.scss',
	],
}

var destsPiecesPHP = {
	users: './statics/login-and-recovery/css',
	users2: './statics/admin-area/css',
	ownPlugins: './statics/core/own-plugins/css',
	general: './statics/core/css',
	avatars: './statics/features/avatars/css',
}
//---------Funciones de compilación

//Compilación plugins propios
function sassCompileOwnPlugins() {
	return src(compilePiecesPHPSassFiles.ownPlugins)
		.pipe(sourcemaps.init())
		.pipe(sassCompileAdapter({}).on('error', sass.logError))
		.pipe(replace('CACHESTAMP', `${new Date().getTime()}`))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(destsPiecesPHP.ownPlugins))
}

//Compilación generales
function sassCompileGeneral() {
	return src(compilePiecesPHPSassFiles.general)
		.pipe(sourcemaps.init())
		.pipe(sassCompileAdapter({}).on('error', sass.logError))
		.pipe(replace('CACHESTAMP', `${new Date().getTime()}`))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(destsPiecesPHP.general))
}

//Compilación usuarios
function sassCompileUsers() {
	return src(compilePiecesPHPSassFiles.users)
		.pipe(sourcemaps.init())
		.pipe(sassCompileAdapter({}).on('error', sass.logError))
		.pipe(replace('CACHESTAMP', `${new Date().getTime()}`))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(destsPiecesPHP.users))
}

//Compilación usuarios 2
function sassCompileUsers2() {
	return src(compilePiecesPHPSassFiles.users2)
		.pipe(sourcemaps.init())
		.pipe(sassCompileAdapter({}).on('error', sass.logError))
		.pipe(replace('CACHESTAMP', `${new Date().getTime()}`))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(destsPiecesPHP.users2))
}

//Compilación avatars
function sassCompileAvatars() {
	return src(compilePiecesPHPSassFiles.avatars)
		.pipe(sourcemaps.init())
		.pipe(sassCompileAdapter({}).on('error', sass.logError))
		.pipe(replace('CACHESTAMP', `${new Date().getTime()}`))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(destsPiecesPHP.avatars))
}

//Tareas de compilación
task("sass-compile-own-plugins", (done) => {
	sassCompileOwnPlugins()
	gulp.emit(removeCacheEvent)
	done()
})
task("sass-compile-general", (done) => {
	sassCompileGeneral()
	gulp.emit(removeCacheEvent)
	done()
})
task("sass-compile-users", (done) => {
	sassCompileUsers()
	gulp.emit(removeCacheEvent)
	done()
})
task("sass-compile-users2", (done) => {
	sassCompileUsers2()
	gulp.emit(removeCacheEvent)
	done()
})
task("sass-compile-avatars", (done) => {
	sassCompileAvatars()
	gulp.emit(removeCacheEvent)
	done()
})

//Tareas de observación
task("sass-vendor:watch", (done) => {
	watch(watchingPiecesPHPSassFiles.ownPlugins, series("sass-compile-own-plugins"))
	watch(watchingPiecesPHPSassFiles.general, series("sass-compile-general"))
	watch(watchingPiecesPHPSassFiles.users, series("sass-compile-users"))
	watch(watchingPiecesPHPSassFiles.users2, series("sass-compile-users2"))
	watch(watchingPiecesPHPSassFiles.avatars, series("sass-compile-avatars"))
	done()
})

//Tarea inicial
task("sass-vendor:init", (done) => {
	sassCompileOwnPlugins()
	sassCompileGeneral()
	sassCompileUsers()
	sassCompileUsers2()
	sassCompileAvatars()
	gulp.emit(removeCacheEvent)
	done()
})

//SASS others
var watchingSassFiles = [
	'./statics/sass/**/*.scss',
]
var compileSassFiles = [
	'./statics/sass/**/*.scss',
	'!./statics/sass/import/**/*.scss',
]
var cssDest = './statics/css'

function sassCompileGeneric() {
	return src(compileSassFiles)
		.pipe(sourcemaps.init())
		.pipe(sassCompileAdapter({}).on('error', sass.logError))
		.pipe(replace('CACHESTAMP', `${new Date().getTime()}`))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(cssDest))
}

task("sass", (done) => {
	sassCompileGeneric()
	gulp.emit(removeCacheEvent)
	done()
})
task("sass:watch", (done) => {
	watch(watchingSassFiles, series("sass"))
	done()
})
task("sass:init", (done) => {
	sassCompileGeneric()
	gulp.emit(removeCacheEvent)
	done()
})

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
	return src(compileMonulesSassFiles)
		.pipe(sourcemaps.init())
		.pipe(sassCompileAdapter({}).on('error', sass.logError))
		.pipe(replace('CACHESTAMP', `${new Date().getTime()}`))
		.pipe(sourcemaps.write('./'))
		.pipe(rename(function (path) {
			return {
				dirname: path.dirname.replace('sass', 'css'),
				basename: path.basename,
				extname: path.extname,
			}
		}))
		.pipe(dest(cssModulesDest))
}

task("sass-modules", (done) => {
	sassCompileModules()
	gulp.emit(removeCacheEvent)
	done()
})
task("sass-modules:watch", (done) => {
	watch(watchingModulesSassFiles, series("sass-modules"))
	done()
})
task("sass-modules:init", (done) => {
	sassCompileModules()
	gulp.emit(removeCacheEvent)
	done()
})

//Compilar todo
task("sass-all", (done) => {

	sassCompileOwnPlugins()
	sassCompileGeneral()
	sassCompileUsers()
	sassCompileUsers2()
	sassCompileAvatars()

	sassCompileGeneric()

	sassCompileModules()

	gulp.emit(removeCacheEvent)
	done()

})
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
task("init-project", (done) => {
	tsTask()
	jsTask()
	parallel(
		"sass-all",
	)()
	gulp.emit(removeCacheEvent)
	done()
})
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
	exec('cd ../files/API && mkdocs build --clean', (error, stdout, stderr) => {
		done()
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
