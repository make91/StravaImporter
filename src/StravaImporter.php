<?php

namespace fjgarlin;

use Iamstuartwilson\StravaApi;

/**
 * Class StravaImporter
 * @package fjgarlin
 */
class StravaImporter {

    /**
     * @var object
     */
    private $config;

    /**
     * @var null
     */
    private $data;

    /**
     * @var StravaApi
     */
    private $api;

    /**
     * @var null
     */
    private $code;

    /**
     * @var null
     */
    private $accessToken;

    /**
     * @var null
     */
    private $athlete;

    /**
     * StravaImporter constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = (object)$config;
        $this->api = new StravaApi(
            $this->config->id,
            $this->config->secret
        );

        $this->code = null;
        $this->data = null;
        $this->accessToken = null;
        $this->athlete = null;
    }

    /**
     * Returns if we're already authorized
     * @return bool
     */
    public function authorized()
    {
        return !is_null($this->accessToken);
    }

    /**
     * Returns the URL to authorize this app
     * @return string
     */
    public function getAuthorizeUrl()
    {
        return $this->api->authenticationUrl($this->config->redirect_url, 'auto', 'write', null);
    }

    /**
     * Get athlete's data
     * @return null
     */
    public function getAthlete()
    {
        return $this->athlete;
    }

    /**
     * Set an athlete
     * @param $athlete
     */
    public function setAthlete($athlete)
    {
        $this->athlete = $athlete;
    }

    /**
     * Perform authorization from a given code
     * @param $code
     */
    public function authorize($code)
    {
        //set code and exchange tokens
        $this->code = $code;
        $accessToken = $this->api->tokenExchange($this->code);

        if ($accessToken) {
            if (isset($accessToken->athlete)) {
                $this->setAthlete($accessToken->athlete);
            }
            else {
                //echo "<pre>" . print_r($accessToken, true). "</pre>"; exit;
            }

            //and finally set the accessToken
            $this->accessToken = $accessToken->access_token;
            $this->api->setAccessToken($this->accessToken);
        }
    }

    /**
     * Upload the given data to Strava
     * @param $data
     * @return object
     */
    public function upload($data)
    {
        if (!$this->authorized()) {
            return (object)[
                'status' => false,
                'message' => 'Not authorized.'
            ];
        }

        if (!$data) {
            return (object)[
                'status' => false,
                'message' => 'Data is not valid.'
            ];
        }

        //if the file is being passed instead of an array
        if (!is_array($data)) {
            $data = $this->_csvToArray($data);
        }

        $this->data = $data;
        $postedObjects = [];
        foreach ($this->data as $activity) {
            $activity = (object)$activity;

            // check if time column is empty, if it is put time as noon
            if (strlen($activity->Time)) {
                $date = date('c',strtotime("$activity->Date $activity->Time"));
            } else {
                $date = date('c',strtotime("$activity->Date 12:00:00"));
            }

            // convert duration to seconds
            $duration = explode(':', $activity->Duration);
            if (sizeof($duration) == 1) {
                $duration = intval($duration[0]);
            } else if (sizeof($duration) == 2) {
                $duration = ($duration[0]*60) + intval($duration[1]);
            } else {
                $duration = ($duration[0]*3600) + ($duration[1]*60) + intval($duration[2]);
            }

            // convert distance to metres based on specified distance unit
            $distanceUnit = $activity->{'Distance Unit'};
            if ($distanceUnit == 'mi') {
                $distance = $activity->Distance * 1609.344;
            } else if ($distanceUnit == 'km') {
                $distance = $activity->Distance * 1000;
            } else if ($distanceUnit == 'm') {
                $distance = $activity->Distance;
            } else if ($distanceUnit == 'yd') {
                $distance = $activity->Distance * 0.9144;
            } else {
                $distance = $activity->Distance * 1609.344;
            }

            // name from workout type and course if exists
            if (strlen($activity->Course)) {
                $name = $activity->Workout . ', ' . $activity->Course;
            } else {
                $name = $activity->Workout;
            }

            $objToPost = [
                'name'             => $name,
                'type'             => $activity->Activity,
                'start_date_local' => $date,
                'elapsed_time'     => $duration,
                'distance'         => $distance,
                'description'      => $activity->Notes
            ];
            array_push($postedObjects, $objToPost);
            $this->logToFile(json_encode($objToPost));
            //http://strava.github.io/api/v3/activities/#create
            $res = $this->api->post('activities', $objToPost);

            //TODO: check if any activity was not uploaded
            //echo "<pre>" . print_r($res, true). "</pre>"; exit;
        }
        return (object)[
            'status' => true,
            'message' => 'Uploaded ' . sizeof($postedObjects) . ' runs',
            'added' => json_encode($postedObjects)
        ];
    }

    // http://php.net/manual/en/function.str-getcsv.php
    protected function _csvToArray($file_path) {
        $csv = array_map('str_getcsv', file($file_path));
        array_walk($csv, function(&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
        });
        array_shift($csv); # remove column header
        return $csv;
    }
    protected function logToFile($text) {
        $log = date("Y-m-d H:i:s", time()) . " " . $text . PHP_EOL;
        file_put_contents('/home/marcus/public/StravaImporter/logs/stravalog_'.date("Y-m-d").'.log', $log, FILE_APPEND);
    }
}