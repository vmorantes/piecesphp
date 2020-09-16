# MkDocs (probado en Ubuntu 20.04)

### Requerimientos:
- Python. (Ubuntu 20.04 se distribuye con python3)
- Pip. (sudo apt -y install python3-pip)

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
