# Instalación de MariaDB en Ubuntu 26.04 LTS

## Introducción
MariaDB es un sistema de gestión de bases de datos compatible con MySQL. Ubuntu 26.04 LTS trae MariaDB 11.8, una
versión de soporte largo. Aquí aprenderás a instalarlo y configurarlo de forma segura.

Desde MariaDB 11, las órdenes se llaman `mariadb`, `mariadb-dump`, `mariadb-secure-installation`… Los nombres antiguos
(`mysql_secure_installation`, `mysqlcheck`…) solo existen si se instalan los paquetes `mariadb-client-compat` y
`mariadb-server-compat`; esta guía usa los nuevos.

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
#Medidas de seguridad
sudo mariadb-secure-installation
#prompt: Enter current password for root (enter for none): (PRESIONA ENTER)
#prompt: Switch to unix_socket authentication [Y/n]: n
#prompt: Change the root password? [Y/n]: n (El usuario root quedará sin contraseña porque es preferible no usarlo por seguridad.)
#prompt: Remove anonymous users? [Y/n]: Y
#prompt: Disallow root login remotely? [Y/n]: Y (Desactivar conexiones externas)
#prompt: Remove test database and access to it? [Y/n]: Y
#prompt: Reload privilege tables now? [Y/n]: Y
```

Sigue las instrucciones para asegurar tu instalación (puedes dejar la contraseña de root vacía si solo usas sockets locales, pero se recomienda establecer una contraseña fuerte).

---

## Crear usuario y base de datos

Genera una contraseña para el usuario (cada instalación, la suya):

```bash
openssl rand -base64 24
```

Accede a la consola de MariaDB:

```bash
sudo mariadb
```

Crea un usuario de ejemplo con ella, en lugar de `PASSWORD`:

```sql
-- Crear general (el usuario y la contraseña son de ejemplo):
CREATE USER 'admin_general'@'localhost' IDENTIFIED BY 'PASSWORD';

-- Otorgar permisos globales al usuario
GRANT ALL PRIVILEGES ON *.* TO 'admin_general'@'localhost';
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
sudo systemctl stop mariadb

#Desactivar verificación de permisos
sudo mariadbd-safe --skip-grant-tables &

#Conectar
sudo mariadb -uroot

#Con --skip-grant-tables, GRANT falla hasta recargar las tablas de privilegios
FLUSH PRIVILEGES;

#Otorgar permisos
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;

#Salir
exit;

#Detener la instancia sin permisos y arrancar el servicio normal
sudo mariadb-admin shutdown
sudo systemctl start mariadb
```

---

## Recursos útiles
- [Documentación oficial de MariaDB](https://mariadb.com/kb/es/documentation/)
