/*
* Dependencias
*/
var gulp = require('gulp')
var sourcemaps = require('gulp-sourcemaps');
var sass = require("gulp-sass")

//--------SASS PiecesPHP

//Archivos que se observar
var watchingPiecesPHPSassFiles = {
	general: [
		'./statics/core/sass/**/*.scss',
		'./statics/core/sass/components/**/*.scss',
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
	general: [
		'./statics/core/sass/ui-pcs.scss',
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
	general: './statics/core/css',
	avatars: './statics/features/avatars/css',
}
//---------Funciones de compilación

//Compilación generales
function sassCompileGeneral() {
	return gulp.src(compilePiecesPHPSassFiles.general)
		.pipe(sourcemaps.init())
		.pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest(destsPiecesPHP.general))
}

//Compilación usuarios
function sassCompileUsers() {
	return gulp.src(compilePiecesPHPSassFiles.users)
		.pipe(sourcemaps.init())
		.pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest(destsPiecesPHP.users))
}

//Compilación avatars
function sassCompileAvatars() {
	return gulp.src(compilePiecesPHPSassFiles.avatars)
		.pipe(sourcemaps.init())
		.pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest(destsPiecesPHP.avatars))
}

//Tareas de compilación
gulp.task("sass-compile-general", sassCompileGeneral)
gulp.task("sass-compile-users", sassCompileUsers)
gulp.task("sass-compile-avatars", sassCompileAvatars)

//Tareas de observación
gulp.task("sass-vendor:watch", () => {
	gulp.watch(watchingPiecesPHPSassFiles.general, ["sass-compile-general"])
	gulp.watch(watchingPiecesPHPSassFiles.users, ["sass-compile-users"])
	gulp.watch(watchingPiecesPHPSassFiles.avatars, ["sass-compile-avatars"])
})

//Tarea inicial
gulp.task("sass-vendor:init", () => {
	sassCompileGeneral()
	sassCompileUsers()
	sassCompileAvatars()
})


//SASS others
var watchingSassFiles = [
	'./statics/sass/**/*.scss',
]
var compileSassFiles = [
	'./statics/sass/**/*.scss',
	'!./statics/sass/import/*.scss',
]
var dest = './statics/css'

function sassCompileGeneric() {
	return gulp.src(compileSassFiles)
		.pipe(sourcemaps.init())
		.pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest(dest))
}

gulp.task("sass", () => {
	sassCompileGeneric()
})

gulp.task("sass:watch", () => {
	gulp
		.watch(watchingSassFiles, ["sass"])
})
gulp.task("sass:init", () => {
	sassCompileGeneric()
})
