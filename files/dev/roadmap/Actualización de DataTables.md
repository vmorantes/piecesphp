# Documento Técnico: Plan de Migración DataTables v2.x e Integración Fomantic UI

## Objetivo
Actualizar la implementación de DataTables v1.13.5 a la versión 2.3.7 (incluyendo extensiones) garantizando la estabilidad del motor, preservando la interfaz basada en Fomantic UI y asegurando la compatibilidad del backend Server-Side Processing (SSP) con PHP 8.4.

---

## FASE 1: Refactorización Crítica (Estabilidad del Motor)
**Prioridad: Alta | Objetivo: Evitar excepciones del Virtual DOM de DataTables 2.x.**

### 1.1. Protección del DOM en Renderizado de Fichas (Cards)
El motor de la versión 2.x requiere mantener la estructura base de la tabla (`<table>`, `<thead>`, `<tbody>`) en el DOM para la reconciliación de eventos. Se debe modificar el comportamiento destructivo por ocultamiento seguro.

* **Archivo:** Archivo de utilidades/helpers de inicialización de DataTables.
* **Función:** `dataTablesServerProccesingOnCards`
* **Evento:** `drawCallback`
* **Acción:**
    ```javascript
    // ❌ CÓDIGO A ELIMINAR
    this.find('tbody').remove();

    // ✅ CÓDIGO A IMPLEMENTAR
    this.find('tbody').hide(); 
    ```

### 1.2. Sustitución de Notación Húngara y Propiedades Privadas
El acceso a `settings.aoColumns` está deprecado y es inestable en la nueva versión. La extracción de metadatos de las columnas debe realizarse a través de la API pública oficial.

* **Archivo:** Archivo de utilidades/helpers de inicialización de DataTables.
* **Función:** `dataTablesServerProccesingOnCards`
* **Evento:** `initComplete`
* **Acción:**
    ```javascript
    // ❌ CÓDIGO A ELIMINAR
    for (let i in settings.aoColumns) { ... }

    // ✅ CÓDIGO A IMPLEMENTAR
    let api = table.DataTable();
    let columns = [];
    
    api.columns().every(function (index) {
        let col = this;
        columns.push({
            index: index,
            name: col.dataSrc() || $(col.header()).text().trim(),
            visible: col.visible(),
            orderable: col.orderable(),
            searchable: col.searchable(),
            htmlElement: col.header(),
        });
    });
    ```

---

## FASE 2: Transición de Capa Visual (Fomantic UI)
**Prioridad: Media | Objetivo: Reemplazar el parámetro obsoleto `dom` por la nueva API `layout`.**

### 2.1. Mapeo de Estructura Base
La inicialización de tablas independientes que utilicen inyecciones de grillas complejas en string deben migrarse a la estructura semántica de DataTables 2.x.

* **Ubicación:** Scripts de inicialización de vistas específicas (ej. noticias, perfiles).
* **Acción:**
    ```javascript
    // ❌ CÓDIGO A ELIMINAR
    dom: `<'ui stackable grid'<'row'<'eight wide column'l><'right aligned eight wide column'<'custom-search'>>><'row dt-table'<'sixteen wide column'tr>><'row'<'seven wide column'i><'right aligned nine wide column'p>>>`,

    // ✅ CÓDIGO A IMPLEMENTAR
    layout: {
        topStart: 'pageLength',
        topEnd: null, // Espacio reservado para inyección manual posterior
        bottomStart: 'info',
        bottomEnd: 'paging'
    },
    ```

### 2.2. Inyección de Componentes Personalizados en el Layout
Los contenedores no estándar (ej. `<div class="custom-search">`) deben anexarse mediante manipulación del DOM posterior a la inicialización del layout.

* **Evento:** `initComplete` (en las vistas independientes)
* **Acción:**
    ```javascript
    initComplete: function (settings, json) {
        const thisDataTable = this.DataTable();
        const container = this.closest('.container-standard-table');
        const templates = $(`<div>${container.find('template').get(0).innerHTML}</div>`);
        const searchFilters = $(templates.find('search-filters').html());
        
        // 1. Crear el wrapper de Fomantic UI
        const customSearchContainer = $('<div class="custom-search right aligned eight wide column"></div>');
        customSearchContainer.append(searchFilters);

        // 2. Inyectar en el nodo topEnd nativo generado por dt-layout
        $(thisDataTable.table().container()).find('.dt-layout-topEnd').html(customSearchContainer);

        // 3. Asignar eventos
        searchFilters.find('[search-input] input').off('keyup').on('keyup', function (e) {
            thisDataTable.search($(e.currentTarget).val()).draw();
        });
        
        configMirrorScrollX('namespace.mirror-scroll-x.all', '.mirror-scroll-x.all');
    }
    ```

---

## FASE 3: Delegación de Eventos y QA Frontend
**Prioridad: Media-Baja | Objetivo: Reducir carga de JS y validar extensiones.**

### 3.1. Delegación de Atributos HTML5
* **Acción Requerida:** Actualizar las plantillas HTML para utilizar `data-searchable="true"` y `data-orderable="true"` en las etiquetas `<th>`.
* **Resultado:** Permite eliminar el bloque de iteración de `thElements` y parseo manual de atributos en la función `dataTableServerProccesing`, delegando esta lectura de manera nativa al motor de DT2.

### 3.2. Pruebas de Calidad (QA) para Extensiones
Se debe ejecutar el siguiente protocolo de validación manual en el entorno de pruebas:
* [ ] **Responsive v3.0.8:** Validar renderizado de fila hija en vistas móviles; confirmar que el evento `click` sobre botones dentro del nodo expandido siga capturándose.
* [ ] **RowReorder v1.5.1 / ColReorder v2.1.2:** Validar "Drag and Drop"; auditar la consola para asegurar que no ocurran recortes de *z-index* con componentes de Fomantic UI ni errores de referencia de nodo (`Node not found`).
* [ ] **Auditoría de Consola:** Navegar por el flujo completo del *dashboard*. Garantizar la ausencia total de advertencias del tipo `[DataTables] Deprecation`.

---

## FASE 4: Blindaje Backend (PHP 8.4)
**Prioridad: Media | Objetivo: Asegurar el procesamiento Server-Side ante el tipado estricto de PHP 8.4.**

### 4.1. Refuerzo de Seguridad de Tipos en Helpers SQL
El analizador estricto de PHP 8.4 penaliza el paso de valores `null` a funciones nativas de manipulación de cadenas (`mb_strtoupper`, `str_replace`, `trim`).

* **Archivo:** `PiecesPHP\Core\Utilities\Helpers\DataTablesHelper`
* **Funciones:** `generateHaving` y procesos de consulta principal.
* **Acción:**
    * Implementar conversión explícita a `string` (casting) o el operador de coalescencia nula (`?? ''`) en la recepción de `$search['value']` y durante el ensamblado de las cláusulas `$where` y `$having`.
    * Asegurar que las variables dinámicas que componen el SQL no introduzcan sentencias vacías inseguras si los parámetros de la petición HTTP llegan nulos.
