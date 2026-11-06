/**
 * Muestra un paso de un conjunto y oculta los demás (un formulario por pasos, un asistente). Cada paso es un elemento con
 * el atributo `step`; los disparadores llevan `data-to-step` con un valor de paso, `next` o `previous`. El siguiente y el
 * anterior salen del ORDEN de la colección de pasos, no del DOM. Si se le da un `ui steps` de Fomantic, marca en él el
 * paso actual y los ya hechos.
 * @example
 * ```js
		const steps = $('[container-steps]') //El paso lo tomará del atributo step de cada elemento
		const triggers = $('[data-to-step]')
		const startOnStep = 1
		const stepsManager = new GenericStepsViewHandler(steps, triggers, startOnStep, {
			indicators: $('.ui.steps[container-steps-indicators]'), //Sus .step[data-step="<valor>"]
		})
		stepsManager.setOnChange((step, toStep) => true) //false, o una promesa de false, no deja cambiar
 * ```
 */
class GenericStepsViewHandler {
	/**
	 * Crea una instancia de GenericStepsViewHandler.
	 * @param {$} steps - Los pasos, en su orden: cada uno con el atributo `step`.
	 * @param {$} triggers - Los disparadores: cada uno con `data-to-step` (un valor de paso, `next` o `previous`).
	 * @param {number} [initialStep=1] - El paso inicial a mostrar (por defecto 1).
	 * @param {{indicators?: HTMLElement|$}} [options={}] - `indicators`: un `ui steps` de Fomantic cuyos
	 * `.step[data-step="<valor>"]` se marcan en cada cambio: `active` el actual y `completed` los anteriores en el orden de
	 * `steps`. `disabled` no se toca.
	 */
	constructor(steps, triggers, initialStep = 1, options = {}) {
		this.steps = steps
		this.triggers = triggers
		this.currentStepValue = initialStep // Guardar el valor del paso actual
		this.previousStepValue = null
		this.onChange = function (step, toStep) { }
		this.beforeChangeCallbacks = []
		this.afterChangeCallbacks = []
		this.indicators = options !== null && typeof options == 'object' && options.indicators ? $(options.indicators) : null
		//Mientras setOnChange no decide, los demás cambios se ignoran: dos clics no avanzan dos pasos.
		this.changing = false
		this.showStep(this.currentStepValue)
		this.setupTriggers() // Configurar los disparadores
	}

	/**
	 * Configura los eventos de clic en los disparadores.
	 */
	setupTriggers() {
		this.triggers.each((index, trigger) => {
			$(trigger).on('click', (e) => {
				e.preventDefault() // Evitar el comportamiento por defecto del enlace
				const targetStep = $(trigger).data('to-step') // Obtener el valor del atributo data-to-step
				if (targetStep == 'next') {
					this.nextStep()
				} else if (targetStep == 'previous') {
					this.previousStep()
				} else {
					this.changeStep(targetStep)
				}
			})
		})
	}

	/**
	 * Cambia al paso especificado. Si ya hay un cambio esperando a setOnChange, este se ignora.
	 * @param {number} stepValue - El valor del paso al que se desea cambiar.
	 */
	changeStep(stepValue) {
		if (this.changing) {
			return
		}
		this.changing = true
		let instance = this
		let decision
		try {
			this.triggerBeforeChange(this.currentStepValue) // Evento antes de cambiar
			decision = this.triggerOnChangeCallback(stepValue)
		} catch (error) {
			this.changing = false
			throw error
		}
		decision.then(function (canChange) {
			canChange = typeof canChange == 'boolean' ? canChange : true
			if (canChange) {
				instance.hideStep(instance.currentStepValue)
				instance.previousStepValue = instance.currentStepValue // Guardar el paso anterior
				instance.currentStepValue = stepValue // Establecer el nuevo paso
				instance.showStep(instance.currentStepValue)
			}
		}).finally(function () {
			instance.changing = false
		})
	}

	/**
	 * Muestra el paso especificado por su valor de atributo 'step' y marca los indicadores, si los hay.
	 * @param {number} stepValue - El valor del paso a mostrar.
	 */
	showStep(stepValue) {
		this.steps.each((index, step) => {
			const stepAttr = $(step).attr('step')
			if (stepAttr == stepValue) {
				$(step).show()
			} else {
				$(step).hide()
			}
		})
		this.syncIndicators(stepValue)
		this.triggerAfterChange(stepValue, this.previousStepValue) // Llamar a la función de evento de cambio
	}

	/**
	 * Oculta el paso especificado por su valor de atributo 'step'.
	 * @param {number} stepValue - El valor del paso a ocultar.
	 */
	hideStep(stepValue) {
		this.steps.each((index, step) => {
			if ($(step).attr('step') == stepValue) {
				$(step).hide()
			}
		})
	}

	/**
	 * Avanza al siguiente paso si es posible.
	 */
	nextStep() {
		const nextStepValue = this.getNextStepValue(this.currentStepValue)
		if (nextStepValue) {
			this.changeStep(nextStepValue)
		}
	}

	/**
	 * Retrocede al paso anterior si es posible.
	 */
	previousStep() {
		const prevStepValue = this.getPreviousStepValue(this.currentStepValue)
		if (prevStepValue) {
			this.changeStep(prevStepValue)
		}
	}

	/**
	 * Obtiene el valor del siguiente paso, en el orden de la colección de pasos.
	 * @param {number} currentStepValue - El valor del paso actual.
	 * @returns {string|null} - El valor del siguiente paso (el texto de su atributo `step`) o null si no hay.
	 */
	getNextStepValue(currentStepValue) {
		const index = this.stepIndex(currentStepValue)
		return index > -1 && index + 1 < this.steps.length ? this.steps.eq(index + 1).attr('step') : null
	}

	/**
	 * Obtiene el valor del paso anterior, en el orden de la colección de pasos.
	 * @param {number} currentStepValue - El valor del paso actual.
	 * @returns {string|null} - El valor del paso anterior (el texto de su atributo `step`) o null si no hay.
	 */
	getPreviousStepValue(currentStepValue) {
		const index = this.stepIndex(currentStepValue)
		return index > 0 ? this.steps.eq(index - 1).attr('step') : null
	}

	/**
	 * La posición de un paso en la colección, o -1.
	 * @param {number|string} stepValue
	 * @returns {number}
	 */
	stepIndex(stepValue) {
		return this.steps.toArray().findIndex((step) => $(step).attr('step') == stepValue)
	}

	/**
	 * Marca los indicadores: `active` el paso actual, `completed` los anteriores y ninguna de las dos los siguientes.
	 * @param {number|string} stepValue - El valor del paso actual.
	 */
	syncIndicators(stepValue) {
		if (this.indicators === null) {
			return
		}
		const current = this.stepIndex(stepValue)
		this.indicators.find('.step[data-step]').each((index, indicator) => {
			const position = this.stepIndex($(indicator).attr('data-step'))
			$(indicator).toggleClass('active', position > -1 && position === current)
			$(indicator).toggleClass('completed', position > -1 && position < current)
		})
	}

	/**
	 * Agrega un callback al ordenar el cambio de paso: recibe (paso actual, paso al que va) y devuelve false, o una promesa
	 * de false, para impedirlo.
	 * @param {function} callback
	 */
	setOnChange(callback) {
		this.onChange = typeof callback == 'function' ? callback : this.onChange
	}

	/**
	 * Agrega un callback que se ejecutará antes de cambiar de paso. Ojo: se ejecuta ANTES de que setOnChange decida, así
	 * que también cuando luego no deja cambiar.
	 * @param {function} callback - La función a ejecutar antes de cambiar de paso.
	 */
	addBeforeChangeCallback(callback) {
		this.beforeChangeCallbacks.push(callback)
	}

	/**
	 * Agrega un callback que se ejecutará después de mostrar un paso.
	 * @param {function} callback - La función a ejecutar después de mostrar un paso.
	 */
	addAfterChangeCallback(callback) {
		this.afterChangeCallbacks.push(callback)
	}

	/**
	 * Dispara los eventos antes de cambiar de paso.
	 * @param {number} stepValue - El valor del paso actual.
	 * @returns {Promise<boolean>} - Retorna una promesa que se resuelve en true si se permite el cambio, false si no.
	 */
	triggerOnChangeCallback(stepValue) {
		const result = this.onChange(this.currentStepValue, stepValue)
		// Si el resultado es una promesa, devolverla
		if (result instanceof Promise) {
			return result
		}
		// Si el resultado es booleano, convertirlo en una promesa
		return Promise.resolve(result)
	}

	/**
	 * Dispara los eventos antes de cambiar de paso.
	 * @param {number} stepValue - El valor del paso actual.
	 */
	triggerBeforeChange(stepValue) {
		this.beforeChangeCallbacks.forEach(callback => callback(stepValue, this.previousStepValue))
	}

	/**
	 * Dispara los eventos después de mostrar un paso.
	 * @param {number} stepValue - El valor del paso actual.
	 * @param {number|null} previousStepValue - El valor del paso anterior o null si no hay.
	 */
	triggerAfterChange(stepValue, previousStepValue) {
		this.afterChangeCallbacks.forEach(callback => callback(stepValue, previousStepValue))
	}
}
