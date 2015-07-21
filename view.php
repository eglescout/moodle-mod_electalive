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

?>
<table class="table table-striped table-bordered">
<tr>
    <td><?php print_string('meetingbegins', 'electalive'); ?></td>
    <td><b><?php echo userdate($electalive->meetingtime); ?></b></td>
</tr>
<tr>
    <td><?php print_string('meetingends', 'electalive');?></td>
    <td><b><?php echo userdate($electalive->meetingtimeend); ?></b></td>
</tr>
<?php if (!empty($electalive->sessiondescription)) { ?>
<tr>
    <td valign=top><?php print_string('description');?></td>
    <td><?php echo $electalive->sessiondescription; ?></td>
</tr>
<?php } ?>

</table>

<?php
   // echo '<div style="margin-top:10px; margin-bottom:5px; border-top:1px #C0C0C0 solid; font-size:14px"><b>'.'</b></div>';
    $t = time();
    $earlyopen = 15*60; //time in seconds to open the classroom before the start time
    $moderatorearlyopen = 30*60; // time in seconds to open the classroom before the start time for instructors and teachers
    $text = get_string('meetingon', 'electalive');
    if (electalive_getAccountType($cm->id) > 500){
        $modmeetingtime = $electalive->meetingtime - $moderatorearlyopen;
    }
    $meetingtime = $electalive->meetingtime - $earlyopen;
    $meetingtimeend = $electalive->meetingtimeend;
    $randomtime = rand(1,75); // distribute the load for the server
    $refreshtime = $meetingtimeend - $t + $randomtime;
    $maxrefresh = 15120; // 4.2 hours > than maximum session time for Moodle
  
    $button = electalive_buildURLString($electalive->roomid, $cm->id);
    if (isset($modmeetingtime) && $meetingtime > $t) {
        if ($modmeetingtime > $t) {
            $text = get_string('meetingnotstarted', 'electalive');
            $button = '';
            // no random timing for instructors
			$refreshtime = $modmeetingtime - $t;
        } else {
            //$regularopening = userdate($meetingtime);
            $text = get_string('moderatoronlystarted', 'electalive',userdate($meetingtime));
        }
    } else {
        if ($meetingtime > $t) {
            $text = get_string('meetingnotstarted', 'electalive');
            $button = '';
			$refreshtime = $meetingtime - $t + $randomtime;
        }
    }
    // limit refresh time to maxrefresh
    if ($refreshtime > $maxrefresh) {
        $refreshtime = $maxrefresh; 
    }

    if ($t > $meetingtimeend) {
      $text = get_string('meetingover', 'electalive');
      $button = '';
			//$refreshtime = $maxrefresh + $randomtime;
            $refreshtime = 0;
    }
    // convert to microseconds
    $refreshtime = $refreshtime*1000;		
    echo '<div style="padding:30px 0">'.$text.'</div>';
    echo $button;
		update_module_button($cm->id, $course->id, $strelectalive);
    if ($refreshtime > 0){
        echo '<script type="text/javascript">
                var electalive_t = setTimeout(function(){window.location.reload()},'.$refreshtime.' )
            </script>';
    }
    /*
?>
	<script type="text/javascript">
			var electalive_t = setTimeout(function(){window.location.reload()}, <?php echo $refreshtime; ?> )
    </script>
<?php
 */
		echo $OUTPUT->footer();
?>
