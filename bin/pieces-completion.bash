#!/bin/bash

# Autocompletado para el CLI de PiecesPHP
# Uso: source bin/pieces-completion.bash

_pieces_cli_completion() {
    local cur prev opts
    COMPREPLY=()
    cur="${COMP_WORDS[COMP_CWORD]}"
    prev="${COMP_WORDS[COMP_CWORD-1]}"

    # Determinar si estamos autocompletando el primer argumento (el comando)
    if [[ ${COMP_CWORD} -eq 1 ]]; then
        # El binario que se está ejecutando actualmente
        local cli_bin="${COMP_WORDS[0]}"
        
        # Intentar obtener la lista de comandos disponibles
        if opts=$($cli_bin _list-actions 2>/dev/null); then
            COMPREPLY=( $(compgen -W "${opts}" -- ${cur}) )
        fi
        return 0
    fi
}

# Registrar la función de completado para el script bin/cli
complete -F _pieces_cli_completion ./bin/cli
complete -F _pieces_cli_completion bin/cli
