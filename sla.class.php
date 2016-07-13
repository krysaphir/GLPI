<?php
/*
 * @version $Id: sla.class.php 22657 2014-02-12 16:17:54Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief 
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class SLA
class SLA extends CommonDBTM {

   // From CommonDBTM
   var $dohistory                      = true;

   static protected $forward_entity_to = array('SLALevel');


   static function getTypeName($nb=0) {
      // Acronymous, no plural
      return __('SLA');
   }


   static function canCreate() {
      return Session::haveRight('sla', 'w');
   }


   static function canView() {
      return Session::haveRight('sla', 'r');
   }


   /**
    * Define calendar of the ticket using the SLA when using this calendar as sla-s calendar
    *
    * @param $calendars_id calendars_id of the ticket
   **/
   function setTicketCalendar($calendars_id) {

      if ($this->fields['calendars_id'] == -1 ) {
         $this->fields['calendars_id'] = $calendars_id;
      }
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab('SlaLevel', $ong, $options);
      $this->addStandardTab('Rule', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);

      return $ong;
   }


   function post_getEmpty() {
      $this->fields['resolution_time'] = DAY_TIMESTAMP;
   }


   function cleanDBonPurge() {
      global $DB;

      // Clean sla_levels
      $query = "SELECT `id`
                FROM `glpi_slalevels`
                WHERE `slas_id` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $slalevel = new SlaLevel();
            while ($data = $DB->fetch_assoc($result)) {
               $slalevel->delete($data);
            }
         }
      }

      // Update tickets : clean SLA
      $query = "SELECT `id`
                FROM `glpi_tickets`
                WHERE `slas_id` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $ticket = new Ticket();
            while ($data = $DB->fetch_assoc($result)) {
               $ticket->deleteSLA($data['id']);
            }
         }
      }

      Rule::cleanForItemAction($this);
   }


   /**
    * Print the sla form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array    of possible options:
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    *@return boolean item found
   **/
   function showForm($ID, $options=array()) {

      $rowspan = 3;
      if ($ID > 0) {
         $rowspan = 4;
      }

      $this->initForm($ID, $options);
      $this->showTabs($options);
      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name", array('value' => $this->fields["name"]));
      echo "<td rowspan='".$rowspan."'>".__('Comments')."</td>";
      echo "<td rowspan='".$rowspan."'>
            <textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      if ($ID > 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Last update')."</td>";
         echo "<td>".($this->fields["date_mod"] ? Html::convDateTime($this->fields["date_mod"])
                                                : __('Never'));
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'><td>".__('Calendar')."</td>";
      echo "<td>";

      Calendar::dropdown(array('value'      => $this->fields["calendars_id"],
                               'emptylabel' => __('24/7'),
                               'toadd'      => array('-1' => __('Calendar of the ticket'))));
      echo "</td></tr>";

      /* ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ##########
	     ########## Début AJOUT parmétrage PEC																										    ##########
	     ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## */
      echo "<tr class='tab_bg_1'><td>".__('Maximum time to react')."</td>";
      echo "<td>";
      $possible_values = array();
      for ($i=10 ; $i<60 ; $i+=10) {
         $possible_values[$i*MINUTE_TIMESTAMP] = sprintf(_n('%d minutes','%d minutes',$i),$i);
      }
      for ($i=1 ; $i<24 ; $i++) {
         $possible_values[$i*HOUR_TIMESTAMP] = sprintf(_n('%d hour','%d hours',$i),$i);
      }
      for ($i=1 ; $i<=100 ; $i++) {
         $possible_values[$i*DAY_TIMESTAMP] = sprintf(_n('%d day','%d days',$i),$i);
      }
      Dropdown::showFromArray('pec_time', $possible_values,
                              array('value' => $this->fields["pec_time"]));
      echo "</td></tr>";
	  /* ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ##########
	     ########## FIN AJOUT paramétrage PEC																										    ##########
	     ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## ########## */
	  

      echo "<tr class='tab_bg_1'><td>".__('Maximum time to solve')."</td>";
      echo "<td>";
      $possible_values = array();
      for ($i=10 ; $i<60 ; $i+=10) {
         $possible_values[$i*MINUTE_TIMESTAMP] = sprintf(_n('%d minutes','%d minutes',$i),$i);
      }
      for ($i=1 ; $i<24 ; $i++) {
         $possible_values[$i*HOUR_TIMESTAMP] = sprintf(_n('%d hour','%d hours',$i),$i);
      }
      for ($i=1 ; $i<=100 ; $i++) {
         $possible_values[$i*DAY_TIMESTAMP] = sprintf(_n('%d day','%d days',$i),$i);
      }
      Dropdown::showFromArray('resolution_time', $possible_values,
                              array('value' => $this->fields["resolution_time"]));
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false;

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'number';

      $tab[4]['table']           = 'glpi_calendars';
      $tab[4]['field']           = 'name';
      $tab[4]['name']            = __('Calendar');
      $tab[4]['datatype']        = 'dropdown';

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['massiveaction']  = false;
      $tab[80]['datatype']       = 'dropdown';

      $tab[86]['table']          = $this->getTable();
      $tab[86]['field']          = 'is_recursive';
      $tab[86]['name']           = __('Child entities');
      $tab[86]['datatype']       = 'bool';

      return $tab;
   }


   /**
    * Get due date based on a sla
    *
    * @param $start_date         datetime start date
    * @param $additional_delay   integer  additional delay to add or substract (for waiting time)
    *                                     (default 0)
    *
    * @return due date time (NULL if sla not exists)
   **/
   function computeDueDate($start_date, $additional_delay=0) {

      if (isset($this->fields['id'])) {
         // Based on a calendar
         if ($this->fields['calendars_id'] > 0) {
            $cal          = new Calendar();
            $work_in_days = ($this->fields['resolution_time'] >= DAY_TIMESTAMP);

            if ($cal->getFromDB($this->fields['calendars_id'])) {
               return $cal->computeEndDate($start_date,
                                           $this->fields['resolution_time']+$additional_delay,
                                           $work_in_days);
            }
         }

         // No calendar defined or invalid calendar
         $starttime = strtotime($start_date);
         $endtime   = $starttime+$this->fields['resolution_time']+$additional_delay;
         return date('Y-m-d H:i:s',$endtime);
      }

      return NULL;
   }
   
   
   /**
    * Get due date based on a sla
    *
    * @param $liveSLA         		predefined SLA parameters
    * @param $type					PEC / solve
    *                                     (default 0)
    *
    * @return due date time (NULL if sla not exists)
   **/   
	function computeDueDatePEC($data, $type) {
		global $DB;
		if (isset($data["id"])) {
			
			// Set status to count
			$data["type"]	= ($type == "PEC") ? array(1) : array(2,3) ;
			
			// Set remaining time
			$data["remain"]	= ($type == "PEC") ? $data["pec_time"] : $data["solve_time"] ;
					
			// Calculation
			$new_day = date("Y-m-d H:i:s", mktime(0,0,0,0,0,0));
			$old_day = date("Y-m-d H:i:s", mktime(0,0,0,0,0,0));
			
			foreach($data["sla_hist"] as $changement) {
				if($changement["old"] != 0) {
					// Get date of the changement
					$new_day = ($changement["new"] != 0) ? $changement["date"] : date("Y-m-d H:i:s", mktime(23,59,59,12,31,date("Y")+10));
				
					// Calculation only for searched status
					if(in_array($changement["old"],$data["type"])) {
					
						// Looking the period day by day
						$day_read = mktime(substr($old_day,11,2),substr($old_day,14,2),substr($old_day,17,2),substr($old_day,5,2) ,substr($old_day,8,2) ,substr($old_day,0,4));
						$day_stop = mktime(substr($new_day,11,2),substr($new_day,14,2),substr($new_day,17,2),substr($new_day,5,2) ,substr($new_day,8,2) ,substr($new_day,0,4));
						while ($day_read <= $day_stop) {

							// Checking if service is in holiday
							$is_closed = false;
							foreach ($data["holiday_cal"] as $holiday) {
								$t1 = mktime(substr($holiday["begin_date"],11,2),substr($holiday["begin_date"],14,2),substr($holiday["begin_date"],17,2),substr($holiday["begin_date"],5,2) ,substr($holiday["begin_date"],8,2) ,substr($holiday["begin_date"],0,4));
								$t2 = mktime(substr($holiday["end_date"],11,2),substr($holiday["end_date"],14,2),substr($holiday["end_date"],17,2),substr($holiday["end_date"],5,2) ,substr($holiday["end_date"],8,2) ,substr($holiday["end_date"],0,4));
								if($t1 <= $day_read and $day_read <= $t2) { $is_closed = true;}
								}
							
							if(!$is_closed) {
								// Checking if service is open
								foreach ($data["week_cal"] as $week_day) {
									if($week_day["day"] == date("N", $day_read)) {
										
										// Convert times to seconds
										$s1 = date("H", $day_read)*3600 + date("i", $day_read)*60 + date("s", $day_read);
										$s2 = (date("Ymd", $day_read) == date("Ymd", $day_stop)) ? date("H", $day_stop)*3600 + date("i", $day_stop)*60 + date("s", $day_stop) : 24*3600;
										$s3 = substr($week_day["begin"],0,2)*3600 + substr($week_day["begin"],3,2)*60 + substr($week_day["begin"],6,2);
										$s4 = substr($week_day["end"],0,2)*3600 + substr($week_day["end"],3,2)*60 + substr($week_day["end"],6,2);
										
										$s3 = max($s1, $s3);
										$s4 = min($s2, $s4);
										
										$s5 = max(0, $s4-$s3);
										
										if($data["remain"] >= $s5){$data["remain"] -= $s5;}
										else {
											$s1 = $s3 + $data["remain"];
											$data["remain"] = 0;
											
											// Update de la base
											$column = ($type == "PEC") ? "due_date_PEC" : "due_date";
											$query  = "UPDATE `glpi_tickets`
												SET `".$column."` = '".date("Y-m-d H:i:s", mktime(0,0,$s1, date("m", $day_read), date("d", $day_read), date("Y", $day_read)))."' 
												WHERE `id` ='".$data["id"]."'";
											
											$DB->query($query);
											return date("Y-m-d H:i:s", mktime(0,0,$s1, date("m", $day_read), date("d", $day_read), date("Y", $day_read)));
											}
										
										}
									}
								}
							
							
							$day_read = mktime(0,0,0, date("m", $day_read), date("d", $day_read)+1, date("Y", $day_read));
							}
					
						}
					}
				$old_day = $changement["date"];
				}
			}

		return NULL;
		}


   /**
    * Get execution date of a sla level
    *
    * @param $start_date         datetime    start date
    * @param $slalevels_id       integer     sla level id
    * @param $additional_delay   integer     additional delay to add or substract (for waiting time)
    *                                        (default 0)
    *
    * @return execution date time (NULL if sla not exists)
   **/
   function computeExecutionDate($start_date, $slalevels_id, $additional_delay=0) {

      if (isset($this->fields['id'])) {
         $slalevel = new SlaLevel();

         if ($slalevel->getFromDB($slalevels_id)) { // sla level exists
            if ($slalevel->fields['slas_id'] == $this->fields['id']) { // correct sla level
               $work_in_days = ($this->fields['resolution_time'] >= DAY_TIMESTAMP);
               $delay        = $this->fields['resolution_time']+$slalevel->fields['execution_time']
                               +$additional_delay;

               // Based on a calendar
               if ($this->fields['calendars_id'] > 0) {
                  $cal = new Calendar();
                  if ($cal->getFromDB($this->fields['calendars_id'])) {
                     return $cal->computeEndDate($start_date, $delay, $work_in_days);
                  }
               }

               // No calendar defined or invalid calendar
               $starttime = strtotime($start_date);
               $endtime   = $starttime+$delay;
               return date('Y-m-d H:i:s',$endtime);
            }
         }
      }
      return NULL;
   }


   /**
    * Get active time between to date time for the active calendar
    *
    * @param $start  datetime begin
    * @param $end    datetime end
    *
    * @return timestamp of delay
   **/
   function getActiveTimeBetween($start, $end) {

      if ($end < $start) {
         return 0;
      }

      if (isset($this->fields['id'])) {
         $slalevel     = new SlaLevel();
         $cal          = new Calendar();
         $work_in_days = ($this->fields['resolution_time'] >= DAY_TIMESTAMP);

         // Based on a calendar
         if ($this->fields['calendars_id'] > 0) {
            if ($cal->getFromDB($this->fields['calendars_id'])) {
               return $cal->getActiveTimeBetween($start, $end, $work_in_days);
            }

         } else { // No calendar
            $timestart = strtotime($start);
            $timeend   = strtotime($end);
            return ($timeend-$timestart);
         }
      }
      return 0;
   }


   /**
    * Add a level to do for a ticket
    *
    * @param $ticket Ticket object
    *
    * @return execution date time (NULL if sla not exists)
   **/
   function addLevelToDo(Ticket $ticket) {

      if ($ticket->fields["slalevels_id"]>0) {
         $toadd                 = array();
         $toadd['date']         = $this->computeExecutionDate($ticket->fields['date'],
                                                              $ticket->fields['slalevels_id'],
                                                              $ticket->fields['sla_waiting_duration']);
         $toadd['slalevels_id'] = $ticket->fields["slalevels_id"];
         $toadd['tickets_id']   = $ticket->fields["id"];
         $slalevelticket        = new SlaLevel_Ticket();
         $slalevelticket->add($toadd);
      }
   }


   /**
    * Add a level to do for a ticket
    *
    * @param $ticket Ticket object
    *
    * @return execution date time (NULL if sla not exists)
   **/
   static function deleteLevelsToDo(Ticket $ticket) {
      global $DB;

      if ($ticket->fields["slalevels_id"] > 0) {
         $query = "SELECT *
                   FROM `glpi_slalevels_tickets`
                   WHERE `tickets_id` = '".$ticket->fields["id"]."'";

         $slalevelticket = new SlaLevel_Ticket();
         foreach ($DB->request($query) as $data) {
            $slalevelticket->delete(array('id' => $data['id']));
         }
      }
   }



	/**
    * live calculation for SLA
    *
    * @param $slas_id      SLA id
    * @param $entities_id  entity ID of the ticket
    *
    * @return array of datas to add in ticket
   **/
   function liveCalculationSLA($ticket_id) {
		global $DB;
		
		
		
		// Get informations from ticket
		$ticket = new Ticket();
		$ticket->check($ticket_id,'w');
		
		$data["id"]			= $ticket->fields["id"];
		$data["date"]		= $ticket->fields["date"];
		$data["status"]		= $ticket->fields["status"];
		$data["slas_id"]	= $ticket->fields["slas_id"];
		
		
		
		// Get informations from SLA
		$sla = new SLA();
		$sla->check($data["slas_id"], 'w');
		
		$data["pec_time"]	= $sla->fields["pec_time"];
		$data["solve_time"]	= $sla->fields["resolution_time"];
		$data["calendar"]	= $sla->fields["calendars_id"];
		$data["pec_time_elapsed"] = 0;
		$data["solve_time_elapsed"] = 0;
		
		// Convert time in second
		if(isset($sla->fields["definition_time"])) {
			
			}
		if(isset($sla->fields["definition_time_PEC"])) {
			
			}
		
		
		
		// Get SLA changements history
		$query1 = "SELECT date_mod, old_value, new_value
			FROM `glpi_logs`
			WHERE itemtype= \"Ticket\" AND items_id = \"".$data["id"]."\" AND id_search_option = \"12\"
			ORDER BY date_mod";
		
		$data["sla_hist"]	= array();
		array_push($data["sla_hist"],array("date" => $data["date"], "old" => 0, "new" => 0));
		foreach ($DB->request($query1) as $result1) {
			if($data["sla_hist"][0]["new"] == 0){$data["sla_hist"][0]["new"] = $result1["old_value"];}
			array_push($data["sla_hist"],array("date" => $result1["date_mod"], "old" => $result1["old_value"], "new" => $result1["new_value"]));
			}
		array_push($data["sla_hist"],array("date" => date("Y-m-d H:i:s"), "old" => $data["sla_hist"][count($data["sla_hist"])-1]["new"], "new" => 0));

		
		// Get week service planning
		$query2 = "SELECT day, begin, end
			FROM `glpi_calendarsegments`
			WHERE calendars_id = \"".$data["calendar"]."\"
			ORDER BY day, begin, end";
		
		$data["week_cal"]	= array();
		foreach ($DB->request($query2) as $result2) {
			array_push($data["week_cal"],array("day" => $result2["day"], "begin" => $result2["begin"], "end" => $result2["end"]));			
			}
		
		
		
		// Get holidays planning
		$query3 = "SELECT holidays_id
			FROM `glpi_calendars_holidays`
			WHERE calendars_id = \"".$data["calendar"]."\"
			ORDER BY holidays_id";
		
		$data["holiday_cal"]	= array();
		foreach ($DB->request($query3) as $result3) {
			$data["holiday_cal"][$result3["holidays_id"]] = array("begin_date" => null, "end_date" => null);
			}
			
		$query4 = "SELECT id, begin_date, end_date
			FROM `glpi_holidays`";
		
		foreach ($DB->request($query4) as $result4) {
			if(isset($data["holiday_cal"][$result4["id"]])){
				$data["holiday_cal"][$result4["id"]]["begin_date"] = date("Y-m-d H:i:s", mktime(0,0,0,substr($result4["begin_date"],5,2),substr($result4["begin_date"],8,2),substr($result4["begin_date"],0,4)));
				$data["holiday_cal"][$result4["id"]]["end_date"] = date("Y-m-d H:i:s", mktime(23,59,59,substr($result4["end_date"],5,2),substr($result4["end_date"],8,2),substr($result4["end_date"],0,4)));
				}
			}
		
		
		// Calculation
		$new_day = date("Y-m-d H:i:s", mktime(0,0,0,0,0,0));
		$old_day = date("Y-m-d H:i:s", mktime(0,0,0,0,0,0));
					
		foreach($data["sla_hist"] as $changement) {
			if($changement["old"] != 0) {
				// Get date of the changement
				$new_day = $changement["date"];
					
				// Calculation only for searched status
				if(in_array($changement["old"],array(1, 2, 3))) {
					
					// Looking the period day by day
					$day_read = mktime(substr($old_day,11,2),substr($old_day,14,2),substr($old_day,17,2),substr($old_day,5,2) ,substr($old_day,8,2) ,substr($old_day,0,4));
					$day_stop = mktime(substr($new_day,11,2),substr($new_day,14,2),substr($new_day,17,2),substr($new_day,5,2) ,substr($new_day,8,2) ,substr($new_day,0,4));
					while ($day_read <= $day_stop) {
						
						// Checking if service is in holiday
						$is_closed = false;
						foreach ($data["holiday_cal"] as $holiday) {
							$t1 = mktime(substr($holiday["begin_date"],11,2),substr($holiday["begin_date"],14,2),substr($holiday["begin_date"],17,2),substr($holiday["begin_date"],5,2) ,substr($holiday["begin_date"],8,2) ,substr($holiday["begin_date"],0,4));
							$t2 = mktime(substr($holiday["end_date"],11,2),substr($holiday["end_date"],14,2),substr($holiday["end_date"],17,2),substr($holiday["end_date"],5,2) ,substr($holiday["end_date"],8,2) ,substr($holiday["end_date"],0,4));
							if($t1 <= $day_read and $day_read <= $t2) { $is_closed = true;}
							}
							
						if(!$is_closed) {
							// Checking if service is open
							foreach ($data["week_cal"] as $week_day) {
								if($week_day["day"] == date("w", $day_read)) {
										
									// Convert times to seconds
									$s1 = date("H", $day_read)*3600 + date("i", $day_read)*60 + date("s", $day_read);
									$s2 = (date("Ymd", $day_read) == date("Ymd", $day_stop)) ? date("H", $day_stop)*3600 + date("i", $day_stop)*60 + date("s", $day_stop) : 24*3600;
									$s3 = substr($week_day["begin"],0,2)*3600 + substr($week_day["begin"],3,2)*60 + substr($week_day["begin"],6,2);
									$s4 = substr($week_day["end"],0,2)*3600 + substr($week_day["end"],3,2)*60 + substr($week_day["end"],6,2);
									
									$s3 = max($s1, $s3);
									$s4 = min($s2, $s4);
									
									$s5 = max(0, $s4-$s3);
									
									if($changement["old"] == 1) {$data["pec_time_elapsed"] += $s5;}
									if(in_array($changement["old"],array(2, 3))) {$data["solve_time_elapsed"] += $s5;}


									}
								}
							}
						
						$day_read = mktime(0,0,0, date("m", $day_read), date("d", $day_read)+1, date("Y", $day_read));
						}
					}
				}
			$old_day = $changement["date"];
			}
			
		return $data;

	  }
   }
?>
