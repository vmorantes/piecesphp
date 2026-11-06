window.addEventListener('load', () => {
	//Los mandos del modo mantenimiento del sitio.
	const formulario = document.querySelector('[site-maintenance-form]')
	const selectorRoles = document.querySelector('[site-maintenance-roles]')
	const campoRolesJSON = document.querySelector('[site-maintenance-roles-json]')
	const interruptor = document.querySelector('[site-maintenance-toggle]')

	if (formulario === null || selectorRoles === null || campoRolesJSON === null || interruptor === null) {
		return
	}

	const textos = formulario.dataset
	const miRol = textos.currentRole !== '' ? parseInt(textos.currentRole, 10) : null
	const rolPrincipal = parseInt(textos.rootCode, 10)

	const seleccionados = function () {
		return Array.from(selectorRoles.options).filter(function (o) {
			return o.selected
		}).map(function (o) {
			return parseInt(o.value, 10)
		}).filter(function (v) {
			return !isNaN(v)
		})
	}

	//La lista viaja como JSON: un selector múltiple sin nada marcado no manda nada, y entonces
	//«ningún rol» sería indistinguible de un error de escritura.
	const sincronizar = function () {
		campoRolesJSON.value = JSON.stringify(seleccionados())
	}

	//`condition` la evalúa genericFormHandler AL ENLAZAR, no al enviar, así que hay que volver a
	//enlazar cada vez que cambia algo o el aviso se quedaría congelado con el estado inicial.
	const enlazar = function () {
		sincronizar()
		const lista = seleccionados()
		const enciende = interruptor.checked === true
		//El principal pasa siempre, así que a él nunca se le avisa de que se queda fuera.
		const meExcluye = miRol !== null && miRol !== rolPrincipal && lista.indexOf(miRol) === -1
		genericFormHandler('[site-maintenance-form]', {
			confirmation: {
				selector: '[site-maintenance-form] button[type="submit"]',
				title: textos.confirmTitle,
				message: meExcluye ? textos.warnExcluded : textos.warnOff,
				positive: textos.confirmYes,
				negative: textos.confirmNo,
				//Avisa, no impide: apagar el sitio a propósito es lo que hace esta pantalla.
				condition: function () {
					return enciende || meExcluye
				},
			},
		})
	}

	//El estado y su efecto se leen junto al interruptor, según lo que se va a guardar.
	const efecto = formulario.querySelector('[data-site-maintenance-effect]')
	const pintarEstado = function () {
		const enciende = interruptor.checked === true
		efecto.textContent = efecto.getAttribute(enciende ? 'data-text-on' : 'data-text-off')
		formulario.querySelector('[data-site-maintenance-state="on"]').hidden = !enciende
		formulario.querySelector('[data-site-maintenance-state="off"]').hidden = enciende
	}

	selectorRoles.addEventListener('change', enlazar)
	interruptor.addEventListener('change', enlazar)
	interruptor.addEventListener('change', pintarEstado)
	//`reset` avisa antes de devolver los valores: se pinta y se reenlaza en el turno siguiente.
	formulario.addEventListener('reset', function () {
		setTimeout(function () {
			pintarEstado()
			enlazar()
		})
	})

	if (typeof $ === 'function' && typeof $(selectorRoles).dropdown === 'function') {
		$(selectorRoles).dropdown({
			onChange: enlazar,
		})
	}

	enlazar()
	pintarEstado()
})
