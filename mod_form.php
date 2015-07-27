<?php

// This file is part of the Electalive module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
* Instance add/edit form
*
* @package    mod_electalive
* @copyright  Chris Egle <chris@bowenlearn.com>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/electalive/lib.php');

class mod_electalive_mod_form extends moodleform_mod {

    public function mod_electalive_mod_form($current, $section, $cm, $course) {
        $this->course = $course;
				$this->cm = $cm;
        parent::moodleform_mod($current, $section, $cm, $course);
				if (!empty($current->meetingtimeend)) {
					$current->duration = ($current->meetingtimeend - $current->meetingtime) / 60;
					} 
        //echo $current->duration;
    }

    protected function definition() {
        global $CFG, $DB;
        $electaliveconfig = get_config('electalive');
        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('sessionname', 'electalive'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        //$mform->addElement('textarea', 'intro', get_string('intro', 'electalive'), "wrap='virtual' rows='5' cols='64'");
        // Introduction 
        $this->add_intro_editor(false, get_string('intro', 'electalive'));
        
        // Check if user has room ID editing privileges - readonly if no
        $room_attributes='';
        if(!empty($this->cm->id)) {
            if(electalive_getChangeRoom($this->cm->id) == 0) {
                $room_attributes='readonly=""';
            }
        }
        $mform->addElement('text', 'roomid', get_string('roomid', 'electalive'),$room_attributes );
        $mform->addRule('roomid', null, 'required', null, 'client');
        $mform->addHelpButton('roomid', 'roomid', 'electalive');
        $mform->setType('roomid',PARAM_INT);
				
        $mform->addElement('date_time_selector', 'meetingtime', get_string('meetingbegins', 'electalive'));
        $mform->setDefault('meetingtime', 0);

	
        $durations = array(
            '0' => '0 minutes',
            '5' => '5 minutes',
            '10' => '10 minutes',
            '15' => '15 Minutes',
            '20' => '20 Minutes',
            '25' => '25 Minutes',
            '30' => '30 Minutes',
            '35' => '35 Minutes',
            '40' => '40 Minutes',
            '45' => '45 Minutes',
            '50' => '50 Minutes',
            '55' => '55 Minutes',
            '60' => '1 Hour',
            '90' => '1.5 Hours',
            '120' => '2 Hours',
            '150' => '2.5 Hours',
            '180' => '3 Hours',
            '240' => '4 Hours',
            '360' => '6 Hours',
            '600' => '10 Hours',
            '720' => '12 Hours',
            '1440' => '24 Hours'
        );

        $mform->addElement('select', 'duration', get_string('meetingduration', 'electalive'), $durations);
        $mform->setDefault('meetingduration', '60');
        
        $mform->addElement('select', 'earlyopen', get_string('earlyopen', 'electalive'), $durations);
        $mform->setDefault('earlyopen', $electaliveconfig->defaultearlyopen);
        $mform->addHelpButton('earlyopen', 'earlyopen', 'electalive');

        $mform->addElement('select', 'moderatorearlyopen', get_string('moderatorearlyopen', 'electalive'), $durations);
        $mform->setDefault('moderatorearlyopen', $electaliveconfig->defaultmoderatorearlyopen);
        $mform->addHelpButton('moderatorearlyopen', 'moderatorearlyopen', 'electalive');

        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }
   /**
     * Some basic validation
     *
     * @param $data
     * @param $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Make sure that duration > 0
        if ($data['duration'] < 1 ) {
            $errors['duration'] = get_string('durationtooshort', 'electalive');
        }
        // Check that the moderatorearlyopen is equal or greater than earlyopen time
        if ($data['moderatorearlyopen'] < $data['earlyopen']) {
            $errors['moderatorearlyopen'] = get_string('lessthanearlyopen', 'electalive');
        }
        
        return $errors;
    }

}

