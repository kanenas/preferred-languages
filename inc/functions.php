<?php
/**
 * Plugin functions.
 *
 * @package PreferredLanguages
 */

/**
 * Registers the option for the preferred languages.
 *
 * @since 1.0.0
 */
function preferred_languages_register_setting() {
	register_setting(
		'general',
		'preferred_languages',
		array(
			'sanitize_callback' => 'preferred_languages_sanitize_list',
			'default'           => '',
			'show_in_rest'      => true,
			'type'              => 'string',
			'description'       => __( 'List of preferred locales.', 'preferred-languages' ),
		)
	);
}

/**
 * Registers the user meta key for the preferred languages.
 *
 * @since 1.0.0
 */
function preferred_languages_register_meta() {
	register_meta(
		'user',
		'preferred_languages',
		array(
			'type'              => 'string',
			'description'       => 'List of preferred languages',
			'single'            => true,
			'sanitize_callback' => 'preferred_languages_sanitize_list',
			'show_in_rest'      => true,
		)
	);
}

/**
 * Updates the user's set of preferred languages.
 *
 * @since 1.0.0
 *
 * @param int $user_id The user ID.
 */
function preferred_languages_update_user_option( $user_id ) {
	if ( isset( $_POST['preferred_languages'] ) ) {
		update_user_meta( $user_id, 'preferred_languages', $_POST['preferred_languages'] );
	}
}

/**
 * Returns the list of preferred languages of a user.
 *
 * @since 1.3.0
 *
 * @param int|WP_User $user_id User's ID or a WP_User object. Defaults to current user.
 * @return array|false Preferred languages or false if user does not exists.
 */
function preferred_languages_get_user_list( $user_id = 0 ) {
	$user = false;

	if ( 0 === $user_id && function_exists( 'wp_get_current_user' ) ) {
		$user = wp_get_current_user();
	} elseif ( $user_id instanceof WP_User ) {
		$user = $user_id;
	} elseif ( $user_id && is_numeric( $user_id ) ) {
		$user = get_user_by( 'id', $user_id );
	}

	if ( ! $user ) {
		return false;
	}

	$preferred_languages = get_user_meta( $user->ID, 'preferred_languages', true );
	return array_filter( explode( ',', $preferred_languages ) );
}

/**
 * Returns the list of preferred languages of the current site.
 *
 * @since 1.3.0
 *
 * @return array Preferred languages.
 */
function preferred_languages_get_site_list() {
	$preferred_languages = get_option( 'preferred_languages', '' );
	return array_filter( explode( ',', $preferred_languages ) );
}

/**
 * Returns the list of preferred languages.
 *
 * If in the admin area, this returns the data for the current user.
 * Otherwise the site settings are used.
 *
 * @since 1.0.0
 *
 * @return array Preferred languages.
 */
function preferred_languages_get_list() {
	$preferred_languages = array();

	if ( is_admin() ) {
		$preferred_languages = preferred_languages_get_user_list( get_current_user_id() );
	}

	if ( ! empty( $preferred_languages ) ) {
		return $preferred_languages;
	}

	// Fall back to site setting.
	return preferred_languages_get_site_list();
}

/**
 * Downloads language packs when saving user meta without any changes.
 *
 * Makes sure the translations are downloaded when it didn't work the first time around.
 *
 * @since 1.4.0
 *
 * @param null|bool $check     Whether to allow updating metadata for the given type.
 * @param int       $user_id   User ID.
 * @param string    $meta_key  Meta key.
 * @param mixed     $value     Meta value.
 * @param mixed     $old_value The previous meta value.
 * @return mixed
 */
function preferred_languages_pre_update_user_meta( $check, $user_id, $meta_key, $value, $old_value ) {
	if ( 'preferred_languages' === $meta_key ) {
		if ( empty( $old_value ) ) {
			$old_value = get_user_meta( $user_id, $meta_key, true );
		}

		if ( $value === $old_value ) {
			$locales = array_filter( explode( ',', $value ) );
			preferred_languages_download_language_packs( $locales );
		}
	}

	return $check;
}

/**
 * Downloads language pack when updating user meta.
 *
 * @since 1.3.0
 *
 * @param int    $meta_id    ID of the metadata entry to update.
 * @param int    $object_id  Object ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Meta value.
 */
function preferred_languages_update_user_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
	if ( 'preferred_languages' !== $meta_key ) {
		return;
	}

	remove_filter( 'update_user_meta', 'preferred_languages_update_user_meta' );

	$locales             = array_filter( explode( ',', $meta_value ) );
	$installed_languages = preferred_languages_download_language_packs( $locales );

	// Only store actually installed languages in option.
	update_user_meta( $object_id, 'preferred_languages', implode( ',', $installed_languages ) );

	add_filter( 'update_user_meta', 'preferred_languages_update_user_meta', 10, 4 );

	// Reload translations after save.
	$preferred_languages_list = preferred_languages_get_user_list( $object_id );
	load_default_textdomain( reset( $preferred_languages_list ) );
}

/**
 * Downloads language packs when saving the site option without any changes.
 *
 * Makes sure the translations are downloaded when it didn't work the first time around.
 *
 * @since 1.4.0
 *
 * @param mixed  $value     The new, unserialized option value.
 * @param string $option    Name of the option.
 * @param mixed  $old_value The old option value.
 * @return mixed
 */
function preferred_languages_pre_update_option( $value, $option, $old_value ) {
	if ( 'preferred_languages' === $option && $value === $old_value ) {
		$locales = array_filter( explode( ',', $value ) );
		preferred_languages_download_language_packs( $locales );
	}

	return $value;
}

/**
 * Downloads language packs upon updating the site option.
 *
 * @since 1.3.0
 *
 * @param string $old_value The old option value.
 * @param string $value     The new option value.
 */
function preferred_languages_update_option( $old_value, $value ) {
	remove_filter( 'update_option_preferred_languages', 'preferred_languages_update_option' );

	$locales             = array_filter( explode( ',', $value ) );
	$installed_languages = preferred_languages_download_language_packs( $locales );

	// Only store actually installed languages in option.
	update_option( 'preferred_languages', implode( ',', $installed_languages ) );

	add_filter( 'update_option_preferred_languages', 'preferred_languages_update_option', 10, 2 );

	// Reload translations after save.
	$preferred_languages_list = preferred_languages_get_site_list();
	load_default_textdomain( reset( $preferred_languages_list ) );
}

/**
 * Downloads language packs upon updating the option.
 *
 * @since 1.0.0
 *
 * @param array $locales The language packs to install.
 * @return array The installed and available languages.
 */
function preferred_languages_download_language_packs( $locales ) {
	// Handle translation install.
	require_once ABSPATH . 'wp-admin/includes/translation-install.php';

	$installed_languages        = array();
	$available_languages        = get_available_languages();
	$user_can_install_languages = current_user_can( 'install_languages' );

	foreach ( $locales as $locale ) {
		if ( in_array( $locale, $available_languages, true ) ) {
			$installed_languages[] = $locale;
			continue;
		}

		if ( ! $user_can_install_languages ) {
			continue;
		}

		$language = wp_download_language_pack( $locale );
		if ( $language ) {
			$installed_languages[] = $language;
		}
	}

	return $installed_languages;
}

/**
 * Sanitizes the preferred languages option.
 *
 * @since 1.0.0
 *
 * @param string $preferred_languages Comma separated list of preferred languages.
 *
 * @return string Sanitized list.
 */
function preferred_languages_sanitize_list( $preferred_languages ) {
	$locales = array_map( 'sanitize_text_field', explode( ',', $preferred_languages ) );

	return implode( ',', $locales );
}

/**
 * Filters calls to get_locale() to use the preferred languages setting.
 *
 * @since 1.0.0
 *
 * @param string $locale The current locale.
 *
 * @return string
 */
function preferred_languages_filter_locale( $locale ) {
	$preferred_languages = preferred_languages_get_site_list();

	if ( empty( $preferred_languages ) ) {
		return $locale;
	}

	return reset( $preferred_languages );
}

/**
 * Filters calls to get_user_locale() to use the preferred languages setting.
 *
 * @since 1.0.0
 *
 * @param null|array|string $value     The value get_metadata() should return - a single metadata value,
 *                                     or an array of values.
 * @param int               $object_id Object ID.
 * @param string            $meta_key  Meta key.
 *
 * @return null|array|string The meta value.
 */
function preferred_languages_filter_user_locale( $value, $object_id, $meta_key ) {
	if ( 'locale' !== $meta_key ) {
		return $value;
	}

	$preferred_languages = preferred_languages_get_user_list( $object_id );

	if ( ! empty( $preferred_languages ) ) {
		return reset( $preferred_languages );
	}

	return $value;
}

/**
 * Filters load_textdomain() calls to respect the list of preferred languages.
 *
 * @since 1.0.0
 *
 * @param string $mofile Path to the MO file.
 *
 * @return string The modified MO file path.
 */
function preferred_languages_load_textdomain_mofile( $mofile ) {
	if ( is_readable( $mofile ) ) {
		return $mofile;
	}

	$preferred_locales = preferred_languages_get_list();

	if ( empty( $preferred_locales ) ) {
		return $mofile;
	}

	$current_locale = get_locale();

	// Locale has been filtered by something else.
	if ( ! in_array( $current_locale, $preferred_locales, true ) ) {
		return $mofile;
	}

	foreach ( $preferred_locales as $locale ) {
		$preferred_mofile = str_replace( $current_locale, $locale, $mofile );

		if ( is_readable( $preferred_mofile ) ) {
			return $preferred_mofile;
		}
	}

	return $mofile;
}

/**
 * Filters load_script_translation_file() calls to respect the list of preferred languages.
 *
 * @since 1.6.0
 *
 * @param string|false $file Path to the translation file to load. False if there isn't one.
 *
 * @return string The modified JSON file path.
 */
function preferred_languages_load_script_translation_file( $file ) {
	if ( is_readable( $file ) ) {
		return $file;
	}

	$preferred_locales = preferred_languages_get_list();

	if ( empty( $preferred_locales ) ) {
		return $file;
	}

	$current_locale = determine_locale();

	// Locale has been filtered by something else.
	if ( ! in_array( $current_locale, $preferred_locales, true ) ) {
		return $file;
	}

	foreach ( $preferred_locales as $locale ) {
		$preferred_file = str_replace( $current_locale, $locale, $file );

		if ( is_readable( $preferred_file ) ) {
			return $preferred_file;
		}
	}

	return $file;
}

/**
 * Registers the needed scripts and styles.
 *
 * @since 1.0.0
 */
function preferred_languages_register_scripts() {
	$asset_file            = plugin_dir_path( __DIR__ ) . 'build/preferred-languages.asset.php';
	$asset                 = is_readable( $asset_file ) ? require $asset_file : array();
	$asset['dependencies'] = isset( $asset['dependencies'] ) ? $asset['dependencies'] : array();
	$asset['version']      = isset( $asset['version'] ) ? $asset['version'] : false;

	wp_register_script(
		'preferred-languages',
		plugin_dir_url( __DIR__ ) . 'build/preferred-languages.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_set_script_translations( 'preferred-languages', 'preferred-languages' );

	wp_register_style(
		'preferred-languages',
		plugin_dir_url( dirname( __FILE__ ) ) . 'build/preferred-languages.css',
		array( 'wp-components' ),
		$asset['version'],
		'screen'
	);

	wp_styles()->add_data( 'preferred-languages', 'rtl', true );
}

/**
 * Enqueues the stylesheet in the admin.
 *
 * @since 1.4.0
 */
function preferred_languages_enqueue_scripts() {
	wp_enqueue_style( 'preferred-languages' );
}

/**
 * Adds a settings field for the preferred languages option.
 *
 * @since 1.0.0
 */
function preferred_languages_settings_field() {
	add_settings_field(
		'preferred_languages',
		'<span id="preferred-languages-label">' . __( 'Site Language', 'preferred-languages' ) . '<span/>',
		'preferred_languages_display_form',
		'general',
		'default',
		array(
			'class'    => 'site-preferred-languages-wrap',
			'selected' => preferred_languages_get_site_list(),
		)
	);
}

/**
 * Adds a settings field for the preferred languages option to the user profile.
 *
 * @since 1.0.0
 *
 * @param WP_User $user The current WP_User object.
 */
function preferred_languages_personal_options( $user ) {
	$languages = get_available_languages();

	if ( ! $languages && ! current_user_can( 'install_languages' ) ) {
		return;
	}
	?>
	<tr class="user-preferred-languages-wrap">
		<th scope="row">
			<span id="preferred-languages-label"><?php _e( 'Language', 'preferred-languages' ); ?></span>
		</th>
		<td>
			<?php
			preferred_languages_display_form(
				array(
					'selected'                    => preferred_languages_get_user_list( $user ),
					'show_available_translations' => false,
					'show_option_en_US'           => true,
				)
			);
			?>
		</td>
	</tr>
	<?php
}

/**
 * Displays the actual form to select the preferred languages.
 *
 * @since 1.0.0
 *
 * @param array $args Optional. Arguments to pass to the form.
 */
function preferred_languages_display_form( $args = array() ) {
	static $instance = 0;
	$instance++;

	wp_enqueue_script( 'preferred-languages' );

	$args = (array) wp_parse_args(
		$args,
		array(
			'selected'                    => array(),
			'show_available_translations' => current_user_can( 'install_languages' ),
			'show_option_en_US'           => false,
		)
	);

	require_once ABSPATH . 'wp-admin/includes/translation-install.php';

	$translations = array_map(
		static function ( $translation ) {
				/* @var array $translation */
				return array(
					'locale'     => $translation['language'],
					'nativeName' => $translation['native_name'],
					'lang'       => current( $translation['iso'] ),
					'installed'  => false,
				);
		},
		wp_get_available_translations()
	);

	$languages = array_map(
		static function( $language ) use ( &$translations ) {
				$result = isset( $translations[ $language ] ) ? $translations[ $language ] : array(
					'locale'     => $language,
					'nativeName' => $language,
				);
				unset( $translations[ $language ] );

				$result['installed'] = true;
				return $result;
		},
		get_available_languages()
	);

	if ( $args['show_option_en_US'] ) {
		array_unshift(
			$languages,
			array(
				'locale'     => 'en_US',
				'nativeName' => 'English (United States)',
				'lang'       => 'en',
				'installed'  => true,
			)
		);
	}

	$options = array_merge(
		$languages,
		$translations
	);

	$preferred_languages = array();

	foreach ( (array) $args['selected'] as $locale ) {
		if ( 'en_US' === $locale ) {
			continue;
		}

		if ( isset( $options[ $locale ] ) ) {
			$preferred_languages[] = $options[ $locale ];
		} else {
			$preferred_languages[] = array(
				'locale'     => $locale,
				'nativeName' => $locale,
				'lang'       => '',
				'installed'  => false,
			);
		}
	}

	$props = array(
		'currentLocale'          => get_locale(),
		'preferredLanguages'     => $preferred_languages,
		'options'                => array_values( $options ),
		'hasMissingTranslations' => false,
	);

	if ( current_user_can( 'install_languages' ) ) {
		foreach ( (array) $args['selected'] as $locale ) {
			if ( 'en_US' === $locale ) {
				continue;
			}

			if ( ! in_array( $locale, $languages, true ) ) {
				$props['hasMissingTranslations'] = true;
				break;
			}
		}
	}

	wp_add_inline_script(
		'preferred-languages',
		sprintf(
			'window.PreferredLanguages.render(\'%1$s\',%2$s);',
			esc_js( 'preferred-languages-root-' . $instance ),
			wp_json_encode( $props )
		)
	);

	?>
	<div
			id="<?php echo esc_attr( 'preferred-languages-root-' . $instance ); ?>"></div>
	<?php
}

/**
 * Initializes the class used for registering textdomains.
 *
 * @since 1.1.0
 */
function preferred_languages_init_registry() {
	global $preferred_languages_textdomain_registry;

	$preferred_languages_textdomain_registry = new Preferred_Languages_Textdomain_Registry();
}

/**
 * Filters gettext calls to work around limitations in just-in-time loading of translations.
 *
 * @since 1.1.0
 *
 * @param string $translation  Translated text.
 * @param string $text         Text to translate.
 * @param string $domain       Text domain. Unique identifier for retrieving translated strings.
 *
 * @return string Translated text.
 */
function preferred_languages_filter_gettext( $translation, $text, $domain ) {
	if ( 'default' === $domain ) {
		return $translation;
	}

	$translations = get_translations_for_domain( $domain );

	if ( $translations instanceof NOOP_Translations ) {
		/* @var Preferred_Languages_Textdomain_Registry $preferred_languages_textdomain_registry */
		global $preferred_languages_textdomain_registry;

		if ( ! $preferred_languages_textdomain_registry instanceof Preferred_Languages_Textdomain_Registry ) {
			preferred_languages_init_registry();
		}

		$path = $preferred_languages_textdomain_registry->get( $domain );

		if ( ! $path ) {
			$preferred_languages_textdomain_registry->get_translation_from_lang_dir( $domain );
		}

		$path = $preferred_languages_textdomain_registry->get( $domain );

		if ( ! $path ) {
			return $translation;
		}

		$preferred_locales = preferred_languages_get_list();

		// Locale has been filtered by something else.
		if ( ! in_array( get_locale(), $preferred_locales, true ) ) {
			return $translation;
		}

		foreach ( $preferred_locales as $locale ) {
			$mofile = "{$path}/{$domain}-{$locale}.mo";

			if ( load_textdomain( $domain, $mofile ) ) {
				$translations = get_translations_for_domain( $domain );
				$translation  = $translations->translate( $text );

				break;
			}
		}
	}

	return $translation;
}
