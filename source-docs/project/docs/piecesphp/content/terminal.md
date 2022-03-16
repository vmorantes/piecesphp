# PiecesPHP Framework

## Terminal

### Instrucciones

- Nota: Desde la carpeta src se debe ejecutar: 
```bash 
php index.php cli <ACCIÓN> [param=1[...]]
```
i.e.:
```bash 
php index.php bundle bundle app=yes zip=yes
```

##### Acciones

- bundle
    - Parámetros:
        - app [yes|no] default: no
        - statis [yes|no] default: no
        - all [yes|no] default: no
        - zip [yes|no] default: no

- db-backup
    - Parámetros:
        - gz [yes|no] default: yes
