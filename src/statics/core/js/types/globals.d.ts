import { initialConfig } from '../configurations.js';

/**
 * Inferencia automática de tipos basada en el objeto de configuración inicial.
 * Ya no es necesario editar este archivo manualmente si añades propiedades a initialConfig.
 */
type UniversalScopeType = typeof initialConfig;

declare global {
    var pcsphpGlobals: UniversalScopeType;
    interface Window {
        pcsphpGlobals: UniversalScopeType;
    }
}
