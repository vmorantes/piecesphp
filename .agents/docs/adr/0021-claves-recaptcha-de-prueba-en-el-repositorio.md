# 0021 — Excepción: las claves de reCAPTCHA v3 de prueba del propietario se versionan

- **Estado:** Aceptada
- **Fecha:** 2026-09-16
- **Decide:** Product Owner (respuesta a P31 en A-049 y aclaración del mismo día: «quiero que quede en el repositorio;
  por eso te la di»)
- **Estructural:** sí (excepción a una regla del PO sobre secretos)

## En cristiano

Por regla, ninguna clave secreta entra en el repositorio. El propietario hace una excepción concreta: creó en Google un
par de claves de reCAPTCHA v3 que solo funcionan en sus dominios de prueba y en `localhost`, y quiere que viajen con el
framework. Así, quien lo instale en local tiene el formulario de contacto funcionando sin configurar nada. En producción
se ponen claves reales en el almacén de claves seguras, que tienen prioridad.

## Contexto

- Google no publica claves de prueba para reCAPTCHA v3 (solo para v2): medido con su FAQ para P31.
- Hasta el lote 10, la clave secreta de un par anterior estaba en claro en `GoogleReCaptchaV3Controller.php` y la de
  sitio en `src/statics/js/contact-form.js`. El PO decidió no regenerar ese par ni el nuevo.
- Regla general del PO: `00-core.md` («nunca incluyas en un commit secretos») y `40-salvaguardas.md` §7.

## Decisión

1. `src/app/config/config.php` lleva `GoogleReCaptchaV3TestSiteKey` y `GoogleReCaptchaV3TestSecretKey` con el par de
   prueba del propietario, y un comentario que dice que son de prueba y dónde van las reales.
2. Las reales se leen de las claves seguras (`secure-keys/recaptcha-v3-secret` y `secure-keys/recaptcha-v3-site`, vía
   `api-keys.php`) y **tienen prioridad**. Sin reales se usan las de prueba; sin ninguna, el formulario rechaza y deja
   una línea en el log.
3. La excepción cubre **solo estas dos claves**. No autoriza versionar ninguna otra credencial.

## Alternativas descartadas

- **Claves vacías en el repositorio** (la opción (a) inicial de P31): obliga a configurar para probar en local, que es lo
  que el propietario quiere evitar.
- **Pasar el módulo a v2**, que tiene claves de prueba públicas: cambia el producto para resolver una comodidad de
  desarrollo.

## Consecuencias

- Buenas: una instalación local o un clon tienen el CAPTCHA operativo; las reales siguen fuera del repositorio.
- Malas: la clave secreta de prueba es pública para cualquiera que lea el repositorio. Su uso está limitado a los
  dominios que el propietario declaró en Google, pero quien la tenga puede consumir su cuota de verificación. Si eso
  ocurre, se regenera y se sustituye aquí.

## Reversión

1. Regenerar el par en la consola de Google.
2. Vaciar las dos entradas de prueba en `config.php` (o poner el par nuevo).
3. Las instalaciones con claves reales no se enteran; las locales vuelven a rechazar el formulario hasta configurar.

## Verificación

- `git grep -c` de claves literales `6L…` en `src/app` y `src/statics/js`: solo `src/app/config/config.php`, con 2.
- La prueba de la suite de correos: sin real y sin prueba, rechazo sin red; sin real y con prueba, usa la de prueba.
