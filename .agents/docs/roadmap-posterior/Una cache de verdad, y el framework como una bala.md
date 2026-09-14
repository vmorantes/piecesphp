# META · Una caché de verdad, y que el framework sea «una bala»

**Meta declarada por el PROPIETARIO — 2026-08-26. ES UNA META, NO UNA TAREA: no lleva plan.**

> La caché de controladores que hay hoy es un experimento antiguo suyo que casi no se usa. Lo que
> quiere no es arreglarlo: es **una caché de verdad**, y que el framework **sea una bala**.

## Por qué esto se escribe aquí y no como ventana

Una ventana tiene alcance, condición de cierre y una puerta. Esto **no tiene ninguna de las tres**
todavía: es la dirección hacia la que el PROPIETARIO quiere que empuje el trabajo, y sirve para
juzgar propuestas, no para ejecutarlas. Escribirlo como tarea sería inventarle un alcance que él no
ha dado.

## Lo que ya se sabe, medido, y que cualquier diseño tendrá que mirar

- **La caché de hoy tiene un solo consumidor.** `PublicationsController` la construye una vez.
  `getCriteries()` no lo llama nadie. Ver T123.
- **Su rehidratación estaba a medias** —una rama de `if/else` vacía— y llevaba años así sin romper
  nada, precisamente porque casi no se usa.
- **Servir una petición cuesta ~22,5 ms de arranque de PHP**, hagas lo que hagas dentro (T111). Ese
  es el suelo contra el que se mide cualquier caché: lo que ahorre tiene que compararse con eso.
- **El servido de estáticos ya delega en el servidor web** cuando puede, y ahí Apache responde en
  0,52 ms frente a los 22,78 ms de la ruta PHP. **La forma que ya funciona es sacar el trabajo de
  PHP**, no hacerlo más rápido dentro.

## Lo que NO se decide aquí

Si «una caché de verdad» es una capa de página completa, una de fragmentos, una de consultas, o
apoyarse en el servidor web y en las cabeceras que el framework ya emite. Y si «una bala» se mide
en milisegundos por petición, en peticiones por segundo, o en cuánto tarda un despliegue en estar
sirviendo.

**Sin esas dos definiciones no hay diseño que proponer**, y las pone el PROPIETARIO.

## Con qué conversación se cruza

Con `El framework como paquete y su despliegue.md` y con `El módulo como patrón mecanizable.md`:
las tres preguntan lo mismo desde ángulos distintos — **qué se hace una vez al desplegar y qué se
hace en cada petición**.
