<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_electalive_mod_form extends moodleform_mod {

    public function mod_electalive_mod_form($current, $section, $cm, $course) {
        $this->course = $course;
        parent::moodleform_mod($current, $section, $cm, $course);
				if (!empty($current->meetingtimeend)) {
					$current->duration = ($current->meetingtimeend - $current->meetingtime) / 60;
					} 
//echo $current->duration;
    }

    function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;

        $strrequired = get_string('required');

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('sessionname', 'electalive'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('textarea', 'sessiondescription', get_string('sessiondesc', 'electalive'), 'wrap="virtual" rows="5" cols="64"');

        $mform->addElement('date_time_selector', 'meetingtime', get_string('meetingbegins', 'electalive'));
        $mform->setDefault('meetingtime', 0);

        $mform->addElement('text', 'roomid', get_string("roomid", "electalive"), "");
        $mform->addRule('roomid', null, 'required', null, 'client');
		$mform->addHelpButton('roomid', 'roomid', 'electalive');		
        $durations = array(
                   "10" => "10 minutes",
                   "15" => "15 Minutes",
                   "20" => "20 Minutes",
                   "25" => "25 Minutes",
                   "30" => "30 Minutes",
                   "35" => "35 Minutes",
                   "40" => "40 Minutes",
                   "45" => "45 Minutes",
                   "50" => "50 Minutes",
                   "55" => "55 Minutes",
                   "60" => "1 Hour",
                   "90" => "1.5 Hours",
                   "120" => "2 Hours",
									 "150" => "2.5 Hours",
                   "180" => "3 Hours",
                   "360" => "6 Hours",
                   "600" => "10 Hours",
                   "720" => "12 Hours",
                   "1440" => "24 Hours"
                   );

        $mform->addElement('select', 'duration', get_string("meetingduration", "electalive"), $durations);
        $mform->setDefault('maxanswers', 0);


        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }


}

