<?php  // $Id: lib.php,v 2.1 2011/05/12 George Sandev $
/**
 * Library of functions and constants for module electalive
 *
 * @author 
 * @version $Id: lib.php,v 2.1 2011/05/12 George Sandev $
 * @package electalive
 **/


$electalive_CONSTANT = 7;     /// for example

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted electalive record
 **/
function eLectaLive_add_instance($electalive, $mform) {
    global $CFG;
    global $USER;
    global $DB;
    $electalive->timemodified = time();
    $electalive->timezone = get_user_timezone($USER->timezone);

//    $start = make_timestamp($electalive->syear, $electalive->smonth,
//           $electalive->sday, $electalive->shour, $electalive->sminute);
    $start = $electalive->meetingtime;
    $end = $start + ($electalive->duration * 60);

    $electalive->meetingtimeend = $end;

    if (($electalive->roomid == '') or (!isset($electalive->roomid)) or ($electalive->roomid == 0)) {
        redirect($CFG->wwwroot . '/course/mod.php?id=' . $electalive->course . '&amp;section=' .
                $electalive->section . '&amp;sesskey=' . $USER->sesskey . '&amp;add=electalive',
                get_string('missingroomno', 'electalive'), 5);

    }

    if ($start > $end) {
        $a = new stdClass;
        $a->timestart = userdate($start);
        $a->timeend   = userdate($end);

        redirect($CFG->wwwroot . '/course/mod.php?id=' . $electalive->course . '&amp;section=' .
                               $electalive->section . '&amp;sesskey=' . $USER->sesskey . '&amp;add=electalive',
                               get_string('invalidmeetingtimes', 'electalive', $a), 5);
    }


    if ($returnid = $DB->insert_record("electalive", $electalive)) {
        
				$event = new stdClass;
        $event->name        = $electalive->name;
        $event->description = $electalive->sessiondescription;
        $event->courseid    = $electalive->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'electalive';
        $event->instance    = $returnid;
        $event->eventtype   = 'electalive';
        $event->timestart   = $electalive->meetingtime;
        $event->timeduration = $electalive->duration * 60;
				$event->visible			= instance_is_visible('electalive', $electalive);

				calendar_event::create($event);
				
    }

    return $returnid;

}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function electalive_update_instance($electalive) {
    global $USER;
    global $CFG;
    global $DB;
		

    $electalive->timemodified = time();
    $electalive->id = $electalive->instance;
    $electalive->timezone = get_user_timezone($USER->timezone);

//    $start = make_timestamp($electalive->syear, $electalive->smonth,
//           $electalive->sday, $electalive->shour, $electalive->sminute);

    $start = $electalive->meetingtime;
    $end = $start + ($electalive->duration * 60);

//    echo time()."<BR>";
//    echo $start;
//die();
	
    $electalive->meetingtimeend = $end;

    if ($start > $end) {


        /// Get the course module ID for this instance.
        $sql = "SELECT cm.id
                    FROM {$CFG->prefix}modules m,
                    {$CFG->prefix}course_modules cm
                    WHERE m.name = 'electalive'
                    AND cm.module = m.id
                    AND cm.instance = '{$electalive->id}'";

                    if (!$cmid = $DB->get_field_sql($sql)) {
                        redirect($CFG->wwwroot . '/mod/electalive/view.php?id=' . $electalive->id,
                         'The meeting start time of ' . userdate($start) . ' is after the meeting end' .
                         'time of ' . userdate($end), 5);
                    }

                    redirect($CFG->wwwroot . '/course/mod.php?update=' . $cmid . '&amp;return=true&amp;' .
                     'sesskey=' . $USER->sesskey,
                     'The meeting start time of ' . userdate($start) . ' is after the meeting end' .
                     'time of ' . userdate($end), 5);
    }

    if (($electalive->roomid == '') or (!isset($electalive->roomid)) or ($electalive->roomid == 0)) {
        $sql = "SELECT cm.id
                    FROM {$CFG->prefix}modules m,
                    {$CFG->prefix}course_modules cm
                    WHERE m.name = 'electalive'
                    AND cm.module = m.id
                    AND cm.instance = '{$electalive->id}'";



                    if (!$cmid = $DB->get_field_sql($sql)) {
                        redirect($CFG->wwwroot . '/mod/electalive/view.php?id=' . $electalive->id,
                         'The meeting start time of ' . userdate($start) . ' is after the meeting end' .
                         'time of ' . userdate($end), 5);
                    }

                    redirect($CFG->wwwroot . '/course/mod.php?update=' . $cmid . '&amp;return=true&amp;' .
                     'sesskey=' . $USER->sesskey,
                     get_string('missingroomno', 'electalive'), 5);
    
    }

    if ($returnid = $DB->update_record("electalive", $electalive)) {
				$data = new stdClass;
        if ($data->id = $DB->get_field('event', 'id', array('modulename'=>'electalive', 'instance'=>$electalive->id))) {
						$data->name        = $electalive->name;
            $data->description = $electalive->sessiondescription;
            $data->timestart   = $electalive->meetingtime;
            $data->timeduration = $electalive->duration * 60;
						$data->visible			= instance_is_visible('electalive', $electalive);
						$eventid = $data->id;
						$event = calendar_event::load($eventid);
						$event->update($data);
        }
    }

    return $returnid;


}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function electalive_delete_instance($id) {
    global $DB;

    if (! $electalive = $DB->get_record("electalive", array('id' => $id))) {
        return false;
    }

    $DB->delete_records('electalive', array('id' => $electalive->id));

    return true;
}

function electalive_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return false;
        case FEATURE_MOD_INTRO:               return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return false;

        default: return null;
    }
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function electalive_user_outline($course, $user, $mod, $electalive) {
    return $return;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function electalive_user_complete($course, $user, $mod, $electalive) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in electalive activities and print it out.
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function electalive_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function electalive_cron () {
    global $CFG;

    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $electaliveid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function electalive_grades($electaliveid) {
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of electalive. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $electaliveid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function electalive_get_participants($electaliveid) {
    return false;
}

/**
 * This function returns if a scale is being used by one electalive
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $electaliveid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function electalive_scale_used ($electaliveid,$scaleid) {
    $return = false;

    //$rec = get_record("electalive","id","$electaliveid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other electalive functions go here.  Each of them must have a name that
/// starts with electalive_
function electaLive_curPageURL() {
 $pageURL = 'http';

 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}

function electaLive_HTTPGET($Url){
 
    // is cURL installed yet?
    if (!function_exists('curl_init')){
        die('cURL is not installed!');
    }
 
    // OK cool - then let's create a new cURL resource handle
    $ch = curl_init();
 
    // Now set some options (most are optional)
 
    // Set URL to download
    curl_setopt($ch, CURLOPT_URL, $Url);
 
    // Set a referer
    curl_setopt($ch, CURLOPT_REFERER, electaLive_curPageURL());
 
    // User agent
    curl_setopt($ch, CURLOPT_USERAGENT, "eLectaLiveMoodleAM/2.1");
 
    // Include header in result? (0 = yes, 1 = no)
    curl_setopt($ch, CURLOPT_HEADER, 0);
 
    // Should cURL return or print out the data? (true = return, false = print)
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
    // Timeout in seconds
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
 
    // Download the given URL, and return output
    $output = curl_exec($ch);
 
    // Close the cURL resource, and free system resources
    curl_close($ch);
 
    return $output;
}

function eLectalive_getSessionToken() {
        $roomURL = get_config('electalive', 'subdomain');
        $appKey = get_config('electalive', 'scode');
        $lcCID = get_config('electalive', 'accountid');
        $AppRoot = 'http://'.$roomURL.'/apps/token.asp?cid=' . $lcCID . '&appkey=' . $appKey . '&result=csv';
        $Res = electaLive_HTTPGET($AppRoot);
        $resultSet = explode(",", $Res);
        $resss = '';
        if ( $resultSet[0] == '0') {
             $resss = $resultSet[1];
        }
        return $resss;
}

function electalive_buildURLString($ARoomID, $cmid) {
        global $CFG;
        global $USER;
        $roomURL = get_config('electalive', 'subdomain');
        $token = electalive_getSessionToken();
        $lcCID = get_config('electalive', 'accountid');

        $lcUTID = electalive_getAccountType($cmid);

        $lcAction = 'http://'.$roomURL.'/apps/launch.asp';
        $theRoomLink =
                    '<form method="post" action="'.$lcAction.'" target="_blank" style="margin:0px;padding:0px">'
                     . '<input type=hidden name="cid" value="'.$lcCID.'">'
                     . '<input type=hidden name="roomid" value="'.$ARoomID.'">'
                     . '<input type=hidden name="externalname" value="'.$USER->username.'">'
                     . '<input type=hidden name="firstname" value="'.$USER->firstname.'">'
                     . '<input type=hidden name="lastname" value="'.$USER->lastname.'">'
                     . '<input type=hidden name="lastname" value="">'
                     . '<input type=hidden name="usertypeid" value="'.$lcUTID.'">'
                     . '<input type=hidden name="token" value="'.$token.'">'
                     . '<input type=submit value="' . get_string('enterelectalive','electalive') . '">'
                     . '</form>';
        return $theRoomLink;
}

function electalive_getAccountType($cmid) {
    global $COURSE, $USER;

    $context = context_module::instance($cmid);
    if (has_capability('mod/electalive:attendteacher', $context)) {
       $AccountType = 1000;
    } else {
       $AccountType = 0;
    }
    return $AccountType;
}
function electalive_getChangeRoom($cmid) {
    global $COURSE, $USER;
		
		if (IS_NULL($cmid)) {
			$ChangeRoom = 1000;
		} else {	
			//use only for the editing case: add instance OR edit live room permissions will allow the user to change the room
			$context = context_module::instance($cmid);
			if ((has_capability('mod/electalive:addinstance', $context)) || (has_capability('mod/electalive:editliveroom', $context)))
			{
				$ChangeRoom = 1000;
			} else {
			 $ChangeRoom = 0;
			}
		}
    return $ChangeRoom;
}

?>