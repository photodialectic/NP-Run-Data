<?php

/*******************************************************
 *
 * Nick's NikePlus Class v1.0
 * Created by Nick Hedberg for personal purposes, http://nickhedberg.com
 * Requires PHP 5 with SimpleXML, json, cURL
 *
 * @author Nick Hedberg
/*******************************************************/
class nikeData{
    //public variables Might consider converting to class constants

        //urls
        public $userData = "http://nikerunning.nike.com/nikeplus/v2/services/app/get_user_data.jsp?_plus=true";
        public $records  = "http://nikerunning.nike.com/nikeplus/v2/services/app/personal_records.jsp?_plus=true";
        public $runList  = "http://nikerunning.nike.com/nikeplus/v2/services/app/run_list.jsp?_plus=true";
        public $goalList = "http://nikerunning.nike.com/nikeplus/v2/services/app/goal_list.jsp?_plus=true";

        //urls that require user id
        public $fullGps = "http://nikerunning.nike.com/nikeplus/v2/services/app/get_gps_detail.jsp?_plus=true&&format=json&id=";
        public $fullRun = "http://nikerunning.nike.com/nikeplus/v2/services/app/get_run.jsp?_plus=true&id=";

        /**
         * Intial connection to Nike run feeds. Uses a cURL to send cookie information for authenticaltion
         *
         * Options:
         *     nikePlusId  - Nike Plus User Id
         *
         * @param  int    $nikePlusId  Nike+ user id
         * @return xml    $ndData Nike+ xml data
         */
        public function __construct($nikePlusId){
            $this->nikePlusID = $nikePlusId;
        }

    /**
     * Intial connection to Nike run feeds. Uses a cURL to send cookie information for authenticaltion
     *
     * Options:
     *     url  - Nike feed url
     *     runId - Nike run id, optional value
     *
     * @param  string $url    Nike+ feed url
     * @param  int    $runId  Nike+ run id
     * @return xml    $ndData Nike+ xml data
     */
    private function getSecrets($url, $runId = null){
        $ndCurl = curl_init();
        curl_setopt($ndCurl, CURLOPT_HTTPHEADER,     array("Cookie: plusid=$this->nikePlusID&nikerunning.nike.com"));
        curl_setopt($ndCurl, CURLOPT_COOKIE,        'plusid=' . $this->nikePlusID);
        curl_setopt($ndCurl, CURLOPT_URL,            $url . $runId);
        curl_setopt($ndCurl, CURLOPT_PORT,           80);
        curl_setopt($ndCurl, CURLOPT_RETURNTRANSFER, TRUE);
        $ndData = curl_exec($ndCurl);
        curl_close($ndCurl);
        return $ndData;
    }

    /**
     * Retrieve basic user data and create an array
     *
     * @return array $userDataArray Array of basic user infromation stored by Nike+
     */
    public function getUserData(){
        $data          = $this->getSecrets($this->userData);
        $data          = new SimpleXMLElement($data, null, false);
        $userDataArray = array(
            'externalProfileID'     => (int)    $data->user['externalProfileID'],
            'id'                    => (int)    $data->user['id'],
            'totalGPSRuns'          => (int)    $data->user->totalGPSRuns,
            'totalDistance'         => (float)  $data->userTotals->totalDistance,
            'totalRunDistance'      => (float)  $data->userTotals->totalRunDistance,
            'totalRunDuration'      => (int)    $data->userTotals->totalRunDuration,
            'totalDuration'         => (int)    $data->userTotals->totalDuration,
            'totalRuns'             => (int)    $data->userTotals->totalRuns,
            'totalCalories'         => (int)    $data->userTotals->totalCalories,
            'averageRunsPerWeek'    => (int)    $data->userTotals->averageRunsPerWeek,
            'preferredRunDayOfWeek' => (string) $data->userTotals->preferredRunDayOfWeek,
            'previousSyncTime'      => (string) $data->userTotals->previousSyncTime,
            'lastCalculated'        => (string) $data->userTotals->lastCalculated
        );
        return $userDataArray;
    }
    /**
     * Retrieves latest records (best times etc.)
     * Since the Nike+ data is sent back with each record as a node, this method runs through and just grabs the type and value and creates a new array
     *
     * @return array $recordsArray Reformatted from Nike+ where type is key and values is value. 
     */
    public function getRecords(){
        $data         = $this->getSecrets($this->records);
        $data         = new SimpleXMLElement($data, null, false);
        $recordKeys   = 'k';
        $recordValues = 'v';
        foreach ($data->PersonalRecordList->PersonalRecord as $record){
            $recordKeys   .= '|' . $record->type;
            $recordValues .= '|' . $record->value;
        }
        $keysArray    = explode('|', $recordKeys);
        $valuesArray  = explode('|', $recordValues);
        $recordsArray = array_combine($keysArray, $valuesArray);
        if($recordsArray['k'] == 'v'){
            unset($recordsArray['k']);
        }
        return $recordsArray;
    }

    /**
     * Retrieves a full run list with corresponding details
     *
     * @return array $runsArray Array of all runs and their basic data
     */
    public function getRunList(){
        $data = $this->getSecrets($this->runList);
        $data = new SimpleXMLElement($data, null, false);
        foreach($data->runList->run as $run){
            $runsArray[] = array(
            'runId'         => (int)    $run['id'],
            'startTime'     => (string) $run->startTime,
            'distance'      => (float)  $run->distance,
            'duration'      => (int)    $run->duration,
            'syncTime'      => (string) $run->syncTime,
            'calories'      => (float)  $run->calories,
            'name'          => (string) $run->name,
            'description'   => (string) $run->description,
            'howFelt'       => (int)    $run->howFelt,
            'weather'       => (int)    $run->weather,
            'terrain'       => (int)    $run->terrain,
            'intensity'     => (int)    $run->intensity,
            'gpxId'         => (string) $run->gpxId,
            'hasGpsData'    => (string) $run->hasGpsData,
            'equipmentType' => (string) $run->equipmentType
            );
        }
        return $runsArray;
    }

    /**
     * Retreives a summary of all runs
     *
     * @return array $runListSummaryArray Array of sum of all runs
     */
    public function getRunListSummary(){
        $data = $this->getSecrets($this->runList);
        $data = new SimpleXMLElement($data, null, false);
        $runListSummaryArray = array(
            'runs'        => (int)   $data->runListSummary->runs,
            'distance'    => (float) $data->runListSummary->distance,
            'runDuration' => (int)   $data->runListSummary->runDuration,
            'calories'    => (float) $data->runListSummary->calories,
            'duration'    => (int)   $data->runListSummary->duration
        );
        return $runListSummaryArray;
    }

    /**
     * Retreives a list of Nike+ goals
     * This is currently not optimized. 
     * 
     * @return SimpleXMLElement Object $data Object of the Nike+ goals feed 
     */
    public function getGoalList(){
        $data = $this->getSecrets($this->goalList);
        $data = new SimpleXMLElement($data, null, false);
        return $data;
    }

    /**
     * Retreives the all GPS data associated with a specific run
     * 
     * @param  int   $runId    Nike+ run id
     * @return array $gpsArray GPS data associated with passed Run ID
     * @throws Exception when $runId is null
     */
    public function getFullGps($runId){
        if(null === $runId){
            throw new Exception('$runId is a required parameter ', 1);
        }
        $data  = $this->getSecrets($this->fullGps, $runId);
        $data  = json_decode($data);
        $gpxId = $data->{'plusService'}->{'route'}->{'id'};
        if(is_array ($data->{'plusService'}->{'route'}->{'waypointList'})){
            foreach ($data->{'plusService'}->{'route'}->{'waypointList'} as $gps){
                $gpsArray[] = array(
                    'runId' => (int) $runId,
                    'lat'   => $gps->{'lat'},
                    'time'  => $gps->{'time'},
                    'lon'   => $gps->{'lon'},
                    'alt'   => $gps->{'alt'},
                    'gpxId' => $gpxId
                );
            }
        }
        return $gpsArray;
    }
    
    /**
     * Retreives the kilometer splits associated with a specific run
     * 
     * @param  int   $runId        Nike+ run id
     * @return array $kmSplitArray Data associated with each km split
     * @throws Exception when $runId is null
     */
    public function getKmSplits($runId){
        if(null === $runId){
            throw new Exception('$runId is a required parameter for getKmSplits', 1);
        }
        $data   = $this->getSecrets($this->fullGps, $runId);
        $data   = json_decode($data);
        $splits = $data->{'plusService'}->{'sportsData'}->{'snapShotList'};
        if(is_array($splits[0]->kmSplit)){
            foreach($splits[0]->kmSplit as $kmSplits){
                $kmSplitArray[] = array(
                    'distance' => $kmSplits->distance,
                    'pace'     => $kmSplits->pace,
                    'event'    => $kmSplits->event,
                    'duration' => $kmSplits->duration,
                    'id'       => $kmSplits->id,
                    'runId'    => (int) $runId
                );
            }
        }
        return $kmSplitArray;
    }

    /**
     * Retreives the mile splits associated with a specific run
     * 
     * @param  int   $runId          Nike+ run id
     * @return array $mileSplitArray Data associated with each mile split
     * @throws Exception when $runId is null
     */
    public function getMileSplits($runId){
        if(null === $runId){
            throw new Exception('$runId is a required parameter for getMileSplits ', 1);
        }
        $data   = $this->getSecrets($this->fullGps, $runId);
        $data   = json_decode($data);
        $splits = $data->{'plusService'}->{'sportsData'}->{'snapShotList'};
        if(is_array($splits[1]->mileSplit)){
            foreach($splits[1]->mileSplit as $mileSplits){
                $mileSplitArray[] = array(
                    'distance' => $mileSplits->distance,
                    'pace'     => $mileSplits->pace,
                    'event'    => $mileSplits->event,
                    'duration' => $mileSplts->duration,
                    'id'       => $mileSplits->id,
                    'runId'    => (int) $runId
                );
            }
        }
        return $mileSplitArray;
    }

    /**
     * Retreives the extended data associated with a specific run
     * Currently the data is comma delimited 
     * 
     * @param  int   $runId        Nike+ run id
     * @return array $extDataArray Various extended data associated with a run id
     * @throws Exception when $runId is null
     */
    public function getExtendedData($runId){
        if(null === $runId){
            throw new Exception('$runId is a required parameter ', 1);
        }
        $data         = $this->getSecrets($this->fullGps, $runId);
        $data         = json_decode($data);
        $extendedData = $data->{'plusService'}->{'sportsData'}->{'extendedDataList'};
        if(is_array($extendedData->extendedData)){
            foreach($extendedData->extendedData as $extData){
                $extDataArray[] = array(
                    'run_id'        => $runId,
                    'dataType'      => $extData->{'dataType'},
                    'data'          => $extData->{'data'},
                    'intervalType'  => $extData->{'intervalType'},
                    'intervalValue' => $extData->{'intervalValue'},
                    'intervalUnit'  => $extData->{'intervalUnit'},
                );
            }
        }
        return $extDataArray;
    }

    /**
     * Retrieve detailed information regarding a specified run, however it does not contain GPS data. 
     * This is not currently utilized as the data is contained in more detail by accessing the GPS detail feed.
     * 
     * @param  int                     $runId Nike+ run id
     * @return SimpleXMLElement Object $data  Object of the Nike+ goals feed 
     * @throws Exception when $runId is null
     */
    public function getFullRun($runId){
        if(null === $runId){
            throw new Exception('$runId is a required parameter ', 1);
        }
        $data = $this->getSecrets($this->fullRun, $runId);
        $data = new SimpleXMLElement($data, null, false);
        return $data;
    }

    /**
     * Retrieves the most recent run id
     * 
     * @return int $runId The run id of the most recent run
     */
    public function getRecentRunId(){
        $data  = $this->getSecrets($this->userData);
        $data  = new SimpleXMLElement($data, null, false);
        $runId = $data->mostRecentRun['id'];
        return $runId;
    }
}
