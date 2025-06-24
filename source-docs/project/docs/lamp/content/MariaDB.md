# Instalación de MariaDB en Ubuntu 24.04 LTS

## Introducción
MariaDB es un sistema de gestión de bases de datos compatible con MySQL. Aquí aprenderás a instalarlo y configurarlo de forma segura.

---

## Instalación

Actualiza los repositorios e instala MariaDB:

```bash
sudo apt update
sudo apt install mariadb-server mariadb-client -y
```

---

## Configuración inicial y seguridad

Ejecuta el script de seguridad:

```bash
#Modo de super usuario
sudo su

#Medidas de seguridad
mysql_secure_installation
#prompt: Enter current password for root (enter for none): (PRESIONA ENTER)
#prompt: Switch to unix_socket authentication [Y/n]: n
#prompt: Change the root password? [Y/n]: n (El usuario root quedará sin contraseña porque es preferible no usarlo por seguridad.)
#prompt: Remove anonymous users? [Y/n]: Y
#prompt: Disallow root login remotely? [Y/n]: Y (Desactivar conexiones externas)
#prompt: Remove test database and access to it? [Y/n]: Y
#prompt: Reload privilege tables now? [Y/n]: Y

#Entrar en consola de mariadb
```

Sigue las instrucciones para asegurar tu instalación (puedes dejar la contraseña de root vacía si solo usas sockets locales, pero se recomienda establecer una contraseña fuerte).

---

## Crear usuario y base de datos

Accede a la consola de MariaDB:

```bash
mysql
```

Crea un usuario y una base de datos de ejemplo:

```sql
-- Crear general (el usuario y la contraseña son de ejemplo):
CREATE USER 'admin_general'@'localhost' IDENTIFIED BY 'PASSWORD';

-- Otorgar permisos globales al usuario
GRANT ALL PRIVILEGES ON *piecesphp_db*.* TO 'admin_general'@'localhost';
-- Refrescar privilegios:
FLUSH PRIVILEGES;
-- Salir de la consola de mariadb
EXIT;
```

---

## Solución de problemas de permisos

Si el usuario root no tiene permisos:

```bash
#Detener servidor mariadb
sudo systemctl stop mysql

#Desactivar verificación de permisos
sudo mysqld_safe --skip-grant-tables &

#Conectar
mysql -uroot

#Otorgar permisos
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;

#Salir
exit;

#Activar servidor mariadb
sudo systemctl start mysql
```

---

## Recursos útiles
- [Documentación oficial de MariaDB](https://mariadb.com/kb/es/documentation/)
