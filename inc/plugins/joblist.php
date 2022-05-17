<?php
// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// HOOKS
// Jobliste
$plugins->add_hook("misc_start", "joblist_misc");
// Teammeldung
$plugins->add_hook("global_start", "joblist_global");
// Modcp
$plugins->add_hook("modcp_nav", "joblist_modcp_nav");
$plugins->add_hook("modcp_start", "joblist_modcp");
// Profil
$plugins->add_hook("member_profile_end", "joblist_memberprofile");
// Postbit

// Online Aktivität
$plugins->add_hook("fetch_wol_activity_end", "joblist_online_activity");
$plugins->add_hook("build_friendly_wol_location_end", "joblist_online_location");
// Einstellungsgedönse
$plugins->add_hook('admin_config_settings_change', 'joblist_settings_change');
$plugins->add_hook('admin_settings_print_peekers', 'joblist_settings_peek');

// MyAlerts
// if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
	//$plugins->add_hook("global_start", "joblist_myalert_alerts");
//}
 
// Die Informationen, die im Pluginmanager angezeigt werden
function joblist_info(){
	return array(
		"name"		=> "Interaktive Jobliste",
		"description"	=> "Pluginbeschreibung",
		"website"	=> "https://github.com/little-evil-genius/Jobliste",
		"author"	=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"	=> "1.0",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function joblist_install(){
	global $db, $cache, $mybb;

    // Datenbank-Tabelle erstellen

	// ARBEITSPLÄTZE - HIER WERDEN DIE INFOS ZU DEN ARBEITSPLÄTZE GESPEICHERT
	$db->query("CREATE TABLE ".TABLE_PREFIX."workplaces(
        `jid` int(10) NOT NULL AUTO_INCREMENT,
		`type` VARCHAR(500) NOT NULL,
		`name` VARCHAR(1000) COLLATE utf8_general_ci NOT NULL,
		`shortfact` VARCHAR(1000) COLLATE utf8_general_ci NOT NULL,
        `city` VARCHAR(5000) COLLATE utf8_general_ci NOT NULL,
        `description` VARCHAR(5000) COLLATE utf8_general_ci NOT NULL,
		`owner` VARCHAR(1000) COLLATE utf8_general_ci NOT NULL,
        `accepted` int(1) NOT NULL,
        `createdby` int(10) NOT NULL,
        PRIMARY KEY(`jid`),
        KEY `jid` (`jid`)
        )
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1"
	);
        
	// MITGLIEDER DER CLUBS - HIER WERDEN DIE USER DER CLUBS GESPEICHERT
	$db->query("CREATE TABLE ".TABLE_PREFIX."jobs(
		`ujid` int(10) NOT NULL AUTO_INCREMENT,
		`jid` int(10) NOT NULL,
		`uid` int(10) NOT NULL,
		`position` VARCHAR(1000) COLLATE utf8_general_ci NOT NULL,
		`halftime` int(1) NOT NULL,
		PRIMARY KEY(`ujid`),
		KEY `ucid` (`ujid`)
		)
		ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1"
	);

    // EINSTELLUNGEN HINZUFÜGEN
    $setting_group = array(
        'name'          => 'joblist',
        'title'         => 'Jobliste',
        'description'   => 'Einstellungen für die Jobliste',
        'disporder'     => 1,
        'isdefault'     => 0
    );
        
     
    $gid = $db->insert_query("settinggroups", $setting_group);     
    
    
    $setting_array = array(
        'joblist_add_allow_groups' => array(
            'title' => 'Erlaubte Gruppen Hinzufügen',
            'description' => 'Welche Gruppen dürfen neue Arbeitsplätze erstellen?',
            'optionscode' => 'groupselect',
            'value' => '4', // Default
            'disporder' => 1
        ),

        'joblist_member_allow_groups' => array(
            'title' => 'Erlaubte Gruppen Beitreten',
            'description' => 'Welche Gruppen dürfen Berufe eintragen?',
            'optionscode' => 'groupselect',
            'value' => '4', // Default
            'disporder' => 2
        ),

        'joblist_type' => array(
            'title' => 'Arbeitsbranche',
            'description' => 'In welche Kategorien können die Arbeitsplätze eingeordnet werden?',
            'optionscode' => 'text',
            'value' => 'öfftl. Einrichtungen/Verwaltung, Bildung, Gesundheit, Landwirtschaft, Medien, Dienstleistung, Einzelhandel, Gastronomie, Unterhaltung & Tourismus, Sonstiges', // Default
            'disporder' => 3
        ),

        'joblist_city' => array(
            'title' => 'Einteilung Städte/Straßen',
            'description' => 'Können die Arbeitsplätze in Städte/Straßen eingeteilt werden?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 4
        ),
          
        'joblist_city_option' => array(
            'title' => 'Städte/Straßen Optionen',
            'description' => 'In welche Städte/Straßen können die Arbeitsplätze eingeordnet werden?',
            'optionscode' => 'text',
            'value' => 'Barton Fields, Bogachiel Way, Calawah Way, Camas Street, Clearwater Road, Cuitan Street, Diamond Lane, Fast Lane, Founders Way, Jubilee Street, Juneau Lane, Kirkpatrick Road, Little Britain, Little Ireland, Little Island, Mill Creek Road, Minnea Street, Old Chapel, Osprey Lane, Seaview Road, Shehan Trail, Steel View, Townhall Boulevard, Valley View Drive, Wishkah Road, Übergreifend', // Default
            'disporder' => 5
        ),

        'joblist_delete' => array(
            'title' => 'Löschfunktion',
            'description' => 'Dürfen User ihre selbsterstellen Arbeitsplätze löschen?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 6
        ),

        'joblist_edit' => array(
            'title' => 'Bearbeitungsfunktion',
            'description' => 'Dürfen User ihre selbsterstellen Arbeitsplätze bearbeiten? Das Team muss Bearbeitungen nicht erneut überprüfen.',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 7
        ),

        'joblist_limit' => array(
            'title' => 'Begrenzte Berufe',
            'description' => 'Dürfen User nur eine bestimmte Anzahl von Berufen nachgehen?',
            'optionscode' => 'yesno',
            'value' => '0', // Default
            'disporder' => 8
        ),

        'joblist_limit_number' => array(
            'title' => 'Anzahl der Berufe',
            'description' => 'Wie viele Berufe dürfen die User eintragen?',
            'optionscode' => 'text',
            'value' => '3', // Default
            'disporder' => 9
        ),

        'joblist_tabs' => array(
            'title' => 'Tabsystem',
            'description' => 'Soll die Jobliste in Tabs eingeteilt werden?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 10
        ),

        'joblist_defaulttab' => array(
            'title' => 'Default Tab',
            'description' => 'Welcher Tab soll standardmäßig offen sein, beim laden der Jobliste?',
            'optionscode' => 'text',
            'value' => 'öfftl. Einrichtungen/Verwaltung', // Default
            'disporder' => 11
        ),

        'joblist_filter' => array(
            'title' => 'Filterfunktion',
            'description' => 'Soll es auf der Joblisten-Seite eine Filterfunktion geben? Sollte die Jobliste in Tabs angezeigt werden, wird diese Funktion automatisch deaktiviert!',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 12
        ),

        'joblist_multipage' => array(
            'title' => 'Multipage-Navigation',
            'description' => 'Sollen die Arbeitsplätze ab einer bestimmten Anzahl auf der Seite auf mehrere Seiten aufgeteilt werden?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 13     
        ),

        'joblist_multipage_show' => array(
            'title' => 'Anzahl der Arbeitsplätze (Multipage-Navigation)',
            'description' => 'Wie viele Arbeitsplätze sollen auf einer Seite angezeigt werden?',
            'optionscode' => 'text',
            'value' => '10', // Default
            'disporder' => 14     
        ),

        'joblist_lists' => array(
            'title' => 'Listen PHP (Navigation Ergänzung)',
            'description' => 'Wie heißt die Hauptseite eurer Listen-Seite? Dies dient zur Ergänzung der Navigation. Falls nicht gewünscht einfach leer lassen.',
            'optionscode' => 'text',
            'value' => 'listen.php', // Default
            'disporder' => 15
        ),  
    );
    
    foreach($setting_array as $name => $setting)
    {
        $setting['name'] = $name;
        $setting['gid']  = $gid;
        $db->insert_query('settings', $setting);
    }
        
    rebuild_settings();

    // TEMPLATES HINZUFÜGEN
		
    // Template Gruppe für jedes Design erstellen
    $templategroup = array(
        "prefix" => "joblist",
        "title" => $db->escape_string("Jobliste"),
    );
    $db->insert_query("templategroups", $templategroup);

    



    // CSS HINZUFÜGEN
    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

    // STYLESHEET HINZUFÜGEN
    $css = array(
		'name' => 'joblist.css',
		'tid' => 1,
		'attachedto' => '',
		"stylesheet" =>	'.joblisttab {
          float: left;
          border: 1px solid #ccc;
          background-color: #f1f1f1;
        }
        
        .joblisttab button {
          display: block;
          background-color: inherit;
          color: black;
          padding: 22px 16px;
          width: 100%;
          border: none;
          outline: none;
          text-align: left;
          cursor: pointer;
          transition: 0.3s;
        }
        
        .joblisttab button:hover {
          background-color: #ddd;
        }
        
        .joblisttab button.active {
          background-color: #ccc;
        }
        
        .joblisttabcontent {
          float: left;
          padding: 0px 12px;
          width: 70%;
          border-left: none;
          height: 300px;
        }',
		'cachefile' => $db->escape_string(str_replace('/', '', 'joblist.css')),
		'lastmodified' => time()
	);
    
    $sid = $db->insert_query("themestylesheets", $css);
	$db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=".$sid), "sid = '".$sid."'", 1);

	$tids = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($tids)) {
		update_theme_stylesheet_list($theme['tid']);
	}

}
 
// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function joblist_is_installed() {
	global $db, $mybb;

    if ($db->table_exists("workplaces")) {
        return true;
    }
    return false;
} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function joblist_uninstall() {

	global $db;

    //DATENBANK LÖSCHEN
    if($db->table_exists("workplaces"))
    {
        $db->drop_table("workplaces");
    }

	if($db->table_exists("jobs"))
    {
        $db->drop_table("jobs");
    }
    
    // EINSTELLUNGEN LÖSCHEN
    $db->delete_query('settings', "name LIKE 'joblist%'");
    $db->delete_query('settinggroups', "name = 'joblist'");

    rebuild_settings();

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title LIKE 'joblist%'");

}
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function joblist_activate() {

	global $db, $cache;

    // MyALERTS STUFF
    if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

        // Alert beim Annehmen
		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('joblist_accepted'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);

        // Alert beim Ablehnen
        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('joblist_declined'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);
    }
    
    // VARIABLEN EINFÜGEN
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets('member_profile', '#'.preg_quote('{$awaybit}').'#', '{$awaybit} {$joblist_memberprofile}');
	find_replace_templatesets('header', '#'.preg_quote('{$bbclosedwarning}').'#', '{$new_joblist_alert} {$bbclosedwarning}');
	find_replace_templatesets('modcp_nav_users', '#'.preg_quote('{$nav_ipsearch}').'#', '{$nav_ipsearch} {$nav_joblist}');

}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function joblist_deactivate() {
	    
	global $db, $cache;

    // VARIABLEN ENTFERNEN
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("member_profile", "#".preg_quote('{$joblist_memberprofile}')."#i", '', 0);
    find_replace_templatesets("header", "#".preg_quote('{$new_joblist_alert}')."#i", '', 0);
    find_replace_templatesets("modcp_nav_users", "#".preg_quote('{$nav_joblist}')."#i", '', 0);

    // MyALERT STUFF
    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertTypeManager->deleteByCode('joblist_declined');
        $alertTypeManager->deleteByCode('joblist_accepted');
	}
}


##############################
### FUNKTIONEN - THE MAGIC ###
##############################

// ONLINE ANZEIGE - WER IST WO
function joblist_online_activity($user_activity) {

    global $parameters;

    $split_loc = explode(".php", $user_activity['location']);
    if($split_loc[0] == $user['location']) {
        $filename = '';
    } else {
        $filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
    }
    
    switch ($filename) {
        case 'misc':
        if($parameters['action'] == "joblist" && empty($parameters['site'])) {
            $user_activity['activity'] = "joblist";
        }
        if($parameters['action'] == "joblist_edit" && empty($parameters['site'])) {
            $user_activity['activity'] = "joblist_edit";
        }
        break;
    }
      

    return $user_activity;
}
function joblist_online_location($plugin_array) {

    global $mybb, $theme, $lang;

	if($plugin_array['user_activity']['activity'] == "joblist") {
		$plugin_array['location_name'] = "Sieht sich die <a href=\"misc.php?action=joblist\">Jobliste</a> an.";
	}
    if($plugin_array['user_activity']['activity'] == "joblist_edit") {
		$plugin_array['location_name'] = "Bearbeitet gerade einen Arbeitsplatz.";
	}

    return $plugin_array;
}

// ADMIN-CP PEEKER
function joblist_settings_change(){
    global $db, $mybb, $joblist_settings_peeker;

    $result = $db->simple_select('settinggroups', 'gid', "name='joblist'", array("limit" => 1));
    $group = $db->fetch_array($result);
    $joblist_settings_peeker = ($mybb->input['gid'] == $group['gid']) && ($mybb->request_method != 'post');
}
function joblist_settings_peek(&$peekers){
    global $mybb, $joblist_settings_peeker;

    // Städte/Liste
    if ($joblist_settings_peeker) {
       $peekers[] = 'new Peeker($(".setting_joblist_city"), $("#row_setting_joblist_city_option"),/1/,true)';
    }
    // Berufsbegrenzung
    if ($joblist_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_joblist_limit"), $("#row_setting_joblist_limit_number"),/1/,true)'; 
    }
    // Tabsystem
    if ($joblist_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_joblist_tabs"), $("#row_setting_joblist_defaulttab"),/1/,true)'; 
    }
    // Filter - Tabsystem = Nein
    if ($joblist_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_joblist_tabs"), $("#row_setting_joblist_filter"),/0/,true)'; 
    }
    // Multipage - Tabsystem = Nein
    if ($joblist_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_joblist_tabs"), $("#row_setting_joblist_multipage"),/0/,true)'; 
    }
    // Multipage Anzahl
    if ($joblist_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_joblist_multipage"), $("#row_setting_joblist_multipage_show"),/1/,true)'; 
    }


}

// TEAMHINWEIS ÜBER NEUE ARBEITSPLÄTZE
function joblist_global() {
    global $db, $cache, $mybb, $templates, $new_joblist_alert;

    // NEUE ARBEITSPLÄTZE
    $count_joblist = $db->fetch_field($db->query("SELECT COUNT(*) as new_workplaces FROM ".TABLE_PREFIX."workplaces    
    WHERE accepted = 0
    "), 'new_workplaces');
      
    if ($mybb->usergroup['canmodcp'] == "1" && $count_joblist == "1") {   
        $new_joblist_alert = "<div class=\"red_alert\"><a href=\"modcp.php?action=joblist\">Ein neuer Arbeitsplatz muss freigeschaltet werden</a></div>";
    } elseif ($mybb->usergroup['canmodcp'] == "1" && $count_joblist > "1") {
        $new_joblist_alert = "<div class=\"red_alert\"><a href=\"modcp.php?action=joblist\">{$count_joblist} neue Arbeitsplätze müssen freigeschaltet werden</a></div>";
    }

}

// DIE SEITEN
function joblist_misc() {
    global $db, $cache, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer, $joblist_tabs_js, $multipage, $joblist_add, $joblist_join, $joblist_options_bit, $filter_type, $filter_city, $city_filter, $joblist_bit;

    // SPRACHDATEI LADEN
    $lang->load('joblist');
    
    // USER-ID
    $user_id = $mybb->user['uid'];

    // ACTION-BAUM BAUEN
    $mybb->input['action'] = $mybb->get_input('action');

	// EINSTELLUNGEN HOLEN
    $joblist_add_allow_groups_setting = $mybb->settings['joblist_add_allow_groups'];
    $joblist_member_allow_groups_setting = $mybb->settings['joblist_member_allow_groups'];

    $joblist_type_setting = $mybb->settings['joblist_type'];

    $joblist_city_setting = $mybb->settings['joblist_city'];
    $joblist_city_option_setting = $mybb->settings['joblist_city_option'];
    
    $joblist_delete_setting = $mybb->settings['joblist_delete'];
    $joblist_edit_setting = $mybb->settings['joblist_edit']; 
    
    $joblist_limit_setting = $mybb->settings['joblist_limit']; 
    $joblist_limit_number_setting = $mybb->settings['joblist_limit_number']; 
    
    $joblist_tabs_setting = $mybb->settings['joblist_tabs']; 
    $joblist_defaulttab_setting = $mybb->settings['joblist_defaulttab'];
    
    $joblist_filter_setting = $mybb->settings['joblist_filter']; 
    $joblist_multipage_setting = $mybb->settings['joblist_multipage']; 
    $joblist_multipage_show_setting = $mybb->settings['joblist_multipage_show'];
    
    $joblist_lists_setting = $mybb->settings['joblist_lists']; 

	// AUSWAHLMÖGLICHKEIT DROPBOX GENERIEREN
	// Kategorien
    $type_select = ""; 
	$joblist_type = explode (", ", $joblist_type_setting);
	foreach ($joblist_type as $type) {
		$type_select .= "<option value='{$type}'>{$type}</option>";
	}
    // Städte/Straßen
    if ($joblist_city_setting == 1) {
        $city_select = "";
        $joblist_city = explode (", ", $joblist_city_option_setting);
        foreach ($joblist_city as $city) {
            $city_select .= "<option value='{$city}'>{$city}</option>";
        }
    }
    

	// JOBLISTE
    if($mybb->input['action'] == "joblist") {

		// NAVIGATION
		if(!empty($joblist_lists_setting)){
			add_breadcrumb("Listen", "$joblist_lists_setting");
			add_breadcrumb($lang->joblist, "misc.php?action=joblist");
		} else{
			add_breadcrumb($lang->joblist, "misc.php?action=joblist");
		}
        
        // Nur den Gruppen, den es erlaubt ist, neue Arbeitsplätze hinzuzufügen, ist es erlaubt, den Link zu sehen.
        if(is_member($joblist_add_allow_groups_setting)) {

            if ($joblist_city_setting == 1) {
                $colspan_add = "5";
                $width_add = "20%";
				$city_td = '<td class="tcat" width="{$width_add}" align="center">Straße/Stadt</td>';
                eval("\$add_city = \"".$templates->get("joblist_add_city")."\";");
            } else {
                $colspan_add = "4";
                $width_add = "25%";
                $city_td = "";
                $add_city = "";
            }

            eval("\$joblist_add = \"".$templates->get("joblist_add")."\";");
        }

        // JOB EINTRAGEN
        if(is_member($joblist_member_allow_groups_setting)) {

			// Zählen, wie oft man schon ein Beruf hat
			$count_user = $db->fetch_field($db->query("SELECT COUNT(*) as count_user FROM ".TABLE_PREFIX."jobs j    
            WHERE uid = '$user_id'
            "), 'count_user');

			$query = $db->query("SELECT * FROM ".TABLE_PREFIX."workplaces ORDER by name ASC");
			while($names = $db->fetch_array($query)) {
				$joblist_options_bit .= "<option value=\"{$names['jid']}\">{$names['name']}</option>";
			}

			// BEGRENZTE BERUFE
			if ($joblist_limit_setting == 1) {
				
                // Unter dem Limit - kann neuen Job eintragen
				if ($count_user < $joblist_limit_number_setting) {
                    eval("\$joblist_join = \"".$templates->get("joblist_join")."\";");
				}
				// Limit erreicht - kann keinen 
				elseif ($count_user == $joblist_limit_number_setting) {
					$joblist_join = "";
				}

			} else {
				eval("\$joblist_join = \"".$templates->get("joblist_join")."\";");
			}

        }

        // FILTER - OPTIONEN
        // Filter ist erlaubt & Tabs ist deaktiviert
        if ($joblist_filter_setting == 1 & $joblist_tabs_setting == 0) {

            // Es gibt auch Städte/Straßen
            if ($joblist_city_setting == 1) {

                // Filter aus den DB Einträgen generieren - Städte
                $city_query = $db->query("SELECT DISTINCT city FROM ".TABLE_PREFIX."workplaces"); 
                
                while($cfilter = $db->fetch_array($city_query)){
                    $filter_city .= "<option value='{$cfilter['city']}'>{$cfilter['city']}</option>";      
                }

                $city_filter = '<select name="city"><option value="%">Stadt/Straße auswählen</option>'.$filter_city.'</select>';
    
                $jcity = $mybb->input['city'];
                if(empty($jcity)) {
                    $jcity = "%";
                }
            

            } else {
                $city_filter = "";
                $filter_city = "";
            }

            // Filter aus den DB Einträgen generieren - Kategorien            
            $type_query = $db->query("SELECT DISTINCT type FROM ".TABLE_PREFIX."workplaces"); 

            while($type_filter = $db->fetch_array($type_query)){
                $filter_type .= "<option value='{$type_filter['type']}'>{$type_filter['type']}</option>";    
            }
    
            $jtype = $mybb->input['type'];
            if(empty($jtype)) {
                $jtype = "%";
            }

            eval("\$joblist_filter = \"".$templates->get("joblist_filter")."\";");
        } else {
            $joblist_filter = "";
        }

        // MULTIPAGE
        // Multipage aktiviert & Tabs deaktiviert
        if ($joblist_multipage_setting == 1 & $joblist_tabs_setting == 0) {

            // Filter aktiviert
            if ($joblist_filter_setting == 1) {

                if ($joblist_city_setting == 1) {
                    // Auszählen nach Filter
                    $workplaces_count = $db->fetch_field($db->query("SELECT COUNT(*) as workplaces FROM ".TABLE_PREFIX."workplaces
                    WHERE accepted = 1
                    AND type LIKE '$jtype'
                    AND city LIKE '$jcity'
                    "), 'workplaces');

                    $type_url = htmlspecialchars_uni("misc.php?action=joblist&type={$jtype}&city={$jcity}");

                } else {

                    // Auszählen nach Filter
                    $workplaces_count = $db->fetch_field($db->query("SELECT COUNT(*) as workplaces FROM ".TABLE_PREFIX."workplaces
                    WHERE accepted = 1
                    AND type LIKE '$jtype'
                    "), 'workplaces');

                    $type_url = htmlspecialchars_uni("misc.php?action=joblist&type={$jtype}");
                }
            } else {

                // Auszählen nach Filter
                $workplaces_count = $db->fetch_field($db->query("SELECT COUNT(*) as workplaces FROM ".TABLE_PREFIX."workplaces
                WHERE accepted = 1
                "), 'workplaces');

            }

            $perpage = $joblist_multipage_show_setting;
            $page = intval($mybb->input['page']);
            if($page) {
                $start = ($page-1) *$perpage;
            }
            else {
                $start = 0;
                $page = 1;
            }
            $end = $start + $perpage;
            $lower = $start+1;
            $upper = $end;
            if($upper > $workplaces_count) {
                $upper = $workplaces_count;
            }

            if ($joblist_filter_setting == 1) {
                $multipage = multipage($workplaces_count, $perpage, $page, $type_url);
            } else {
                $multipage = multipage($workplaces_count, $perpage, $page, $_SERVER['PHP_SELF']);
            }


        } else {
            $multipage = "";
        }

		// ABFRAGE ALLER ARBEITSPLÄTZE - MULTIPAGE 
		if ($joblist_multipage_setting == 1 & $joblist_tabs_setting == 0) {

            // Filter aktiv
            if ($joblist_filter_setting == 1) {

                // Stadt/Straßen Filter
                if ($joblist_city_setting == 1) {
        
                    $query_workplace = $db->query("SELECT * FROM ".TABLE_PREFIX."workplaces
                    WHERE accepted != '0'
                    AND type LIKE '$jtype'
                    AND city LIKE '$jcity'
                    ORDER by name ASC
                    LIMIT $start, $perpage
                    ");

                } else {
        
                    $query_workplace = $db->query("SELECT * FROM ".TABLE_PREFIX."workplaces
                    WHERE accepted != '0'
                    AND type LIKE '$jtype'
                    ORDER by name ASC
                    LIMIT $start, $perpage
                    ");
                    
                }

            } else {

                $query_workplace = $db->query("SELECT * FROM ".TABLE_PREFIX."workplaces
                WHERE accepted != '0'
                ORDER by name ASC
                LIMIT $start, $perpage
                ");
            }

		} 
		// ABFRAGE ALLER ARBEITSPLÄTZE - OHNE MULTIPAGE
		elseif ($joblist_multipage_setting == 0 & $joblist_tabs_setting == 0) {

            // Filter aktiv
            if ($joblist_filter_setting == 1) {

                // Stadt/Straßen Filter
                if ($joblist_city_setting == 1) {
        
                    $query_workplace = $db->query("SELECT * FROM ".TABLE_PREFIX."workplaces
                    WHERE accepted != '0'
                    AND type LIKE '$jtype'
                    AND city LIKE '$jcity'
                    ORDER by name ASC
                    ");

                } else {
        
                    $query_workplace = $db->query("SELECT * FROM ".TABLE_PREFIX."workplaces
                    WHERE accepted != '0'
                    AND type LIKE '$jtype'
                    ORDER by name ASC
                    ");
                    
                }

            } else {

                $query_workplace = $db->query("SELECT * FROM ".TABLE_PREFIX."workplaces
                WHERE accepted != '0'
                ORDER by name ASC
                ");
            }

		}

		// AUSGABE ALLER ARBEITSPLÄTZE - ohne Tabs
        if ($joblist_tabs_setting == 0) {
        
            while($work = $db->fetch_array($query_workplace)) {
        
                // ALLES LEER LAUFEN LASSEN
                $jid = "";
                $type = "";
                $city = "";
                $name = "";
                $shortfact = "";
                $description = "";
                $owner = "";
                $accepted = "";
                $createdby = "";
        
                // MIT INFOS FÜLLEN
                $jid = $work['jid'];
                $type = "» ".$work['type'];
                $city = $work['city'];
                $name = $work['name'];
                $shortfact = $work['shortfact'];
                $description = $work['description'];
                $owner = $work['owner'];
                $accepted = $work['accepted'];
                $createdby = $work['createdby'];

                if ($joblist_city_setting == 1) {
                    eval("\$joblist_city = \"" . $templates->get("joblist_bit_city") . "\";");
                } else {
                    $joblist_city = "";
                }

                // BERUFE INNERHALB DER ARBEITSSTELLE        
                // Abfrage
                $user_query = $db->query("SELECT * FROM ".TABLE_PREFIX."jobs j
                LEFT JOIN ".TABLE_PREFIX."users u
                ON (j.uid = u.uid)
                WHERE j.jid = '$jid'
                AND u.uid IN (SELECT uid FROM ".TABLE_PREFIX."users)
                ORDER BY u.username ASC
                ");
        
                // Leer laufen lassen    
                $user_bit = "";

                // Auslese             
                while($users = $db->fetch_array($user_query)){
                
                    $users['username'] = format_name($users['username'], $users['usergroup'], $users['displaygroup']);
                    $user = build_profile_link($users['username'], $users['uid']);
            
                    if ($users['halftime'] == 1) {
                        $position = $users['position'];
                    } else {
                        $position = "{$users['position']} [Nebenjob]";
                    }

                    eval("\$user_bit .= \"".$templates->get("joblist_bit_users")."\";");            
                }

                // LÖSCH- & BEARBEITUNGSOPTIONEN			
                // Team kann es immer die Optionen sehen
                if($mybb->usergroup['canmodcp'] == "1"){
                    $edit = "» <a href=\"misc.php?action=joblist_edit&jid={$jid}\">Arbeitsplatz bearbeiten</a>";
                    $delete = "» <a href=\"misc.php?action=joblist&delete={$jid}\">Arbeitsplatz löschen</a>";
                } 
                // Einsender 
                elseif ($user_id == $createdby) {
                    // User darf löschen und bearbeiten
                    if($joblist_delete_setting == 1 && $joblist_edit_setting == 1) {
                        $edit = "» <a href=\"misc.php?action=joblist_edit&jid={$jid}\">Arbeitsplatz bearbeiten</a>";
                        $delete = "» <a href=\"misc.php?action=joblist&delete={$jid}\">Arbeitsplatz löschen</a>";
                    } 
                    // User darf nur bearbeiten
                    elseif ($joblist_delete_setting != 1 && $joblist_edit_setting == 1) {
                        $edit = "» <a href=\"misc.php?action=joblist_edit&jid={$jid}\">Arbeitsplatz bearbeiten</a>";
                        $delete = "";
                    }
                    // User darf nur löschen
                    elseif ($joblist_delete_setting == 1 && $joblist_edit_setting != 1) {
                        $edit = "";
                        $delete = "» <a href=\"misc.php?action=joblist&delete={$jid}\">Arbeitsplatz löschen</a>";
                    }
                    // User darf nichts
                    else {
                        $edit = "";
                        $delete = "";
                    }

                // Gäste & alle anderen	User
                } else {
                    $edit = "";
                    $delete = "";
                }

                if($edit != "" OR $delete != "" ) {
                    eval("\$joblist_option = \"" . $templates->get("joblist_bit_option") . "\";"); 
                } else {
                    $joblist_option = "";
                }

                eval("\$joblist_bit .= \"".$templates->get("joblist_bit")."\";");
            }

        } 
        // AUSGABE ALLER ARBEITSPLÄTZE - mit Tabs 
        elseif ($joblist_tabs_setting == 1) {

            $tab_menu = "";
            foreach ($joblist_type as $joblist_typ){
                $default_tab = "";

                // muss per Hand angepasst werden, so dass es ein Default gibt!
                if($joblist_typ == $joblist_defaulttab_setting){
                    $default_tab = "id=\"defaultJoblistOpen\"";
                }
                $tab_menu .= "<button class=\"tablinks\" onclick=\"openJoblist(event, '{$joblist_typ}')\"  {$default_tab}>{$joblist_typ}</button>";

                $joblist_bit = "";
                // Einmal alle Work auslesen, die in der aktuellen Kategorie sind!
                $query_workplace = $db->query("SELECT * FROM ".TABLE_PREFIX."workplaces 
                WHERE type LIKE '%".$joblist_typ."%'
                AND accepted != '0'
                ORDER BY name ASC
                ");

                while($work = $db->fetch_array($query_workplace)) {
        
                    // ALLES LEER LAUFEN LASSEN
                    $jid = "";
                    $type = "";
                    $city = "";
                    $name = "";
                    $shortfact = "";
                    $description = "";
                    $owner = "";
                    $accepted = "";
                    $createdby = "";
            
                    // MIT INFOS FÜLLEN
                    $jid = $work['jid'];
                    $city = $work['city'];
                    $name = $work['name'];
                    $shortfact = $work['shortfact'];
                    $description = $work['description'];
                    $owner = $work['owner'];
                    $accepted = $work['accepted'];
                    $createdby = $work['createdby'];

                    if ($joblist_city_setting == 1) {
                        eval("\$joblist_city = \"" . $templates->get("joblist_bit_city") . "\";");
                    } else {
                        $joblist_city = "";
                    }
    
                    // BERUFE INNERHALB DER ARBEITSSTELLE        
                    // Abfrage
                    $user_query = $db->query("SELECT * FROM ".TABLE_PREFIX."jobs j
                    LEFT JOIN ".TABLE_PREFIX."users u
                    ON (j.uid = u.uid)
                    WHERE j.jid = '$jid'
                    AND u.uid IN (SELECT uid FROM ".TABLE_PREFIX."users)
                    ORDER BY u.username ASC
                    ");
            
                    // Leer laufen lassen    
                    $user_bit = "";
    
                    // Auslese             
                    while($users = $db->fetch_array($user_query)){
                
                        $users['username'] = format_name($users['username'], $users['usergroup'], $users['displaygroup']);
                        $user = build_profile_link($users['username'], $users['uid']);
                
                        if ($users['halftime'] == 1) {
                            $position = $users['position'];
                        } else {
                            $position = "{$users['position']} [Nebenjob]";
                        }
    
                        eval("\$user_bit .= \"".$templates->get("joblist_bit_users")."\";");            
                    }
    
                    // LÖSCH- & BEARBEITUNGSOPTIONEN			
                    // Team kann es immer die Optionen sehen
                    if($mybb->usergroup['canmodcp'] == "1"){
                        $edit = "» <a href=\"misc.php?action=joblist_edit&jid={$jid}\"><i class=\"fas fa-edit\" original-title=\"Arbeitsplatz bearbeiten\"></i></a>";
                        $delete = "» <a href=\"misc.php?action=joblist&delete={$jid}\"><i class=\"fas fa-trash\" original-title=\"Arbeitsplatz löschen\"></i></a>";
                    } 
                    // Einsender 
                    elseif ($user_id == $createdby) {
                        // User darf löschen und bearbeiten
                        if($joblist_delete_setting == 1 && $joblist_edit_setting == 1) {
                            $edit = "» <a href=\"misc.php?action=joblist_edit&jid={$jid}\"><i class=\"fas fa-edit\" original-title=\"Arbeitsplatz bearbeiten\"></i></a>";
                            $delete = "» <a href=\"misc.php?action=joblist&delete={$jid}\"><i class=\"fas fa-trash\" original-title=\"Arbeitsplatz löschen\"></i></a>";
                        } 
                        // User darf nur bearbeiten
                        elseif ($joblist_delete_setting != 1 && $joblist_edit_setting == 1) {
                            $edit = "» <a href=\"misc.php?action=joblist_edit&jid={$jid}\"><i class=\"fas fa-edit\" original-title=\"Arbeitsplatz bearbeiten\"></i></a>";
                            $delete = "";
                        }
                        // User darf nur löschen
                        elseif ($joblist_delete_setting == 1 && $joblist_edit_setting != 1) {
                            $edit = "";
                            $delete = "» <a href=\"misc.php?action=joblist&delete={$jid}\"><i class=\"fas fa-trash\" original-title=\"Arbeitsplatz löschen\"></i></a>";
                        }
                        // User darf nichts
                        else {
                            $edit = "";
                            $delete = "";
                        }    
                    // Gäste & alle anderen	User
                    } else {
                        $edit = "";
                        $delete = "";
                    }

                    if($edit != "" OR $delete != "" ) {
                        eval("\$joblist_option = \"" . $templates->get("joblist_bit_option") . "\";"); 
                    } else {
                        $joblist_option = "";
                    }
    
                    eval("\$joblist_bit .= \"".$templates->get("joblist_bit")."\";");
                }

                
                eval("\$typ_tabs .= \"" . $templates->get("joblist_tabs_category") . "\";");
            }

        }
    
        // ARBEITSPLATZ LÖSCHEN
        $delete = $mybb->input['delete'];
        if($delete) {
            // in DB workplaces löschen
            $db->delete_query("workplaces", "jid = '$delete'");
            // in DB jobs löschen
            $db->delete_query("jobs", "jid = '$delete'");
            redirect("misc.php?action=joblist", "{$lang->joblist_delete_redirect}");
        }

        
        // TABSYSTEM AKTIV
        if ($joblist_tabs_setting == 1) {
            eval("\$joblist_tabs_js = \"" . $templates->get("joblist_tabs_js") . "\";");
            eval("\$page = \"".$templates->get("joblist_tabs")."\";");
        } else {
            eval("\$page = \"".$templates->get("joblist")."\";");
        }
        output_page($page);
        die();
	}

    if($mybb->usergroup['canmodcp'] == '1') {
        eval("\$adminlinks = \"" . $templates->get("listen_nav_admin") . "\";");
    } else {
        $adminlinks = "";
    }

    // ARBEITSPLATZ HINZUFÜGEN
    elseif($_POST['add_workplace']) {
        
        if($mybb->input['type'] == "")
        {
            error("Es muss eine Kategorie ausgewählt werden!");
        }
        elseif($mybb->input['city'] == "")
        {
            error("Es muss eine Stadt/Straße ausgewählt werden!");
        }
        elseif($mybb->input['name'] == "")
        {
            error("Es muss ein Name eingetragen werden!");
        }
        elseif($mybb->input['shortfact'] == "")
        {
            error("Es muss ein Schlagwort eingetragen werden!");	
        } 
        elseif($mybb->input['description'] == "")
        {
            error("Es muss eine Beschreibung eingetragen werden!");	
        } else{
  
            //Wenn das Team eine Bildungseinrichtung erstellt, dann werden diese sofort freigeschaltet
            if($mybb->usergroup['canmodcp'] == '1'){
                $accepted = 1;
            } else {
                $accepted = 0;
            }

            $type = $db->escape_string ($_POST['type']);
            $city = $db->escape_string ($_POST['city']);
            $name = $db->escape_string ($_POST['name']);
            $shortfact = $db->escape_string ($_POST['shortfact']);
            $description = $db->escape_string ($_POST['description']);
            $owner = $db->escape_string ($_POST['owner']);

            $new_workplace = array(
                "type" => $type,
                "city" => $city,
                "name" => $name,
                "shortfact" => $shortfact,
                "owner" => $owner,
                "description" => $description,
                "createdby" => (int)$mybb->user['uid'],
                "accepted" => $accepted
            );

            $db->insert_query("workplaces", $new_workplace);
            redirect("misc.php?action=joblist", "{$lang->joblist_add_redirect}");
        }  
    }

    // BERUF EINTRAGEN
	elseif($mybb->input['action'] == "join_jobs") {

        $new_job = array(
            "jid" => (int)$mybb->get_input('jid'),
            "position" => $db->escape_string($mybb->get_input('position')),
            "halftime" => $db->escape_string($mybb->get_input('halftime')),
            "uid" => (int)$mybb->user['uid']
        );

		$db->insert_query("jobs", $new_job);

        redirect("misc.php?action=joblist", "{$lang->joblist_join_redirect}");
    }

    // BILDUNGSEINRICHTUNG BEARBEITEN
    elseif($mybb->input['action'] == "joblist_edit") {

        // NAVIGATION
        if(!empty($joblist_lists_setting)){
            add_breadcrumb("Listen", "$joblist_lists_setting");
            add_breadcrumb ($lang->joblist, "misc.php?action=joblist");
            add_breadcrumb ($lang->joblist_edit, "misc.php?action=joblist_edit");
        } else {
            add_breadcrumb ($lang->joblist, "misc.php?action=joblist");
            add_breadcrumb ($lang->joblist_edit, "misc.php?action=joblist_edit");
        }

        $jid =  $mybb->get_input('jid', MyBB::INPUT_INT);

        $edit_query = $db->query("SELECT * FROM ".TABLE_PREFIX."workplaces    
        WHERE jid = '".$jid."'
        ");

        $edit = $db->fetch_array($edit_query);

        // ALLES LEER LAUFEN LASSEN
        $jid = "";
        $type = "";
        $city = "";
        $name = "";
        $shortfact = "";
        $description = "";
        $owner = "";
        $accepted = "";
        $createdby = "";
    
        // MIT INFOS FÜLLEN
        $jid = $edit['jid'];
        $type = $edit['type'];
        $city = $edit['city'];
        $name = $edit['name'];
        $shortfact = $edit['shortfact'];
        $description = $edit['description'];
        $owner = $edit['owner'];
        $accepted = $edit['accepted'];
        $createdby = $edit['createdby'];

        //Der neue Inhalt wird nun in die Datenbank eingefügt bzw. die alten Daten überschrieben.        
        if($_POST['edit_joblist']){

            $jid = $mybb->input['jid'];
            $type = $db->escape_string ($_POST['type']);
            $city = $db->escape_string ($_POST['city']);
            $name = $db->escape_string ($_POST['name']);
            $shortfact = $db->escape_string ($_POST['shortfact']);
            $description = $db->escape_string ($_POST['description']);
            $owner = $db->escape_string ($_POST['owner']);
       
            $edit_workplace = array(
                "type" => $type,
                "city" => $city,
                "name" => $name,
                "shortfact" => $shortfact,
                "owner" => $owner,
                "description" => $description,
            );
            
            $db->update_query("workplaces", $edit_workplace, "jid = '".$jid."'");
            redirect("misc.php?action=joblist", "{$lang->joblist_edit_redirect}");
        }

        $createdby_uid = $db->fetch_field($db->simple_select("workplaces", "createdby", "jid = '{$jid}'"), "createdby");
        if ($createdby_uid == $user_id && $joblist_edit_setting == 1 || $mybb->usergroup['canmodcp'] == "1"){ 
            // TEMPLATE FÜR DIE SEITE
            eval("\$page = \"".$templates->get("joblist_edit")."\";");
            output_page($page);
            die();
        } else {
            error_no_permission();
        }
    }
}

// MOD-CP - NAVIGATION
function joblist_modcp_nav() {

    global $db, $mybb, $templates, $theme, $header, $headerinclude, $footer, $lang, $modcp_nav, $nav_joblist;
    
    $lang->load('joblist');
    
    eval("\$nav_joblist = \"".$templates->get ("joblist_modcp_nav")."\";");
}
