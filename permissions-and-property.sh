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
echo -e "\n[+] Estableciendo permisos generales (775 para directorios y 664 para archivos)..."
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Directorios de aplicación que requieren permisos de escritura
echo -e "\n[+] Estableciendo permisos para directorios de aplicación que requieren permisos de escritura..."
echo -e "\n\t[+] Directorios: 2775 y archivos: 664"

WRITABLE_DIRS=(
    "app/logs"
    "app/cache"
    "app/lang/missing-lang-messages"
    "app/lang/dynamic-translations"
    "tmp"
    "dumps"
    "statics/css"
    "statics/server-delegated"
    "statics/uploads"
    "statics/filemanager"
)

for dir in "${WRITABLE_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo -e "\n\t->[+] Permisos para $dir"
        # Directorios
        find "$dir" -type d -exec chmod 2775 {} \;
        # Archivos
        find "$dir" -type f -exec chmod 664 {} \;
    fi
done

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
