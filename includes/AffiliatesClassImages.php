<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * This class handles logo images for CPD affiliates saved in the media library.
 * 
 * Example image url:
 * "http:\/\/localhost\/cpd\/wp-content\/uploads\/2022\/12\/vocal_ny.png"
 * Path consists of 4 parts: 1. wp-content/uploads 2. year 3. month 4. image name
 * 
 * Example affiliate name for the logo image file name:
 * "Vocal NY" -> "vocalny.png"
 * "Make the Road Nevada" -> "maketheroadnevada.png"
 * Affiliate name is lowercased and spaces are removed. 
 * Accent characters are replaced with QWERTY characters.
 * These characters are disregarded: /-_&'()
 * 
 * @return AffiliatesClassImages
 */
class AffiliatesClassImages
{
    /**
     * Special string to search for affiliate library images
     * 
     * @var string
     */
    public $affiliate_substr = 'affiliate-';

    /**
     * Cache name
     * 
     * @var string
     */
    public $cpd_affiliate_image_cache = 'cpd_affiliate_images';

    /**
     * @var integer
     */
    public $cache_time_period = 60 * 60 * 24;

    /**
     * @return array|null
     */
    public function get_all_image_urls(): ?array
    {
        // Retrieve all images in cache
        $cache_of_logo_arrays = get_transient($this->cpd_affiliate_image_cache);

        if (false === $cache_of_logo_arrays) {
            $query_images_args = array(
                'post_type'      => 'attachment',
                'post_mime_type' => 'image',
                'post_status'    => 'inherit',
                'posts_per_page' => -1,
            );

            $query_images = new WP_Query($query_images_args);

            $cache_of_logo_arrays = array();
            $image_url = "";

            foreach ($query_images->posts as $image) {
                $image_url = wp_get_attachment_url($image->ID);

                if (str_contains($image_url, $this->affiliate_substr)) {
                    // Break image url into parts
                    $current_img_url_segments = explode($this->affiliate_substr, $image_url);

                    // Get the state and affiliate name from the above
                    $current_img_name = $current_img_url_segments[1];
                    $current_img_affiliate = str_replace(".png", "", $current_img_name);

                    $cache_of_logo_arrays[] = array(
                        'id' => $image->ID,
                        'url' => $image_url,
                        'affiliate' => $current_img_affiliate,
                    );
                }
            }

            // Cache the results for 1 day
            set_transient($this->cpd_affiliate_image_cache, $cache_of_logo_arrays, $this->cache_time_period);
        }


        return $cache_of_logo_arrays;
    }

    /**
     * @param string $affiliate_name
     * 
     * @return string|null
     */
    public function get_logo_url_by_affiliate_name($affiliate_name): ?string
    {
        $all_images = $this->get_all_image_urls();

        foreach ($all_images as $image) {
            $reformed_p_name = str_replace([" ", "(", ")", "/", "-", "'", "&", "_",], "", strtolower($affiliate_name));

            if (strtolower($image["affiliate"]) == $reformed_p_name) {
                return $image["url"];
            }
        }

        return null;
    }

    /**
     * Set affiliate image in media library
     * 
     * @param $file
     * 
     * @return void
     */
    public function set_affiliate_image($file): void
    {
        $affiliate_name = str_replace([" ", "(", ")", "/", "-", "'", "&", "_",], "", strtolower($file["name"]));
        $image_url = $file["url"];

        $image_name = $this->affiliate_substr . $affiliate_name . ".png";

        $image = file_get_contents($image_url);

        $upload_dir = wp_upload_dir();
        $upload_path = str_replace('/', DIRECTORY_SEPARATOR, $upload_dir['path']) . DIRECTORY_SEPARATOR;
        $filename = $upload_path . $image_name;

        file_put_contents($filename, $image);
    }

    /**
     * Delete affiliate image from media library
     * 
     * @param integer $name
     * 
     * @return void
     */
    public function delete_affiliate_image($name): void
    {
        $id = null;

        // Grab image id from cached images
        $all_images = $this->get_all_image_urls();

        foreach ($all_images as $image) {
            if (strtolower($image["affiliate"]) == strtolower($name)) {
                $id = $image["id"];
            }
        }

        wp_delete_attachment($id, true);
    }
}
