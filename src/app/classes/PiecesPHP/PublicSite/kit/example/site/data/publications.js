/* Datos de ejemplo de «publications». Los genera el sistema: los campos son los que existen de verdad. */
window.PCS_DATA = window.PCS_DATA || {}
window.PCS_DATA['publications'] = {
	fields: {
		title: { type: 'text', example: 'Título de una noticia' },
		excerpt: { type: 'text', example: 'Un resumen corto de la noticia, de una o dos frases.' },
		date: { type: 'date', example: '20 de noviembre de 2026' },
		url: { type: 'url', example: '#' },
		image: { type: 'image', example: 'assets/sample.svg' },
	},
	items: [
		{ title: 'Abrimos nueva sede', excerpt: 'Desde el lunes atendemos también en el centro.', date: '18 de noviembre de 2026', url: '#', image: 'assets/sample.svg' },
		{ title: 'Taller gratuito de diseño', excerpt: 'Inscripciones abiertas hasta fin de mes.', date: '12 de noviembre de 2026', url: '#', image: 'assets/sample.svg' },
		{ title: 'Memoria anual', excerpt: 'Ya puedes leer lo que hicimos este año.', date: '2 de noviembre de 2026', url: '#', image: 'assets/sample.svg' },
		{ title: 'Nuevo horario', excerpt: 'Ampliamos el horario de tarde.', date: '28 de octubre de 2026', url: '#', image: 'assets/sample.svg' },
	],
}
