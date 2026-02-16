<?php

namespace App\Middlewares;

/**
 * Middleware Content-Security-Policy (CSP).
 *
 * Définit une politique de sécurité du contenu pour limiter les sources
 * autorisées de scripts, styles, images, polices et connexions.
 *
 * Architecture :
 * - handle() : point d'entrée global, envoie le header HTTP
 * - buildPolicy() : logique pure, testable sans effets de bord
 */
class SecurityHeadersMiddleware
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Point d'entrée : envoie le header CSP.
     */
    public function handle(): void
    {
        $policy = $this->buildPolicy();

        if ($policy !== '') {
            header('Content-Security-Policy: ' . $policy);
        }
    }

    /**
     * Construit la chaîne CSP — logique pure, testable.
     *
     * Directives :
     * - default-src 'self'          : tout restreint à l'origine par défaut
     * - script-src  'self' + CDN    : scripts locaux + Chart.js (CDN)
 * - style-src   'self' + CDN    : styles locaux + FontAwesome (plus de style="" inline)
     * - img-src     'self' data:    : images locales + data-URI (icônes inline)
     * - font-src    'self' + CDN    : polices locales + FontAwesome webfonts
     * - connect-src 'self'          : fetch/XHR vers l'API locale uniquement
     * - frame-src   'none'          : pas d'iframe
     * - object-src  'none'          : pas de plugins (Flash, Java)
     * - base-uri    'self'          : empêche la redirection de <base>
     * - form-action 'self'          : formulaires vers l'origine uniquement
     *
     * Note : 'unsafe-inline' est requis pour style-src car l'application
     * utilise abondamment l'attribut style="" dans le HTML.
     * Les scripts inline ont été supprimés, donc script-src n'a PAS besoin
     * de 'unsafe-inline'.
     */
    public function buildPolicy(): string
    {
        $csp = $this->config['csp'] ?? [];

        // Directives par défaut (peuvent être surchargées via config)
        $directives = [
            'default-src' => $csp['default_src'] ?? ["'self'"],
            'script-src'  => $csp['script_src']  ?? ["'self'", 'https://cdn.jsdelivr.net'],
            'style-src'   => $csp['style_src']   ?? ["'self'", 'https://cdnjs.cloudflare.com'],
            'img-src'     => $csp['img_src']      ?? ["'self'", 'data:'],
            'font-src'    => $csp['font_src']     ?? ["'self'", 'https://cdnjs.cloudflare.com'],
            'connect-src' => $csp['connect_src']  ?? ["'self'"],
            'frame-src'   => $csp['frame_src']    ?? ["'none'"],
            'object-src'  => $csp['object_src']   ?? ["'none'"],
            'base-uri'    => $csp['base_uri']     ?? ["'self'"],
            'form-action' => $csp['form_action']  ?? ["'self'"],
        ];

        $parts = [];
        foreach ($directives as $directive => $sources) {
            if (!empty($sources)) {
                $parts[] = $directive . ' ' . implode(' ', $sources);
            }
        }

        return implode('; ', $parts);
    }
}
