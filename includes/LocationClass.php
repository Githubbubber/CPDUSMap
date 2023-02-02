<?php

/** 
 * GEOLITE2_ACCOUNT_ID and GEOLITE2_LICENSE_KEY are defined in wp-config.php
 */
class LocationClass
{
    /**
     * Use the GEOLITE2_ACCOUNT_ID and GEOLITE2_LICENSE_KEY to 
     * get the user's current location
     * 
     * TODO: Make DC IP address a fallback address?
     * 
     * @return array|null
     */
    public function getUserCurrentLocation(): ?array
    {
        // Get the user's IP address
        $ip = $this->getUserIpAddress();

        // Get the user's current location
        $location = !empty($ip) ? $this->getUserLocation($ip) : null;

        return $location;
    }

    /**
     * Get the user's ip address
     * 
     * @return string|null
     */
    public function getUserIpAddress(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] !== '::1' ? $_SERVER['REMOTE_ADDR'] : "130.185.153.196";

        return $ip;
    }

    /**
     * Get the user's location from IP address and 
     * MaxMind's GeoLite2 API
     * 
     * @param string $ip
     * 
     * @return array|null
     */
    public function getUserLocation($ip): ?array
    {
        $url = "https://geoip.maxmind.com/geoip/v2.1/city/"
            . $ip . "?host="
            . $ip . "&accountId="
            . GEOLITE2_ACCOUNT_ID
            . "&license_key="
            . GEOLITE2_LICENSE_KEY;

        $response = wp_remote_get($url);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        return $response_body;
    }
}
