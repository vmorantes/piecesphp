# Traducciones (IA)

_Solo si está activa la bandera `API_TRANSLATION_MODULE` (ver el [índice](../index.md))._

Rutas para traducir texto con un modelo de IA (adaptadores de OpenAI y Mistral, en `src/app/classes/API/Adapters/`).
Las acciones `translate` y `translateGroup` solo existen si la configuración `translationAIEnable` está activa; si no,
responden **404**.

## GET | POST

### {{baseURL}}/core/api/translations/
Forma corta de `translations/translate/`.

### {{baseURL}}/core/api/translations/translate/
- **Autorización:** Sí
- **Descripción:** traduce uno o varios textos.
- **Parámetros** (en el cuerpo si es POST, en la consulta si es GET):
	- **text:** string|json (requerido) Texto a traducir. Puede ser un string o un objeto JSON con varios campos.
	- **from:** string (requerido) Código del idioma de origen (ej: `es`, `en`, `fr`).
	- **to:** string (requerido) Código del idioma de destino.
	- **asHTMLProperties:** array (opcional) Claves de `text` que se tratan como HTML para segmentarlas sin romperlas.
- **Devolución:**
	- **Tipo:** JSON
	- **Propiedades:**
		- **success:** bool
		- **message:** string
		- **result.translation:** string|object El texto o los textos traducidos.
		- **AI:** uso de tokens (`tokensUsed`, `lastUsage`) y, según el caso, la respuesta original del proveedor.
		- **error:** string, solo si falló el servicio.
	- El idioma de los mensajes sale de `responseExpectedLang` si está configurado.

## POST

### {{baseURL}}/core/api/translations/translateGroup/
- **Autorización:** Sí, y **solo desde el mismo dominio** (si no, **404**). Es la que usa el navegador para las
  traducciones dinámicas.
- **Método:** solo POST; con GET, **405**.
- **Descripción:** recibe **claves, nunca valores**. El servidor traduce las que falten, filtra la respuesta del modelo y
  guarda lo aceptado.
- **Parámetros:**
	- **to:** string (requerido) Idioma de destino.
	- **group:** string (requerido) Grupo de traducciones.
	- **keys:** array<string> o su JSON (requerido) Lista de claves.
- **Devolución:**
	- **Tipo:** JSON. **400** si la entrada no es válida.
	- **Propiedades:** `success`, `message`, `translation` (`{grupo: {clave: traducción}}`), `rejected`, `saved` (int),
	  `error`, `AI.tokensUsed`.

### {{baseURL}}/core/api/translations/saveGroup/
- **Retirada.** Responde siempre **410** con `saved: 0`. Use `translateGroup`.
