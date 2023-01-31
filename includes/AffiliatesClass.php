<?php

/**
 * Responsibilities of class AffiliatesClass:
 * - Registration of widget/block with WordPress
 * - CRUD methods for affiliate data
 * 
 * @package AffiliatesClass
 */
class AffiliatesClass extends WP_Widget
{
  const CPD_WIDGET_BASE_NAME = 'cpdusmap';
  const CPD_WIDGET_BASE_ID = 'cpdusmap_widget';
  const CPD_WIDGET_NAME = 'CPDUSMap Map';
  const CPD_WIDGET_DESCRIPTION = 'Widget to display the CPD US Map';

  /**
   * Cache name
   * 
   * @var string
   */
  public $cpd_affiliates = 'cpd_affiliates';

  /**
   * @var integer
   */
  public $cache_time_period = 60 * 60 * 24;

  function __construct()
  {
    parent::__construct(
      self::CPD_WIDGET_BASE_ID,
      esc_html__(self::CPD_WIDGET_NAME),
      array('description' => esc_html__(self::CPD_WIDGET_DESCRIPTION))
    );
  }

  /**
   * Front-end display of widget.
   *
   * @see WP_Widget::widget()
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function widget($args, $instance): void
  {
    echo $args['before_widget']; // Whatever you want to display before widget (<div>, etc)

    // Widget Content Output
    echo "Hello!";

    echo $args['after_widget']; // Whatever you want to display after widget (</div>, etc)
  }

  /**
   * Get state affiliate info
   * esc_url() - Sanitizes a URL for use in the href attribute of a link.
   * 
   * TODO: Find correct query to retrieve map-related data. csv vs db?
   * 
   * @return array
   */
  public function get_all_affiliates(): array
  {
    $args = array(
      'post_type' => 'affiliate',
      'posts_per_page' => -1,
      'orderby' => 'state',
    );

    $affiliate_query = new WP_Query($args);

    $affiliate_data = array();

    if ($affiliate_query->have_posts()) {
      while ($affiliate_query->have_posts()) {
        $affiliate_query->the_post();

        $affiliate_data[] = array(
          'id' => get_the_ID(),
          'name' => get_the_title(),
          'url' => get_post_field('site_url'),
          'logo' => get_post_field('logo'),
          'logo_alt' => get_post_field('logo_alt'),
          'address' => get_post_field('address'),
          'tagline' => get_post_field('tagline'),
          'volunteer_url' => get_post_field('volunteer_url')
        );
      }
    }

    $url = add_query_arg(
      [
        'action' => 'wporg_frontend_delete', // TODO: backend version?
        'post'   => $affiliate_data["id"],
        'nonce'  => wp_create_nonce('wporg_frontend_delete'),
      ],
      home_url() // TODO: backend version?
    );

    $affiliate_data = array_merge(
      $affiliate_data,
      array('delete_url' => '<a href="' . esc_url($url) . '">' . esc_html__('delete', 'wporg') . '</a>')
    );

    wp_reset_postdata();

    // [gallery] shortcode to create a carousel of the logos alone?

    return $affiliate_data;
  }

  /**
   * Back-end affiliate info sanitizer. Detects nulls, too.
   * 
   * @param array $info
   * 
   * @return bool|array
   */
  public function get_affiliate_info_sanitized($info)
  {
    $required_info_values = [
      $info["affiliateNameText"],
      $info["affiliateSiteURL"],
      $info["affiliateAddress1Text"],
      $info["affiliateCityText"],
      $info["affiliateStateSelect"],
      $info["affiliateZipcodeText"]
    ];

    foreach ($required_info_values as $value) {
      if (empty($value) || $value === "") {
        return false;
      }
    }

    $states_and_abbrs = (new AffiliatesAdministration())->states_and_abbrs;

    $affiliate_logo_id = 0;

    foreach ($_POST as $key => $value) {
      if (strpos($key, 'affiliateLogo-') !== false && !empty($value)) {
        $affiliate_logo_id = $value;
      }
    }

    // sanitize the general affiliate input
    $affiliate_id_number = $_POST['affiliateIDNumber'];
    $affiliate_name_text = remove_accents(sanitize_text_field(trim($_POST['affiliateNameText'])));
    $affiliate_site_url = esc_url_raw($_POST['affiliateSiteURL']);

    // sanitize the address/phone number-related input
    $state_name = $states_and_abbrs[$_POST['affiliateStateSelect']];
    $affiliate_address1_text = sanitize_text_field(trim($_POST['affiliateAddress1Text']));
    $affiliate_address2_select = sanitize_text_field(trim($_POST['affiliateAddress2Select'])) ?? null;
    $affiliate_address2_text = sanitize_text_field(trim($_POST['affiliateAddress2SelectETC'])) ?? null;
    $affiliate_city_text = sanitize_text_field(trim($_POST['affiliateCityText']));
    $affiliate_state_select = sanitize_text_field(trim($_POST['affiliateStateSelect']));
    $affiliate_zipcode_text = sanitize_text_field(trim($_POST['affiliateZipcodeText']));
    $affiliate_tel_text = sanitize_text_field(trim($_POST['affiliateTelText'])) ?? null;

    // sanitize the tagline/volunteer-related input
    $affiliate_tagline_text = sanitize_text_field(trim($_POST['affiliateTaglineText'])) ?? null;
    $affiliate_volunteer_text = sanitize_text_field(trim($_POST['affiliateVolunteerText'])) ?? null;
    $affiliate_volunteer_url = esc_url_raw($_POST['affiliateVolunteerURL']) ?? null;

    return [
      "affiliateIdNumber" => $affiliate_id_number,
      "affiliateLogoIdNumber" => $affiliate_logo_id,
      "stateName" => $state_name,
      "affiliateNameText" => $affiliate_name_text,
      "affiliateSiteURL" => $affiliate_site_url,
      "affiliateAddress1Text" => $affiliate_address1_text,
      "addr2" => $affiliate_address2_select,
      "addr2_etc" => $affiliate_address2_text,
      "affiliateCityText" => $affiliate_city_text,
      "affiliateStateSelect" => $affiliate_state_select,
      "affiliateZipcodeText" => $affiliate_zipcode_text,
      "affiliateTelText" => $affiliate_tel_text,
      "affiliateTaglineText" => $affiliate_tagline_text,
      "affiliateVolunteerText" => $affiliate_volunteer_text,
      "affiliateVolunteerURL" => $affiliate_volunteer_url
    ];
  }

  /**
   * Back-end affiliate info validator.
   * 
   * @return bool
   */
  public function is_affiliate_info_valid($sanitized_info): bool
  {
    // Sift through all text fields to make sure text is valid
    $letters_unchecked_array = [
      "affiliateCityText" => $sanitized_info["affiliateCityText"],
      "affiliateStateSelect" => $sanitized_info["affiliateStateSelect"]
    ];

    $items_to_check_for_letters_array = array_filter($letters_unchecked_array, function ($initial_value, $item) {
      return $initial_value && !empty($item) && $item !== "";
    }, true);

    $failed_letters_array = array();

    foreach ($items_to_check_for_letters_array as $key => $item) {
      if (!preg_match('/^[a-zA-Z]{2,}$/', $item)) {
        $failed_letters_array = array_merge($failed_letters_array, [$key => false]);
      }
    }

    $alphanumeric_chars_unchecked_array = [
      "affiliateNameText" => $sanitized_info["affiliateNameText"],
      "affiliateAddress1Text" => $sanitized_info["affiliateAddress1Text"],
      "affiliateTaglineText" => $sanitized_info["affiliateTaglineText"],
      "affiliateVolunteerText" => $sanitized_info["affiliateVolunteerText"],
      "addr2_etc" => $sanitized_info["addr2_etc"]
    ];

    $items_to_check_for_alphanumerics_array = array_filter($alphanumeric_chars_unchecked_array, function ($initial_value, $item) {
      return $initial_value && !empty($item) && $item !== "";
    }, true);

    $failed_alphanumerics_array = array();

    foreach ($items_to_check_for_alphanumerics_array as $key => $item) {
      if (!preg_match('/^[a-zA-Z0-9.!,\';"()&-.: áéíóúñü¿¡ÁÉÍÓÚÑ]+$/', $item)) {
        $failed_alphanumerics_array = array_merge($failed_alphanumerics_array, [$key => false]);
      }
    }

    $has_numbers_select_chars_only = preg_match('/[\+\s\(\)0-9-]{10,21}/', $sanitized_info["affiliateTelText"]);

    $zipcode_is_valid = strlen(trim($sanitized_info["affiliateZipcodeText"])) === 5 && preg_match('/^\d{5}$/', $sanitized_info["affiliateZipcodeText"]);

    $ids_are_numbers = is_numeric($sanitized_info["affiliateIdNumber"]) && is_numeric($sanitized_info["affiliateLogoIdNumber"]);

    $is_valid = count($failed_letters_array) === 0
      && count($failed_alphanumerics_array) === 0
      && $has_numbers_select_chars_only
      && $zipcode_is_valid
      && $ids_are_numbers;

    return $is_valid;
  }

  /**
   * If the above two functions work
   * then let's save the new affiliate!
   * 
   * @param array $info
   * 
   * @return string
   */
  public function set_processed_new_affiliate($info): string
  {
    $sanitized_info = $this->get_affiliate_info_sanitized($info);
    $validated_info = $this->is_affiliate_info_valid($sanitized_info);

    if (!$validated_info) {
      return "Invalid data";
    }

    // do the processing		
    $csv_file = dirname(__FILE__) . "\data\affiliates.csv";

    $sanitized_info = implode(",", $sanitized_info) . "\n";

    if (file_exists($csv_file)) {
      $handle = fopen($csv_file, "a+");

      fwrite($handle, $sanitized_info);

      fclose($handle);

      return "New affiliate added";
    } else {
      return "File does not exist";
    }
  }

  /**
   * @param array $info
   * @param int $id
   * 
   * @return string
   */
  public function set_processed_edited_affiliate($info): string
  {
    $sanitized_info = $this->get_affiliate_info_sanitized($info);
    $validated_info = $this->is_affiliate_info_valid($sanitized_info);

    if (!$validated_info) {
      return "Invalid data";
    }

    // do the processing		
    $csv_file = dirname(__FILE__) . "\data\affiliates.csv";

    // Replace row in file with new data where id matches
    if (file_exists($csv_file)) {
      $handle = fopen($csv_file, "r");

      $new_file = array();

      while (($line = fgetcsv($handle)) !== FALSE) {
        if ($line[0] !== $sanitized_info["affiliateIdNumber"]) {
          $new_file[] = implode(",", $line) . "\n";
        } else {
          $new_file[] = implode(",", $sanitized_info) . "\n";
        }
      }

      fclose($handle);

      $handle = fopen($csv_file, "w");

      rewind($handle);

      foreach ($new_file as $line) {
        fwrite($handle, $line);
      }

      fclose($handle);

      return "Affiliate edited";
    } else {
      return "File does not exist";
    }
  }

  /**
   * @param array $info
   * @param int $id
   * 
   * @return string
   */
  public function set_deleted_affiliate($id): string
  {
    if (!$id) {
      return "Invalid data";
    }

    // do the processing		
    $csv_file = dirname(__FILE__) . "\data\affiliates.csv";

    if (file_exists($csv_file)) {
      $handle = fopen($csv_file, "r");

      $new_file = array();

      while (($line = fgetcsv($handle)) !== FALSE) {
        if ($line[0] !== $id) {
          $new_file[] = $line;
        } else {
          // Delete the affiliate's logo image from the media library
          $cpd_affiliate_images = new AffiliatesClassImages();
          $cpd_affiliate_images->delete_affiliate_image($line[2]);
        }
      }

      fclose($handle);

      $handle = fopen($csv_file, "w");

      rewind($handle);

      foreach ($new_file as $line) {
        fputcsv($handle, $line);
      }

      fclose($handle);

      return "Affiliate is deleted";
    } else {
      return "File does not exist";
    }
  }

  /*
	 * Destroys subclass options (for plug-in uninstall).
   * 
   * @return void
	 */
  public static function purge_widget_after_uninstall(): void
  {
    if (!\delete_option('widget_' . self::CPD_WIDGET_BASE_ID))
      error_log(__FILE__ . ': "delete_option()" failed for ' . self::CPD_WIDGET_BASE_ID);

    $occurrences = get_option('sidebars_widgets');

    if (is_array($occurrences)) {
      foreach ($occurrences as $sidebar => $instances) {
        if (is_array($instances)) foreach ($instances as $key => $widget) {
          if (1 == preg_match('/^' . self::CPD_WIDGET_BASE_ID . '(?:-\\d+)?$/', $widget))
            unset($occurrences[$sidebar][$key]);
        }
      }

      update_option('sidebars_widgets', $occurrences);
    }
  }
}
