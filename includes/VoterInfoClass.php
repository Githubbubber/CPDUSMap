<?php

/**
 * Reference for the Google Civic Information API:
 * https://developers.google.com/civic-information/docs/v2
 * 
 * The CIVICINFO_API_KEY constant is the API key for the 
 * API, to be placed in wp_config.php
 * 
 * Elections:
 * electionQuery - List of available elections.
 * voterInfoQuery - Looks up information relevant to a voter based on the 
 * voter's (REQUIRED parameter) registered address.
 * 
 * Representatives:
 * representativeInfoByAddress - Looks up political geography and 
 * representative information for a single address.
 * representativeInfoByDivision - Looks up representative information 
 * for a single geographic division. Needs an OCD ID (REQUIRED parameter)
 * 
 * Example list of US IDs for NYS
 * https://github.com/opencivicdata/ocd-division-ids/blob/master/identifiers/country-us/state-ny-local_gov.csv
 * 
 * Divisions:
 * search - Searches for political divisions by their natural name or OCD ID.
 * 
 * There seems to be no data on Puerto Rico
 * 
 * @package CPDUSMap
 */
class VoterInfoClass
{
    /**
     * The address to be used for various queries
     * (city, state AND/OR zip)
     * 
     * To be url-encoded in the query string
     * 
     * @var string
     */
    public $address;

    /**
     * The chosen election ID 
     * 
     * @var int
     */
    public $electionId;

    /**
     * The chosen Open Civic Data division identifier
     * 
     * @var string
     */
    public $ocd_id;

    /**
     * @param string $address
     * @param string $ocd_id
     * @param int $electionId
     */
    function __construct($address = null, $ocd_id = null, $electionId = null)
    {
        $location = new LocationClass();
        $location = $location->getUserCurrentLocation();

        $this->address = !empty($address) ? urlencode($address) : null; // : urlencode($location);
        $this->ocd_id = !empty($ocd_id) ? urlencode($ocd_id) : null;
        $this->electionId = $electionId;
    }

    /**
     * API url is https://www.googleapis.com/civicinfo/v2/elections
     * 
     * @return array|null
     */
    public function getElectionQueryInfo(): ?array
    {
        $url = "https://www.googleapis.com/civicinfo/v2/elections?key=" . CIVICINFO_API_KEY;

        $response = wp_remote_get($url);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        return $response_body;
    }

    /**
     * API url is https://www.googleapis.com/civicinfo/v2/voterinfo
     * 
     * Optional query parameters: electionId (long) and officialOnly (bool)
     * 
     * @param int $electionId
     * @param bool $officialOnly
     * 
     * @return array|null
     */
    public function getVoterInfoQueryInfo($electionId = null, $officialOnly = null): ?array
    {
        $url = "https://www.googleapis.com/civicinfo/v2/voterinfo?key="
            . CIVICINFO_API_KEY
            . "&address=" . $this->address;

        if ($electionId) {
            $url .= "&electionId=" . $electionId;
        }

        if ($officialOnly) {
            $url .= "&officialOnly=" . $officialOnly;
        }

        $response = wp_remote_get($url);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        return $response_body;
    }

    /**
     * API url is https://www.googleapis.com/civicinfo/v2/representatives
     * 
     * All query params are optional: address, includeOffices (bool), levels, roles
     * 
     * @param bool $includeOffices
     * @param string $levels
     * @param string $roles
     * 
     * @return array|null
     */
    public function getRepresentativeInfoByAddress($includeOffices = null, $levels = null, $roles = null): ?array
    {
        $url = "https://www.googleapis.com/civicinfo/v2/representatives?key="
            . CIVICINFO_API_KEY
            . "&address=" . $this->address;

        if ($includeOffices) {
            $url .= "&includeOffices=" . $includeOffices;
        }

        if ($levels) {
            $url .= "&levels=" . $levels;
        }

        if ($roles) {
            $url .= "&roles=" . $roles;
        }

        $response = wp_remote_get($url);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        return $response_body;
    }

    /**
     * ocd_id - REQUIRED query parameter.
     * 
     * API url is https://www.googleapis.com/civicinfo/v2/representatives/{ocd_id}
     * 
     * Optional query parameters: levels, recursive (bool), roles
     * 
     * @param string $levels
     * @param bool $recursive
     * @param string $roles
     * 
     * @return array|null
     */
    public function getRepresentativeInfoByDivision($levels = null, $recursive = null, $roles = null): ?array
    {
        $url = "https://www.googleapis.com/civicinfo/v2/representatives/"
            . $this->ocd_id . "?key="
            . CIVICINFO_API_KEY;

        if ($levels) {
            $url .= "&levels=" . $levels;
        }

        if ($recursive) {
            $url .= "&recursive=" . $recursive;
        }

        if ($roles) {
            $url .= "&roles=" . $roles;
        }

        $response = wp_remote_get($url);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        return $response_body;
    }

    /**
     * API url is https://www.googleapis.com/civicinfo/v2/divisions
     * 
     * Optional query parameter: query
     * 
     * @param string $query
     * 
     * @return array|null
     */
    public function getSearchInfo($query = null): ?array
    {
        $url = "https://www.googleapis.com/civicinfo/v2/divisions?key="
            . CIVICINFO_API_KEY;

        if ($query) {
            $url .= "&query=" . $query;
        }

        $response = wp_remote_get($url);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        return $response_body;
    }
}
