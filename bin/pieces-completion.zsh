#compdef bin/cli

# Autocompletado para el CLI de PiecesPHP en Zsh
# Uso: source bin/pieces-completion.zsh

_pieces_cli() {
    local -a commands
    # El binario que se está ejecutando actualmente
    local cli_bin="$words[1]"
    
    # Intentar obtener los comandos dinámicamente
    if _call_program commands $cli_bin _list-actions 2>/dev/null; then
        commands=(${(f)"$(_call_program commands $cli_bin _list-actions 2>/dev/null)"})
        _describe 'pieces cli commands' commands
    fi
}

_pieces_cli "$@"
