<?php  // $Id: view.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
/**
 * This page prints a particular instance of electalive
 * 
 * @author Mark Nielsen, others, Chris Egle
 * @version $Id: 
 * @package electalive
 **/

    require_once("../../config.php");
    require_once("lib.php");

    global $DB;

    $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $a  = optional_param('a', 0, PARAM_INT);  // electalive ID

    if ($id) {
        if (! $cm = get_coursemodule_from_id('electalive', $id)) {
            print_error('invalidcoursemodule');
        }

        if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
            print_error('coursemisconf');
        }
    
        if (! $electalive = $DB->get_record("electalive", array("id" => $cm->instance))) {
            print_error('invalidcoursemodule');
        }

    } else {
        if (! $electalive = $DB->get_record("electalive", array("id"=> $a))) {
            print_error('invalidcoursemodule');
        }
        if (! $course = $DB->get_record("course", array("id" => $electalive->course))) {
            print_error('coursemisconf');
        }
        if (! $cm = get_coursemodule_from_instance("electalive", $electalive->id, $course->id)) {
            print_error('invalidcoursemodule');
        }
    }
		// Check login and get context.
		require_login($course, false, $cm);
		$context = context_module::instance($cm->id);
		require_capability('mod/electalive:view', $context);

		// Log this request.
		$params = array(
			'objectid' => $electalive->id,
			'context' => $context
		);
		$event = \mod_electalive\event\course_module_viewed::create($params);
		$event->trigger();
		
// Initialize $PAGE, print headers

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    $strelectalives = get_string("modulenameplural", "electalive");
    $strelectalive  = get_string("modulename", "electalive");
		
		
		$PAGE->set_url('/mod/electalive/view.php', array('id' => $cm->id));
		$PAGE->set_context($context);
		$pagetitle = $course->shortname.': '.$electalive->name;
		$PAGE->set_title($pagetitle);
		$PAGE->set_heading($course->fullname);
		$PAGE->set_cacheable(true);
		$PAGE->set_button(update_module_button($cm->id, $course->id, $strelectalive));

		echo $OUTPUT->header();
        echo $OUTPUT->heading(format_text($electalive->name));
		
// Print the main part of the page
    if (!empty($electalive->intro)) {
        echo $OUTPUT->box(format_module_intro('electalive', $electalive, $cm->id), 'generalbox', 'intro');
    }
    $t = time();
    //get earlyopen, moderatorearlyopen from instance
    $earlyopen = $electalive->earlyopen*60; //time in seconds to open the classroom before the start time
    $moderatorearlyopen = $electalive->moderatorearlyopen*60; // time in seconds to open the classroom before the start time for instructors and teachers
    $text = get_string('meetingon', 'electalive');
    // set open time for those with moderator privileges
    if (electalive_getAccountType($cm->id) > 500){
        $moderatormeetingopen = $electalive->meetingtime - $moderatorearlyopen;
    }
    // set open time for everyone else
    $meetingopen = $electalive->meetingtime - $earlyopen;
    $meetingtimeend = $electalive->meetingtimeend;
    $randomtime = rand(1,75); // distribute the load for the server
    $refreshtime = $meetingtimeend - $t + $randomtime;
    $maxrefresh = 15120; // 4.2 hours - greater than the maximum session time for Moodle - so the page refresh doesn't keep someone logged in inadvertantly
    $button = electalive_buildURLString($electalive->roomid, $cm->id);
    $moderatoropennotice='';
    if (isset($moderatormeetingopen) && $meetingopen > $t) {
        if ($moderatormeetingopen > $t) {
            $text = get_string('meetingnotstarted', 'electalive');
            $button = '';
            $moderatoropennotice = get_string('moderatoropennotice', 'electalive',userdate($moderatormeetingopen));
            // no random timing for instructors
			$refreshtime = $moderatormeetingopen - $t;
        } else {
            //$regularopening = userdate($meetingopen);
            $text = get_string('moderatoronlystarted', 'electalive',userdate($meetingopen));
        }
    } else {
        if ($meetingopen > $t) {
            $text = get_string('meetingnotstarted', 'electalive');
            $button = '';
			$refreshtime = $meetingopen - $t + $randomtime;
        }
    }
    // limit refresh time to maxrefresh
    if ($refreshtime > $maxrefresh) {
        $refreshtime = $maxrefresh; 
    }
    // set refreshtime to 0 if the session is already over
    if ($t > $meetingtimeend) {
      $text = get_string('meetingover', 'electalive');
      $button = '';
			//$refreshtime = $maxrefresh + $randomtime;
            $refreshtime = 0;
    }
    // convert to microseconds
    $refreshtime = $refreshtime*1000;		
?>

<table class="table table-striped table-bordered">
    <tbody>
        <tr>
            <td><?php print_string('meetingopens', 'electalive'); ?></td>
            <td><b><?php echo userdate($meetingopen); ?></b><?php echo $moderatoropennotice; ?></td>
        </tr>
        <tr>
            <td><?php print_string('meetingbegins', 'electalive'); ?></td>
            <td><b><?php echo userdate($electalive->meetingtime); ?></b></td>
        </tr>
        <tr>
            <td><?php print_string('meetingends', 'electalive');?></td>
            <td><b><?php echo userdate($electalive->meetingtimeend); ?></b></td>
        </tr>
    </tbody>
</table>

<?php

    echo '<div style="padding:30px 0">'.$text.'</div>';
    echo $button;
		update_module_button($cm->id, $course->id, $strelectalive);
    // skip setting the page to reload if the refreshtime is 0
    if ($refreshtime > 0){
        echo '<script type="text/javascript">
                var electalive_t = setTimeout(function(){window.location.reload()},'.$refreshtime.' )
            </script>';
    }
		echo $OUTPUT->footer();
?>
