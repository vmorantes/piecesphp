# 0010 — Pruebas contra la aplicación local: navegador simulado y escrituras en la base de prueba

- **Estado:** Aceptada
- **Fecha:** 2026-09-15
- **Decide:** Product Owner
- **Estructural:** sí (levanta en parte una salvaguarda: `00-core.md` y `40-salvaguardas.md` §2)

## En cristiano

A partir de ahora, el coder puede probar el framework como lo usaría una persona: hace
peticiones a la aplicación local como un navegador, entra con las credenciales de prueba y crea,
edita o borra registros en la base local. Todo eso se hace SOLO en la instalación de esta
máquina, cuya base es de prueba y desechable. Nada de esto vale para un servidor remoto ni para
credenciales reales. Existe porque el PO quiere que las mejoras de rendimiento y seguridad se
prueben a fondo, sin que se rompa nada de lo que funciona.

## Contexto

- Hasta hoy, cada instrucción prohibía hacer peticiones HTTP a la aplicación y escribir en la
  base. Las pruebas se limitaban a lo que se podía comprobar sin base o con `SELECT`. Varias no
  pudieron discriminar por falta de filas (`#031`, `#033` y `#037`).
- El PO ya había dicho que la base local del framework es desechable y no contiene datos de un
  proyecto real (2026-08-29, regla 30, «El PO, en sus palabras»).
- **Lo que dijo el PO el 2026-09-15**, formalizado: el coder puede simular navegadores, tiene
  acceso a todas las rutas y a las credenciales de prueba y puede crear registros, porque es una
  base de prueba. Pide aplicar las mejoras necesarias y probarlo todo exhaustivamente. Todo tiene
  que seguir funcionando como se espera y como está programado, y cualquier mejora de rendimiento
  y seguridad es bienvenida.

## Decisión

- **Permitido**, solo contra la instalación local de esta máquina (el `baseurl` local y la base
  de desarrollo local):
  - peticiones HTTP a la aplicación como un navegador (`curl` o similar), con o sin sesión;
  - iniciar sesión con las credenciales de prueba locales;
  - crear, editar y borrar registros por la propia aplicación o por las pruebas.
- **Buenas prácticas obligatorias:**
  - todo registro creado para una prueba lleva un prefijo reconocible (por ejemplo,
    `zz-prueba-`) y el reporte lo enumera;
  - antes de una tanda que escribe, el coder guarda el estado (`bin/cli db-backup`) y lo nombra
    en su reporte.
- **Sigue prohibido:**
  - cualquier servidor remoto y cualquier credencial que no sea la local de prueba;
  - imprimir contraseñas o secretos, también los de prueba: el repositorio se publica;
  - los clientes de base de datos (`mysql`, etc.): se escribe por la aplicación o por
    `bin/cli`;
  - las tareas destructivas de `bin/cli` sobre la base entera (`db-restore`, `scheme-drop`,
    `clean-all`) sin que la instrucción las nombre;
  - pruebas de carga que puedan tumbar la máquina.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Seguir sin HTTP ni escrituras | Deja pruebas que no discriminan y cambios que nunca se ven funcionar de punta a punta |
| Pedir permiso al PO en cada ronda | Él lo ha dado de forma general para la base de prueba; pedirlo cada vez contradice su decisión |
| Una base aparte solo para pruebas | Es infraestructura nueva sin necesidad: la local ya es desechable |

## Consecuencias

- **Lo bueno:**
  - las pruebas de rechazo pueden discriminar por resultado;
  - los cambios se ven funcionar en el navegador simulado, con sesión y sin ella.
- **Lo malo:**
  - la base local se ensucia con registros de prueba: por eso llevan prefijo y se enumeran;
  - una petición mal hecha puede escribir donde no se quería: por eso se guarda el estado
    antes.

## Reversión

1. Quitar la excepción de `40-salvaguardas.md` §2.
2. Volver a escribir en las instrucciones «prohibido HTTP y escribir en la base».

Los registros ya creados se quedan; se limpian por su prefijo si hace falta.

## Verificación

Cada reporte que use esta excepción enumera las peticiones hechas (ruta, método, con o sin
sesión), los registros creados con su prefijo y la copia de seguridad previa.
