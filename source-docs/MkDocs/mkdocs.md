# MkDocs (Ubuntu 24.04 LTS, recomendado con pipx)

## ¿Por qué pipx?
`pipx` es la forma recomendada y segura de instalar aplicaciones de línea de comandos de Python en Ubuntu 24.04 LTS. Permite aislar cada herramienta en su propio entorno, evitando conflictos y protegiendo el sistema.

---

## Requerimientos
- Python 3 (Ubuntu 24.04 LTS ya incluye python3)
- pipx (gestor recomendado para instalar aplicaciones Python CLI)

Instala pipx:
```bash
sudo apt update
sudo apt install pipx -y
pipx ensurepath
```

---

## Instalación de MkDocs

```bash
pipx install mkdocs
```

---

## Compilar y probar
- Dentro del directorio del proyecto:
```bash
mkdocs serve #para probar en http://127.0.0.1:8000/
mkdocs build #para compilar
mkdocs build --clean #para compilar y eliminar archivos que ya no deberían existir
```

---

## Instalar otras plantillas (ejemplo: mkdocs-material)

```bash
pipx inject mkdocs mkdocs-material
```

---

## Solución de problemas
- Si el comando `mkdocs` no se encuentra, ejecuta `pipx ensurepath` y reinicia la terminal.
- Consulta la [documentación oficial de pipx](https://pypa.github.io/pipx/) para más detalles.

---

## Recursos útiles
- [Documentación oficial de MkDocs](https://www.mkdocs.org/)
- [Temas para MkDocs](https://github.com/mkdocs/mkdocs/wiki/MkDocs-Themes)
- [mkdocs-material](https://squidfunk.github.io/mkdocs-material/)
