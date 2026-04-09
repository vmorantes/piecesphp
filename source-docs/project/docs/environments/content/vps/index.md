# 🐧 Configuración y Tips VPS

Muchos proveedores de nube (como AWS, Google Cloud, OVH o DigitalOcean) entregan las instancias de VPS con configuraciones restrictivas o predefinidas que pueden dificultar la administración habitual. Esta guía recopila soluciones a problemas comunes y pasos para una configuración base robusta.

---

## 🛠️ 1. Habilitar la Cuenta Root

Primero, acceda a su servidor con el usuario proporcionado por el proveedor y asigne una contraseña al usuario `root`.

```bash
# Entrar como superusuario temporalmente
sudo su -

# Asignar contraseña a root
passwd root
```

---

## 🔑 2. Habilitar Login de Root vía SSH

Por defecto, el servidor SSH suele rechazar el acceso directo a `root`. Para habilitarlo:

1.  Abra el archivo de configuración de SSH:
    ```bash
    nano /etc/ssh/sshd_config
    ```

2.  Busque la línea `PermitRootLogin` y cámbiela a:
    ```bash
    PermitRootLogin yes
    ```
    *(Asegúrese de que la línea no esté comentada con un #)*.

3.  Reinicie el servicio para aplicar los cambios:
    ```bash
    systemctl restart ssh
    ```

---

## 🔐 3. Habilitar Autenticación por Contraseña

Si desea poder conectarse sin usar su llave `.pem` o `.pub`, debe habilitar la autenticación por contraseña:

1.  En el mismo archivo `/etc/ssh/sshd_config`, busque `PasswordAuthentication`.
2.  Cambie el valor a `yes`:
    ```bash
    PasswordAuthentication yes
    ```
3.  Reinicie nuevamente el servicio:
    ```bash
    systemctl restart ssh
    ```

---

## 🔍 4. Errores Comunes

### "Please login as the user 'ubuntu' rather than the user 'root'"

Si al intentar entrar como `root` recibe este error, es porque el proveedor ha forzado una restricción en el archivo `authorized_keys`.

**Solución:**
1.  Inicie sesión con el usuario permitido (ej. `ubuntu`).
2.  Edite el archivo de llaves autorizadas de root:
    ```bash
    sudo nano /root/.ssh/authorized_keys
    ```
3.  Elimine todo el texto que aparece **antes** de `ssh-rsa` o `ssh-ed25519`. Normalmente es una cadena que empieza por `no-port-forwarding,no-agent-forwarding...`.
4.  Guarde el archivo. Ahora podrá entrar directamente como `root`.

---

### Error SFTP: "Received unexpected end-of-file"

Este error ocurre cuando SSH funciona correctamente pero la conexión SFTP se cierra de golpe. Suele deberse a una configuración errónea del subsistema SFTP en el servidor.

1.  **Verificar el Subsistema SFTP:**
    Abra el archivo de configuración: `sudo nano /etc/ssh/sshd_config` y busque la línea que empieza por `Subsystem sftp`.

    *   ❌ **Incorrecto:** `Subsystem sftp internal-sftp-server`
    *   ✅ **Correcto (Opción A):** `Subsystem sftp internal-sftp`
    *   ✅ **Correcto (Opción B):** `Subsystem sftp /usr/lib/openssh/sftp-server`

2.  **Reiniciar el servicio:**
    ```bash
    sudo systemctl restart ssh
    ```

3.  **Probar desde consola:**
    Intente conectar directamente para descartar problemas del cliente (como FileZilla):
    ```bash
    sftp usuario@tu-ip
    ```

> [!TIP]
> **Logs de ayuda:** Si el problema persiste, revise los intentos de conexión en tiempo real con:
> `sudo tail -f /var/log/auth.log` (o `/var/log/secure` en CentOS/RHEL).

---


> [!WARNING]
> **Seguridad:** Habilitar el acceso root por contraseña hace que su servidor sea más vulnerable a ataques de fuerza bruta. Se recomienda encarecidamente instalar **Fail2Ban** o deshabilitar el acceso por contraseña una vez finalizada la configuración inicial.
