# Instalación de Tomcat 9 con SSL

## Paquetes generales
```bash
sudo apt update

# Soporte de español, pueden revisarse los idiomas disponibles con locale -a
sudo apt-get install language-pack-es

sudo apt install -y ufw openssl letsencrypt nano unzip zip
```

## Tomcat
```bash
sudo apt install -y tomcat9
```

## Cambiar puerto de Tomcat (opcional)
```bash
# Cambiar a puerto 80: <Connector port="8080" a "80" y <Connector ... redirectPort="8443" a "443"
nano /tomcat/conf/server.xml
```

## Certificado SSL

### Instalación
```bash
export DOMAIN=domain.tld

#Instalar certbot
sudo apt install -y certbot 

## Crear certificado
certbot certonly --standalone -d $DOMAIN

# Verificar
ls /etc/letsencrypt/live/$DOMAIN/ 

# Copiar archivos pertinentes
cd /etc/letsencrypt/live/$DOMAIN/

cp {cert,chain,privkey}.pem /var/lib/tomcat9/conf/

## Permisos
sudo chown tomcat:tomcat /var/lib/tomcat9/conf/*.pem
```

### Configuración

```bash
# Archivo de configuración
sudo nano /var/lib/tomcat9/conf/server.xml
```

```xml
<!-- Agregar configuraciones (el puerto por defecto es 8443) -->
<Connector port="8443" protocol="org.apache.coyote.http11.Http11NioProtocol"
    maxThreads="150" SSLEnabled="true">
    <SSLHostConfig>
        <Certificate certificateFile="/var/lib/tomcat9/conf/cert.pem"
            certificateKeyFile="/var/lib/tomcat9/conf/privkey.pem"
            certificateChainFile="/var/lib/tomcat9/conf/chain.pem" 
            type="RSA" />
    </SSLHostConfig>
</Connector>
```

```bash
# Reiniciar servicio
sudo service tomcat9 restart
```

## Habilitar CORS

```bash
sudo nano /var/lib/tomcat9/conf/web.xml
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

## Asignar más memoria 
```bash
#Añadir export JAVA_OPTS="-Djava.awt.headless=true -Xms1024m -Xmx1024m"
nano /usr/share/tomcat9/bin/setenv.sh

# Reiniciar servicio
sudo service tomcat9 restart
```
