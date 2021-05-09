/*
* Dependencias
*/
const { src, dest, watch, task, series } = require('gulp');
// const pug = require('gulp-pug'); // Pug default view template
const sass = require('gulp-sass')
const sourcemaps = require('gulp-sourcemaps')
const rename = require('gulp-rename')
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
	avatars: [
		'./statics/features/avatars/sass/**/*.scss',
	],
}

var destsPiecesPHP = {
	users: './statics/login-and-recovery/css',
	ownPlugins: './statics/core/own-plugins/css',
	general: './statics/core/css',
	avatars: './statics/features/avatars/css',
}
//---------Funciones de compilación

//Compilación plugins propios
function sassCompileOwnPlugins() {
	return src(compilePiecesPHPSassFiles.ownPlugins)
		.pipe(sourcemaps.init())
		.pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(destsPiecesPHP.ownPlugins))
}

//Compilación generales
function sassCompileGeneral() {
	return src(compilePiecesPHPSassFiles.general)
		.pipe(sourcemaps.init())
		.pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(destsPiecesPHP.general))
}

//Compilación usuarios
function sassCompileUsers() {
	return src(compilePiecesPHPSassFiles.users)
		.pipe(sourcemaps.init())
		.pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(destsPiecesPHP.users))
}

//Compilación avatars
function sassCompileAvatars() {
	return src(compilePiecesPHPSassFiles.avatars)
		.pipe(sourcemaps.init())
		.pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(destsPiecesPHP.avatars))
}

//Tareas de compilación
task("sass-compile-own-plugins", (done) => {
	sassCompileOwnPlugins()
	done()
})
task("sass-compile-general", (done) => {
	sassCompileGeneral()
	done()
})
task("sass-compile-users", (done) => {
	sassCompileUsers()
	done()
})
task("sass-compile-avatars", (done) => {
	sassCompileAvatars()
	done()
})

//Tareas de observación
task("sass-vendor:watch", (done) => {
	watch(watchingPiecesPHPSassFiles.ownPlugins, series("sass-compile-own-plugins"))
	watch(watchingPiecesPHPSassFiles.general, series("sass-compile-general"))
	watch(watchingPiecesPHPSassFiles.users, series("sass-compile-users"))
	watch(watchingPiecesPHPSassFiles.avatars, series("sass-compile-avatars"))
	done()
})

//Tarea inicial
task("sass-vendor:init", (done) => {
	sassCompileOwnPlugins()
	sassCompileGeneral()
	sassCompileUsers()
	sassCompileAvatars()
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
		.pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
		.pipe(sourcemaps.write('./'))
		.pipe(dest(cssDest))
}

task("sass", (done) => {
	sassCompileGeneric()
	done()
})

task("sass:watch", (done) => {
	watch(watchingSassFiles, series("sass"))
	done()
})
task("sass:init", (done) => {
	sassCompileGeneric()
	done()
})

//Modules
var watchingModulesSassFiles = [
	'./app/classes/**/sass/**/*.scss',
	'./statics/core/sass/_admin_app_base.scss',
]
var compileMonulesSassFiles = [
	'./app/classes/**/sass/**/*.scss',
]
var cssModulesDest = './app/classes'

function sassCompileModules() {
	return src(compileMonulesSassFiles)
		.pipe(sourcemaps.init())
		.pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
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
	done()
})

task("sass-modules:watch", (done) => {
	watch(watchingModulesSassFiles, series("sass-modules"))
	done()
})
task("sass-modules:init", (done) => {
	sassCompileModules()
	done()
})


//Compilar todo
task("sass-all", (done) => {

	sassCompileOwnPlugins()
	sassCompileGeneral()
	sassCompileUsers()
	sassCompileAvatars()

	sassCompileGeneric()

	sassCompileModules()

	done()

})
