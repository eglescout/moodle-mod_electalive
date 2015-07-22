<?php
// This file is part of Moodle - http://moodle.org/
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

// This file keeps track of upgrades to
// the feedback module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

function xmldb_electalive_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();


    // Moodle v2.7.0 release upgrade line
    // Put any upgrade step following this
        if ($oldversion < 2015072200) {

        // Rename field sessiondescription on table electalive to intro.
        $table = new xmldb_table('electalive');
        $field = new xmldb_field('sessiondescription', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');

        // Launch rename field intro.
        $dbman->rename_field($table, $field, 'intro');

        // Define field earlyopen to be added to electalive.
        //$table = new xmldb_table('electalive');
        $field = new xmldb_field('earlyopen', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0', 'meetingtimeend');

        // Conditionally launch add field earlyopen.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Define field moderatorearlyopen to be added to electalive.
        //$table = new xmldb_table('electalive');
        $field = new xmldb_field('moderatorearlyopen', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0', 'earlyopen');

        // Conditionally launch add field moderatorearlyopen.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Electalive savepoint reached.
        upgrade_mod_savepoint(true, 2015072200, 'electalive');
    }
      
    return true;
}


