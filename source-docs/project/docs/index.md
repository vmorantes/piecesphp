# Introducción

![Screenshot](./statics/logo-piecesphp.svg)

Es un framework de desarrollo web es una biblioteca de software que permite a los desarrolladores construir aplicaciones y sitios web de manera eficiente y rápida; esta documentación introduce el despliegue y la utilización de **PiecesPHP**, un framework basado como su nombre lo indica en PHP (probado en PHP 8.5) que no solo facilita el desarrollo rápido de aplicaciones web, sino que también incorpora características modulares avanzadas que abordan las necesidades modernas de escalabilidad, seguridad y rendimiento. 

## Cómo está organizada la documentación

Hay cuatro capas, según quién lee y para qué:

| Capa | Para quién | Dónde |
| :-- | :-- | :-- |
| **Desarrollar sobre PiecesPHP** | Quien construye un proyecto a partir del framework | [PiecesPHP Framework](./piecesphp/index.md): estructura, rutas, mappers, terminal, [crear un módulo](./piecesphp/content/modules.md) y [el panel por dentro](./piecesphp/content/panel.md). La API, en la documentación aparte de `source-docs/api/` |
| **Mantener y extender el framework** | Quien cambia el propio PiecesPHP | [Mantener el framework](./piecesphp/content/maintain.md) y el `CHANGELOG.md` de la raíz |
| **Guías de entorno y herramientas** | Quien prepara un servidor o una herramienta | Viven en el repositorio de desarrollo (`source-docs/entornos`) y no viajan a los clones. Lo que el framework exige —PHP, sus extensiones y el correo de desarrollo— está en [Despliegue](./piecesphp/content/general.md) |
| **Agentes de IA** | Los asistentes que trabajan en el repositorio | `AGENTS.md` y `.agents/`, en el repositorio; no se publican aquí |

## Consideraciones iniciales

PiecesPHP requiere el despliegue de un entorno LAMP, que es el acrónimo usado para describir un sistema de infraestructura de internet el cual usa las siguientes herramientas:

* Linux, el sistema operativo
* Apache, el servidor web
* MySQL/MariaDB, el gestor de bases de datos
* PHP, el lenguaje de programación

Lo que PiecesPHP exige de ese entorno en Ubuntu 26.04 LTS (la versión de PHP, sus extensiones y Apache) está en
[Despliegue](./piecesphp/content/general.md).

[Despliegue de PiecesPHP](./piecesphp/content/general.md){ .md-button }

> Aunque gracias a WSL es posible el despliegue en sistemas windows, para más información visite los siguientes enlaces:

- *[¿Qué es WSL?](https://learn.microsoft.com/es-es/windows/wsl/about)* 
- *[Instalar WSL en Windows 11](https://learn.microsoft.com/es-es/windows/wsl/install)*
- *[Instalar Ubuntu en Windows 11](https://canonical-ubuntu-wsl.readthedocs-hosted.com/en/latest/guides/install-ubuntu-wsl2/)*

