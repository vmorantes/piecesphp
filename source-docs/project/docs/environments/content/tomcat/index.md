# Instalación de Tomcat 11 con SSL (Ubuntu 26.04 LTS)

Ubuntu 26.04 LTS trae Tomcat 11 en el repositorio *universe* (paquete `tomcat11`, rama 11.0.x). Es la versión que pide
GeoServer 3 (ver la guía de GeoServer), que además exige Java 17 o 21. El Java por defecto de Ubuntu 26.04 es el 25,
así que esta guía instala primero OpenJDK 21 y fija Tomcat sobre él.

El paquete `tomcat9` ya no existe en Ubuntu 26.04. Si necesitas desplegar una aplicación antigua que use `javax.*`
(Tomcat 9), esta guía no te sirve tal cual.

## Paquetes generales

```bash
sudo apt update

# Soporte de español, pueden revisarse los idiomas disponibles con locale -a
sudo apt install -y language-pack-es

sudo apt install -y ufw openssl nano unzip zip
```

## Java 21 y Tomcat 11

Primero Java 21: así `apt` no instala el Java 25 por defecto para satisfacer la dependencia de Tomcat.

```bash
sudo apt install -y openjdk-21-jre-headless
sudo apt install -y tomcat11
```

Fija el Java de Tomcat en `/etc/default/tomcat11` (en una máquina `arm64`, la ruta termina en `-arm64`; compruébala con
`ls /usr/lib/jvm/`):

```bash
sudo nano /etc/default/tomcat11
```

```bash
JAVA_HOME=/usr/lib/jvm/java-21-openjdk-amd64
```

```bash
sudo systemctl restart tomcat11
sudo systemctl status tomcat11
```

Tomcat responde en `http://<servidor>:8080/`. Rutas del paquete:

| Qué | Dónde |
| :-- | :-- |
| Configuración (`server.xml`, `web.xml`) | `/etc/tomcat11/` (también accesible como `/var/lib/tomcat11/conf`) |
| Aplicaciones desplegadas | `/var/lib/tomcat11/webapps/` |
| Registros | `/var/log/tomcat11/` |
| Opciones del servicio (`JAVA_HOME`, `JAVA_OPTS`) | `/etc/default/tomcat11` |

El servicio corre como el usuario `tomcat` con `ProtectSystem=strict`: solo puede escribir en
`/var/lib/tomcat11/webapps/`, `/var/log/tomcat11/` y `/etc/tomcat11/Catalina/`. Una aplicación que necesite escribir
en otra ruta necesita un `ReadWritePaths=` adicional (ver «Escribir fuera de webapps»).

## Cambiar puerto de Tomcat (opcional)

```bash
# Cambiar a puerto 80: <Connector port="8080" a "80" y <Connector ... redirectPort="8443" a "443"
sudo nano /etc/tomcat11/server.xml
sudo systemctl restart tomcat11
```

El servicio ya tiene la capacidad `CAP_NET_BIND_SERVICE`, así que puede escuchar en el 80 y el 443 sin correr como
`root`.

## Certificado SSL

### Instalación

`certbot --standalone` levanta su propio servidor en el puerto 80. Si Tomcat escucha en el 80, detenlo mientras se
emite el certificado; los ganchos `--pre-hook` y `--post-hook` quedan guardados y se repiten en cada renovación.

```bash
export DOMAIN=domain.tld

# Instalar certbot
sudo apt install -y certbot

# Crear certificado (detiene Tomcat mientras certbot usa el puerto 80)
sudo certbot certonly --standalone -d $DOMAIN \
    --pre-hook "systemctl stop tomcat11" \
    --post-hook "systemctl start tomcat11"

# Verificar
sudo ls /etc/letsencrypt/live/$DOMAIN/

# Copiar los archivos a la configuración de Tomcat (legibles por el grupo tomcat, no modificables)
sudo cp /etc/letsencrypt/live/$DOMAIN/{cert,chain,privkey}.pem /etc/tomcat11/
sudo chown root:tomcat /etc/tomcat11/{cert,chain,privkey}.pem
sudo chmod 640 /etc/tomcat11/{cert,chain,privkey}.pem
```

Los certificados de Let's Encrypt caducan a los 90 días y `certbot` los renueva solo, pero Tomcat lee la copia. Para que
cada renovación la actualice, crea un gancho de despliegue:

```bash
sudo tee /etc/letsencrypt/renewal-hooks/deploy/tomcat11.sh > /dev/null <<'EOF'
#!/bin/sh
cp "$RENEWED_LINEAGE"/cert.pem "$RENEWED_LINEAGE"/chain.pem "$RENEWED_LINEAGE"/privkey.pem /etc/tomcat11/
chown root:tomcat /etc/tomcat11/cert.pem /etc/tomcat11/chain.pem /etc/tomcat11/privkey.pem
chmod 640 /etc/tomcat11/cert.pem /etc/tomcat11/chain.pem /etc/tomcat11/privkey.pem
systemctl restart tomcat11
EOF
sudo chmod 755 /etc/letsencrypt/renewal-hooks/deploy/tomcat11.sh

# Simular una renovación
sudo certbot renew --dry-run
```

### Configuración

```bash
# Archivo de configuración
sudo nano /etc/tomcat11/server.xml
```

```xml
<!-- Agregar configuraciones (el puerto por defecto es 8443) -->
<Connector port="8443" protocol="org.apache.coyote.http11.Http11NioProtocol"
    maxThreads="150" SSLEnabled="true">
    <SSLHostConfig>
        <Certificate certificateFile="/etc/tomcat11/cert.pem"
            certificateKeyFile="/etc/tomcat11/privkey.pem"
            certificateChainFile="/etc/tomcat11/chain.pem"
            type="RSA" />
    </SSLHostConfig>
</Connector>
```

```bash
# Reiniciar servicio
sudo systemctl restart tomcat11
```

Si abres los puertos con `ufw`, permite el SSH antes de activarlo:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 8080/tcp
sudo ufw allow 8443/tcp
sudo ufw enable
```

## Habilitar CORS

Para todas las aplicaciones del servidor, en el `web.xml` global (para una sola aplicación, en el
`WEB-INF/web.xml` de esa aplicación; GeoServer trae su propio bloque, ver su guía):

```bash
sudo nano /etc/tomcat11/web.xml
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
sudo systemctl restart tomcat11
```

## Asignar más memoria

El script de arranque del paquete lee `JAVA_OPTS` de `/etc/default/tomcat11`:

```bash
sudo nano /etc/default/tomcat11
# Dejar la línea así:
# JAVA_OPTS="-Djava.awt.headless=true -Xms1024m -Xmx1024m"

# Reiniciar servicio
sudo systemctl restart tomcat11
```

## Escribir fuera de webapps

Si una aplicación necesita escribir en otra carpeta (por ejemplo, un directorio de datos de GeoServer en
`/var/lib/geoserver_data`), amplía el servicio con un *override* de systemd:

```bash
sudo systemctl edit tomcat11
```

```ini
[Service]
ReadWritePaths=/var/lib/geoserver_data/
```

```bash
sudo systemctl restart tomcat11
```

## Recursos útiles

- [Documentación de Tomcat 11](https://tomcat.apache.org/tomcat-11.0-doc/index.html)
