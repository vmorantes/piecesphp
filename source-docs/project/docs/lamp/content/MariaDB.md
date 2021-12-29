# Instalación de MariaDB 10.5

- Ubuntu 20
- MariaDB 10.5

## Actualizar repositorios

```bash
sudo apt-get install software-properties-common
sudo apt-key adv --fetch-keys 'https://mariadb.org/mariadb_release_signing_key.asc'
sudo add-apt-repository 'deb [arch=amd64,arm64,ppc64el] http://mirror.ufscar.br/mariadb/repo/10.5/ubuntu focal main'
```

## Instalación

```bash
sudo apt update
sudo apt install mariadb-server mariadb-client
```

## Configuración de usuario

```bash
#Moverse al directorio de usuario
cd ~

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
mysql 
```
```sql
-- Crear general (el usuario y la contraseña son de ejemplo):
CREATE USER 'admin_general'@'localhost' IDENTIFIED BY 'pPz_afcad6464e_lr_m646464am';
-- Otorgar permisos globales al usuario
GRANT ALL PRIVILEGES ON *.* TO 'admin_general'@'localhost' WITH GRANT OPTION;
-- Refrescar privilegios:
FLUSH PRIVILEGES;
-- Salir de la consola de mariadb
exit;
```
```bash
#Salir del modo de super usuario
exit
```
### Caso en que el usuario root carece de permisos

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
