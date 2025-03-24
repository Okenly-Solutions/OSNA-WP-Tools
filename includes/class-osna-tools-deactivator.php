<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes
 */

class OSNA_Tools_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Flush rewrite rules to ensure our custom post types are removed
        flush_rewrite_rules();
    }
}
