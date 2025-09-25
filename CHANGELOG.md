# 6.4.200002

- Ajuste de SQL a utf8mb4.
- Otros ajustes menores.

# 6.4.200001

- Ajustes para CORS.
- Algunos ajustes en mailing.
- Mejor gestión de errores en rutas 404.

# 6.4.2

- Eliminación de console.log innecesarios.
- Independización de archivos que gestionan la traducción con IA en el front.
- Internacionalización:
    - Mejora en revisión de traducciones pendientes.
    - Optimización de adaptadores de modelo IA para mejor manejo de las traducciones.
    - Fragmentos grandes de HTML se dividen en traducción con IA, deben ser especificados en asHTMLProperties.
    - Se destruye la conexión con la base de datos actual para evitar el error de "MySQL server has gone away" por tiempo de espera en la traducción con IA.
    - Se añade en lang.php la configuración DYNAMIC_TRANSLATIONS para gestionar elementos relevantes del sistema de inyección dinámica de mensajes de traducción.
    - Gestión de JSON de "traducciones" dinámicas se reemplaza por GeneriContentPseudoMapper.
    - Se simplifica la lógica de translations/saveGroup por solo interacción con base de datos.
    - Introducción de DynamicTranslationsHelper para persistencia de traducciones dinámicas. Ahora la base de datos se usa solo como un estado intermedio para guardar las traducciones "pendientes" y los mensajes fijos se circuncriben a un JSON denominado current-translations. Se gestiona fechas de actualización para no leer innecesariamente desde la base de datos. Se refactoriza add-dynamic-translations.php
- Servido estático de archivos personalizado:
    - Mejora en el servido de archivos estáticos desde los módulos.
    - Refactorización de ServerStatics.php.
    - ServerStatics.php crea enlaces simbólicos en statics/server-delegated para tener que servirlos siempre con PHP.
    - Los métodos staticRoute ahora se soportan con staticRouteModulesResolver de container para hacer funcionar la lógica de ServerStatics.php anteriormente descrita. Valida si el enlace simbólico existe.
- Bases de datos:
    - En BaseModel si introdujo gestión de tiempo de ejecución de MySQL con PDO::ATTR_TIMEOUT basado en 'max_execution_time' de PHP.
- Sesión:
    - Corregido: Ahora se toma en cuenta distintos estados de organización que son candidatos para habilitar el ingreso.
- Configuraciones en config.php:
    - Se introducen: domain, domain_protocol, base_domain_path, base_url.
        - i.e.: domain.tld, https://, /ruta/base/src, https://domain.tld/ruta/base/src
- En librerías base del framework:
    - Loader general:
        - Modulizarización de showGenericLoader, removeGenericLoader activeGenericLoader por un manejador desde una clases.
        - Se mejoró la lógica interna y se añadió la posibilidad mostrar un mensaje.
    - Se independizó la función genericFormHandler hacia un archivo único.
- En el adaptador del editor CKEditor:
    - Se añadió insertLink para permitir la posibilidad de carga de cualquier tipo de archivo como link.
    - Se hizo el ajuste correspondiente en la gestión del manejador de archivos.
- Se recomienda el tag en comentarios @category SpecialCaseSolution para soluciones particulares de modo que sean fáciles de buscar.
- Llaves mapbox se manejan desde "variables de entorno".

# 6.4.2-beta

- Ajuste de bug que hacía que se registraran sesiones "expiradas" sin motivo.
- Separación de require-dev del composer principal hacia bin/tools.
- Mejoramiento de base de código js del framework:
    - CookiesHandler.
    - GenericStepsViewHandler.
    - Mejor gestión de adición de librerías adicionales en helpers mediante combinación en gulp con helpers-lib/*
    - Exposiciones de pcsAdminSideBarIsOpen y pcsAdminSideBarToggle para manipular el sidebar del menú.
    - Manejo de persistencia de estado (plegado/desplegado) del sidebar con localStorage.
    - registerDynamicLocalizationMessages y relacionados puede cargar múltiples grupos simultáneamente.
    - Adición ignoreSearch en MapBoxAdapter para casos en los que no se quiera ejecutar la búsqueda de forma automática.
- Vista de reporte integrada en front.
- Internacionalización:
    - Adición de mensajes, en general.
    - Optimización de manejo persistente de idiomas, preferencia según navegador y otras mejoras. Se delega el manejor pleno a Config.php
- Ajustes de permisos según organizaciones y de sistema de aprobaciones.
- Ajustes de algunas opciones por defecto en inicio de MySpace.
- Simplificación general de archivos delete-config.js en términos de internacionalización. Es el primer paso para le delegación completa al sistema en lugar de manejarlo en el archivo.
- Mejora en el manejo de errores para renderización de BaseController.
- Mejora de funcionalidad de validaciones en PiecesPHP\Core\Validation\Validator.
- Sesión:
    - El inicio de sesión toma en cuenta estados de usuario y de organización que son candidatos para habilitar el ingreso.
- Sidebar interno diferenciado según tipos de usuario.
- En aprobaciones:
    - Se verifica isActive que se añade dinámicamente por el manejador.
    - Optimización de auto aprobaciones.
- Soporte base de reportes.
- Soporte de "variables de entorno" con GeneriContentPseudoMapper.
- Varios modos de listado base de publicaciones.

# 6.4.0

- Unificación de archivos del módulo de ubicación.
- getPCSPHPConfig a configurations.js.
- Mejora del sistema de traducciones y agrupaciones de mensajes más modularizadas.
    - Actualización de módulo de noticias internas y de publicaciones.
    - Mejoramiento de función de cambio de idioma y manejo persistente de selección (lang_by_cookie, cookie_lang_definer).
    - Búsqueda de traducciones faltantes con scan-missing-lang y registro de faltantes en app/lang/missing-lang-messages.
- Unificación de plantillas de correo en view/mailing/template_base.php y plantilla con poco html en view/mailing/template_base_no_style.php.
- Ampliación de roles de usuarios base.
- Mejora del listado de usuarios.
- Sistema de usuarios con capa de aprobación y mejor acoplado a sistema de organizaciones. Como medida que "prescinde" de esa características se puede dejar la organización base única.
- Sistema de "Perfiles" para usuarios y organizaciones.
- Ajuste de error en DefaultAccessControlModules que hacía que algunas rutas se mostran indebidamente con 403. Se verifica que empiece por la parte comparada del nombre de la ruta que se está buscando.
- Eliminación y reordenamiento de código scss.
- Mejoramiento de LocationsAdapter para trabajar con par país-ciudad (y más) y de MapBoxAdapter para mejorar la búsqueda del geocoder. Y mejoras en general.
- En AttachmentPlaceholder se agregó una opción para nombres personalizados distinto del nombre del archivo.
- Para ROOT, se integra en backend la posibilidad de "conectarse" como otro usuario.
- Adjuntos en Publications es añadible.
- Ajustes de lógica y orden en sistema de reporte de login.
- Ajuste dinámico de algunos permisos según si se es el administrador de una organización.
- Sistema de aprobación, según el que si no se está aprobado el márgen de acción es limitado (integrado con organizaciones, usuarios, convocatorias y publicaciones).
    - BaseEntityMapper intercepta fieldsToSelect (por lo tanto debe definirse como protected) con y devuelve un campo en consulta relacionado al estatus de aprobación (systemApprovalStatus).
- Comentarios @category AddToBackendSidebarMenu para rastrear mejor el uso del menú lateral del backend.
- ContentNavigationHub como un módulo de navegación entre los contenidos de otros módulos internamente.
- Implementación de un sistema de eventos en BaseEventDispatcher. Útil para el sistema de aprobaciones.
    - En BaseEntityMapper se disparan: saving, saved, updating y updated.
    - aseEventDispatcher::dispatch('AddDynamicTransaltions', 'added') para después de añadidas las traducciones dinámicas.

# 6.3.4

- Mejoramiento de multi-idioma.
- Traducción de textos faltantes.
- Integración con IA para traducción.
- Configuración dinámica de IA OpenAI y Mistral.
- Flujo de multi-idioma de Publicaciones mejorado, integración con traducción por IA.
- Acceso a claves seguras con getKeyFromSecureKeys.
- Evento onChange en RichEditorAdapterComponent y método textareaTarget.get(0).updateRichEditor
- onSuccessFinally en genericFormHandler
- PCSPHP-Response-Expected-Language como método de definir un idioma para la respuesta back-end desde front-end (recibe el idioma, ie.: es, en, fr, etc....)
- Mejoramiento de configuraciones finales, se pueden añadir archivos indefinidamente para configuraciones más claras.
- getExtension en FileObject

# 6.3.1

- Módulo de localización mejorado con LocalizationSystem que permite acceder a las traducciones desde front mediante una ruta con registerDynamicLocalizationMessages.
    - Se añade en el header la ruta lang-messages-from-server-url
- onLogout en PiecesPHPGenericHandlerSession.
- Actualización de adminer.
- Estructura de base de datos definida en utf8mb3.
- Organizaciones:
    - Ajustes en permisos.
    - Campos requeridos.
    - Traducciones.
- Ajustes menores en filtro de países.
- Ajustes menores en vistas de recursos de MySpaceController.
- Adición de SurveyJS como plugin frontend integrado.
- GEO_IP en config.php.
- Mejoramiento en manejo de errores 403 y 404.
- Función para devolver banderas según idioma en set_config 'get_fomantic_flag_by_lang', lang.php.
- Más idiomas por defecto.
- Remoción de #[\ReturnTypeWillChange].
- Prevención de inexistencia de constantes de carpeta de errores en GenericHandler.
- Mejor manejo de errores en BaseController.
- Mejoramiento en convert_lang_url y adición de lang2 y getCookie.
- Configuración pcsphp_system_translations contiene todas las traducciones.
- setConfigValue en AppConfigModel para agilizar la creación.
- Tipo de usuario Administrativo => Administrador.
- Ajustes en plantillas de correo.
- Adición de mailing-logo en gestión de imágenes.
- Intentar usar color principal en círculo de carga genérico.
- Configuración alternatives_url_include_current incluye la ruta del idioma actual.
- Configuración calculate_alternatives_langs_urls es una función que recrea las alternatives_url y alternatives_url_include_current.

# V6.3.0

- Independización de módulo importador.
- Manejador de sesiones sin usuario: PiecesPHPGenericHandlerSession, SessionTokenIsolated.
- Ajustes de seguridad en rutas expuestas.
- En módulo de publicaciones cambio de self::view por $this->render sobreescrito para no repetir importación de módulos.
- Unificación y simplificación de plantillas de correo electrónico.
- Nuevos métodos de encriptación bidireccional (BaseHashEncryption).
- Utilidad para crear cookie: setCookieByConfig.
- @strftime para ignorar deprecated.
- TokenModel/TokenController ajustados.
- Ajustes menores en módulo de ubicaciones.
- GoogleReCaptchaV3 ajustado para poder ser desactivado.
- Ajustes en recursos de prueba.

# V6

- Cambio de versión de Slim a v4.
    - Ya no es retrocompatible.
- Verisión mínima de compatibilidad de PHP: 7.4

# V5

- Implementación de la plantilla Editorial de HTML5UP en el front por defecto.
	- Formulario de contacto.
	- Vistas de blog.
	- Slidershow.
- Migración a Gulp 4 para las tareas.
	- Se recomiendan los pasos:
		- npm install
		- npm audit --force -fix
		- npm --force install
- Actualización a JQuery 3.5.1
- PiecesPHPSystemUserHelper.js libre de JQuery (usa Fetch API).
- Creación de CustomNamespace.js para algunas tareas genéricas (con la intención de eliminar helpers.js en el futuro)
	- Slideshow.
	- Desplazamiento suave.
	- Loader.
- Varias modificaciones que no afectan el comportamiento en algunos archivos JS/PHP.
- En el módulo de imágenes (HeroController en PHP) se implemento internacionalización y posibilidad de eliminar.
- Mejoramiento del sistema de traducciones.
- Mejoramiento en el sistema de rutas.
Nota: No hay nigún problema de retro-compatibilidad conocido.
