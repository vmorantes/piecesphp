///<reference path="../helpers.js" />
/**
 * Clase que maneja los pasos de registro en la interfaz de usuario.
 * @example 
 * ```js
		const steps = $('[container-steps]') //El paso lo tomará del atributo step de cada elemento
		const triggers = $('[data-to-step]')
		const startOnStep = 1
		const stepsManager = new GenericStepsViewHandler(steps, triggers, startOnStep)
 * ```
 */
class GenericStepsViewHandler {
	/**
	 * Crea una instancia de GenericStepsViewHandler.
	 * @param {$} steps - Un conjunto de elementos $ que representan los pasos de registro.
	 * @param {$} triggers - Un conjunto de elementos $ que actúan como disparadores para cambiar de paso.
	 * @param {number} [initialStep=1] - El paso inicial a mostrar (por defecto 1).
	 */
	constructor(steps, triggers, initialStep = 1) {
		this.steps = steps
		this.triggers = triggers
		this.currentStepValue = initialStep // Guardar el valor del paso actual
		this.previousStepValue = null
		this.onChange = function (step, toStep) { }
		this.beforeChangeCallbacks = []
		this.afterChangeCallbacks = []
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
	 * Cambia al paso especificado.
	 * @param {number} stepValue - El valor del paso al que se desea cambiar.
	 */
	changeStep(stepValue) {
		let instance = this
		this.triggerBeforeChange(this.currentStepValue) // Evento antes de cambiar
		this.triggerOnChangeCallback(stepValue).then(function (canChange) {
			canChange = typeof canChange == 'boolean' ? canChange : true
			if (canChange) {
				instance.hideStep(instance.currentStepValue)
				instance.previousStepValue = instance.currentStepValue // Guardar el paso anterior
				instance.currentStepValue = stepValue // Establecer el nuevo paso
				instance.showStep(instance.currentStepValue)
			}
		})
	}

	/**
	 * Muestra el paso especificado por su valor de atributo 'step'.
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
	 * Obtiene el valor del siguiente paso basado en el valor actual.
	 * @param {number} currentStepValue - El valor del paso actual.
	 * @returns {number|null} - El valor del siguiente paso o null si no hay.
	 */
	getNextStepValue(currentStepValue) {
		const currentStep = this.steps.filter(`[step="${currentStepValue}"]`)
		const nextStep = currentStep.next('[registration-form-step]')
		return nextStep.length ? nextStep.attr('step') : null
	}

	/**
	 * Obtiene el valor del paso anterior basado en el valor actual.
	 * @param {number} currentStepValue - El valor del paso actual.
	 * @returns {number|null} - El valor del paso anterior o null si no hay.
	 */
	getPreviousStepValue(currentStepValue) {
		const currentStep = this.steps.filter(`[step="${currentStepValue}"]`)
		const prevStep = currentStep.prev('[registration-form-step]')
		return prevStep.length ? prevStep.attr('step') : null
	}

	/**
	 * Agrega un callback al ordenar el cambio de paso
	 * @param {function} callback
	 */
	setOnChange(callback) {
		this.onChange = typeof callback == 'function' ? callback : this.onChange
	}

	/**
	 * Agrega un callback que se ejecutará antes de cambiar de paso.
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