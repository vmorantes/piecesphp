# Guía completa: Configurar ZRAM + Swapfile tradicional con rendimiento óptimo

---

## Jerarquía de memoria: ¿qué se prioriza?

El sistema operativo utiliza la memoria en el siguiente orden de preferencia:

| Prioridad | Recurso | Velocidad | Observación |
| :---: | :--- | :--- | :--- |
| 1 | **RAM física** | la más rápida | Siempre la más rápida. El objetivo es que el sistema la agote lo menos posible. |
| 2 | **ZRAM (swap comprimido en RAM)** | muy superior al disco | Usa RAM real para almacenar páginas comprimidas. Mucho más rápido que disco. |
| 3 | **Swapfile en disco (SSD/NVMe)** | la más lenta | Último recurso. Solo se usa cuando ZRAM ya no puede absorber más. |

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

El porcentaje fija la capacidad del dispositivo ZRAM **sin comprimir**. Lo que ocupa en RAM real es lo que esa capacidad
ocupa ya comprimida, y la documentación del kernel
([zram](https://docs.kernel.org/admin-guide/blockdev/zram.html)) lo resume así: no tiene sentido crear un ZRAM de más
del doble del tamaño de la memoria, porque se espera una compresión de 2:1; y ZRAM ocupa cerca del 0,1 % de su tamaño
aun sin usarse, así que uno enorme desperdicia memoria. Un ZRAM al 300% que se llenara de datos que comprimen 2:1
necesitaría el 150% de la RAM: bajo presión intensa provoca el **OOM Killer**. La compresión real de tu sistema la
muestra `zramctl` (columnas `DATA`, sin comprimir, y `COMPR`, comprimido).

**Ejemplo con 8 GB de RAM, ZRAM al 50% y la compresión de 2:1 que da el kernel como referencia:**

```
Capacidad ZRAM configurada: 4 GB (tamaño sin comprimir)
RAM real consumida al llenarse: ~2 GB
RAM libre restante para apps: ~6 GB
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

Asegúrate de que contiene estas líneas sin comentar (son los valores con los que lo instala el paquete en Ubuntu 26.04):

```
ALGO=lz4
PERCENT=50
PRIORITY=100
```

`PERCENT` es un porcentaje de la memoria total y, si está definido, manda sobre `SIZE` (un tamaño fijo en MiB).

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

Si `/etc/fstab` tiene otro archivo de swap (por ejemplo `/swap.img`), quita también su línea en el paso 5 y borra ese
archivo.

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

Indica al kernel el coste relativo de usar swap frente a leer del sistema de archivos, y con ello cuánto recurre al swap.
El rango es **0-200** (desde el kernel 5.8; antes, 0-100). Referencia:
[vm.swappiness](https://docs.kernel.org/admin-guide/sysctl/vm.html#swappiness).

| Valor | Comportamiento |
| :---: | :--- |
| **0** | El kernel no empieza a usar swap hasta que la memoria libre y la caché de archivos caen por debajo de su umbral alto. |
| **10-30** | Conservador: prioriza fuertemente la RAM física (y la caché de archivos) frente al swap. |
| **60** | Valor por defecto. |
| **100** | El kernel considera igual de caro el swap que la lectura de archivos. |
| **100-200** | El swap se considera más barato que el disco. La documentación del kernel contempla estos valores para swap en memoria como ZRAM. |

### Recomendación con ZRAM

- **Estaciones de trabajo con RAM suficiente (≥8 GB):** `vm.swappiness = 10` a `30`. Se favorece **la RAM física directa** y ZRAM actúa como colchón suave.
- **Sistemas con RAM escasa (<4 GB):** `vm.swappiness = 60` a `100`. Permite que ZRAM absorba más carga.
- **Servidores:** `vm.swappiness = 10` a `20`. Predecibilidad sobre todo.

Esta guía elige valores bajos para que el swap, aunque sea ZRAM, sea el último recurso. La documentación del kernel
propone lo contrario cuando el swap está en memoria (valores por encima de 100, porque ZRAM es más rápido que el disco).
Las dos estrategias son válidas; la del kernel aprovecha más ZRAM y deja más RAM para caché de archivos.

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

`zswap` es una caché comprimida, en RAM, delante de los dispositivos de swap; ZRAM es en sí un dispositivo de swap
comprimido en RAM. Con los dos activos, las páginas que `zswap` expulsa van a ZRAM y se comprimen dos veces.

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
* **ZRAM como amortiguador.** Al 50% de la RAM, con `lz4` y `prioridad 100`, ZRAM comprime páginas inactivas. Con la compresión de 2:1 que la documentación del kernel da como referencia, 4 GB configurados consumen ~2 GB reales cuando están llenos; `zramctl` muestra la de tu sistema.
* **Swapfile como último recurso.** Con `prioridad 10`, el disco solo se toca cuando ZRAM ya se llenó. Esto evita la latencia de I/O en disco casi por completo en uso normal.
* **Sin `zswap`.** Evita la doble compresión y el desperdicio de ciclos de CPU.
* **`page-cluster = 0`.** Desactiva la lectura anticipada de swap (por defecto lee 8 páginas consecutivas de golpe). En ZRAM no hay búsqueda de disco que amortizar, y leer solo la página pedida reduce la latencia de cada fallo de página.
* **`vfs_cache_pressure = 50`.** Reduce la tendencia del kernel a desalojar cachés de metadatos del sistema de archivos, mejorando la velocidad de acceso a archivos.
