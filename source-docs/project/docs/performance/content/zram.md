# Guía completa: Configurar ZRAM + Swapfile tradicional con prioridades optimizadas

---

## 1. Instalar y configurar ZRAM

### a) Instalar paquete `zram-tools`

```bash
sudo apt update
sudo apt install zram-tools -y
```

### b) Configurar tamaño de ZRAM

Edita el archivo de configuración:

```bash
sudo nano /etc/default/zramswap
```

Asegúrate que contiene estas líneas:

```
ENABLED=true
PERCENTAGE=300
```

Esto configura ZRAM para usar 300% de la RAM física.

Guarda y cierra (`Ctrl+O`, `Enter`, `Ctrl+X`).

---

## 2. Reiniciar servicio ZRAM

```bash
sudo systemctl restart zramswap
```

---

## 3. Crear swapfile tradicional (ejemplo 15 GB)

### a) Desactivar todos los swaps activos:

```bash
sudo swapoff -a
```

### b) Eliminar swapfile anterior (si existe):

```bash
sudo rm -f /swapfile
```

### c) Crear nuevo swapfile:

```bash
sudo fallocate -l 15G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
```

---

## 4. Activar swapfile con prioridad 10

```bash
sudo swapon --priority 10 /swapfile
```

---

## 5. Activar manualmente ZRAM como swap con prioridad 100

A veces el servicio no lo hace automáticamente, por lo que:

```bash
sudo mkswap /dev/zram0
sudo swapon --priority 100 /dev/zram0
```

---

## 6. Hacer swapfile permanente con prioridad

Edita `/etc/fstab`:

```bash
sudo nano /etc/fstab
```

Agrega o reemplaza la línea para el swapfile:

```
/swapfile none swap sw,pri=10 0 0
```

Guarda y cierra.

---

## 7. Verificar estado

### a) Mostrar swaps activos:

```bash
swapon --show
```

Debe mostrar:

```
NAME       TYPE      SIZE USED PRIO
/dev/zram0 partition XG   0B  100
/swapfile  file       15G   0B   10
```

### b) Ver detalles de ZRAM

```bash
sudo zramctl
```

---

## 8. Reiniciar para validar persistencia

Reinicia el sistema:

```bash
sudo reboot
```

Al volver a iniciar, revisa con:

```bash
swapon --show
sudo zramctl
free -h
```

---

# Explicación breve

* **ZRAM** es un swap comprimido en RAM rápida, con prioridad alta para acelerar uso de memoria virtual.
* **Swapfile** es respaldo en disco, más lento, con prioridad menor.
* Configurar prioridades permite que ZRAM se use primero, y disco solo cuando sea necesario.
* Esto mejora el rendimiento en VPS o PCs con RAM limitada.
