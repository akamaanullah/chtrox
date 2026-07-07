<?php

namespace App\Helpers;

use HTMLPurifier;
use HTMLPurifier_AttrDef_Text;
use HTMLPurifier_Config;

/**
 * HtmlSanitizer — server-side allowlist-based HTML sanitizer for message bodies.
 *
 * Wraps HTMLPurifier with a configuration that exactly mirrors the client-side
 * editor allowedTags:  b, i, s, u, strong, em, ul, ol, li, p, br, span, div, a
 *
 * Usage:
 *   $clean = HtmlSanitizer::clean($rawHtml);
 */
class HtmlSanitizer
{
    private static ?HTMLPurifier $instance = null;

    /**
     * Sanitize raw HTML from user input.
     * Returns a safe HTML string with all disallowed tags/attributes stripped.
     */
    public static function clean(string $html): string
    {
        $sanitized = self::getPurifier()->purify($html);

        // Auto-linkify plain text URLs (ignoring already linkified ones)
        $parts = preg_split('/(<[^>]+>)/', $sanitized, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if ($part !== '' && $part[0] !== '<') {
                $parts[$i] = preg_replace_callback(
                    '/\b(https?:\/\/[^\s<]+)/i',
                    function ($matches) {
                        $url = htmlspecialchars_decode($matches[1]);
                        $suffix = '';
                        if (preg_match('/([.,;:?]+)$/', $url, $punctMatches)) {
                            $suffix = $punctMatches[1];
                            $url = substr($url, 0, -strlen($suffix));
                        }
                        $href = htmlspecialchars($url);
                        return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($url) . '</a>' . $suffix;
                    },
                    $part
                );
            }
        }
        $sanitized = implode('', $parts);

        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        $hostUrl = '';
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $hostUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }

        // Remove relative links or links not starting with http/https, and internal routes
        $sanitized = preg_replace_callback('/<a\b[^>]*>/i', function($matches) use ($baseUrl, $hostUrl) {
            $tag = $matches[0];
            if (preg_match('/href="([^"]+)"/i', $tag, $hrefMatches)) {
                $url = trim($hrefMatches[1]);
                $isRelative = (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0);
                
                $isInternal = false;
                if (!$isRelative) {
                    // Check if it matches base url or host url but is not a file download
                    $hasBasePrefix = ($baseUrl !== '' && strpos($url, $baseUrl) === 0);
                    $hasHostPrefix = ($hostUrl !== '' && strpos($url, $hostUrl) === 0);
                    if ($hasBasePrefix || $hasHostPrefix) {
                        if (strpos($url, '/files/download/') === false) {
                            $isInternal = true;
                        }
                    }
                }
                
                if ($isRelative || $isInternal) {
                    $tag = preg_replace('/href="[^"]*"/i', '', $tag);
                }
            }
            return $tag;
        }, $sanitized);

        // Filter out classes not matching ql-*, language-*, or mention or dm-search-highlight
        $sanitized = preg_replace_callback('/class="([^"]+)"/i', function($matches) {
            $classes = explode(' ', $matches[1]);
            $allowed = array_filter($classes, function($c) {
                $c = trim($c);
                return strpos($c, 'ql-') === 0 || strpos($c, 'language-') === 0 || in_array($c, ['mention', 'dm-search-highlight'], true);
            });
            if (empty($allowed)) {
                return '';
            }
            return 'class="' . implode(' ', $allowed) . '"';
        }, $sanitized);

        return $sanitized;
    }

    /**
     * Build and cache the HTMLPurifier instance (singleton per PHP process).
     * Building is expensive; we reuse the same instance across calls.
     */
    private static function getPurifier(): HTMLPurifier
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = HTMLPurifier_Config::createDefault();

        // ── Disk cache for serialized definitions ─────────────────────────────
        $cacheDir = ROOT_DIR . '/storage/htmlpurifier';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $config->set('Cache.SerializerPath', $cacheDir);

        // ── Cache versioning (bump DefinitionRev when allowlist changes) ──────
        $config->set('HTML.DefinitionID',  'chatrox-message-sanitizer');
        $config->set('HTML.DefinitionRev', 7);

        // ── Allowed HTML elements ─────────────────────────────────────────────
        // Exactly mirrors the client-side editor allowedTags:
        //   b, i, s, u, strong, em, ul, ol, li, p, br, span, div, a, pre, code
        $config->set('HTML.AllowedElements', [
            'b', 'i', 's', 'u', 'strong', 'em',
            'ul', 'ol', 'li',
            'p', 'br',
            'span', 'div',
            'a', 'pre', 'code',
        ]);

        // ── Allowed attributes ───────────────────────────────────────
        $config->set('HTML.AllowedAttributes', [
            '*.style',           // inline CSS — scoped by CSS.AllowedProperties
            '*.align',           // text alignment
            '*.class',           // CSS classes
            'a.href',            // hyperlink destination (http/https only)
            'a.target',          // _blank etc.
            'a.contenteditable', // editor uses this on anchors
            'a.download',        // download attribute
            'span.contenteditable', // editor marks @mention spans non-editable
            'span.data-member-id',
            'span.data-username',
            'span.data-emoji',
        ]);

        // ── URI scheme whitelist ──────────────────────────────────────────────
        // Blocks javascript:, data:, vbscript: in href automatically.
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);

        // ── Strip disallowed (don't encode as entities) ───────────────────────
        $config->set('Core.EscapeInvalidTags', false);

        // ── CSS property allowlist ────────────────────────────────────────────
        // Editor uses inline style for text alignment; allow only safe props.
        $config->set('CSS.AllowedProperties', [
            'text-align',
            'font-weight',
            'font-style',
            'text-decoration',
        ]);

        // ── Custom data-* attributes ──────────────────────────────────────────
        // HTMLPurifier 4.x does NOT support data-* via HTML.AllowedAttributes.
        // They MUST be registered on the HTMLDefinition before the purifier
        // is instantiated. maybeGetRawHTMLDefinition() returns a live definition
        // object when the cache is absent (first run) or the DefinitionRev changed.
        // When it returns null, the previously cached definition (which already
        // has the custom attrs) is used automatically.
        $def = $config->maybeGetRawHTMLDefinition();
        if ($def !== null) {
            $text = new HTMLPurifier_AttrDef_Text();
            // @mention attributes on <span>
            $def->addAttribute('span', 'data-member-id', $text);
            $def->addAttribute('span', 'data-username',  $text);
            $def->addAttribute('span', 'data-emoji',     $text);
            // download on <a>
            $def->addAttribute('a', 'download', $text);
        }

        self::$instance = new HTMLPurifier($config);
        return self::$instance;
    }
}
