<?php
/**
 * Frak Config Endpoint
 * 
 * Handles dynamic JavaScript configuration delivery with proper caching
 */
class Frak_Config_Endpoint {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register the endpoint
        add_action('init', array($this, 'register_endpoint'));
        add_action('template_redirect', array($this, 'handle_endpoint'));
        
        // Flush rewrite rules on activation
        register_activation_hook(FRAK_PLUGIN_FILE, array($this, 'flush_rewrite_rules'));
        register_deactivation_hook(FRAK_PLUGIN_FILE, array($this, 'flush_rewrite_rules'));
    }

    /**
     * Register the custom endpoint
     */
    public function register_endpoint() {
        add_rewrite_rule('^frak-config\.js$', 'index.php?frak_config=1', 'top');
        add_rewrite_tag('%frak_config%', '([^&]+)');
    }

    /**
     * Handle the endpoint request
     */
    public function handle_endpoint() {
        global $wp_query;
        
        if (!isset($wp_query->query_vars['frak_config'])) {
            return;
        }

        // Generate the full script
        $script = $this->generate_full_script();

        // Generate ETag based on script content
        $etag = md5($script);
        $last_modified = get_option('frak_config_last_modified', time());
        
        // Set proper headers
        $this->set_cache_headers($etag, $last_modified);
        
        // Check if client has valid cached version
        if ($this->is_not_modified($etag)) {
            status_header(304);
            exit;
        }

        // Output the JavaScript
        header('Content-Type: text/html; charset=utf-8');
        
        echo $script;
        exit;
    }

    /**
     * Generate the full script including SDK loading and configuration
     */
    private function generate_full_script() {
        $app_name = esc_js(get_option('frak_app_name', get_bloginfo('name')));
        $logo_url = esc_js(get_option('frak_logo_url', ''));
        $modal_language = get_option('frak_modal_language', 'default');
        $floating_button_position = esc_js(get_option('frak_floating_button_position', 'right'));
        $modal_i18n = get_option('frak_modal_i18n', '{}');
        
        // Escape the shop name for JS
        $shop_name = esc_js(get_bloginfo('name'));
        
        // Handle language setting
        $modal_lng = $modal_language === 'default' ? 'default' : esc_js($modal_language);
        
        // Properly escape the i18n JSON
        $modal_i18n_escaped = str_replace(
            array('&', '<', '>', "'", '"'),
            array('&amp;', '&lt;', '&gt;', '&#39;', '&quot;'),
            $modal_i18n
        );
        
        return <<<HTML
<script src="https://cdn.jsdelivr.net/npm/@frak-labs/components" defer="defer"></script>
<script type="text/javascript">
  let logoUrl = '{$logo_url}';
  const lang = '{$modal_lng}' === 'default' ? undefined : '{$modal_lng}';

  let i18n = {};
  try {
    i18n = JSON.parse('{$modal_i18n_escaped}'.replace(
    /&amp;|&lt;|&gt;|&#39;|&quot;/g,
    tag =>
      ({
        '&amp;': '&',
        '&lt;': '<',
        '&gt;': '>',
        '&#39;': "'",
        '&quot;': '"'
      }[tag] || tag)
  )) || {};
  } catch (error) {
    console.error('Error parsing i18n customizations:', error);
  }

  window.FrakSetup = {
    config: { walletUrl: 'https://wallet.frak.id', metadata: { name: '{$shop_name}', lang, logoUrl }, customizations: { i18n }, domain: window.location.host },
    modalConfig: { login: { allowSso: true, ssoMetadata: { logoUrl, homepageLink: window.location.host } } },
    modalShareConfig: { link: window.location.href },
    modalWalletConfig: { metadata: { position: '{$floating_button_position}' } },
  };
</script>
HTML;
    }


    /**
     * Set proper cache headers
     */
    private function set_cache_headers($etag, $last_modified) {
        // Allow caching for 1 hour, but validate with ETag
        header('Cache-Control: public, max-age=3600, must-revalidate');
        header('ETag: "' . $etag . '"');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $last_modified) . ' GMT');
        header('X-Content-Type-Options: nosniff');
        
        // Add CORS headers if needed
        $allowed_origin = get_option('frak_cors_origin', '*');
        header('Access-Control-Allow-Origin: ' . $allowed_origin);
    }

    /**
     * Check if client has valid cached version
     */
    private function is_not_modified($etag) {
        $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : false;
        
        if ($if_none_match && $if_none_match === '"' . $etag . '"') {
            return true;
        }
        
        return false;
    }

    /**
     * Flush rewrite rules
     */
    public function flush_rewrite_rules() {
        $this->register_endpoint();
        flush_rewrite_rules();
    }

    /**
     * Get config URL with version parameter
     */
    public static function get_config_url() {
        // Create version hash based on all settings
        $settings = array(
            'app_name' => get_option('frak_app_name', ''),
            'logo_url' => get_option('frak_logo_url', ''),
            'modal_language' => get_option('frak_modal_language', 'default'),
            'floating_button_position' => get_option('frak_floating_button_position', 'right'),
            'modal_i18n' => get_option('frak_modal_i18n', '{}')
        );
        $version = substr(md5(json_encode($settings)), 0, 8);
        
        return home_url('/frak-config.js?v=' . $version);
    }

    /**
     * Update last modified timestamp when config changes
     */
    public static function update_last_modified() {
        update_option('frak_config_last_modified', time());
        
        // Clear caches from popular caching plugins
        self::clear_external_caches();
    }
    
    /**
     * Simple JavaScript minification
     */
    private function minify_javascript($js) {
        // Remove comments
        $js = preg_replace('/\/\*[\s\S]*?\*\/|\/\/.*$/m', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove whitespace around operators
        $js = preg_replace('/\s*([{}:;,=+\-*\/])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Clear caches from popular caching plugins
     */
    private static function clear_external_caches() {
        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        
        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        // WP Fastest Cache
        if (class_exists('WpFastestCache') && method_exists('WpFastestCache', 'deleteCache')) {
            $wpfc = new WpFastestCache();
            $wpfc->deleteCache(true);
        }
        
        // LiteSpeed Cache
        if (class_exists('LiteSpeed\Purge')) {
            LiteSpeed\Purge::purge_all();
        }
        
        // Autoptimize
        if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
            autoptimizeCache::clearall();
        }
        
        // Clear WordPress object cache
        wp_cache_flush();
    }
}