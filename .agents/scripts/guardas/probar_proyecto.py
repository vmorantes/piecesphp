#!/usr/bin/env python3
"""Pruebas del validador de la configuración del proyecto, con valores inventados."""
import copy
import importlib
import json
import os
import shutil
import subprocess
import sys
import tempfile
import unittest

AQUI = os.path.dirname(os.path.abspath(__file__))
GUION = os.path.join(AQUI, "proyecto.py")
TIEMPO_LIMITE = 10


def ejecutar(raiz):
    orden = [sys.executable, "-B", GUION, "--raiz", raiz]
    return subprocess.run(orden, capture_output=True, text=True, timeout=TIEMPO_LIMITE)


def escribir_config(raiz, datos):
    ruta = os.path.join(raiz, ".agents", "project-architect-coder.json")
    os.makedirs(os.path.dirname(ruta), exist_ok=True)
    with open(ruta, "w", encoding="utf-8") as f:
        json.dump(datos, f)
    return ruta


def config_base():
    return {
        "format": 1,
        "project": "Prueba",
        "sessions": {"architect": "Prueba-Arquitecto-Main", "coder": "Prueba-Coder-Main"},
        "guard": {
            "sibling_repositories": [
                {"path": "../vecino", "can_tag": True, "creatable_branches": ["rama-prueba"]},
            ],
            "banned_scripts": ["scripts/peligroso.sh"],
            "extra_banned_commands": ["herramienta"],
            "forbidden_reads": ["claves/**"],
            "allowed_dependencies": [
                {"manager": "composer", "command": "update", "packages": ["paquete/prueba"],
                 "directories": ["herramientas/"], "reason": "motivo de prueba"},
            ],
        },
        "deliverables": {"prefixes": ["herramientas/"], "files": ["README.md"], "excluded": []},
    }


class PruebasProyecto(unittest.TestCase):

    def test_r1_base_legitima(self):
        with tempfile.TemporaryDirectory() as tmp:
            escribir_config(tmp, config_base())
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)
            self.assertIn("PROYECTO OK: Prueba", r.stdout)

    def test_r2_sin_archivo(self):
        with tempfile.TemporaryDirectory() as tmp:
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("no existe", r.stderr)

    def test_r2_json_roto(self):
        with tempfile.TemporaryDirectory() as tmp:
            ruta = os.path.join(tmp, ".agents", "project-architect-coder.json")
            os.makedirs(os.path.dirname(ruta), exist_ok=True)
            with open(ruta, "w", encoding="utf-8") as f:
                f.write("{ esto no es json")
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("JSON inválido", r.stderr)

    def test_r2_formato(self):
        for valor in (2, "1", True):
            with self.subTest(valor=valor):
                with tempfile.TemporaryDirectory() as tmp:
                    datos = copy.deepcopy(config_base())
                    datos["format"] = valor
                    escribir_config(tmp, datos)
                    r = ejecutar(tmp)
                    self.assertEqual(r.returncode, 1)
                    self.assertIn("formato", r.stderr)

    def test_r3_falta_el_campo_raiz(self):
        for campo in ("project", "sessions", "guard", "deliverables"):
            with self.subTest(campo=campo):
                with tempfile.TemporaryDirectory() as tmp:
                    datos = copy.deepcopy(config_base())
                    del datos[campo]
                    escribir_config(tmp, datos)
                    r = ejecutar(tmp)
                    self.assertEqual(r.returncode, 1)
                    self.assertIn("falta el campo", r.stderr)

    def test_r3_falta_el_campo_anidado(self):
        casos = [("guard", "banned_scripts"), ("deliverables", "files")]
        for contenedor, clave in casos:
            with self.subTest(campo="%s.%s" % (contenedor, clave)):
                with tempfile.TemporaryDirectory() as tmp:
                    datos = copy.deepcopy(config_base())
                    del datos[contenedor][clave]
                    escribir_config(tmp, datos)
                    r = ejecutar(tmp)
                    self.assertEqual(r.returncode, 1)
                    self.assertIn("falta el campo", r.stderr)

    def test_r4_clave_desconocida(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["extra"] = 1
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("clave desconocida", r.stderr)

        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["extra"] = 1
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("clave desconocida", r.stderr)

        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["sibling_repositories"][0]["extra"] = 1
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("clave desconocida", r.stderr)

    def test_r5_tipo_invalido_project(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["project"] = 5
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("tipo inválido", r.stderr)

    def test_r5_can_tag_invalido(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["sibling_repositories"][0]["can_tag"] = "sí"
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("tipo inválido", r.stderr)

    def test_r5_can_tag_valido(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["sibling_repositories"][0]["can_tag"] = True
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r6_vacio(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["project"] = "  "
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("vacío", r.stderr)

    def test_r7_sibling_ruta_invalida(self):
        for valor in ("/abs", "otro", "../otro/../x"):
            with self.subTest(valor=valor):
                with tempfile.TemporaryDirectory() as tmp:
                    datos = copy.deepcopy(config_base())
                    datos["guard"]["sibling_repositories"][0]["path"] = valor
                    escribir_config(tmp, datos)
                    r = ejecutar(tmp)
                    self.assertEqual(r.returncode, 1)
                    self.assertIn("ruta inválida", r.stderr)

    def test_r7_sibling_ruta_valida(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["sibling_repositories"][0]["path"] = "../vecino"
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r8_forbidden_reads_invalidos(self):
        for valor in ("../x", "/etc/x"):
            with self.subTest(valor=valor):
                with tempfile.TemporaryDirectory() as tmp:
                    datos = copy.deepcopy(config_base())
                    datos["guard"]["forbidden_reads"] = [valor]
                    escribir_config(tmp, datos)
                    r = ejecutar(tmp)
                    self.assertEqual(r.returncode, 1)
                    self.assertIn("ruta inválida", r.stderr)

    def test_r8_forbidden_reads_valido(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["forbidden_reads"] = ["claves/**"]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r8_forbidden_reads_home_validos(self):
        for valor in ("~/.ssh/**", "~/.config/app/*.json"):
            with self.subTest(valor=valor):
                with tempfile.TemporaryDirectory() as tmp:
                    datos = copy.deepcopy(config_base())
                    datos["guard"]["forbidden_reads"] = [valor]
                    escribir_config(tmp, datos)
                    r = ejecutar(tmp)
                    self.assertEqual(r.returncode, 0, r.stderr)

    def test_r8_forbidden_reads_home_invalidos(self):
        for valor in ("~", "~/", "~otro/x", "~/../x", "/srv/x/.ssh", "~/a/./b"):
            with self.subTest(valor=valor):
                with tempfile.TemporaryDirectory() as tmp:
                    datos = copy.deepcopy(config_base())
                    datos["guard"]["forbidden_reads"] = [valor]
                    escribir_config(tmp, datos)
                    r = ejecutar(tmp)
                    self.assertEqual(r.returncode, 1)
                    self.assertIn("ruta inválida", r.stderr)

    def test_r9_gestor_desconocido(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["allowed_dependencies"][0]["manager"] = "brew"
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("gestor desconocido", r.stderr)

    def test_r9_gestor_valido(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["allowed_dependencies"][0]["manager"] = "composer"
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r9_packages_vacio(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["allowed_dependencies"][0]["packages"] = []
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("vacío", r.stderr)

    def test_r9_reason_vacio(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["allowed_dependencies"][0]["reason"] = "  "
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("vacío", r.stderr)

    def test_r10_prefix_sin_barra(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["deliverables"]["prefixes"] = ["src"]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("ruta inválida", r.stderr)

    def test_r10_prefix_con_barra(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["deliverables"]["prefixes"] = ["src/"]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r10_file_con_barra(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["deliverables"]["files"] = ["doc/"]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("ruta inválida", r.stderr)

    def test_r11_orden_invalida(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["extra_banned_commands"] = ["rm -rf"]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("nombre de orden inválido", r.stderr)

    def test_r11_orden_valida(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["extra_banned_commands"] = ["herramienta"]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r12_duplicado(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["sibling_repositories"].append(
                copy.deepcopy(datos["guard"]["sibling_repositories"][0]))
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("duplicado", r.stderr)

    def test_r13_un_solo_error(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            del datos["project"]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            ultima_linea = r.stderr.strip().splitlines()[-1]
            self.assertEqual(ultima_linea, "PROYECTO ROTO: 1 error")

    def test_r13_dos_errores(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            del datos["project"]
            del datos["sessions"]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            ultima_linea = r.stderr.strip().splitlines()[-1]
            self.assertEqual(ultima_linea, "PROYECTO ROTO: 2 errores")

    def test_r14_sin_raiz_usa_la_propia(self):
        with tempfile.TemporaryDirectory() as tmp:
            destino_dir = os.path.join(tmp, ".agents", "scripts", "guardas")
            os.makedirs(destino_dir, exist_ok=True)
            destino = os.path.join(destino_dir, "proyecto.py")
            shutil.copyfile(GUION, destino)
            escribir_config(tmp, config_base())

            orden = [sys.executable, "-B", destino]
            r = subprocess.run(orden, capture_output=True, text=True, timeout=TIEMPO_LIMITE)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r15_api(self):
        sys.path.insert(0, AQUI)
        try:
            import proyecto
            importlib.reload(proyecto)

            with tempfile.TemporaryDirectory() as tmp:
                escribir_config(tmp, config_base())
                config, errores = proyecto.cargar(tmp)
                self.assertEqual(errores, [])
                self.assertIsInstance(config, dict)
                self.assertEqual(config["project"], "Prueba")

            with tempfile.TemporaryDirectory() as tmp:
                datos = copy.deepcopy(config_base())
                del datos["project"]
                escribir_config(tmp, datos)
                config, errores = proyecto.cargar(tmp)
                self.assertIsNone(config)
                self.assertTrue(errores)
        finally:
            sys.path.remove(AQUI)

    def test_r16_orden_con_tabulacion(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["extra_banned_commands"] = ["a\tb"]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("nombre de orden inválido", r.stderr)

    def test_r16_orden_valida(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["extra_banned_commands"] = ["abc"]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r17_directorio_hermano_valido(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["allowed_dependencies"][0]["directories"] = ["../vecino"]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r17_directorio_invalido(self):
        for valor in ("/abs", "../x/../y"):
            with self.subTest(valor=valor):
                with tempfile.TemporaryDirectory() as tmp:
                    datos = copy.deepcopy(config_base())
                    datos["guard"]["allowed_dependencies"][0]["directories"] = [valor]
                    escribir_config(tmp, datos)
                    r = ejecutar(tmp)
                    self.assertEqual(r.returncode, 1)
                    self.assertIn("ruta inválida", r.stderr)

    def test_r18_falta_budget_es_legitimo(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            self.assertNotIn("budget", datos)
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r18_budget_rules_words_valido(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["budget"] = {"rules_words": 3500}
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r18_budget_vacio_falta_el_campo(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["budget"] = {}
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("falta el campo", r.stderr)
            self.assertIn("budget.rules_words", r.stderr)

    def test_r18_budget_rules_words_invalido(self):
        for valor in (0, -1, "3500", True, 1.5):
            with self.subTest(valor=valor):
                with tempfile.TemporaryDirectory() as tmp:
                    datos = copy.deepcopy(config_base())
                    datos["budget"] = {"rules_words": valor}
                    escribir_config(tmp, datos)
                    r = ejecutar(tmp)
                    self.assertEqual(r.returncode, 1)
                    self.assertIn("tipo inválido", r.stderr)
                    self.assertIn("budget.rules_words", r.stderr)

    def test_r18_budget_clave_desconocida(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["budget"] = {"rules_words": 3500, "otra": 1}
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("clave desconocida", r.stderr)
            self.assertIn("budget.otra", r.stderr)

    def test_r18_budget_no_es_un_objeto(self):
        for valor in ([], "3500", 3500):
            with self.subTest(valor=valor):
                with tempfile.TemporaryDirectory() as tmp:
                    datos = copy.deepcopy(config_base())
                    datos["budget"] = valor
                    escribir_config(tmp, datos)
                    r = ejecutar(tmp)
                    self.assertEqual(r.returncode, 1)
                    self.assertIn("tipo inválido", r.stderr)
                    self.assertIn("budget", r.stderr)

    def test_r19_allowed_commands_ausente_es_legitimo(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            self.assertNotIn("allowed_commands", datos["guard"])
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r19_allowed_commands_valido(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["allowed_commands"] = [{"command": "mysql", "reason": "motivo de prueba"}]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r19_allowed_commands_escalada(self):
        for valor in ("sudo", "su", "passwd"):
            with self.subTest(valor=valor):
                with tempfile.TemporaryDirectory() as tmp:
                    datos = copy.deepcopy(config_base())
                    datos["guard"]["allowed_commands"] = [{"command": valor, "reason": "x"}]
                    escribir_config(tmp, datos)
                    r = ejecutar(tmp)
                    self.assertEqual(r.returncode, 1)
                    self.assertIn("escalada", r.stderr)

    def test_r19_allowed_commands_no_prohibida(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["allowed_commands"] = [{"command": "ls", "reason": "x"}]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("orden no prohibida", r.stderr)

    def test_r19_allowed_commands_reason_vacio(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["allowed_commands"] = [{"command": "mysql", "reason": "  "}]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("vacío", r.stderr)

    def test_r19_allowed_commands_clave_de_mas(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["allowed_commands"] = [
                {"command": "mysql", "reason": "x", "extra": 1}]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("clave desconocida", r.stderr)

    def test_r19_allowed_commands_tipo_invalido(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["allowed_commands"] = [{"command": 5, "reason": "x"}]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("tipo inválido", r.stderr)

    def test_r20_extra_banned_y_allowed_en_conflicto(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["extra_banned_commands"] = ["mysql"]
            datos["guard"]["allowed_commands"] = [{"command": "mysql", "reason": "x"}]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("a la vez en extra_banned_commands y en allowed_commands", r.stderr)

    def test_r20_extra_banned_y_allowed_sin_conflicto(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["guard"]["extra_banned_commands"] = ["herramienta"]
            datos["guard"]["allowed_commands"] = [{"command": "mysql", "reason": "x"}]
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)


class PruebasRutasEscribibles(unittest.TestCase):
    """`guard.writable_paths`, propia de PiecesPHP: carpetas personales enteras, cada una con su motivo."""

    def _codigo(self, rutas):
        with tempfile.TemporaryDirectory() as tmp:
            config = config_base()
            config["guard"]["writable_paths"] = rutas
            escribir_config(tmp, config)
            return ejecutar(tmp).returncode

    def test_legitima(self):
        self.assertEqual(self._codigo([{"path": "~/Documentos/Prueba", "reason": "ADR 0001"}]), 0)

    def test_rechaza_lo_que_no_es_personal(self):
        for ruta in ("/etc", "~", "~/", "../otra", "~/a/../b", "~/*"):
            self.assertEqual(self._codigo([{"path": ruta, "reason": "ADR 0001"}]), 1, ruta)

    def test_exige_motivo(self):
        self.assertEqual(self._codigo([{"path": "~/Documentos/Prueba"}]), 1)
        self.assertEqual(self._codigo([{"path": "~/Documentos/Prueba", "reason": " "}]), 1)

    def test_clave_desconocida(self):
        self.assertEqual(self._codigo([{"path": "~/Documentos/Prueba", "reason": "x", "otra": 1}]), 1)


class PruebasModo(unittest.TestCase):
    """`sessions.mode` elige el protocolo; si falta, es «dos»."""

    def test_r21_sin_mode_sigue_valiendo(self):
        with tempfile.TemporaryDirectory() as tmp:
            escribir_config(tmp, config_base())
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r21_mode_dos(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["sessions"]["mode"] = "dos"
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r21_mode_una_con_coder_subagente(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["sessions"]["mode"] = "una"
            datos["sessions"]["coder"] = "coder"
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 0, r.stderr)

    def test_r21_mode_una_con_otro_coder_falla(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["sessions"]["mode"] = "una"
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("sessions.coder", r.stderr)
            self.assertIn('en modo una tiene que ser "coder"', r.stderr)

    def test_r21_mode_desconocido_falla(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["sessions"]["mode"] = "tres"
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("modo desconocido", r.stderr)
            self.assertIn("sessions.mode", r.stderr)

    def test_r21_mode_no_es_texto_falla(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["sessions"]["mode"] = 1
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("tipo inválido", r.stderr)
            self.assertIn("sessions.mode", r.stderr)

    def test_r21_clave_desconocida_en_sessions_sigue_fallando(self):
        with tempfile.TemporaryDirectory() as tmp:
            datos = copy.deepcopy(config_base())
            datos["sessions"]["modo"] = "una"
            escribir_config(tmp, datos)
            r = ejecutar(tmp)
            self.assertEqual(r.returncode, 1)
            self.assertIn("clave desconocida", r.stderr)
            self.assertIn("sessions.modo", r.stderr)

    def test_r21_la_funcion_modo_responde_lo_mismo(self):
        sys.path.insert(0, AQUI)
        proyecto = importlib.import_module("proyecto")
        try:
            self.assertEqual(proyecto.modo(config_base()), "dos")
            una = copy.deepcopy(config_base())
            una["sessions"].update({"mode": "una", "coder": "coder"})
            self.assertEqual(proyecto.modo(una), "una")
        finally:
            sys.path.remove(AQUI)


if __name__ == "__main__":
    unittest.main(verbosity=2)
