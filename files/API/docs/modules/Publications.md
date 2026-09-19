# Publications
_Solo si está activo el módulo_

## GET

### {{baseURL}}/core/api/publications/publications/list/
- Autorización: Sí
- Descripción: Ruta que devuelve el listado de las publicaciones
- Parámetros:
	- page: integer (opcional) El avance dentro de la paginación de los resultados
	- perPage: integer (opcional) Cantidad de elementos por página
	- category: integer (opcional) Identificador de la categoría
	- title: text (opcional) Título (total o parcial)
- Devolución:
	- Tipo: JSON
	- Propiedades: 
		- elements: array<JSON> Los elementos
		- isFinal: bool Informa si la página actual es la última
		- nextPage: int Siguiente página
		- page: int Página actual
		- parsedElements: mixed Elementos modificados, puede variar
		- perPage: int Cantidad de elemento por pagina
		- prevPage: int Página anterior
		- totalElements: int Elementos totales
		- totalPages: int Cantidad total de páginas
	- Ejemplo:
```js
//Solicitud
var xhr = new XMLHttpRequest();
xhr.withCredentials = true;

xhr.addEventListener("readystatechange", function() {
  if(this.readyState === 4) {
    console.log(this.responseText);
  }
});

xhr.open("GET", "https://domain.tld/core/api/publications/publications/list/?page=1&perPage=10");

xhr.send();

//Respuesta
{
    "elements": [],
    "isFinal": true,
    "nextPage": 1,
    "page": 1,
    "parsedElements": [],
    "perPage": 10,
    "prevPage": 1,
    "totalElements": 0,
    "totalPages": 1
}
```

### {{baseURL}}/core/api/publications/publications/detail/
- Autorización: Sí
- Descripción: Datos de un elemento
- Parámetros:
	- id: integer (requerido) El ID del elemento
- Devolución:
	- Tipo: JSON
	- Propiedades:
		- publicationData: JSON|null La información del elemento o null en caso de no encontrar coincidencias
	- Ejemplo:
```js
//Solicitud
var xhr = new XMLHttpRequest();
xhr.withCredentials = true;

xhr.addEventListener("readystatechange", function() {
  if(this.readyState === 4) {
    console.log(this.responseText);
  }
});

xhr.open("GET", "https://domain.tld/core/api/publications/publications/detail/?id=1");

xhr.send();

//Respuesta
{
    "publicationData": {
        "id": ...,
        "preferSlug": ...,
        "title": ...,
        "content": ...,
        "seoDescription": ..,
        "author": {
            ...,
            ...,
            ...,
        },
        "category": {
            ...,
            ...,
            ...,
        },
        "mainImage": ...,
        "thumbImage": ...,
        "ogImage": ...,
        "folder": ...,
        "visits": ...,
        "publicDate": ...,
        "startDate": ...,
        "endDate": ...,
        "createdAt": ...,
        "updatedAt": ...,
        "createdBy": ...,
        "modifiedBy": ...,
        "status": ...,
        "featured": ...
    }
}
```

### {{baseURL}}/core/api/publications/categories/list/
- Autorización: Sí
- Descripción: Ruta que devuelve el listado de las categorías
- Parámetros:
	- page: integer (opcional) El avance dentro de la paginación de los resultados
	- perPage: integer (opcional) Cantidad de elementos por página
- Devolución:
	- Tipo: JSON
	- Propiedades: 
		- elements: array<JSON> Los elementos
		- isFinal: bool Informa si la página actual es la última
		- nextPage: int Siguiente página
		- page: int Página actual
		- parsedElements: mixed Elementos modificados, puede variar
		- perPage: int Cantidad de elemento por pagina
		- prevPage: int Página anterior
		- totalElements: int Elementos totales
		- totalPages: int Cantidad total de páginas
	- Ejemplo:
```js
//Solicitud
var xhr = new XMLHttpRequest();
xhr.withCredentials = true;

xhr.addEventListener("readystatechange", function() {
  if(this.readyState === 4) {
    console.log(this.responseText);
  }
});

xhr.open("GET", "https://domain.tld/core/api/publications/categories/list/?page=1&perPage=10");

xhr.send();

//Respuesta
{
    "elements": [],
    "isFinal": true,
    "nextPage": 1,
    "page": 1,
    "parsedElements": [],
    "perPage": 10,
    "prevPage": 1,
    "totalElements": 0,
    "totalPages": 1
}
```
