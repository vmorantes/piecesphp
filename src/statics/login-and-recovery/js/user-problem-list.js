$(document).ready(function (e) {

	const LOADER_NAME = "RECOVERY_LOADER"
	const recoveryItemUserAndPass = $("[recovery-user-pass]")
	const recoveryModalUserAndPass = $("[modal-password]")

	const recoveryItemUserName = $("[recovery-user-name]")
	const recoveryModalUserName = $("[modal-user-name]")

	const recoveryItemUserBloked = $("[user-blocked]")
	const recoveryModalUserBloked = $("[modal-user-blocked]")

	const userProblemItem = $("[other-problem-item]")
	const userProblemModal = $("[modal-user-support]")

	let lang =
		pcsphpGlobals.lang == pcsphpGlobals.defaultLang
			? ""
			: pcsphpGlobals.lang + "/"
	let counter = 1
	const stepsPlaceCounter = recoveryModalUserAndPass.find("[steps]")
	const back = recoveryModalUserAndPass.find("button.back")
	const hasCode = recoveryModalUserAndPass.find("button[has-code]")
	const allSteps = recoveryModalUserAndPass.find(".steps").children()
	const mainForm = recoveryModalUserAndPass.find("[steps-form]")

	$("#modalNoUserButton").on('click', function (e) {
		e.preventDefault();
		$("#modalNoUser").removeClass("modal-no-user");
	})

	$("#cardModal").on('click', function (e) {
		e.stopPropagation();
	})

	$("#backgrodund-modal").on('click', function () {
		$("#modalNoUser").addClass("modal-no-user");
	})

	// Recuperación de contraseñas 

	back.on("click", (evt) => {
		evt.preventDefault()
		if (counter == 2) {
			$(allSteps[1]).hide()
			$(allSteps[0]).show()
			$("[only-start]").css("opacity", "1")
		} else if (counter == 3) {
			$(allSteps[2]).hide()
			$(allSteps[1]).show()
		} else if (counter <= 1) {
			return
		}
		counter--
		stepsPlaceCounter.text(counter)
	})

	hasCode.on("click", (evt) => {
		evt.preventDefault()
		$(allSteps[0]).hide()
		$(allSteps[1]).show()
		$("[only-start]").css("opacity", "0")
		counter++
		stepsPlaceCounter.text(counter)
	})

	recoveryModalUserAndPass.find("[close-modal]").on("click", (evt) => {
		evt.preventDefault()
		recoveryModalUserAndPass.modal("hide")
	})

	mainForm.on("submit", (evt) => {
		evt.preventDefault()
		showGenericLoader(LOADER_NAME)
		const dataObject = Object.fromEntries(new FormData(evt.target))
		const formData = new FormData()

		if (counter == 1) {
			formData.set("username", dataObject.email)

			postRequest(lang + "users/recovery-code", formData)
				.done((res) => {
					if (res.send_mail || res.success) {
						successMessage(res.message)
						$(allSteps[0]).hide()
						$(allSteps[1]).show()
						$("[only-start]").css("opacity", "0")
						counter++
						stepsPlaceCounter.text(counter)
					} else {
						openModalByType(res.error)
					}
				})
				.always(() => {
					removeGenericLoader(LOADER_NAME)
				})
		} else if (counter == 2) {
			formData.set("code", dataObject.code)

			postRequest(lang + "users/verify-create-password-code", formData)
				.done((res) => {
					if (res.success) {
						successMessage(res.message)
						$("[user-name]").text(res.userName)
						$(allSteps[1]).hide()
						$(allSteps[2]).show()
						counter++
						stepsPlaceCounter.text(counter)
					} else {
						openModalByType(res.error)
					}
				})
				.always(() => {
					removeGenericLoader(LOADER_NAME)
				})
		} else if (counter == 3) {
			formData.set("code", dataObject.code)
			formData.set("password", dataObject.password)
			formData.set("repassword", dataObject.password2)

			postRequest(lang + "users/create-password-code", formData)
				.done((res) => {
					if (res.success) {
						$("[user-name]").text("")
						$(allSteps[2]).hide()
						$(allSteps[0]).show()
						counter = 1
						$("[only-start]").css("opacity", "1")
						stepsPlaceCounter.text(counter)
						mainForm.trigger("reset")
						recoveryModalUserAndPass.modal("hide")
						openModalByType("SUCCESS_PASSWORD_CHANGE")
					} else {
						openModalByType(res.error)
					}
				})
				.always(() => {
					removeGenericLoader(LOADER_NAME)
				})
		}
	})

	recoveryItemUserAndPass.on("click", () => {
		recoveryModalUserAndPass
			.modal({
				closeIcon: false, allowMultiple: true, onShow: () => {
					counter = 1
					stepsPlaceCounter.text(counter)
					allSteps.hide()
					$(allSteps[0]).show()
				}
			})
			.modal("show")
	})

	const openModalByType = (type) => {
		const notificationsModal = $("[notifications-modal]")
		const modalsData = {
			USER_NO_EXISTS: {
				title: _i18n("userProblems", "ERROR CORREO NO REGISTRADO"),
				message: _i18n(
					"userProblems",
					"El correo ingresado no esta asociado a ningún usuario, por favor ingrese otra cuenta de correo o puede crear una solicitud de soporte para asociar ese correo a su cuenta."
				),
				options: `<div class="actions support">
                        <a class="btn support" href="${OTHER_PROBLEMS_FORM}">Solicitar soporte</a>
                    </div>`,
			},
			EXPIRED_OR_NOT_EXIST_CODE: {
				title: _i18n("userProblems", "ERROR CÓDIGO INCORRECTO"),
				message: _i18n(
					"userProblems",
					"El código ingresado esta errado, por favor vuelva a ingresar el código, solicite uno nuevo o puede crear una solicitud de soporte para informar del error."
				),
				options: `<div class="actions code-error">
                        <a class="btn support gray" href="${OTHER_PROBLEMS_FORM}">Solicitar soporte</a>
                        <a class="btn support green" other-code>Nuevo Código</a>
                    </div>`,
			},
			SUCCESS_PASSWORD_CHANGE: {
				title: _i18n("userProblems", "SU CONTRASEÑA HA SIDO CAMBIADA"),
				message: _i18n(
					"userProblems",
					"Ingrese con su usuario y la nueva contraseña"
				),
				options: `<div class="actions login">
                        <a class="btn support" href="${USER_FORM_LOGIN}">Iniciar Sesión</a>
                    </div>`,
			},
		}

		if (Object.keys(modalsData).includes(type)) {
			notificationsModal.attr("modal-type", type)
			notificationsModal.find(".title").text(modalsData[type].title)
			notificationsModal.find("p").text(modalsData[type].message)
			notificationsModal
				.find(".alert-modal-options")
				.html(modalsData[type].options)
		} else {
			notificationsModal
				.find(".title")
				.text(_i18n("userProblems", "ERROR DESCONOCIDO"))
			notificationsModal
				.find("p")
				.text(_i18n("userProblems", "Por favor intente nuevamente."))
		}

		notificationsModal.removeClass("modal-no-user")

		notificationsModal.find(".container-modals").on("click", () => {
			notificationsModal.addClass("modal-no-user")
		})
		notificationsModal.find(".card-ui-center").on("click", (e) => {
			e.stopPropagation()
		})
		notificationsModal.find("[other-code]").on("click", (e) => {
			notificationsModal.addClass("modal-no-user")
			recoveryModalUserAndPass.find("[name='code']").val("")
		})
	}

	// Recuperar usuario

	recoveryItemUserName.on("click", () => {
		recoveryModalUserName
			.modal({
				closeIcon: false, allowMultiple: true, onShow: () => {
					counter = 1
					userNameAllsteps.hide()
					$(userNameAllsteps[0]).show()
					$("[only-start]").css("opacity", "1")
					stepsIndicator.text(counter)
				}
			})
			.modal("show")
	})

	const userNameAllsteps = recoveryModalUserName.find(".steps").children()
	const stepsIndicator = recoveryModalUserName.find("[steps]")
	const userNameBack = recoveryModalUserName.find("button.back")
	const userNameHasCode = recoveryModalUserName.find("button[has-code]")

	userNameBack.on("click", (evt) => {
		evt.preventDefault()
		if (counter == 2) {
			$(userNameAllsteps[1]).hide()
			$(userNameAllsteps[0]).show()
			$("[only-start]").css("opacity", "1")
		} else if (counter == 3) {
			$(userNameAllsteps[2]).hide()
			$(userNameAllsteps[1]).show()
		} else if (counter <= 1) {
			return
		}
		counter--
		stepsIndicator.text(counter)
	})

	userNameHasCode.on("click", (evt) => {
		evt.preventDefault()
		$(userNameAllsteps[0]).hide()
		$(userNameAllsteps[1]).show()
		$("[only-start]").css("opacity", "0")
		counter++
		stepsIndicator.text(counter)
	})

	recoveryModalUserName.find("form").on("submit", (e) => {
		e.preventDefault()

		showGenericLoader(LOADER_NAME)

		const ObjectFormData = Object.fromEntries(new FormData(e.target))
		const formData = new FormData()

		formData.set('type', 'TYPE_USER_FORGET')

		if (counter == 1) {
			formData.set('username', ObjectFormData.email)
			postRequest(lang + "users/user-forget-code", formData)
				.done((res) => {
					if (res.error == 'NO_ERROR') {
						$(userNameAllsteps[0]).hide()
						$(userNameAllsteps[1]).show()
						$("[only-start]").css("opacity", "0")
						counter++
						stepsIndicator.text(counter)
					} else {
						openModalByType(res.error)
					}
				})
				.always(() => {
					removeGenericLoader(LOADER_NAME)
				})
		} else if (counter == 2) {
			formData.set('code', ObjectFormData.code)
			postRequest(lang + "users/get-username", formData)
				.done((res) => {
					if (res.success) {
						$(userNameAllsteps[1]).hide()
						$(userNameAllsteps[2]).show()
						$(userNameAllsteps[2]).find("h3").text(res.message)
						counter++
						stepsIndicator.text(counter)
					} else {
						openModalByType(res.error)
					}
				})
				.always(() => {
					removeGenericLoader(LOADER_NAME)
				})
		}




	})

	recoveryModalUserName.find("[close-modal]").on("click", (evt) => {
		evt.preventDefault()
		recoveryModalUserName.modal("hide")
	})

	// Usuario bloqueado

	const userBlokedAllsteps = recoveryModalUserBloked.find(".steps").children()
	const stepsBlokedIndicator = recoveryModalUserBloked.find("[steps]")
	const userBlockBack = recoveryModalUserBloked.find("button.back")
	const userBlockHasCode = recoveryModalUserBloked.find("button[has-code]")

	recoveryItemUserBloked.on("click", () => {
		recoveryModalUserBloked
			.modal({
				closeIcon: false, allowMultiple: true, onShow: () => {
					counter = 1
					userBlokedAllsteps.hide()
					$(userBlokedAllsteps[0]).show()
					$("[only-start]").css("opacity", "1")
					stepsBlokedIndicator.text(counter)
				}
			})
			.modal("show")
	})

	userBlockBack.on("click", (evt) => {
		evt.preventDefault()
		if (counter == 2) {
			$(userBlokedAllsteps[1]).hide()
			$(userBlokedAllsteps[0]).show()
			$("[only-start]").css("opacity", "1")
		} else if (counter == 3) {
			$(userBlokedAllsteps[2]).hide()
			$(userBlokedAllsteps[1]).show()
		} else if (counter <= 1) {
			return
		}
		counter--
		stepsBlokedIndicator.text(counter)
	})

	userBlockHasCode.on("click", (evt) => {
		evt.preventDefault()
		$(userBlokedAllsteps[0]).hide()
		$(userBlokedAllsteps[1]).show()
		$("[only-start]").css("opacity", "0")
		counter++
		stepsBlokedIndicator.text(counter)
	})

	recoveryModalUserBloked.find("form").on("submit", (e) => {
		e.preventDefault()

		showGenericLoader(LOADER_NAME)

		const ObjectFormData = Object.fromEntries(new FormData(e.target))
		const formData = new FormData()

		formData.set('type', 'TYPE_USER_BLOCKED')

		if (counter == 1) {
			formData.set('username', ObjectFormData.email)
			postRequest(lang + "users/user-blocked-code", formData)
				.done((res) => {
					if (res.error == 'NO_ERROR') {
						$(userBlokedAllsteps[0]).hide()
						$(userBlokedAllsteps[1]).show()
						$("[only-start]").css("opacity", "0")
						counter++
						stepsBlokedIndicator.text(counter)
					} else {
						openModalByType(res.error)
					}
				})
				.always(() => {
					removeGenericLoader(LOADER_NAME)
				})
		} else if (counter == 2) {
			formData.set('code', ObjectFormData.code)
			postRequest(lang + "users/unblock-user", formData)
				.done((res) => {
					if (res.error == 'NO_ERROR') {
						$(userBlokedAllsteps[1]).hide()
						$(userBlokedAllsteps[2]).show()
						$(userBlokedAllsteps[2]).find("h3").text(res.message)
						counter++
						stepsBlokedIndicator.text(counter)
					} else {
						openModalByType(res.error)
					}
				})
				.always(() => {
					removeGenericLoader(LOADER_NAME)
				})
		}

	})

	recoveryModalUserBloked.find("[close-modal]").on("click", (evt) => {
		evt.preventDefault()
		recoveryModalUserBloked.modal("hide")
	})

	// Solicitud de soporte

	userProblemItem.on('click', () => {
		userProblemModal.modal({
			closeIcon: false, allowMultiple: true, onShow: () => {
				$("#modalNoUser").addClass("modal-no-user");
				userProblemModal.find('form').find('button[type=submit]').show()
				supportAllSteps.hide()
				$(supportAllSteps[0]).show()
			}
		}).modal("show")
	})

	const supportAllSteps = userProblemModal.find(".steps").children()

	userProblemModal.find('form').on('submit', (e) => {
		e.preventDefault()

		const formData = new FormData(e.target)

		showGenericLoader(LOADER_NAME)

		postRequest(lang + 'users/other-problems', formData)
			.done((res) => {
				if (res.send_mail) {
					$(supportAllSteps[0]).hide()
					$(supportAllSteps[1]).show()
					userProblemModal.find('form').find('button[type=submit]').hide()
				} else {
					openModalByType(res.error)
				}
			})
			.always(() => {
				removeGenericLoader(LOADER_NAME)
			})
	})

	userProblemModal.find("[close-modal]").on("click", (evt) => {
		evt.preventDefault()
		userProblemModal.modal("hide")
	})

})
