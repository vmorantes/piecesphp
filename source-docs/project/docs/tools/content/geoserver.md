# Instalación de GeoServer 3.0.x en Ubuntu 26.04 LTS

## Introducción
GeoServer es una plataforma open source para compartir, procesar y editar datos geoespaciales.

GeoServer 3 pasa a Jakarta EE (Servlet 6.1): su archivo web (`geoserver.war`) exige **Tomcat 11.0.x** y **Java 17 o
21**. Instala antes Tomcat 11 con Java 21 siguiendo la guía de Tomcat; esta guía da por hecho que existe
`/var/lib/tomcat11/`. GeoServer 2.x, que usaba `javax.*` y Tomcat 9, no corre sobre Tomcat 11.

---

## Instalación de dependencias

```bash
sudo apt update
sudo apt install -y gdal-bin unzip wget
```

---

## Instalación de GeoServer

La versión estable es la 3.0.1. Comprueba si hay otra más reciente en <https://geoserver.org/download/> y, si la hay,
cambia `GS_VERSION`.

```bash
export GS_VERSION=3.0.1

cd /tmp
wget -O geoserver-war.zip "https://sourceforge.net/projects/geoserver/files/GeoServer/${GS_VERSION}/geoserver-${GS_VERSION}-war.zip/download"
unzip geoserver-war.zip geoserver.war

# Poner GeoServer en la raíz: se guarda la aplicación ROOT de Tomcat y se sustituye
cd /var/lib/tomcat11/webapps
sudo tar -cf /var/lib/tomcat11/ROOT-bk.tar ROOT
sudo rm -Rf ROOT
sudo mv /tmp/geoserver.war ROOT.war
sudo chown tomcat:tomcat ROOT.war
rm /tmp/geoserver-war.zip

# Reiniciar servicio (Tomcat descomprime ROOT.war en ROOT/)
sudo systemctl restart tomcat11
```

Si prefieres servirlo en `/geoserver`, deja `ROOT` como está y llama al archivo `geoserver.war`.

El despliegue se sigue con `sudo journalctl -u tomcat11 -f`.

---

## Habilitar CORS

El `web.xml` de GeoServer ya trae el filtro de CORS para Tomcat, comentado. Edítalo en la aplicación desplegada:

```bash
sudo nano /var/lib/tomcat11/webapps/ROOT/WEB-INF/web.xml
```

Quita los comentarios (`<!--` y `-->`) de los dos bloques que empiezan así:

```xml
<!-- Uncomment following filter to enable CORS in Tomcat. Do not forget the second config block further down. -->
<filter>
   <filter-name>cross-origin</filter-name>
   <filter-class>org.apache.catalina.filters.CorsFilter</filter-class>
   ...
</filter>

<!-- Uncomment following filter-mapping to enable CORS -->
<filter-mapping>
    <filter-name>cross-origin</filter-name>
    <url-pattern>/*</url-pattern>
</filter-mapping>
```

Reinicia Tomcat:

```bash
sudo systemctl restart tomcat11
```

Si vuelves a desplegar un `ROOT.war` nuevo, Tomcat regenera `ROOT/` y este cambio se pierde: repítelo.

---

## Acceso y credenciales
- Dirección: `http://<servidor>:8080/` (o `/geoserver` si lo desplegaste con ese nombre).
- Usuario por defecto: `admin`
- Contraseña por defecto: `geoserver`

Cámbiala en el primer acceso, en **Seguridad → Users, Groups, Roles** (con la interfaz en español, esa entrada del
menú sigue en inglés).

---

## Directorio de datos

Por defecto, GeoServer guarda su configuración y sus datos dentro de la aplicación
(`/var/lib/tomcat11/webapps/ROOT/data`), y un despliegue nuevo la sustituye. Para conservarla entre versiones, sácala a
un directorio propio, partiendo de la que trae el despliegue:

```bash
sudo mkdir -p /var/lib/geoserver_data
sudo cp -a /var/lib/tomcat11/webapps/ROOT/data/. /var/lib/geoserver_data/
sudo chown -R tomcat:tomcat /var/lib/geoserver_data
```

Después:

1. Permite que el servicio escriba ahí, como explica la guía de Tomcat en «Escribir fuera de webapps».
2. En `/etc/default/tomcat11`, añade a `JAVA_OPTS` la propiedad `-DGEOSERVER_DATA_DIR=/var/lib/geoserver_data`.
3. `sudo systemctl restart tomcat11`.

---

## Recursos útiles
- [Documentación oficial de GeoServer](https://docs.geoserver.org/)
- [Instalación del archivo web (WAR)](https://docs.geoserver.org/main/en/user/installation/war/)
