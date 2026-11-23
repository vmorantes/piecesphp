#!/bin/bash

#==============================================
# Ajustes generales de permisos en proyectos de PiecesPHP.
#==============================================

# Ir al raíz del proyecto
SCRIPT_DIR=$(cd $(dirname "${BASH_SOURCE[0]}") && pwd)
cd "$SCRIPT_DIR"

# Establecer variables utilitarias
echo -n "Ingrese del usuario propietario (defecto: ${USER}): "
read -r INPUT_OWNER_USER

echo -n "Ingrese del grupo propietario (defecto: www-data): "
read -r INPUT_OWNER_GROUP

export USER_OWNER=${INPUT_OWNER_USER:="$USER"}
export GROUP_OWNER=${INPUT_OWNER_GROUP:="www-data"}

# Moverse a carpeta src
export SRC_DIR="$SCRIPT_DIR/src"

echo -e "\n[+] Carpeta src: $SRC_DIR"

cd "$SRC_DIR"

# Ajustes de propiedad
echo -e "\n[+] Estableciendo propiedad ($USER_OWNER:$GROUP_OWNER)..."
sudo chown -R "$USER_OWNER":"$GROUP_OWNER" .

# Permisos restrictivos generales
echo -e "\n[+] Estableciendo permisos generales (755 para directorios y 644 para archivos)..."
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Directorios de aplicación que requieren permisos de escritura
echo -e "\n[+] Estableciendo permisos para directorios de aplicación que requieren permisos de escritura..."
echo -e "\n\t[+] Directorios: 2775 y archivos: 664"

#La lista vive en app/config/writable-dirs.txt (relativa a src/): la leen los dos guiones y la Salud de la instalación.
mapfile -t WRITABLE_DIRS < <(grep -v -e '^#' -e '^[[:space:]]*$' "app/config/writable-dirs.txt")

for dir in "${WRITABLE_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo -e "\n\t->[+] Permisos para $dir"
        # Directorios
        find "$dir" -type d -exec chmod 2775 {} \;
        # Archivos
        find "$dir" -type f -exec chmod 664 {} \;
    fi
done

# Carpeta de claves de la raíz (el llavero de la aplicación, ADR 0061): la escriben la terminal y el servidor web.
#Con private/secure-keys en uso no se crea la de la raíz: AppKeyring::directory() la miraría primero y cambiaría de llavero.
KEYS_DIR="$SCRIPT_DIR/secure-keys"
KEYS_FILE="$KEYS_DIR/app-key.json"

cd "$SCRIPT_DIR"

if [ ! -d "$KEYS_DIR" ] && [ -d "$SCRIPT_DIR/private/secure-keys" ]; then
    echo -e "\n[!] Se usa private/secure-keys: no se crea $KEYS_DIR (ajuste esa carpeta a mano: grupo $GROUP_OWNER, 2770; app-key.json 0640)."
else
    echo -e "\n[+] Carpeta de claves: $KEYS_DIR ($USER_OWNER:$GROUP_OWNER, 2770; app-key.json 0640)"
    if [ ! -d "$KEYS_DIR" ]; then
        mkdir -p "$KEYS_DIR"
    fi
    sudo chown "$USER_OWNER":"$GROUP_OWNER" "$KEYS_DIR"
    sudo chmod 2770 "$KEYS_DIR"
    if [ -f "$KEYS_FILE" ]; then
        sudo chown "$USER_OWNER":"$GROUP_OWNER" "$KEYS_FILE"
        sudo chmod 0640 "$KEYS_FILE"
    fi
fi

# Permisos de scripts de utilidad
export EXECUTABLES_DIR="${SCRIPT_DIR}/bin"

cd "$EXECUTABLES_DIR"

echo -e "\n[+] Estableciendo permisos de utilidades en $EXECUTABLES_DIR"
echo -e "\n\t[+] scripts: +x"
echo -e "\n\t[+] scripts: 744"
echo -e "\n\t[+] scripts: $USER_OWNER:$USER_OWNER"

EXECUTABLES_FILES=(
    "bin/node/copyDependencies.sh"
    "cli"
    "package-css"
    "phpstan"
    "pieces-completion.zsh"
    "pieces-completion.bash"
)


for file in "${EXECUTABLES_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "\n\t->[+] Permisos para $file"
        sudo chmod +x "$file"
        sudo chmod 744 "$file"
        sudo chown "$USER_OWNER":"$USER_OWNER" "$file"
    fi
done

cd $SCRIPT_DIR

echo -e "\n[+] Procedimiento finalizado."
