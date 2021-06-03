# PiecesPHP Framework

## Terminal

### Instrucciones

- Nota: Desde la carpeta src se debe ejecutar: 
```bash 
php index.php terminal route=terminal-[ROUTE_NAME] [param=1[...]]
```
i.e.:
```bash 
php index.php terminal route=terminal-bundle app=yes zip=yes
```

##### Acciones

- terminal-bundle
    - Parámetros:
        - app [yes|no] default: no
        - statis [yes|no] default: no
        - all [yes|no] default: no
        - zip [yes|no] default: no

- terminal-db-backup
    - Parámetros:
        - gz [yes|no] default: yes
