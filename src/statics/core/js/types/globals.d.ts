/**
 * El tipo de `pcsphpGlobals` (configurations.js), cerrado: una propiedad que no está aquí da error donde se compruebe el
 * tipo (`// @ts-check`) y no sale en el autocompletado. Una propiedad nueva de `pcsphpGlobals` se añade AQUÍ, también si
 * la crea otro archivo. Sin `import` ni `export`: es un archivo de declaraciones globales, como los JS que lo usan, que
 * son scripts y no módulos.
 */
interface PcsphpGlobals {
    /** El de user-system/main_system_user.js, cuando se carga; `null` hasta entonces. */
    globalAuthenticator: PiecesPHPSystemUserHelper | null
    /** Nombres de los eventos de `window` que lanza configurations.js. */
    events: {
        configurationsLoad: string
        configurationsAndWindowLoad: string
    }
    /** `document.head.baseURI`. */
    baseURL: string
    /** Del meta `config-admin-url`; sin él, `{ relative: true, url: 'admin' }`. */
    adminURLConfig: {
        relative: boolean
        url: string
    }
    /** Del meta `front-configurations` (JSON); `{}` si no llega o no es un objeto. */
    frontConfigurationsFromBackend: Record<string, any>
    /** Del meta `lang-messages-from-server-url`; `null` sin él. */
    langMessagesFromServerURL: string | null
    /** Los grupos ya pedidos por registerDynamicLocalizationMessages(). */
    langMessagesFromServerURLRequested: string[]
    /** Del meta `mapbox-key-url`; `null` sin él. */
    mapboxKeyURL: string | null
    /** Del meta `map-default-center`; `null` si no llega o no es válido, y cada mapa usa el suyo. */
    mapDefaultCenter: {
        longitude: number
        latitude: number
        zoom: number
    } | null
    /** El `lang` de `<html>`, o `es`. */
    lang: string
    /** El `dlang` de `<html>`, o `es`. */
    defaultLang: string
    /** El `set` de los Proxy de `messages`. */
    messagesProxySetHandler: (target: Record<string | symbol, any>, property: string | symbol, value: any) => boolean
    /** Las traducciones: idioma → grupo → clave → texto (o lo que guarde el grupo). */
    messages: Record<string, Record<string, Record<string, any>>>
    /** Del meta `cache-stamp`, o `none`. */
    cacheStamp: string
    /** Los ajustes por omisión del calendario de Fomantic. */
    configCalendar: Record<string, any>
    /** Los ajustes por omisión de DataTables. */
    configDataTables: Record<string, any>
    /** Los ajustes por omisión de Cropper. */
    configCropper: Record<string, any>
    /** Anchos (px) y clases de los puntos de corte. */
    responsive: {
        sizes: Record<'rsXLarge' | 'rsLarge' | 'rsLarge1' | 'rsLarge2' | 'rsLarge3' | 'rsMedium' | 'rsTablet' | 'rsMobileMedium' | 'rsMobile', number>
        class: Record<'rsXLarge' | 'rsLarge' | 'rsLarge1' | 'rsLarge2' | 'rsLarge3' | 'rsMedium' | 'rsTablet' | 'rsMobileMedium' | 'rsMobile', string>
    }
}
