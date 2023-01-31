<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return AffiliatesAdministration
 */
class AffiliatesAdministration
{

    /**
     * Cache name
     * 
     * @var string
     */
    public $cpd_affiliates = 'cpd_affiliates';

    /**
     * Cache the number of affiliates
     * 
     * @var integer
     */
    public $cpd_affiliates_count = 0;

    /**
     * @var integer
     */
    public $cache_time_period = 60 * 60 * 24;

    public $affiliate_addr2_select = ["Suite", "Apartment", "Floor", "Room", "Department", "Class", "Building", "Unit", "Other"];

    public $states_and_abbrs = ["AK" => "Alaska", "AL" => "Alabama", "AR" => "Arkansas", "AS" => "American Samoa", "AZ" => "Arizona", "CA" => "California", "CO" => "Colorado", "CT" => "Connecticut", "DC" => "District of Columbia", "DE" => "Delaware", "FL" => "Florida", "GA" => "Georgia", "GU" => "Guam", "HI" => "Hawaii", "IA" => "Iowa", "ID" => "Idaho", "IL" => "Illinois", "IN" => "Indiana", "KS" => "Kansas", "KY" => "Kentucky", "LA" => "Louisiana", "MA" => "Massachusetts", "MD" => "Maryland", "ME" => "Maine", "MI" => "Michigan", "MN" => "Minnesota", "MO" => "Missouri", "MS" => "Mississippi", "MT" => "Montana", "NC" => "North Carolina", "ND" => "North Dakota", "NE" => "Nebraska", "NH" => "New Hampshire", "NJ" => "New Jersey", "NM" => "New Mexico", "NV" => "Nevada", "NY" => "New York", "OH" => "Ohio", "OK" => "Oklahoma", "OR" => "Oregon", "PA" => "Pennsylvania", "PR" => "Puerto Rico", "RI" => "Rhode Island", "SC" => "South Carolina", "SD" => "South Dakota", "TN" => "Tennessee", "TX" => "Texas", "UT" => "Utah", "VA" => "Virginia", "VI" => "Virgin Islands", "VT" => "Vermont", "WA" => "Washington", "WI" => "Wisconsin", "WV" => "West Virginia", "WY" => "Wyoming"];

    /**
     * If the user's state abbreviation is irretrievable, return "dc" (District of Columbia)
     * 
     * @param string $ip
     * 
     * @return string
     */
    public function getStateFromIP($ip): string
    {
        $user_state = $this->getStateFromIPInfo($ip);

        // If the user's state is still null, try the next API
        if (is_null($user_state)) {
            $user_state = $this->getStateFromGeoLite($ip);
        }

        return $user_state ?? "dc";
    }

    /**
     * Get the user's state from the IP address using the IPInfo API
     * 
     * @param string $ip
     * 
     * @return string|null
     */
    public function getStateFromIPInfo($ip): ?string
    {
        $user_state = null;

        // Get the user's state from the IP address
        $ipinfo = "https://ipinfo.io/" . $ip . "?token=" . IPINFO_TOKEN;
        $ipinfo_response = wp_remote_get($ipinfo);

        if (!is_wp_error($ipinfo_response)) {
            $ipinfo_response_body = wp_remote_retrieve_body($ipinfo_response);
            $ipinfo_response_body = json_decode($ipinfo_response_body);

            if (isset($ipinfo_response_body->region)) {
                $user_state = $ipinfo_response_body->region;
            }
        }

        return $user_state;
    }

    /**
     * Get the user's state from the IP address using the GeoLite API
     * 
     * @param string $ip
     * 
     * @return string|null
     */
    public function getStateFromGeoLite($ip): ?string
    {
        $user_state = null;

        // Retrieve data for your IP address with a curl request.
        $curl = curl_init('https://geolite.info/geoip/v2.1/city/' . $ip);
        $auth_info = GEOLITE2_ACCOUNT_ID . ":" . GEOLITE2_LICENSE_KEY;

        // curl_setopt($curl, CURLOPT_USERPWD, $auth_info);
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($curl, CURLOPT_HTTPHEADER, [$auth_info]);
        curl_setopt($curl, CURLOPT_CERTINFO, true);

        $json = curl_exec($curl);

        curl_close($curl);

        if (curl_errno($curl) || empty($json)) {
            return $user_state;
        }

        // Decode the JSON response.
        $location = json_decode($json, true);

        // Print the location data.
        if (isset($location['subdivisions'][0]['iso_code'])) {
            $user_state = $location['subdivisions'][0]['iso_code'];
        }

        return $user_state;
    }

    public function getAffiliateCountInCurrentState()
    {
        // IP address of the user
        $ip = !empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '::1' ? $_SERVER['REMOTE_ADDR'] : TEST_REMOTE_ADDR;

        // Get abbreviation for current state
        $user_state = $this->get_current_state_array();

        // Get the affiliates collection from the cache
        $affiliates_collection = $this->get_cached_affiliates();

        // Get the affiliates in the user's state
        $affiliates_in_user_state = $affiliates_collection[$user_state["state"]];

        // Get the number of affiliates in the user's state
        return count($affiliates_in_user_state);
    }

    public function getAllAffiliateStateCounts()
    {
        // Get the affiliates collection from the cache
        $affiliates_collection = $this->get_cached_affiliates();

        // Get the number of affiliates in each state
        $affiliates_in_each_state = [];

        foreach ($affiliates_collection as $state => $affiliates) {
            foreach ($this->states_and_abbrs as $stored_abbr => $stored_state) {
                if ($state !== $stored_state) {
                    continue;
                }

                $affiliates_in_each_state[strtolower($stored_abbr)] = count($affiliates);
            }
        }

        return $affiliates_in_each_state;
    }

    /**
     * If the user's ip address is irretrievable, return "dc" (District of Columbia)
     * 
     * @return array
     */
    public function get_current_state_array(): array
    {
        $user_state_array = [
            "abbr" => "dc",
            "state" => "District of Columbia"
        ];

        // IP address of the user
        $ip = !empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '::1' ? $_SERVER['REMOTE_ADDR'] : TEST_REMOTE_ADDR;

        if (!empty($ip)) {
            // Get the user's state from the IP address
            $user_state = $this->getStateFromIP($ip);

            foreach ($this->states_and_abbrs as $abbr => $state) {
                if ($user_state === $state) {
                    return [
                        "abbr" => $abbr,
                        "state" => $state
                    ];
                }
            }
        }

        return $user_state_array;
    }

    /**
     * @return array|void
     */
    public function get_list_of_affiliates(): ?array
    {
        $count = 0;

        $csv_file = dirname(__FILE__) . "\data\affiliates.csv";

        if (file_exists($csv_file)) {
            $handle = fopen($csv_file, "r");
            $affiliates_collection = [];

            while (($data = fgetcsv($handle)) !== FALSE) {
                if ($data[0] !== "id") {
                    $affiliates_collection[$data[2]][] = [
                        "id" => $data[0],
                        "state_name" => $data[2],
                        "name" => $data[3],
                        "site_url" => $data[4],
                        "addr1" => $data[5],
                        "addr2" => $data[6] ?? null,
                        "addr2_etc" => $data[7] ?? null,
                        "city" => $data[8],
                        "state" => $data[9],
                        "zipcode" => $data[10],
                        "phone" => $data[11] ?? null,
                        "tagline" => $data[12] ?? null,
                        "volunteer" => $data[13] ?? null,
                        "volunteer_url" => $data[14] ?? null,
                    ];

                    $count++;
                }

                $this->cpd_affiliates_count = $count;
            }

            fclose($handle);

            // Sort the affiliates collection by state name.
            ksort($affiliates_collection);

            // Cache the affiliates collection.
            set_transient($this->cpd_affiliates, $affiliates_collection, 60 * 60 * 24);

            return $affiliates_collection;
        } else {
            return null;
        }
    }

    /**
     * Where the admin plugins page retrieves each 
     * state section with the affiliates locagted there.
     * Each affiliate has an html form with an 
     * edit and delete option.
     * 
     * @return void
     */
    public function show_affiliates_by_state(): void
    {
        $states_and_affiliates = $this->get_list_of_affiliates(); ?>

        <!-- Show the state sections with the affiliates in them. -->
        <div class="wrap">
            <h2>Current Affiliates:</h2>

            <?php
            foreach ($states_and_affiliates as $state => $affiliates) {
                if (gettype($affiliates) === "string" || count($affiliates) === 0) {
                    continue;
                }

                echo "<h3>$state</h3>";

                echo "<ul class='stateAffiliateContainer'>";

                foreach ($affiliates as $affiliate) {
                    // Set up the thickbox of the edit affiliate form
                    $this->set_up_edit_affiliate($affiliate["id"]);

                    // Set up the thickbox of the delete affiliate form
                    $this->set_up_delete_affiliate($affiliate["id"]);

                    if (isset($_POST['submit_image_selector-' . $affiliate["id"]]) && isset($_POST['image_attachment_id-' . $affiliate["id"]])) :
                        update_option(
                            'media_selector_attachment_id-' . $affiliate["id"],
                            absint($_POST['image_attachment_id-' . $affiliate["id"]])
                        );
                    endif;
            ?>
                    <li class="singleAffiliate">
                        <strong>
                            <a href="<?php echo esc_attr($affiliate["site_url"]); ?>">
                                <?php echo esc_html($affiliate["name"]); ?>
                            </a>
                        </strong>
                        <br />
                        <span>
                            <?php echo esc_html($affiliate["addr1"]); ?>
                        </span>
                        <br />
                        <?php if ($affiliate["addr2"] !== "" && $affiliate["addr2"] !== null) : ?>
                            <span>
                                <?php echo esc_html($affiliate["addr2"] . " " . $affiliate["addr2_etc"]); ?>
                            </span>
                            <br />
                        <?php endif; ?>

                        <span>
                            <?php echo esc_html($affiliate["city"] . ", " . $affiliate["state"] . " " . $affiliate["zipcode"]); ?>
                        </span><br />

                        <?php if ($affiliate["phone"]) : ?><span>
                                <?php echo esc_html($affiliate["phone"]); ?>
                            </span><br />
                        <?php endif; ?>

                        <?php if ($affiliate["tagline"]) : ?><span>
                                <em>
                                    <?php echo esc_html($affiliate["tagline"]); ?>
                                </em>
                            </span><br />
                        <?php endif; ?>

                        <?php if ($affiliate["volunteer"]) : ?><span>
                                <em>
                                    <a href="<?php echo esc_attr($affiliate["volunteer_url"]); ?>">
                                        <?php echo esc_html($affiliate["volunteer"]); ?>
                                    </a>
                                </em>
                            </span><br />
                        <?php endif; ?>

                        <a href="#TB_inline?&width=600&height=550&inlineId=edit-affiliate-<?php echo $affiliate["id"]; ?>" class="thickbox">Edit</a> &nbsp;
                        <a href="#TB_inline?&width=600&height=550&inlineId=delete-affiliate-<?php echo $affiliate["id"]; ?>" class="thickbox">Delete</a>
                    </li>
            <?php }
                echo "</ul>";
            } ?>
        </div>

        <?php
        $my_saved_attachment_post_id = get_option('media_selector_attachment_id-' . $affiliate["id"], 0);

        wp_enqueue_media();
        ?>

        <script type='text/javascript'>
            const uploadImageButtons = document.querySelectorAll('input[id^="upload_image_button"]');

            let file_frame;
            let set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>; // Set this

            uploadImageButtons.forEach(uploadImageButton => {
                uploadImageButton.addEventListener('click', event => {
                    event.preventDefault();

                    if (file_frame) {
                        // Set the post ID to what we want
                        file_frame.uploader.uploader.param('post_id', set_to_post_id);
                        file_frame.open();
                        return;
                    }

                    const idFromAttr = event.target.id;
                    const idFromAttrSplit = idFromAttr.split('-');
                    const idFromAttrSplitLast = idFromAttrSplit[idFromAttrSplit.length - 1];

                    file_frame = wp.media.frames.file_frame = wp.media({
                        title: 'Select a image to upload',
                        button: {
                            text: 'Use this image',
                        },
                        multiple: false
                    });

                    file_frame.on('select', function() {
                        attachment = file_frame.state().get('selection').first().toJSON();

                        // Do something with attachment.id and/or attachment.url here
                        const imagePreviewImg = document.querySelector('#image-preview-' + idFromAttrSplitLast);
                        const imageAttachmentId = document.querySelector('#image_attachment_id-' + idFromAttrSplitLast);

                        imagePreviewImg.setAttribute('src', attachment.url);
                        imagePreviewImg.setAttribute('width', 'auto');
                        imagePreviewImg.classList.remove('hidden');
                        imageAttachmentId.setAttribute('value', attachment.id);
                    });

                    // Open the media library frame.
                    file_frame.open();
                });
            });

            const aAddMedia = document.querySelectorAll('.add_media')[0];
            if (aAddMedia !== undefined) {
                aAddMedia.addEventListener('click', () => {
                    wp.media.model.settings.post.id = wp_media_post_id;
                });
            }
        </script>
    <?php
    }

    /**
     * @return void
     */
    public function show_add_new_affiliate_thickbox(): void
    {
        add_thickbox();

        // get the add new affiliate form
        $this->set_up_add_affiliate();
    }

    /**
     * @return array|null
     */
    public function get_cached_affiliates(): ?array
    {
        $cached_affiliates = get_transient($this->cpd_affiliates);

        if (false === $cached_affiliates) {
            $cached_affiliates = $this->get_list_of_affiliates();
        }

        return $cached_affiliates;
    }

    /**
     * @return int
     */
    public function get_cached_affiliates_count(): int
    {
        if ($this->cpd_affiliates_count === 0) {
            $stored = $this->get_cached_affiliates();

            // Get the highest id number
            $this->cpd_affiliates_count = max(array_map(function ($state) {
                return max(array_map(function ($affiliate) {
                    return $affiliate["id"];
                }, $state));
            }, $stored));
        }

        return $this->cpd_affiliates_count;
    }

    /**
     * After the "Add New Affiliate" button is clicked, this function will 
     * output the form for adding a new affiliate.
     *
     * @return void
     */
    public function set_up_add_affiliate(): void
    {
        $new_affiliate = [
            "type" => "add",
            "id" => $this->get_cached_affiliates_count() + 1,
            "state_name" => "Alaska",
            "text_name" => "this organization",
            "name" => "Organization Name, Inc.",
            "site_url" => "https://www.example.com",
            "addr1" => "123 Main St",
            "city" => "Anchorage",
            "state" => "AK",
            "zipcode" => "99501",
            "phone" => "555-555-5555",
            "tagline" => "You are now one with the universe.",
            "volunteer" => "Yes, volunteer with us!",
            "volunteer_url" => "https://www.example.com/volunteer",
            "class" => "add-p-form",
            "settings_fields" => "cpd_add_affiliate_form",
            "action" => "cpd_add_affiliate",
            "nonce_name" => "cpd_add_affiliate_nonce",
            "nonce_value" => "cpd_add_affiliate",
            "submit_text" => "Add New Affiliate",
        ]; ?>

        <a href="#TB_inline?&width=600&height=550&inlineId=add-new-affiliate" class="thickbox show-new-p-form">Add New Affiliate</a>

        <div id="add-new-affiliate" style="display:none;">
            <h2>Add New Affiliate</h2>

            <?php $this->get_p_form($new_affiliate); ?><br />
            <br />
            <div id="cpd_add_affiliate_form_feedback"></div>
        </div>
    <?php
    }

    /**
     * After the "Edit" button is clicked, this function will output the form for editing an affiliate.
     *
     * @param int $id
     * 
     * @return void
     */
    public function set_up_edit_affiliate($id): void
    {
        $affiliate = [];

        // Retrieve affiliate info from cache, by finding affiliate id in array of all affiliates
        $all_affiliates = $this->get_cached_affiliates();

        foreach ($all_affiliates as $value) {
            foreach ($value as $iterated_p) {
                if ($iterated_p["id"] === $id) {
                    $affiliate = $iterated_p;
                }
            }
        }

        $cpd_affiliate_images = new AffiliatesClassImages();
        $image_url = $cpd_affiliate_images->get_logo_url_by_affiliate_name($affiliate["name"]);

        $affiliate = array_merge(
            $affiliate,
            [
                "type" => "edit",
                "text_name" => $affiliate["name"],
                "class" => "edit-p-form",
                "logo_url" => $image_url,
                "settings_fields" => "cpd_edit_affiliate_form",
                "action" => "cpd_edit_affiliate",
                "nonce_name" => "cpd_edit_affiliate_nonce",
                "nonce_value" => "cpd_edit_affiliate",
                "submit_text" => "Update Affiliate",
            ],
        ); ?>

        <div id="edit-affiliate-<?php echo $id; ?>" style="display:none;">
            <h2>Edit affiliate <?php echo $affiliate["name"]; ?> info</h2>

            <?php $this->get_p_form($affiliate); ?><br />
            <br />
            <div id="edit-affiliate-<?php echo $id; ?>-form-feedback"></div>
        </div>
    <?php
    }

    /**
     * @param $id
     * 
     * @return void
     */
    public function set_up_delete_affiliate($id): void
    {
        $affiliate = [];

        // Retrieve affiliate info from cache
        $all_affiliates = $this->get_cached_affiliates();

        foreach ($all_affiliates as $value) {
            foreach ($value as $iterated_p) {
                if ($iterated_p["id"] === $id) {
                    $affiliate = $iterated_p;
                }
            }
        }

        $cpd_affiliate_images = new AffiliatesClassImages();
        $image_url = $cpd_affiliate_images->get_logo_url_by_affiliate_name($affiliate["name"]);

        $affiliate = array_merge(
            $affiliate,
            [
                "class" => "delete-p-form",
                "logo_url" => $image_url,
                "settings_fields" => "cpd_delete_affiliate_form",
                "action" => "cpd_delete_affiliate",
                "nonce_name" => "cpd_delete_affiliate_nonce",
                "nonce_value" => "cpd_delete_affiliate",
            ],
        ); ?>

        <div id="delete-affiliate-<?php echo $id; ?>" style="display:none;">
            <img src="<?php echo esc_attr($affiliate["logo_url"]); ?>" alt="<?php echo esc_attr($affiliate["name"]); ?> Logo" class="logo_thumbnail" /><br />
            <br />
            <h2 class="h2_inline">
                <strong><a href="<?php echo esc_attr($affiliate["site_url"]); ?>"><?php echo esc_html($affiliate["name"]); ?></a></strong>
            </h2>
            <span>, located in the state of <strong><?php echo $affiliate["state_name"]; ?></strong>.</span><br />
            <br />
            <span>
                <?php echo esc_html($affiliate["addr1"]); ?>
            </span>
            <br />
            <?php if ($affiliate["addr2"] !== "" && $affiliate["addr2"] !== null) : ?>
                <span>
                    <?php echo esc_html($affiliate["addr2"] . " " . $affiliate["addr2_etc"]); ?>
                </span>
                <br />
            <?php endif; ?>
            <span>
                <?php echo esc_html($affiliate["city"] . ", " . $affiliate["state"] . " " . $affiliate["zipcode"]); ?>
            </span>
            <br />
            <span>
                <?php echo esc_html($affiliate["phone"]); ?>
            </span>
            <br />
            <span>
                <em>
                    <?php echo esc_html($affiliate["tagline"]); ?>
                </em>
            </span>
            <br />
            <span>
                <em>
                    <a href="<?php echo esc_attr($affiliate["volunteer_url"]); ?>">
                        <?php echo esc_html($affiliate["volunteer"]); ?>
                    </a>
                </em>
            </span><br />
            <br />
            <h3>Are you sure you want to delete CPD Affiliate <?php echo $affiliate["name"]; ?>?</h3>
            <br />
            <a href="#TB_inline?&width=600&height=550&inlineId=edit-affiliate-<?php echo $affiliate["id"]; ?>" class="thickbox">No, edit this affiliate instead</a> &nbsp;
            <form class="<?php echo esc_attr($affiliate["class"]); ?>" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">

                <?php
                // Output security fields for the registered setting
                settings_fields($affiliate["settings_fields"]);
                ?>

                <input type="hidden" name="action" value="<?php echo esc_attr($affiliate["action"]); ?>" />
                <input type="hidden" name="affiliate_id_number" value="<?php echo esc_attr($affiliate["id"]); ?>" />
                <input type="hidden" name="<?php echo esc_attr($affiliate["nonce_name"]); ?>" value="<?php echo wp_create_nonce($affiliate["nonce_value"]); ?>">

                <?php submit_button("Yes, delete " . $affiliate["name"]); ?>
            </form><br />
            <br />
            <div id="delete-affiliate-<?php echo $id; ?>-form-feedback"></div>
        </div>
    <?php
    }

    public function get_p_form($affiliate): void
    {
        $placeholder = !empty($affiliate["addr2_etc"]) ? $affiliate["addr2_etc"] : '100';
        $submit_image_selector = "submit_image_selector-" . $affiliate["id"];
    ?>

        <form class="<?php echo esc_attr($affiliate["class"]); ?>" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">

            <?php
            // Output security fields for the registered setting
            settings_fields($affiliate["settings_fields"]);
            ?>

            <input type="hidden" name="action" value="<?php echo esc_attr($affiliate["action"]); ?>" />
            <input type="hidden" name="affiliateIDNumber" value="<?php echo esc_attr($affiliate["id"]); ?>" />
            <input type="hidden" name="<?php echo esc_attr($affiliate["nonce_name"]); ?>" value="<?php echo wp_create_nonce($affiliate["nonce_value"]); ?>">

            <!-- (Upload/Add) Logo -->
            <div class='image-preview-wrapper'>

                <img id="image-preview-<?php echo esc_attr($affiliate["id"]); ?>" src="<?php if (!empty($affiliate["logo_url"])) : ?><?php echo esc_attr($affiliate["logo_url"]); ?><?php else : ?><?php echo wp_get_attachment_url(get_option('media_selector_attachment_id-' . $affiliate["id"])); ?><?php endif; ?>" alt="<?php echo esc_attr($affiliate["name"]); ?> Logo" class="<?php if ($affiliate["type"] === "add") : ?>hidden <?php endif; ?>logo_thumbnail" />

            </div>
            <br />
            <br />
            <label>Upload a logo for <?php echo esc_html($affiliate["text_name"]); ?>: &nbsp;

                <input id="upload_image_button-<?php echo esc_attr($affiliate["id"]); ?>" type="button" class="button" value="<?php _e('Upload image'); ?>" <?php if ($affiliate["type"] === "add") : ?> required <?php endif; ?> />

                <input id='image_attachment_id-<?php echo esc_attr($affiliate["id"]); ?>' type='hidden' name='affiliateLogo-<?php echo esc_attr($affiliate["id"]); ?>' value='<?php echo get_option('media_selector_attachment_id-' . $affiliate["id"]); ?>' />

            </label>
            <br />
            <br />

            <!-- General Info -->
            <label for="affiliateNameText">Affiliate Name:
                <input type="text" name="affiliateNameText" class='regular-text' placeholder="<?php echo esc_attr($affiliate["name"]); ?>" <?php if ($affiliate["type"] === "edit") : ?> value="<?php echo esc_attr($affiliate["name"]); ?>" <?php endif; ?> required />
            </label><br />
            <label for="affiliateSiteURL">Site URL:
                <input type="url" name="affiliateSiteURL" placeholder="<?php echo esc_attr($affiliate["site_url"]); ?>" <?php if ($affiliate["type"] === "edit") : ?> value="<?php echo esc_attr($affiliate["site_url"]); ?>" <?php endif; ?> required />
            </label><br />
            <label for="affiliateTelText">Phone Number:
                <input type="tel" name="affiliateTelText" placeholder="<?php echo esc_attr($affiliate["phone"]); ?>" minlength="10" maxlength="15" <?php if ($affiliate["type"] === "edit") : ?> value="<?php echo esc_attr($affiliate["phone"]); ?>" <?php endif; ?> />
            </label>
            <br />
            <!-- Address -->
            <label for="affiliateAddress1Text">Address Line 1:
                <input type="text" name="affiliateAddress1Text" class="regular-text" placeholder="<?php echo esc_attr($affiliate["addr1"]); ?>" <?php if ($affiliate["type"] === "edit") : ?> value="<?php echo esc_attr($affiliate["addr1"]); ?>" <?php endif; ?> required />
            </label><br />
            <label for="affiliateAddress2Select">Address Line 2:
                <select name="affiliateAddress2Select">
                    <!-- Loop through the addr2 select options -->
                    <?php foreach ($this->affiliate_addr2_select as $choice) : ?>
                        <option value="<?php echo esc_attr($choice); ?>" <?php if (!empty($affiliate["addr2"]) && $affiliate["addr2"] === $choice) : ?> selected <?php endif; ?>><?php echo esc_html($choice); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="affiliateAddress2SelectETC" placeholder="<?php echo $placeholder; ?>" <?php if ($affiliate["type"] === "edit" && !empty($affiliate["addr2_etc"])) : ?> value="<?php echo $affiliate["addr2_etc"]; ?>" <?php endif; ?> /><br />
            </label> &nbsp;
            &nbsp;
            <label for="affiliateCityText">City:
                <input type="text" name="affiliateCityText" class="regular-text" placeholder="<?php echo esc_attr($affiliate["city"]); ?>" <?php if ($affiliate["type"] === "edit") : ?> value="<?php echo esc_attr($affiliate["city"]); ?>" <?php endif; ?> required />
            </label><br />
            <label for="affiliateStateSelect">State:
                <select name="affiliateStateSelect" required>
                    <!-- Loop through the state select options -->
                    <?php foreach ($this->states_and_abbrs as $abbr => $state) : ?>
                        <option value="<?php echo esc_attr($abbr); ?>" <?php if ($affiliate["type"] === "edit" && $affiliate["state_name"] === $state) : ?> selected <?php endif; ?>><?php echo esc_html($state); ?></option>
                    <?php endforeach; ?>
                </select>
            </label><br />
            <label for="affiliateZipcodeText">Zipcode:
                <input type="text" name="affiliateZipcodeText" class="regular-text" placeholder="<?php echo esc_attr($affiliate["zipcode"]); ?>" autocomplete="postal-code" inputmode="numeric" minlength="5" maxlength="5" <?php if ($affiliate["type"] === "edit") : ?> value="<?php echo esc_attr($affiliate["zipcode"]); ?>" <?php endif; ?> required />
            </label>
            <br />
            <!-- Tagline, Volunteering Details -->
            <label for="affiliateTaglineText">Affiliate Tagline:
                <input type="text" name="affiliateTaglineText" class="regular-text" placeholder='"<?php echo esc_attr($affiliate["tagline"]); ?>"' <?php if ($affiliate["type"] === "edit") : ?> value="<?php echo esc_attr($affiliate["tagline"]); ?>" <?php endif; ?> />
            </label><br />
            <label for="affiliateVolunteerText">Volunteer Text:
                <input type="text" name="affiliateVolunteerText" class="regular-text" placeholder='"<?php echo esc_attr($affiliate["volunteer"]); ?>"' <?php if ($affiliate["type"] === "edit") : ?> value="<?php echo esc_attr($affiliate["volunteer"]); ?>" <?php endif; ?> />
            </label><br />
            <label for="affiliateVolunteerURL">Volunteer URL:
                <input type="url" name="affiliateVolunteerURL" class="regular-text" placeholder="<?php echo esc_attr($affiliate["volunteer_url"]); ?>" <?php if ($affiliate["type"] === "edit") : ?> value="<?php echo esc_attr($affiliate["volunteer_url"]); ?>" <?php endif; ?> />
            </label>

            <?php submit_button($affiliate["submit_text"], "primary", $submit_image_selector, true, ["class" => "button-primary"]); ?>
        </form>
<?php
    }
}
