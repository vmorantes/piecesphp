# MkDocs (probado en Ubuntu 20.04)

### Requerimientos:
- Python. (Ubuntu 20.04 se distribuye con python3)
- Pip. (sudo apt -y install python3-pip)
- Plantillas disponibles:
    - [readthedocs](https://www.mkdocs.org/user-guide/styling-your-docs/#readthedocs)
    - [mkdocs](https://www.mkdocs.org/user-guide/styling-your-docs/#mkdocs)

## Instalación
```bash
sudo pip3 install mkdocs
```

## Compilar y probar
- Dentro del directorio del proyecto:
```bash
mkdocs serve #para probar en http://127.0.0.1:8000/
mkdocs build #para compilar
mkdocs build --clean #para compilar y eliminar archivos que ya no deberían existir
```
## Instalar otras plantillas

- Nota: Varias plantillas disponibles en [MKDocs-Themes](https://github.com/mkdocs/mkdocs/wiki/MkDocs-Themes)

### [material](https://github.com/squidfunk/mkdocs-material)
- [Docs](https://squidfunk.github.io/mkdocs-material/)
```bash
pip install mkdocs-material
```
