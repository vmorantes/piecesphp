# Traducciones (IA)

Rutas para realizar traducciones de texto utilizando modelos de Inteligencia Artificial (OpenAI o Mistral).

## GET | POST

### {{baseURL}}/core/api/translations/
- **Autorización:** Sí
- **Descripción:** Realiza la traducción de uno o varios textos.
- **Parámetros:**
	- **text:** string|json (requerido) Texto a traducir. Puede ser un string simple o un objeto JSON con múltiples campos.
	- **from:** string (requerido) Código del idioma de origen (ej: `es`, `en`, `fr`).
	- **to:** string (requerido) Código del idioma de destino.
	- **asHTMLProperties:** array (opcional) Lista de claves en `text` que deben tratarse como HTML para una segmentación segura.
- **Devolución:**
	- **Tipo:** JSON
	- **Propiedades:**
		- **success:** bool
		- **result:** 
			- **translation:** string|object El texto o textos traducidos.
		- **AI:** Información de uso de tokens y modelo utilizado.

### {{baseURL}}/core/api/translations/saveGroup/
- **Autorización:** Sí
- **Descripción:** Guarda un grupo de traducciones en el sistema de archivos del framework.
- **Parámetros:**
	- **text:** json (requerido) Conjunto de traducciones.
	- **to:** string (requerido) Idioma de destino.
	- **saveGroup:** string (requerido) Nombre del grupo de idiomas (archivo).
