# 0023 — El coder crea y usa su propio usuario de prueba local

- **Estado:** Aceptada
- **Fecha:** 2026-09-19
- **Decide:** Product Owner (respuesta a P53: «Absolutamente sí. Puede crear usuarios y lo que le venga en gana; este
  repositorio vive para desarrollo, no para un proyecto, así que todo es de pruebas»)
- **Estructural:** sí (amplía la excepción del ADR 0010)

## En cristiano

Hasta hoy el coder no podía probar el panel en un navegador: no tenía un usuario con el que entrar. El propietario
autoriza que el coder se cree sus propios usuarios de prueba en la base local y los use para recorrer el panel. Las
contraseñas no se escriben en ningún archivo versionado ni se imprimen: viven en un ajuste local de la máquina que git
ignora.

## Contexto

- El ADR 0010 permite al coder peticiones HTTP contra la instalación local, con las credenciales de prueba locales, y
  crear, editar o borrar registros con prefijo reconocible y `db-backup` antes.
- Faltaba quién pone esas credenciales. P53 proponía que las pusiera el PO en `.claude/settings.local.json`.
- La base local es desechable (PO, 2026-08-29).

## Decisión

1. El coder crea en la base LOCAL los usuarios de prueba que necesite, con el prefijo `zz-agente-` (por ejemplo, uno
   root y uno de cada tipo), por el camino normal de la aplicación o por la terminal, nunca con SQL a mano.
2. Sus contraseñas se generan al azar y se guardan **solo** en `.claude/settings.local.json` (ignorado por git, permisos
   `0600`), bajo `"env"`, como `PCSPHP_WALK_USER` y `PCSPHP_WALK_PASS` (el root) y, si hacen falta más, con nombres
   `PCSPHP_TEST_<TIPO>_USER|PASS`.
3. **Nunca** se imprimen, ni en reportes ni en la terminal, ni se escriben en otro archivo. En los reportes se nombra el
   usuario, no la contraseña.
4. Solo contra la instalación local. Nunca contra un servidor ni con credenciales de otra persona.

## Alternativas descartadas

- **Que el PO ponga las credenciales a mano:** funciona, pero depende de un paso suyo cada vez que la base se rehace.
- **Guardar las credenciales en `files/dev/`:** esa carpeta se versiona.

## Consecuencias

- El coder puede recorrer el panel con usuarios reales de cada tipo, y las pruebas de pantallas dejan de quedarse en
  «probado por HTML».
- Si la base local se restaura, los usuarios `zz-agente-` pueden desaparecer: el coder los vuelve a crear.
- `.claude/settings.local.json` pasa a tener secretos locales: no se copia a otra máquina ni se comparte.

## Reversión

1. Borrar las claves `PCSPHP_WALK_*` y `PCSPHP_TEST_*` de `.claude/settings.local.json`.
2. Borrar de la base local los usuarios `zz-agente-`.
3. Marcar este ADR como reemplazado.

## Verificación

- `git check-ignore -v .claude/settings.local.json` nombra la línea de `.gitignore`.
- `python3 -B .agents/scripts/menciones_ia.py` y la guarda siguen en verde.
- Ningún reporte del coder contiene una contraseña.
