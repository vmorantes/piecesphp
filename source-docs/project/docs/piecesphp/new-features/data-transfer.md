# Importar y exportar datos (DataTransfer)

PiecesPHP trae un **motor de importación y exportación** en su núcleo (`PiecesPHP\Core\DataTransfer`) y un **panel**,
«Importar y exportar», que lo usa. Sirve para cargar datos desde una hoja de cálculo y para sacar datos del sistema,
desde un listado sencillo hasta un informe con filtros, varias hojas, totales y logo.

Decisión y alternativas: ADR 0022 de la documentación de agentes.

## Las ideas en cuatro frases

1. **Cada tipo de dato es una definición**: una clase que dice qué columnas tiene, quién puede usarla y qué hacer con
   los datos. Una `ImportDefinition` para importar, una `ExportDefinition` para exportar.
2. **Se registra en el `routes()` de su módulo**, y eso crea sus rutas. **El nombre de cada ruta es su permiso**, como en
   el resto del framework.
3. **El motor hace lo común**: leer y validar el archivo, todo o nada, filtros tipados, escribir XLSX o CSV sin que un
   dato se ejecute como fórmula, formularios, vista previa y permisos. La definición solo aporta lo suyo.
4. **Cada definición elige su nivel de interfaz**: ninguna (solo URL o terminal), automática, automática con extras, o
   una vista propia. El motor es el mismo en los cuatro.

## Por dónde empezar

| Quiero… | Lee |
| :-- | :-- |
| Importar o exportar desde el panel | [Usar el panel](data-transfer/panel.md) |
| Programar un importador | [Crear un importador](data-transfer/importador.md) |
| Programar un exportador o un informe | [Crear un exportador o un informe](data-transfer/exportador.md) |
| Consultar una clase, un método o una opción | [Referencia](data-transfer/referencia.md) |
| Pasar un exportador viejo (`BaseExportData`) al motor nuevo | [Migrar desde `BaseExportData`](data-transfer/migrar-desde-baseexportdata.md) |

## Los ejemplos de esta guía son código real

Todo el código de las páginas de importador y exportador sale de dos clases del repositorio,
`src/app/classes/DataImportExportUtility/Examples/`:

- `ExampleUserNamesImportDefinition`: actualiza el nombre y los apellidos de usuarios que ya existen, con simulacro y
  en una transacción.
- `ExampleUsersReportExportDefinition`: un informe de usuarios con cuatro filtros, dos hojas, logo, totales, vista
  previa, elección de columnas y filtros guardados.

La guía los **incrusta desde esos archivos** al construirse, y la suite `data-transfer-examples` los ejecuta: si un
ejemplo deja de funcionar, la verificación falla; si cambia, la guía cambia con él. **No están registrados en el panel**:
para verlos funcionar, cópialos a tu módulo y regístralos (lo explica cada página).

## Apagar y encender

- **El módulo entero**: la constante `DATA_IMPORT_EXPORT_MODULE` de `src/app/config/constants.php`. En `false` no hay
  rutas, ni menú, ni portada, y registrar una definición no hace nada ni da error.
- **Un importador o exportador concreto, desde el panel**: root tiene un interruptor por fila en la portada. Apagado,
  desaparece para los demás y **todas sus entradas responden 403**: formulario, subida, plantilla, descarga, vista
  previa, filtros guardados y terminal (también para root, hasta que lo vuelva a encender). Se guarda en la
  configuración (`data_transfer_disabled`), así que sobrevive a las actualizaciones, y cada cambio queda en el registro
  de acciones (si el módulo de registro está encendido).
- **Un importador o exportador concreto, desde el código**: sin registrarlo, o con nivel de interfaz 0 para quitarlo del
  panel y dejar solo su URL.
- **Por tipo de usuario**: con `allowedUserTypes()` de la definición y los permisos de sus rutas.
