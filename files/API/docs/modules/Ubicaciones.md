# Ubicaciones
_Solo si está activo el módulo_

## GET

### {{baseURL}}/locations/countries/
- Autorización: No
- Descripción: Ruta para listar los países
- Parámetros:
	- ids: int[] (opcional) Ids de los países solicitados
- Devolución:
	- Tipo: JSON
	
### {{baseURL}}/locations/states/
- Autorización: No
- Descripción: Ruta para listar los estados
- Parámetros:
	- country: int (opcional) ID del país al que pertenecen
	- ids: int[] (opcional) Ids de los estados solicitados
- Devolución:
	- Tipo: JSON
	
### {{baseURL}}/locations/cities/
- Autorización: No
- Descripción: Ruta para listar las ciudades
- Parámetros:
	- state: int (opcional) ID del estado al que pertenecen
	- ids: int[] (opcional) Ids de los países solicitados
- Devolución:
	- Tipo: JSON
	
### {{baseURL}}/locations/points/
- Autorización: No
- Descripción: Ruta para listar las localidades
- Parámetros:
	- city: int (opcional) ID de las ciudades a las que pertenecen
- Devolución:
	- Tipo: JSON
