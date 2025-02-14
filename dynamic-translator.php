<?php
/**
 * Plugin Name: Dynamic String Translator
 * Description: Simple interface for managing string translations
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: dynamic-translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class DynamicTranslator {
    private static $instance = null;
    private $options_key = 'dynamic_translator_strings';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

	private function __construct() {
    add_action('admin_menu', array($this, 'add_menu_page'));
    add_action('admin_init', array($this, 'register_settings'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    add_action('admin_post_save_translations', array($this, 'save_translations'));
    add_filter('style_loader_src', array($this, 'add_cache_busting_query_string'), 20);
    add_filter('script_loader_src', array($this, 'add_cache_busting_query_string'), 20);
    $this->init_translations();
}




	// Settings and storage

	public function register_settings() {
        register_setting('dynamic_translator_options', $this->options_key);
    }

    private function init_translations() {
        // Simple strings
        add_filter('gettext', array($this, 'translate_strings'), 20, 3);
        // Dynamic strings
        add_filter('gettext', array($this, 'translate_dynamic_strings'), 20, 3);
        // Profile strings
        add_filter('gettext', array($this, 'translate_profile_strings'), 20, 3);
    }

    private function get_translations() {
        return get_option($this->options_key, array(
            'simple' => array(),
            'dynamic' => array(),
            'profile' => array()
        ));
    }

// Translation functions

public function translate_strings($translated_text, $text, $domain) {
	$translations = $this->get_translations();
	if (isset($translations['simple'][$text])) {
		return $translations['simple'][$text];
	}
	return $translated_text;
}

public function translate_dynamic_strings($translated_text, $text, $domain) {
	$translations = $this->get_translations();
	if (isset($translations['dynamic'][$text])) {
		return $translations['dynamic'][$text];
	}
	return $translated_text;
}

public function translate_profile_strings($translated_text, $text, $domain) {
	if (!is_user_logged_in() || !isset($_SERVER['REQUEST_URI']) ||
		strpos($_SERVER['REQUEST_URI'], '/profile/') === false) {
		return $translated_text;
	}

	$translations = $this->get_translations();
	if (isset($translations['profile'][$text])) {
		return $translations['profile'][$text];
	}
	return $translated_text;
}


//Admin Interface

public function add_menu_page() {
	add_options_page(
		'Dynamic Translator',
		'Dynamic Translator',
		'manage_options',
		'dynamic-translator',
		array($this, 'render_admin_page')
	);
}

public function render_admin_page() {
	$translations = $this->get_translations();
	?>
	<div class="wrap">
		<h2>Dynamic Translator Settings</h2>
		<?php
        	if (isset($_GET['updated']) && $_GET['updated'] == 'true') {
            	echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
        }
        ?>


		<!--  <form method="post" action="options.php">  -->
		<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    		<?php wp_nonce_field('dynamic_translator_options-options'); ?>
    		<input type="hidden" name="action" value="save_translations">

			<?php settings_fields('dynamic_translator_options'); ?>

			<div class="nav-tab-wrapper">
				<a href="#simple" class="nav-tab nav-tab-active">Simple Strings</a>
				<a href="#dynamic" class="nav-tab">Dynamic Strings</a>
				<a href="#profile" class="nav-tab">Profile Strings</a>
			</div>

			<div id="simple" class="tab-content">
				<table class="form-table">
					<tr>
						<th>Original Text</th>
						<th>Translation</th>
						<th>Action</th>
					</tr>
					<?php foreach ($translations['simple'] as $original => $translated): ?>
					<tr>
						<td><input type="text" name="original[]" value="<?php echo esc_attr($original); ?>" /></td>
						<td><input type="text" name="translated[]" value="<?php echo esc_attr($translated); ?>" /></td>
						<td><button type="button" class="button remove-row">Remove</button></td>
					</tr>
					<?php endforeach; ?>
					<tr>
						<td colspan="3">
							<button type="button" class="button add-row">Add New Translation</button>
						</td>
					</tr>
				</table>
			</div>



			<div id="dynamic" class="tab-content" style="display:none;">
			    <table class="form-table">
			        <tr>
			            <th>Original Text</th>
			            <th>Translation</th>
			            <th>Action</th>
			        </tr>
			        <?php foreach ($translations['dynamic'] as $original => $translated): ?>
			        <tr>
			            <td><input type="text" name="dynamic_original[]" value="<?php echo esc_attr($original); ?>" /></td>
			            <td><input type="text" name="dynamic_translated[]" value="<?php echo esc_attr($translated); ?>" /></td>
			            <td><button type="button" class="button remove-row">Remove</button></td>
			        </tr>
			        <?php endforeach; ?>
			        <tr>
			            <td colspan="3">
			                <button type="button" class="button add-row">Add New Translation</button>
			            </td>
			        </tr>
			    </table>
			</div>


			<div id="profile" class="tab-content" style="display:none;">
			    <table class="form-table">
			        <tr>
			            <th>Original Text</th>
			            <th>Translation</th>
			            <th>Action</th>
			        </tr>
			        <?php foreach ($translations['profile'] as $original => $translated): ?>
			        <tr>
			            <td><input type="text" name="profile_original[]" value="<?php echo esc_attr($original); ?>" /></td>
			            <td><input type="text" name="profile_translated[]" value="<?php echo esc_attr($translated); ?>" /></td>
			            <td><button type="button" class="button remove-row">Remove</button></td>
			        </tr>
			        <?php endforeach; ?>
			        <tr>
			            <td colspan="3">
			                <button type="button" class="button add-row">Add New Translation</button>
			            </td>
			        </tr>
			    </table>
			</div>



			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}


public function add_cache_busting_query_string($src) {
    if (strpos($src, '.css') !== false || strpos($src, '.js') !== false) {
        $file_path = ABSPATH . str_replace(get_site_url(), '', $src);
        if (file_exists($file_path)) {
            $src = add_query_arg('ver', filemtime($file_path), $src);
        }
    }
    return $src;
}



public function enqueue_admin_scripts($hook) {
    if ('settings_page_dynamic-translator' !== $hook) {
        return;
    }

    wp_enqueue_script(
        'dynamic-translator-admin',
        plugins_url('js/admin.js', __FILE__),
        array('jquery'),
        '1.0.0',
        true
    );

    wp_enqueue_style(
        'dynamic-translator-admin',
        plugins_url('css/admin.css', __FILE__)
    );
}

private function sanitize_translations($input) {
    $clean = array(
        'simple' => array(),
        'dynamic' => array(),
        'profile' => array()
    );

    if (!empty($input['simple'])) {
        foreach ($input['simple'] as $key => $value) {
            $original = sanitize_text_field($key);
            $translated = sanitize_text_field($value);

            if (!empty($original) && !empty($translated)) {
                $clean['simple'][$original] = $translated;
            }
        }
    }

    return $clean;
}

private function clear_cache() {
    wp_cache_flush();

    if (function_exists('w3tc_flush_all')) {
        w3tc_flush_all();
    }

    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }

    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();
    }
}

public function save_translations() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_POST['submit'])) {
        check_admin_referer('dynamic_translator_options-options');

        try {
            $input = array(
                'simple' => isset($_POST['original']) && isset($_POST['translated']) ?
                    array_combine($_POST['original'], $_POST['translated']) : array(),
                'dynamic' => isset($_POST['dynamic_original']) && isset($_POST['dynamic_translated']) ?
                    array_combine($_POST['dynamic_original'], $_POST['dynamic_translated']) : array(),
                'profile' => isset($_POST['profile_original']) && isset($_POST['profile_translated']) ?
                    array_combine($_POST['profile_original'], $_POST['profile_translated']) : array()
            );

            $clean = $this->sanitize_translations($input);
            update_option($this->options_key, $clean);

            $redirect_url = add_query_arg(
                array(
                    'page' => 'dynamic-translator',
                    'updated' => 'true'
                ),
                admin_url('options-general.php')
            );

            wp_redirect($redirect_url);
            exit;

        } catch (Exception $e) {
            wp_die('Error saving translations: ' . esc_html($e->getMessage()));
        }
    }
}



}

// Initialize plugin
add_action('plugins_loaded', array('DynamicTranslator', 'get_instance'));
