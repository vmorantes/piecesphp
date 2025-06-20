/**
 * @function IndexedDBAdapter
 * @param {IndexedDBAdapterConfiguration} configurations
 * @param {IndexedDBAdapterTableStore[]} stores
 * @param {String} instanceID Si ya existe se asignará uno nuevo
 */
function IndexedDBAdapter(configurations = {}, stores = [], instanceID = null) {

	const langGroup = 'IndexedDBAdapter'
	const loaderName = 'IndexedDBAdapter_INIT'

	IndexedDBAdapter.registerDynamicMessages(langGroup)

	/**
	 * @type {IndexedDBAdapterConfiguration}
	 */
	const adapterConfigurarionDefault = {
		databaseName: {
			required: true,
			defaultValue: null,
			validate: (v) => typeof v == 'string' && v.length > 0,
			parse: (v) => v,
		},
		databaseVersion: {
			required: false,
			defaultValue: 1,
			validate: (v) => typeof v == 'number',
			parse: (v) => v,
		},
	}

	/**
	 * @type {IndexedDBAdapterTableStore}
	 */
	const tableStoreDefault = {
		name: {
			required: true,
			defaultValue: null,
			validate: (v) => typeof v == 'string' && v.length > 0,
			parse: (v) => v,
		},
		key: {
			required: false,
			defaultValue: 'id',
			validate: (v) => typeof v == 'string' && v.length > 0,
			parse: (v) => v,
		},
		autoIncrement: {
			required: false,
			defaultValue: true,
			validate: (v) => typeof v == 'boolean',
			parse: (v) => v,
		},
		columns: {
			required: true,
			defaultValue: null,
			validate: (v) => {

				let valid = true

				try {

					if (Array.isArray(v) && v.length > 0) {
						for (const index in v) {
							processConfigurarion(tableStoreColumnDefault, Object.assign({}, v[index]), 'columnsStore')
						}
					} else {
						valid = false
					}

				} catch {
					valid = false
				}

				return valid
			},
			parse: (v) => {

				for (const index in v) {
					v[index] = processConfigurarion(tableStoreColumnDefault, Object.assign({}, v[index]), 'columnsStore')
					if (v[index].keyPath === null) {
						v[index].keyPath = v[index].name
					}
				}

				return v
			},
		},
	}

	/**
	 * @type {IndexedDBAdapterTableStoreColumn}
	 */
	const tableStoreColumnDefault = {
		name: {
			required: true,
			defaultValue: null,
			validate: (v) => typeof v == 'string' && v.length > 0,
			parse: (v) => v,
		},
		keyPath: {
			required: false,
			defaultValue: null,
			validate: (v) => typeof v == 'string' && v.length > 0,
			parse: (v) => v,
		},
		unique: {
			required: false,
			defaultValue: false,
			validate: (v) => typeof v == 'boolean',
			parse: (v) => v,
		},
		multiEntry: {
			required: false,
			defaultValue: false,
			validate: (v) => typeof v == 'boolean',
			parse: (v) => v,
		},
	}

	/**
	 * @type {IndexedDBAdapter}
	 */
	const instance = this

	/**
	 * @type {Set<String>}
	 */
	const databaseStoresNames = new Set()

	/**
	 * @type {IDBDatabase}
	 */
	let database = null

	/**
	 * @type {Boolean}
	 */
	let isReady = false

	/**
	 * @type {Object} events
	 */
	let events = {}

	/**
	 * @type {HTMLElement} eventer
	 */
	let eventer = document.createElement('div')

	instanceID = typeof instanceID == 'string' && !IndexedDBAdapter.instances.has(instanceID) ? instanceID : generateUniqueID()
	IndexedDBAdapter.instances.set(instanceID, instance)

	showGenericLoader(loaderName)

	/** 
	 * @param {String} eventName 
	 * @param {Function} callback 
	 * @returns {IndexedDBAdapter}
	 */
	this.on = function (eventName, callback) {

		eventName = typeof eventName == 'string' && eventName.trim().length > 0 ? eventName.trim() : null
		callback = typeof callback == 'function' ? callback : null

		if (eventName !== null && callback !== null) {

			if (typeof events[eventName] == 'undefined') {
				events[eventName] = {}
			}

			const codeCallback = generateUniqueID()
			events[eventName][codeCallback] = function () {
				callback()
			}

			let addEvent = true

			if (eventName === IndexedDBAdapter.events.DatabaseReady) {

				if (instance.isReady()) {
					callback()
					addEvent = false
				}

			}

			if (addEvent) {
				eventer.addEventListener(`IndexedDBAdapterCustomEvent-${eventName}`, events[eventName][codeCallback])
			}

		}

		return this

	}

	/** 
	 * @param {String} eventName
	 * @returns {IndexedDBAdapter}
	 */
	this.off = function (eventName) {

		eventName = typeof eventName == 'string' && eventName.trim().length > 0 ? eventName.trim() : null

		if (eventName !== null) {

			if (typeof events[eventName] == 'object') {

				for (const codeCallback in events[eventName]) {
					const callback = events[eventName][codeCallback]
					eventer.removeEventListener(`IndexedDBAdapterCustomEvent-${eventName}`, callback)
				}

			}

		}

		return this

	}

	/**
	 * @param {String} storeName 
	 * @param {Object} data 
	 * @returns {Promise} En resolve no devuelve nada, en reject devuelve un objeto con las propiedades message y error
	 */
	this.addRecord = function (storeName, data = {}) {

		const loaderName = 'addRecord_' + generateUniqueID()

		showGenericLoader(loaderName)

		return new Promise(function (resolve, reject) {

			if (instance.isReady()) {

				if (databaseStoresNames.has(storeName)) {

					const transaction = database.transaction(storeName, 'readwrite')
					const addRequest = transaction.objectStore(storeName).add(data)

					addRequest.onerror = function (e) {
						reject({
							message: `Error al guardar la información`,
							error: e
						})
					}

					transaction.oncomplete = function (e) {
						resolve()
					}

				} else {
					reject({
						message: `El store ${storeName} no existe.`,
						error: null
					})
				}

			} else {
				reject({
					message: `La base de datos ${configurations.databaseName} aún no está lista.`,
					error: null
				})
			}

		}).finally(() => removeGenericLoader(loaderName))

	}

	/**
	 * @param {String} storeName 
	 * @param {String} columnReferenceKey 
	 * @param {String} columnReferenceValue 
	 * @param {Object} data 
	 * @returns {Promise} En resolve devuelve el objeto resultante, en reject devuelve un objeto con las propiedades message y error
	 */
	this.updateRecord = function (storeName, columnReferenceKey, columnReferenceValue, data = {}) {

		const loaderName = 'updateRecord_' + generateUniqueID()
		data = typeof data == 'object' ? data : {}

		showGenericLoader(loaderName)

		return new Promise(function (resolve, reject) {

			if (instance.isReady()) {

				if (databaseStoresNames.has(storeName)) {

					const transaction = database.transaction(storeName, 'readwrite')
					const index = transaction.objectStore(storeName).index(columnReferenceKey)
					const cursorRequest = index.openCursor()

					cursorRequest.onsuccess = function (e) {

						/**
						 * @type {IDBCursor}
						 */
						const cursor = e.target.result

						if (cursor instanceof IDBCursor) {

							const currentData = cursor.value

							if (currentData[columnReferenceKey] === columnReferenceValue) {

								for (const inputProperty in data) {

									const propertyExists = Object.keys(currentData).indexOf(inputProperty) !== -1
									if (inputProperty !== columnReferenceKey && inputProperty !== 'id' && propertyExists) {
										currentData[inputProperty] = data[inputProperty]
									}

								}

								const updateRequest = cursor.update(currentData)
								updateRequest.onsuccess = function () {
									resolve(currentData)
								}

							} else {
								cursor.continue()
							}

						} else {
							resolve(null)
						}

					}

					cursorRequest.onerror = function (e) {
						reject({
							message: `Error al actualizar la información`,
							error: e,
						})
					}

				} else {
					reject({
						message: `El store ${storeName} no existe.`,
						error: null,
					})
				}

			} else {
				reject({
					message: `La base de datos ${configurations.databaseName} aún no está lista.`,
					error: null,
				})
			}

		}).finally(() => removeGenericLoader(loaderName))

	}

	/**
	 * @param {String} storeName 
	 * @param {String} columnReferenceKey 
	 * @param {String} columnReferenceValue 
	 * @returns {Promise} En resolve devuelve el objeto del registro, en reject devuelve un objeto con las propiedades message y error
	 */
	this.getRecord = function (storeName, columnReferenceKey, columnReferenceValue) {

		const loaderName = 'getRecord_' + generateUniqueID()

		showGenericLoader(loaderName)

		return new Promise(function (resolve, reject) {

			if (instance.isReady()) {

				if (databaseStoresNames.has(storeName)) {

					const transaction = database.transaction(storeName, 'readonly')
					const getRequest = transaction.objectStore(storeName).index(columnReferenceKey).get(columnReferenceValue)

					getRequest.onsuccess = function (e) {

						const record = {}

						for (const property in e.target.result) {
							const value = e.target.result[property]
							record[property] = value
						}

						resolve(record)
					}

					getRequest.onerror = function (e) {
						reject({
							message: `Error al recuperar la información`,
							error: e,
						})
					}

				} else {
					reject({
						message: `El store ${storeName} no existe.`,
						error: null,
					})
				}

			} else {
				reject({
					message: `La base de datos ${configurations.databaseName} aún no está lista.`,
					error: null,
				})
			}

		}).finally(() => removeGenericLoader(loaderName))

	}

	/**
	 * @param {String} storeName 
	 * @returns {Promise} En resolve devuelve un array de objetos con los registros, en reject devuelve un objeto con las propiedades message y error
	 */
	this.getAllRecords = function (storeName) {

		const loaderName = 'getAllRecords_' + generateUniqueID()

		showGenericLoader(loaderName)

		return new Promise(function (resolve, reject) {

			if (instance.isReady()) {

				if (databaseStoresNames.has(storeName)) {

					const transaction = database.transaction(storeName, 'readonly')
					const getRequest = transaction.objectStore(storeName).getAll()

					getRequest.onsuccess = function (e) {
						const records = Array.isArray(e.target.result) ? e.target.result : []
						resolve(records)
					}

					getRequest.onerror = function (e) {
						reject({
							message: `Error al recuperar la información`,
							error: e,
						})
					}

				} else {
					reject({
						message: `El store ${storeName} no existe.`,
						error: null,
					})
				}

			} else {
				reject({
					message: `La base de datos ${configurations.databaseName} aún no está lista.`,
					error: null,
				})
			}

		}).finally(() => removeGenericLoader(loaderName))

	}

	/**
	 * @param {String} storeName 
	 * @param {String} columnReferenceKey 
	 * @param {String} columnReferenceValue 
	 * @returns {Promise} En resolve devuelve un array de objetos con los registros, en reject devuelve un objeto con las propiedades message y error
	 */
	this.getAllRecordsBy = function (storeName, columnReferenceKey, columnReferenceValue) {

		const loaderName = 'getAllRecordsBy_' + generateUniqueID()

		showGenericLoader(loaderName)

		return new Promise(function (resolve, reject) {

			if (instance.isReady()) {

				if (databaseStoresNames.has(storeName)) {

					const transaction = database.transaction(storeName, 'readonly')
					const getRequest = transaction.objectStore(storeName).index(columnReferenceKey).getAll(columnReferenceValue)

					getRequest.onsuccess = function (e) {
						const records = Array.isArray(e.target.result) ? e.target.result : []
						resolve(records)
					}

					getRequest.onerror = function (e) {
						reject({
							message: `Error al recuperar la información`,
							error: e,
						})
					}

				} else {
					reject({
						message: `El store ${storeName} no existe.`,
						error: null,
					})
				}

			} else {
				reject({
					message: `La base de datos ${configurations.databaseName} aún no está lista.`,
					error: null,
				})
			}

		}).finally(() => removeGenericLoader(loaderName))

	}

	/**
	 * @param {String} storeName 
	 * @param {String} columnReferenceKey 
	 * @param {String} columnReferenceValue 
	 * @returns {Promise} En resolve devuelve el objeto del registro eliminado o null si ninguno coincide, en reject devuelve un objeto con las propiedades message y error
	 */
	this.removeRecord = function (storeName, columnReferenceKey, columnReferenceValue) {

		const loaderName = 'removeRecord_' + generateUniqueID()
		data = typeof data == 'object' ? data : {}

		showGenericLoader(loaderName)

		return new Promise(function (resolve, reject) {

			if (instance.isReady()) {

				if (databaseStoresNames.has(storeName)) {

					const transaction = database.transaction(storeName, 'readwrite')
					const index = transaction.objectStore(storeName).index(columnReferenceKey)
					const cursorRequest = index.openCursor()

					cursorRequest.onsuccess = function (e) {

						/**
						 * @type {IDBCursor}
						 */
						const cursor = e.target.result

						if (cursor instanceof IDBCursor) {

							const currentData = cursor.value

							if (currentData[columnReferenceKey] === columnReferenceValue) {

								const updateRequest = cursor.delete()
								updateRequest.onsuccess = function () {
									resolve(currentData)
								}

							} else {
								cursor.continue()
							}

						} else {
							resolve(null)
						}

					}

					cursorRequest.onerror = function (e) {
						reject({
							message: `Error al eliminar la información`,
							error: e,
						})
					}

				} else {
					reject({
						message: `El store ${storeName} no existe.`,
						error: null,
					})
				}

			} else {
				reject({
					message: `La base de datos ${configurations.databaseName} aún no está lista.`,
					error: null,
				})
			}

		}).finally(() => removeGenericLoader(loaderName))

	}


	/** 
	 * @returns {IDBDatabase}
	 */
	this.database = function () {
		return database
	}

	/** 
	 * @returns {Boolean}
	 */
	this.isReady = function () {
		return isReady
	}

	/** 
	 * @returns {String}
	 */
	this.getInstanceID = function () {
		return instanceID
	}

	/** 
	 * @returns {Set<String>}
	 */
	this.databaseStoresNames = function () {
		return databaseStoresNames
	}

	//Construcción
	configurations = typeof configurations == 'object' ? configurations : {}
	stores = Array.isArray(stores) && stores.length > 0 ? stores : []
	try {

		configurations = processConfigurarion(adapterConfigurarionDefault, Object.assign({}, configurations), 'configurations')

		if (stores.length == 0) {
			throw new Error(`Es obligatorio definir los "stores".`)
		}

		for (const index in stores) {
			stores[index] = processConfigurarion(tableStoreDefault, Object.assign({}, stores[index]), 'stores')
		}

		initializeIndexDB(configurations.databaseName, configurations.databaseVersion, stores).then(function () {
			dispatch(IndexedDBAdapter.events.DatabaseReady)
		}).finally(() => removeGenericLoader(loaderName))

	} catch (error) {
		throw error
	}

	/**
	 * @param {String} databaseName
	 * @param {Number} databaseVersion
	 * @param {IndexedDBAdapterTableStore[]} stores
	 * @returns {Promise<Boolean,IDBOpenDBRequest|null>}
	 */
	function initializeIndexDB(databaseName, databaseVersion, stores) {

		return new Promise(function (resolve, reject) {

			if (!instance.isReady()) {

				/**
				 * @param {IDBDatabase} database 
				 */
				const updateStoreNames = function (database) {
					const storesNames = Array.from(database.objectStoreNames)
					for (const storeName of storesNames) {
						if (!databaseStoresNames.has(storeName)) {
							databaseStoresNames.add(storeName)
						}
					}
				}

				//Solicitar creación de base de datos
				const DBOpenRequest = indexedDB.open(databaseName, databaseVersion)

				//En caso de error
				DBOpenRequest.onerror = function (e) {
					console.error(`La base de datos "${databaseName}" no pudo abrise.`)
					reject(false, e)
				}

				//En caso de éxito
				DBOpenRequest.onsuccess = function (e) {

					database = DBOpenRequest.result
					console.info(`Base de datos "${databaseName}" abierta.`)

					if (instance.isReady() || instance.database() !== null) {
						isReady = true
						updateStoreNames(database)
						resolve(true, DBOpenRequest)
					}

				}

				//Creación de almacen (como una tabla)
				DBOpenRequest.onupgradeneeded = function (e) {

					/**
					 * @type {IDBDatabase}
					 */
					database = DBOpenRequest.result

					updateStoreNames(database)

					for (const storeConfig of stores) {

						const storeName = storeConfig.name
						const storeKey = storeConfig.key
						const autoIncrement = storeConfig.autoIncrement
						const storeColumns = storeConfig.columns

						if (!databaseStoresNames.has(storeName)) {

							const store = database.createObjectStore(storeName, {
								keyPath: storeKey,
								autoIncrement: autoIncrement,
							})

							for (const column of storeColumns) {
								const columnName = column.name
								const columnKeyPath = column.keyPath
								const columnUnique = column.unique
								const columnMultiEntry = column.multiEntry
								store.createIndex(columnName, columnKeyPath, {
									unique: columnUnique,
									multiEntry: columnMultiEntry,
								})
							}

							databaseStoresNames.add(storeName)

						}

					}

				}

			} else {
				resolve(true, null)
			}

		})

	}

	/**
	 * @param {Object} baseConfiguration 
	 * @param {Object} inputConfiguration 
	 * @param {String} paramName 
	 * @returns {Object}
	 */
	function processConfigurarion(baseConfiguration, inputConfiguration, paramName = '') {

		const validated = []

		for (const property in baseConfiguration) {

			const configDefault = baseConfiguration[property]

			const required = configDefault['required']
			const defaultValue = configDefault['defaultValue']
			const validate = configDefault['validate']
			const parse = configDefault['parse']

			const valueInput = inputConfiguration[property]
			const valueWasInput = typeof valueInput !== 'undefined'

			if (validated.indexOf(property) === -1) {

				if (!valueWasInput) {

					if (!required) {
						inputConfiguration[property] = defaultValue
					} else {
						throw new Error(`El valor ${property} es obligatorio ${paramName.length > 0 ? '(' + paramName + ')' : ''}.`.trim())
					}

				} else {

					if (validate(valueInput)) {
						inputConfiguration[property] = parse(valueInput)
					} else {
						throw new Error(`El valor en ${property} es incorrecto ${paramName.length > 0 ? '(' + paramName + ')' : ''}.`.trim())
					}

				}

			}

			validated.push(property)

		}

		return inputConfiguration
	}

	/** 
	 * @param {String} eventName 
	 * @returns {IndexedDBAdapter}
	 */
	function dispatch(eventName) {

		eventName = typeof eventName == 'string' && eventName.trim().length > 0 ? eventName.trim() : null

		if (eventName !== null) {
			eventer.dispatchEvent(new Event(`IndexedDBAdapterCustomEvent-${eventName}`))
		}

		return this

	}

}

/**
 * @type {Map<String,IndexedDBAdapter>}
 */
IndexedDBAdapter.instances = new Map()
/**
 * @param {String} id
 * @returns {IndexedDBAdapter|null}
 */
IndexedDBAdapter.instanceByID = function (id) {
	const instances = IndexedDBAdapter.instances
	return instances.has(id) ? instances.get(id) : null
}
/**
 * @property {String} DatabaseReady Devuelve los parámetros: database
 */
IndexedDBAdapter.events = {
	DatabaseReady: 'DatabaseReady',
}

/**
 * @param {String} name 
 * @returns {void}
 */
IndexedDBAdapter.registerDynamicMessages = function (name) {

	if (typeof pcsphpGlobals != 'object') {
		pcsphpGlobals = {}
	}
	if (typeof pcsphpGlobals.messages != 'object') {
		pcsphpGlobals.messages = {}
	}
	if (typeof pcsphpGlobals.messages.es != 'object') {
		pcsphpGlobals.messages.es = {}
	}
	if (typeof pcsphpGlobals.messages.en != 'object') {
		pcsphpGlobals.messages.en = {}
	}

	let es = {}

	let en = {}

	for (let i in es) {
		if (typeof pcsphpGlobals.messages.es[name] == 'undefined') pcsphpGlobals.messages.es[name] = {}
		pcsphpGlobals.messages.es[name][i] = es[i]
	}

	for (let i in en) {
		if (typeof pcsphpGlobals.messages.en[name] == 'undefined') pcsphpGlobals.messages.en[name] = {}
		pcsphpGlobals.messages.en[name][i] = en[i]
	}

}

/**
 * @typedef {Object} IndexedDBAdapterConfiguration
 * @property {String} databaseName
 * @property {Number} [databaseVersion=1]
 */
/**
 * @typedef {Object} IndexedDBAdapterTableStore
 * @property {String} name
 * @property {String} [key=id]
 * @property {Boolean} [autoIncrement=true]
 * @property {IndexedDBAdapterTableStoreColumn[]} columns
 */
/**
 * @typedef {Object} IndexedDBAdapterTableStoreColumn
 * @property {String} name
 * @property {String} [keyPath] Se configura por defecto igual que name
 * @property {Boolean} [unique=false]
 * @property {Boolean} [multiEntry=false]
 */
