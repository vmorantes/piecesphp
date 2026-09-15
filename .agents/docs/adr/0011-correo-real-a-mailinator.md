# 0011 — Correo real de prueba, solo a buzones públicos de Mailinator

- **Estado:** Aceptada
- **Fecha:** 2026-09-15
- **Decide:** Product Owner
- **Estructural:** sí (amplía el ADR 0010: el correo sale de esta máquina)

## En cristiano

Cuando una prueba necesite que el framework envíe un correo de verdad, el coder puede hacerlo, pero
solo a direcciones `@mailinator.com`, que son buzones públicos y desechables. Luego le dice al PO a
qué direcciones escribió, para que las revise. Ninguna otra dirección recibe correos de prueba. Existe
porque varias funciones (aprobar solicitudes, recuperar contraseña) envían correo, y hasta ahora se
evitaban en las pruebas.

## Contexto

- En `#048` el coder no usó `approvalAction` porque envía un correo real con el Mailer/SMTP
  configurado, y fijó los estados por el mapper.
- **El PO, el 2026-09-15**, formalizado: si hay que enviar un correo real, que vaya a una
  dirección `@mailinator.com` y se le diga para revisarlo.
- El lote 7c del mapa (la ventana de correo) prevé una capa de «entrega real revisada a mano»
  con buzones públicos (`18` T7). Esto la hace posible.

## Decisión

- **Permitido:** que la aplicación local envíe correo real a direcciones
  `zz-prueba-<algo>@mailinator.com`, con el SMTP ya configurado en la instalación local.
- **Obligatorio:**
  - el reporte lista cada dirección usada, qué acción envió el correo y a qué hora, para que el
    PO lo revise en mailinator.com;
  - el asunto o el cuerpo no llevan datos reales ni secretos;
  - los usuarios de prueba con esas direcciones llevan el prefijo `zz-prueba-`.
- **Prohibido:**
  - cualquier otro dominio o dirección;
  - tocar la configuración SMTP;
  - imprimir sus credenciales;
  - enviar en bucle o en masa.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| No enviar nunca | Deja sin probar de punta a punta las funciones que envían correo |
| Un sumidero SMTP local (Mailpit o MailHog) | Es una dependencia y un servicio que autoriza el PO. Sigue en pie para la capa 2 del 7c |
| Un «Mailinator propio» | Es una idea del PO para el futuro, no ahora |

## Consecuencias

- **Lo bueno:** se prueba la entrega real, y el PO puede ver el correo tal cual llega.
- **Lo malo:**
  - Mailinator es público: cualquiera puede leer esos buzones. Por eso nada sensible va en
    ellos;
  - usa el SMTP real de la instalación local, así que cuenta en su cuota y en su reputación. Por
    eso, pocos envíos.

## Reversión

1. Quitar la excepción de `40-salvaguardas.md` §2.
2. Volver a prohibir las acciones que envían correo.

## Verificación

Cada reporte que envíe correo incluye la lista de direcciones, acciones y horas, y el PO confirma
que los ve.
