# Instalación de GeoServer 2.20.x en Ubuntu 24.04 LTS

## Introducción
GeoServer es una plataforma open source para compartir, procesar y editar datos geoespaciales.

---

## Instalación de dependencias

```bash
sudo apt update
sudo apt install gdal-bin unzip wget -y
```

---

## Instalación de GeoServer

```bash
cd /var/lib/tomcat9/
wget -O geoserver.zip https://sourceforge.net/projects/geoserver/files/GeoServer/2.20.4/geoserver-2.20.4-war.zip
unzip geoserver.zip -d /var/lib/tomcat9/webapps
cd /var/lib/tomcat9/webapps
rm -Rf target GPL.txt LICENSE.txt README.txt NOTICE.md  license

# Poner GeoServer en la raíz
tar -cvf ROOT-bk.tar ROOT
rm -Rf ROOT
mv ROOT-bk.tar ../ROOT-bk.tar
mv geoserver.war ROOT.war

# Reiniciar servicio
sudo systemctl restart tomcat9
```

---

## Habilitar CORS

Edita el archivo de configuración:

```bash
sudo nano /var/lib/tomcat9/webapps/ROOT/WEB-INF/web.xml
```

Agrega el siguiente bloque:

```xml
<filter>
	<filter-name>CorsFilter</filter-name>
	<filter-class>org.apache.catalina.filters.CorsFilter</filter-class>
	<init-param>
		<param-name>cors.allowed.origins</param-name>
		<param-value>*</param-value>
	</init-param>
</filter>
<filter-mapping>
	<filter-name>CorsFilter</filter-name>
	<url-pattern>/*</url-pattern>
</filter-mapping>
```

Reinicia Tomcat:

```bash
# Reiniciar servicio
sudo systemctl restart tomcat9
```

---

## Acceso y credenciales
- Usuario por defecto: `admin`
- Contraseña por defecto: `geoserver`

---

## Recursos útiles
- [Documentación oficial de GeoServer](https://docs.geoserver.org/)
