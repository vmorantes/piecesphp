# Instalación de Tomcat 8 (Debian 9)

```bash
# Crear directorio
mkdir /tomcat
cd /tomcat

# Descargar tomcat 8
wget https://www-eu.apache.org/dist/tomcat/tomcat-8/v8.5.50/bin/apache-tomcat-8.5.50.tar.gz
tar xvzf apache-tomcat-8.5.50.tar.gz
rm apache-tomcat-8.5.50.tar.gz
cd apache-tomcat-8.5.50
mv * ../
cd ..

#Verificar o instalar java
java -version
apt install default-jdk default-jre

#Verificar el JAVA_HOME
update-alternatives --config java

# Configurar variables de entorno
nano ~/.bashrc
export JAVA_HOME=/usr/lib/jvm/java-8-openjdk-amd64
export CATALINA_HOME=/tomcat

# Activar tomcat
/tomcat/bin/startup.sh

#Cambiar a puerto 80: <Connector port="8080" a "80" y <Connector ... redirectPort="8443" a "443"
nano /tomcat/conf/server.xml

##Descomentar/Agregar AUTHBIND=yes
nano /etc/default/tomcat7

##Configurar authbind
apt install authbind
touch /etc/authbind/byport/80
chmod 500 /etc/authbind/byport/80
chown root /etc/authbind/byport/80
touch /etc/authbind/byport/443
chmod 500 /etc/authbind/byport/443
chown root /etc/authbind/byport/443
/tomcat/bin/shutdown.sh
/tomcat/bin/startup.sh
```

## Configurar Let´s Encrypt
```bash
#Paquetes
apt install openssl letsencrypt certbot
wget https://dl.eff.org/certbot-auto -P /usr/local/bin
chmod a+x /usr/local/bin/certbot-auto

#Configurar variable útiles
export DOMAIN_TOMCAT_I="DOMINIO.COM"
export EMAIL_ALERT_TOMCAT_I="admin@webmaster"
export PASS_SSL_TOMCAT_I="CONTRASENNA"
export ALIAS_SSL_TOMCAT_I="tomcat_let"

#Crear certificado
certbot-auto certonly --standalone -d $DOMAIN_TOMCAT_I  --preferred-challenges http --agree-tos -n -m  $EMAIL_ALERT_TOMCAT_I --keep-until-expiring

certbot-auto renew

openssl pkcs12 -export -out /etc/letsencrypt/fullchain.p12 -in /etc/letsencrypt/live/$DOMAIN_TOMCAT_I/fullchain.pem -inkey /etc/letsencrypt/live/$DOMAIN_TOMCAT_I/privkey.pem -name $ALIAS_SSL_TOMCAT_I

keytool -importkeystore -deststorepass $PASS_SSL_TOMCAT_I -destkeypass $PASS_SSL_TOMCAT_I -destkeystore /etc/letsencrypt/$DOMAIN_TOMCAT_I.jks -srckeystore /etc/letsencrypt/fullchain.p12  -srcstoretype PKCS12 -srcstorepass $PASS_SSL_TOMCAT_I -alias $ALIAS_SSL_TOMCAT_I

#Configurar servidor
nano /tomcat/conf/server.xml
```
```xml
<Connector executor="tomcatThreadPool" ... />
...
<Connector port="443" protocol="org.apache.coyote.http11.Http11Protocol"
            maxThreads="150" SSLEnabled="true" scheme="https" secure="true"
            keystoreFile="/etc/letsencrypt/DOMINIO.COM.jks"
            keystorePass="CONTRASENNA"
            clientAuth="false" sslProtocol="TLS" />
```
```bash
#Reiniciar servidor
/tomcat/bin/shutdown.sh
/tomcat/bin/startup.sh
```

## Habilitar CORS
```bash
nano /tomcat/conf/web.xml
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
#Reiniciar servidor
/tomcat/bin/shutdown.sh
/tomcat/bin/startup.sh
```

## Asignar más memoria 
```bash
#Añadir export JAVA_OPTS="-Djava.awt.headless=true -Xms1024m -Xmx1024m"
nano /tomcat/bin/setenv.sh

#Reiniciar servidor
/tomcat/bin/shutdown.sh
/tomcat/bin/startup.sh
```
