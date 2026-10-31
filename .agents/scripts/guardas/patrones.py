"""
Patrones de ATRIBUCIÓN a una IA que no pueden aparecer en lo que se entrega
(regla 40-salvaguardas.md §5, ADR 0003). Compartido por la guarda de hooks
y por menciones_ia.py, para que los dos juzguen igual.

Aquí NO se persigue el vocabulario: el producto tiene funciones de IA reales
(adaptadores de OpenAI y Groq, un plugin de Gemini para adminer, textos de
interfaz sobre inteligencia artificial). Lo prohibido es firmar el trabajo
como hecho por una IA.
"""
import os
import re
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import proyecto  # noqa: E402

# Quién podría figurar como autor.
_AUTOR = (
    r"(la |una |el |un )?"
    r"(ia|ai|inteligencia artificial|artificial intelligence|modelo|agente|asistente|"
    r"an? (ai|llm|language model|assistant)|llm|claude|chatgpt|gpt-?\d\w*|copilot|gemini|codex|cursor)\b"
)

_PATRONES = [
    # Coautoría de un agente.
    r"co-authored-by:.*(claude|anthropic|openai|chatgpt|copilot|gemini|cursor|codex)",
    # «Generated with/by X» y sus equivalentes en español.
    r"generated (with|by|using) " + _AUTOR,
    r"generad[oa]s? (con|por|mediante|usando) " + _AUTOR,
    r"(escrit|hech|redactad)[oa]s? (con|por) " + _AUTOR,
    # El nombre del asistente o de su fabricante. Las rutas .claude/ y el
    # archivo CLAUDE.md no cuentan: la documentación puede señalar dónde vive
    # la configuración.
    r"(?<![.\w/-])claude(?![\w-]|\.md)",
    r"\banthropic\b",
    "\U0001F916",  # emoji de robot
]

PATRONES = [re.compile(p, re.IGNORECASE) for p in _PATRONES]

# Rutas entregables, relativas a la raíz. Fuera de ellas (.agents/, .claude/,
# AGENTS.md, CLAUDE.md) hablar de agentes es el tema, no una firma.
# Salen de .agents/project-architect-coder.json; lo excluido es código de terceros.
_RAIZ = os.path.realpath(
    os.environ.get("CLAUDE_PROJECT_DIR") or os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", "..")
)
_ENTREGABLES = (proyecto.cargar(_RAIZ)[0] or {}).get("deliverables", {})
PREFIJOS_ENTREGABLES = tuple(_ENTREGABLES.get("prefixes", ()))
ARCHIVOS_ENTREGABLES = tuple(_ENTREGABLES.get("files", ()))
EXCLUIDOS = tuple(_ENTREGABLES.get("excluded", ()))


def es_entregable(ruta_relativa):
    ruta = ruta_relativa.replace("\\", "/")
    if ruta.startswith(EXCLUIDOS) or "/node_modules/" in ruta:
        return False
    return ruta.startswith(PREFIJOS_ENTREGABLES) or ruta in ARCHIVOS_ENTREGABLES


def buscar(texto):
    """Devuelve [(número de línea, fragmento)] de cada coincidencia."""
    hallazgos = []
    for n, linea in enumerate(texto.splitlines(), 1):
        for patron in PATRONES:
            m = patron.search(linea)
            if m:
                hallazgos.append((n, m.group(0)))
                break
    return hallazgos
