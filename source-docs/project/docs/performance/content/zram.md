# Guía completa: Configurar ZRAM + Swapfile tradicional con rendimiento óptimo

---

## Jerarquía de memoria: ¿qué se prioriza?

El sistema operativo utiliza la memoria en el siguiente orden de preferencia:

| Prioridad | Recurso | Velocidad | Observación |
| :---: | :--- | :--- | :--- |
| 1 | **RAM física** | ~50 GB/s | Siempre la más rápida. El objetivo es que el sistema la agote lo menos posible. |
| 2 | **ZRAM (swap comprimido en RAM)** | ~10-20 GB/s | Usa RAM real para almacenar páginas comprimidas. Mucho más rápido que disco. |
| 3 | **Swapfile en disco (SSD/NVMe)** | ~0.5-3 GB/s | Último recurso. Solo se usa cuando ZRAM ya no puede absorber más. |

> **Regla de oro:** La RAM física es, por mucho, el recurso más valioso. ZRAM es un excelente amortiguador, pero consume ciclos de CPU para comprimir/descomprimir y **ocupa RAM real**. Mientras más RAM física libre tengas, mejor.

---

## 1. Relación óptima: RAM, ZRAM y Swap en disco

### Recomendaciones según caso de uso

| Escenario | ZRAM (% de RAM) | Swapfile en disco | Notas |
| :--- | :---: | :---: | :--- |
| **Estación de trabajo** (≥8 GB RAM) | 50% – 100% | 2 – 4 GB | Absorbe picos por navegadores, IDEs, VMs. |
| **Servidor** (RAM predecible) | 25% – 50% | 1 – 2 GB | Prioriza estabilidad; evita overhead excesivo de CPU. |
| **RAM limitada** (<4 GB, VPS) | 100% | 2 – 4 GB | Compensa la escasez, pero no reemplaza RAM real. |

### ¿Por qué no 200-300%?

ZRAM no reserva toda su capacidad de inmediato, pero cuando se llena, consume RAM real proporcional al ratio de compresión (típicamente 2:1 a 3:1). Un ZRAM al 300% **podría** agotar toda la RAM disponible bajo presión intensa, provocando el **OOM Killer**. Es mejor ser conservador.

**Ejemplo con 8 GB de RAM y ZRAM al 50%:**

```
Capacidad ZRAM configurada: 4 GB (tamaño sin comprimir)
RAM real consumida al llenarse: ~1.3-2 GB (con compresión 2:1 a 3:1)
RAM libre restante para apps: ~6-6.7 GB
```

---

## 2. Instalar y configurar ZRAM

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
ALGO=lz4
PERCENT=50
PRIORITY=100
```

| Parámetro | Valor | Justificación |
| :--- | :---: | :--- |
| `ALGO` | `lz4` | Mejor balance velocidad/eficiencia. Usa `zstd` si necesitas más compresión a costa de CPU. |
| `PERCENT` | `50` | Conservador y seguro. Ajustar según la tabla del punto 1. |
| `PRIORITY` | `100` | Prioridad alta: el kernel usa ZRAM antes que el swapfile de disco. |

Guarda y cierra (`Ctrl+O`, `Enter`, `Ctrl+X`).

---

## 3. Reiniciar servicio ZRAM

```bash
sudo systemctl restart zramswap
sudo systemctl enable zramswap
```

---

## 4. Crear swapfile tradicional (respaldo en disco)

### a) Desactivar todos los swaps activos:

```bash
sudo swapoff -a
```

### b) Eliminar swapfile anterior (si existe):

```bash
sudo rm -f /swapfile
```

### c) Crear nuevo swapfile (ejemplo: 4 GB):

```bash
sudo fallocate -l 4G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
```

### d) Activar swapfile con prioridad baja:

```bash
sudo swapon --priority 10 /swapfile
```

---

## 5. Hacer swapfile permanente

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

## 6. Configurar `vm.swappiness` y parámetros del kernel

### ¿Qué es `vm.swappiness`?

Controla qué tan agresivamente el kernel mueve páginas de memoria a swap. El rango es **0-200** (en kernels modernos ≥5.8).

| Valor | Comportamiento |
| :---: | :--- |
| **0** | El kernel evita swap casi por completo. Solo usa swap bajo OOM inminente. |
| **10-30** | Conservador: prioriza fuertemente la RAM física. Ideal para **máxima velocidad** cuando la RAM alcanza. |
| **60** | Valor por defecto. Balance genérico. |
| **100+** | Agresivo: mueve páginas inactivas a swap tempranamente para liberar RAM para caché de archivos. |

### Recomendación con ZRAM

- **Estaciones de trabajo con RAM suficiente (≥8 GB):** `vm.swappiness = 10` a `30`. Se favorece **la RAM física directa** y ZRAM actúa como colchón suave.
- **Sistemas con RAM escasa (<4 GB):** `vm.swappiness = 60` a `100`. Permite que ZRAM absorba más carga.
- **Servidores:** `vm.swappiness = 10` a `20`. Predecibilidad sobre todo.

### Aplicar de forma persistente

Crea un archivo dedicado en `/etc/sysctl.d/` (no editar `/etc/sysctl.conf` directamente):

```bash
sudo nano /etc/sysctl.d/99-swap-optimizations.conf
```

Agrega el siguiente contenido:

```ini
# Priorizar RAM física: valor bajo = menos agresividad de swap
vm.swappiness = 20

# Desactivar lectura en cluster (optimización para swap en disco, innecesaria para ZRAM)
vm.page-cluster = 0

# Presión mínima de caché de VFS (valor por defecto: 100)
vm.vfs_cache_pressure = 50
```

| Parámetro | Valor | Justificación |
| :--- | :---: | :--- |
| `vm.swappiness` | `20` | Prioriza RAM física. ZRAM solo entra en presión moderada. |
| `vm.page-cluster` | `0` | Evita leer/escribir páginas en bloques. Optimizado para swap en RAM (ZRAM). |
| `vm.vfs_cache_pressure` | `50` | Reduce la presión para desalojar cachés de inodos/dentries, favoreciendo la RAM. |

Aplicar los cambios sin reiniciar:

```bash
sudo sysctl --system
```

Verificar:

```bash
sysctl vm.swappiness vm.page-cluster vm.vfs_cache_pressure
```

Salida esperada:

```
vm.swappiness = 20
vm.page-cluster = 0
vm.vfs_cache_pressure = 50
```

---

## 7. Desactivar `zswap` (evitar conflicto)

`zswap` y `zram` ambos comprimen páginas en RAM. Tener ambos activos causa doble compresión innecesaria.

### Verificar si `zswap` está activo:

```bash
cat /sys/module/zswap/parameters/enabled
```

Si devuelve `Y`, desactívalo:

### a) Temporalmente (hasta reinicio):

```bash
echo 0 | sudo tee /sys/module/zswap/parameters/enabled
```

### b) Permanentemente (vía GRUB):

```bash
sudo nano /etc/default/grub
```

Busca la línea `GRUB_CMDLINE_LINUX_DEFAULT` y agrega `zswap.enabled=0`:

```
GRUB_CMDLINE_LINUX_DEFAULT="quiet splash zswap.enabled=0"
```

Actualiza GRUB:

```bash
sudo update-grub
```

---

## 8. Verificar estado completo

### a) Swaps activos con prioridades:

```bash
swapon --show
```

Salida esperada:

```
NAME       TYPE      SIZE  USED  PRIO
/dev/zram0 partition  4G    0B   100
/swapfile  file       4G    0B    10
```

### b) Detalles de ZRAM (compresión, algoritmo):

```bash
sudo zramctl
```

### c) Uso general de memoria:

```bash
free -h
```

### d) Parámetros del kernel activos:

```bash
sysctl vm.swappiness vm.page-cluster vm.vfs_cache_pressure
```

---

## 9. Reiniciar para validar persistencia

```bash
sudo reboot
```

Al volver a iniciar, verifica **todo**:

```bash
swapon --show
sudo zramctl
free -h
sysctl vm.swappiness vm.page-cluster vm.vfs_cache_pressure
cat /sys/module/zswap/parameters/enabled
```

Todo debe reflejar la configuración aplicada sin intervención manual.

---

## Resumen de archivos modificados

| Archivo | Propósito |
| :--- | :--- |
| `/etc/default/zramswap` | Tamaño, algoritmo y prioridad de ZRAM. |
| `/etc/fstab` | Persistencia del swapfile con prioridad baja. |
| `/etc/sysctl.d/99-swap-optimizations.conf` | `swappiness`, `page-cluster` y `vfs_cache_pressure` persistentes. |
| `/etc/default/grub` | Desactivación permanente de `zswap`. |

---

## Explicación de la estrategia

* **RAM física primero.** Un `swappiness` bajo (20) garantiza que el kernel intente mantener las páginas activas en RAM real el mayor tiempo posible. Solo bajo presión moderada recurre a ZRAM.
* **ZRAM como amortiguador.** Al 50% de la RAM, con `lz4` y `prioridad 100`, ZRAM comprime páginas inactivas eficientemente. Gracias a la compresión ~3:1, 4 GB configurados solo consumen ~1.3 GB reales cuando están llenos.
* **Swapfile como último recurso.** Con `prioridad 10`, el disco solo se toca cuando ZRAM ya se llenó. Esto evita la latencia de I/O en disco casi por completo en uso normal.
* **Sin `zswap`.** Evita la doble compresión y el desperdicio de ciclos de CPU.
* **`page-cluster = 0`.** ZRAM no se beneficia de lectura secuencial como un disco mecánico; leer página por página es más eficiente.
* **`vfs_cache_pressure = 50`.** Reduce la tendencia del kernel a desalojar cachés de metadatos del sistema de archivos, mejorando la velocidad de acceso a archivos.
