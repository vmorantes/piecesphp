# Instalación de GeoServer 2.20
- Nota: El usuario por defecto es admin y la contrañesa geoserver

```bash
# Paquetes útiles
apt install gdal-bin

# Instalación de GeoServer
cd /var/lib/tomcat9/
wget -O geoserver.zip https://sourceforge.net/projects/geoserver/files/GeoServer/2.20.4/geoserver-2.20.4-war.zip
unzip geoserver.zip -d /var/lib/tomcat9/webapps
cd /var/lib/tomcat9/webapps
rm -Rf target GPL.txt LICENSE.txt README.txt NOTICE.md  license

# Poner Geoserver en la raíz
zip -r ROOT-bk.zip ROOT
rm -Rf ROOT
mv ROOT-bk.zip ../ROOT-bk.zip
mv geoserver.war ROOT.war

# Reiniciar servicio
sudo service tomcat9 restart
```

## Habilitar CORS

```bash
nano /var/lib/tomcat9/webapps/ROOT/WEB-INF/web.xml
```

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

```bash
# Reiniciar servicio
sudo service tomcat9 restart
```
