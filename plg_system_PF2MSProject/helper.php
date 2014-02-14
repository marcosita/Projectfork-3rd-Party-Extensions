<?php
/**
 * @package      Projectfork Export to MS Project
 *
 * @author       Kon Angelopoulos (ANGEK DESIGN)
 * @copyright    Copyright (C) 2013 ANGEK DESIGN. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();

jimport('joomla.application.component.controller');
jimport('joomla.application.component.helper');
jimport('projectfork.framework');
JLoader::register('ProjectforkModelDashboard', JPATH_SITE . '/components/com_projectfork/models/dashboard.php');

require_once dirname(__FILE__) . '/helpers/milestones.php';
require_once dirname(__FILE__) . '/helpers/tasklists.php';
require_once dirname(__FILE__) . '/helpers/tasks.php';
		
class PFtomsprojectHelper
{
	private $xmlContent;
	private $xmlHeader;
	private $xmlBody;
	private $xmlFooter;
	private $curr_code;
	private $curr_sign;
	private $curr_pos;
	private $cid;
	private $item;
	private $items;

	
	public function __construct()
	{
		$pcfg 				= PFApplicationHelper::getProjectParams();
						
		$this->curr_code 	= $pcfg->get('currency_code');
		$this->curr_sign 	= $pcfg->get('currency_sign');
		$this->curr_pos  	= $pcfg->get('currency_position');
		$cid 				= (int)JRequest::getVar('cid',null);
		$this->pid			= $cid;
	}
	
	public function getProjectInfo()
	{
		$projects 		= new ProjectforkModelDashboard();
		$milestones		= new PFtomsprojectHelperMilestones();
		$lists			= new PFtomsprojectHelperLists();
		$tasks			= new PFtomsprojectHelperTasks();
		
		$m 				= $milestones->getItems();		
		$l 				= $lists->getItems();
		$t 				= $tasks->getItems();
		$this->items 	= $this->hierarchySort($m, $l, $t);
		
		//echo "MS Project Items:<pre>"; print_r($this->items); echo "</pre>"; 
		
		
		$this->item 				= $projects->getItem($this->pid);
		$this->item->created 		= str_replace(" ","T", $this->item->created);
		$this->item->start_date 	= str_replace(" ","T", $this->item->start_date);
		$this->item->end_date 		= str_replace(" ","T", $this->item->end_date);

		///echo "Project Info:<pre>"; print_r($this->item); echo "</pre>"; 
		//exit;
	}
	
	public function exportXML($df)
	{
		$this->xmlHeader = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Project xmlns="http://schemas.microsoft.com/project">
	<SaveVersion>14</SaveVersion>
	<Name>'.$this->item->title.'.xml</Name>
	<CreationDate>'.$this->item->created.'</CreationDate>
	<ScheduleFromStart>1</ScheduleFromStart>
	<StartDate>'.$this->item->start_date.'</StartDate>
	<FinishDate>'.$this->item->end_date.'</FinishDate>
	<FYStartDate>1</FYStartDate>
	<CriticalSlackLimit>0</CriticalSlackLimit>
	<CurrencyDigits>2</CurrencyDigits>
	<CurrencySymbol>'.$this->curr_sign.'</CurrencySymbol>
	<CurrencyCode>'.$this->curr_code.'</CurrencyCode>
	<CurrencySymbolPosition>'.$this->curr_pos.'</CurrencySymbolPosition>	
	<CalendarUID>3</CalendarUID>
	<DefaultStartTime>00:00:00</DefaultStartTime>
	<DefaultFinishTime>23:30:00</DefaultFinishTime>
	<MinutesPerDay>1440</MinutesPerDay>
	<MinutesPerWeek>10080</MinutesPerWeek>
	<DaysPerMonth>30</DaysPerMonth>
	<DefaultTaskType>0</DefaultTaskType>
	<DefaultFixedCostAccrual>3</DefaultFixedCostAccrual>
	<DefaultStandardRate>0</DefaultStandardRate>
	<DefaultOvertimeRate>0</DefaultOvertimeRate>
	<DurationFormat>'.$df.'</DurationFormat>
	<WorkFormat>2</WorkFormat>
	<EditableActualCosts>0</EditableActualCosts>
	<HonorConstraints>0</HonorConstraints>
	<InsertedProjectsLikeSummary>0</InsertedProjectsLikeSummary>
	<MultipleCriticalPaths>0</MultipleCriticalPaths>
	<NewTasksEffortDriven>0</NewTasksEffortDriven>
	<NewTasksEstimated>1</NewTasksEstimated>
	<SplitsInProgressTasks>1</SplitsInProgressTasks>
	<SpreadActualCost>0</SpreadActualCost>
	<SpreadPercentComplete>0</SpreadPercentComplete>
	<TaskUpdatesResource>1</TaskUpdatesResource>
	<FiscalYearStart>0</FiscalYearStart>
	<WeekStartDay>1</WeekStartDay>
	<MoveCompletedEndsBack>0</MoveCompletedEndsBack>
	<MoveRemainingStartsBack>0</MoveRemainingStartsBack>
	<MoveRemainingStartsForward>0</MoveRemainingStartsForward>
	<MoveCompletedEndsForward>0</MoveCompletedEndsForward>
	<BaselineForEarnedValue>0</BaselineForEarnedValue>
	<AutoAddNewResourcesAndTasks>1</AutoAddNewResourcesAndTasks>
	<MicrosoftProjectServerURL>1</MicrosoftProjectServerURL>
	<Autolink>0</Autolink>
	<NewTaskStartDate>0</NewTaskStartDate>
	<NewTasksAreManual>1</NewTasksAreManual>
	<DefaultTaskEVMethod>0</DefaultTaskEVMethod>
	<ProjectExternallyEdited>0</ProjectExternallyEdited>
	<ActualsInSync>0</ActualsInSync>
	<RemoveFileProperties>0</RemoveFileProperties>
	<AdminProject>0</AdminProject>
	<UpdateManuallyScheduledTasksWhenEditingLinks>1</UpdateManuallyScheduledTasksWhenEditingLinks>
	<KeepTaskOnNearestWorkingTimeWhenMadeAutoScheduled>0</KeepTaskOnNearestWorkingTimeWhenMadeAutoScheduled>
	<OutlineCodes/>
	<WBSMasks/>
	<ExtendedAttributes/>
	<Calendars>
			<Calendar>
			<UID>3</UID>
			<Name>24 Hours</Name>
			<IsBaseCalendar>1</IsBaseCalendar>
			<IsBaselineCalendar>0</IsBaselineCalendar>
			<BaseCalendarUID>-1</BaseCalendarUID>
			<WeekDays>
				<WeekDay>
					<DayType>1</DayType>
					<DayWorking>1</DayWorking>
					<WorkingTimes>
						<WorkingTime>
							<FromTime>00:00:00</FromTime>
							<ToTime>00:00:00</ToTime>
						</WorkingTime>
					</WorkingTimes>
				</WeekDay>
				<WeekDay>
					<DayType>2</DayType>
					<DayWorking>1</DayWorking>
					<WorkingTimes>
						<WorkingTime>
							<FromTime>00:00:00</FromTime>
							<ToTime>00:00:00</ToTime>
						</WorkingTime>
					</WorkingTimes>
				</WeekDay>
				<WeekDay>
					<DayType>3</DayType>
					<DayWorking>1</DayWorking>
					<WorkingTimes>
						<WorkingTime>
							<FromTime>00:00:00</FromTime>
							<ToTime>00:00:00</ToTime>
						</WorkingTime>
					</WorkingTimes>
				</WeekDay>
				<WeekDay>
					<DayType>4</DayType>
					<DayWorking>1</DayWorking>
					<WorkingTimes>
						<WorkingTime>
							<FromTime>00:00:00</FromTime>
							<ToTime>00:00:00</ToTime>
						</WorkingTime>
					</WorkingTimes>
				</WeekDay>
				<WeekDay>
					<DayType>5</DayType>
					<DayWorking>1</DayWorking>
					<WorkingTimes>
						<WorkingTime>
							<FromTime>00:00:00</FromTime>
							<ToTime>00:00:00</ToTime>
						</WorkingTime>
					</WorkingTimes>
				</WeekDay>
				<WeekDay>
					<DayType>6</DayType>
					<DayWorking>1</DayWorking>
					<WorkingTimes>
						<WorkingTime>
							<FromTime>00:00:00</FromTime>
							<ToTime>00:00:00</ToTime>
						</WorkingTime>
					</WorkingTimes>
				</WeekDay>
				<WeekDay>
					<DayType>7</DayType>
					<DayWorking>1</DayWorking>
					<WorkingTimes>
						<WorkingTime>
							<FromTime>00:00:00</FromTime>
							<ToTime>00:00:00</ToTime>
						</WorkingTime>
					</WorkingTimes>
				</WeekDay>
			</WeekDays>
		</Calendar>
	</Calendars>
	<Tasks>';
		
		$this->xmlBody 	= "";
		$counter 		= 1;
		$index 			= 0;
		$index_a		= 0;
		$index_b		= 0;
		
		$predecessors 	= array();
		$new_predecessors = array();
		
		foreach ($this->items as $key => $val) {
			if ($val->type == 'task' && $val->parents) {
				foreach ($val->parents as $child) {
					$x = $child + 1;
					$predecessors[$key][] = $child;
				}
			}
		}
		foreach ($this->items as $key => $val) {
			foreach($predecessors as $a=>$f){
				if (in_array($val->id, $f) && $val->type == 'task'){
					$k = array_search($val->id, $f);
					$new_predecessors[$a][] = $key + 1;
				}
			}
		}

		foreach ($this->items as $pos => $itm) {
			$milestone 	= 0;
			$summary 	= 0;
			$outline	= 1;
			$index_str 	= null;
			
			if ($itm->type == 'milestone') {
				$milestone 	= 1;
				$summary 	= 1;
			}
			else if ($itm->type == 'tasklist') {
				$summary 	= 1;
			}
			if (!$itm->complete) {
				$complete 	= 0;
			}
			else {
				$complete 	= 100;
			}
			
			$itm->start_date 	= str_replace(" ","T",$itm->start_date);
			$itm->end_date 		= str_replace(" ","T",$itm->end_date);
			
			$uid = $pos + $counter;
			
			switch ($itm->type){
				case 'task':
					if ($itm->milestone_id && $itm->list_id){
						$outline = 3;
						$index_b = $index_b + 1;						
					}
					elseif ($itm->milestone_id || $itm->list_id){
						$outline = 2;
						$index_a = $index_a + 1;
					}
					else {
						$index = $index + 1;
					}
					$index_str = $index .".". $index_a .".".$index_b;
				break;
				case 'tasklist':				
					if (!$this->checkIfEmpty($itm->id, $itm->type)) {
						$summary = 0;
					}
					
					if ($itm->milestone_id){
						$outline = 2;
						$index_a = $index_a + 1;
					}
					else {
						$index = $index + 1;
						$index_a = 0;
						$index_b = 0;
					}
					$index_str = $index .".". $index_a;
				break;
				case 'milestone':
					if (!$this->checkIfEmpty($itm->id, $itm->type)) {
						$summary = 0;
					}
					$outline = 1;
					$index = $index + 1;
					$index_a = 0;
					$index_b = 0;
					$index_str = $index;
				break;
			}
			
			$this->xmlBody .= "<Task>\n";
			$this->xmlBody .= "<UID>".$uid."</UID>\n";
			$this->xmlBody .= "<ID>".$uid."</ID>\n";
			$this->xmlBody .= "<Name>".$itm->title."</Name>\n";
			$this->xmlBody .= "<Active>1</Active>\n";
			$this->xmlBody .= "<Manual>1</Manual>\n";
			$this->xmlBody .= "<Type>0</Type>\n";
			$this->xmlBody .= "<IsNull>0</IsNull>\n";
			$this->xmlBody .= "<CreateDate>".$itm->created."</CreateDate>\n";
			$this->xmlBody .= "<WBS>".$index_str."</WBS>\n";
			$this->xmlBody .= "<OutlineNumber>".$index_str."</OutlineNumber>\n";
			$this->xmlBody .= "<OutlineLevel>".$outline."</OutlineLevel>\n";
			$this->xmlBody .= "<Priority>500</Priority>\n";
			$this->xmlBody .= "<Start>".$itm->start_date."</Start>\n";
			$this->xmlBody .= "<Finish>".$itm->end_date."</Finish>\n";
			$this->xmlBody .= "<Duration>".$itm->fduration."</Duration>\n";
			$this->xmlBody .= "<ManualStart>".$itm->start_date."</ManualStart>\n";
			$this->xmlBody .= "<ManualFinish>".$itm->end_date."</ManualFinish>\n";
			$this->xmlBody .= "<ManualDuration>".$itm->fduration."</ManualDuration>\n";
			$this->xmlBody .= "<DurationFormat>".$df."</DurationFormat>\n";
			$this->xmlBody .= "<Work>PT0H0M0S</Work>\n";
			$this->xmlBody .= "<ResumeValid>0</ResumeValid>\n";
			$this->xmlBody .= "<EffortDriven>0</EffortDriven>\n";
			$this->xmlBody .= "<Recurring>0</Recurring>\n";
			$this->xmlBody .= "<OverAllocated>0</OverAllocated>\n";
			$this->xmlBody .= "<Estimated>0</Estimated>\n";
			
			$this->xmlBody .= "<Milestone>".$milestone."</Milestone>\n";
			
			$this->xmlBody .= "<Summary>".$summary."</Summary>\n";
			$this->xmlBody .= "<DisplayAsSummary>0</DisplayAsSummary>\n";
			$this->xmlBody .= "<Critical>0</Critical>\n";
			$this->xmlBody .= "<IsSubproject>0</IsSubproject>\n";
			$this->xmlBody .= "<IsSubprojectReadOnly>0</IsSubprojectReadOnly>\n";
			$this->xmlBody .= "<ExternalTask>0</ExternalTask>\n";
			
			$this->xmlBody .= "<StartVariance>0</StartVariance>\n";
			$this->xmlBody .= "<FinishVariance>0</FinishVariance>\n";
			$this->xmlBody .= "<WorkVariance>0.00</WorkVariance>\n";
			$this->xmlBody .= "<FixedCost>0</FixedCost>\n";
			$this->xmlBody .= "<FixedCostAccrual>3</FixedCostAccrual>\n";
			
			$this->xmlBody .= "<PercentComplete>".$complete."</PercentComplete>\n";
			$this->xmlBody .= "<PercentWorkComplete>".$complete."</PercentWorkComplete>\n";
			
			$this->xmlBody .= "<ACWP>0.00</ACWP>\n";
			$this->xmlBody .= "<CV>0.00</CV>\n";
			$this->xmlBody .= "<ConstraintType>0</ConstraintType>\n";
			$this->xmlBody .= "<CalendarUID>3</CalendarUID>\n";
			$this->xmlBody .= "<LevelAssignments>1</LevelAssignments>\n";
			$this->xmlBody .= "<LevelingCanSplit>1</LevelingCanSplit>\n";
			$this->xmlBody .= "<LevelingDelay>0</LevelingDelay>\n";
			$this->xmlBody .= "<LevelingDelayFormat>8</LevelingDelayFormat>\n";
			$this->xmlBody .= "<IgnoreResourceCalendar>0</IgnoreResourceCalendar>\n";
			$this->xmlBody .= "<HideBar>0</HideBar>\n";
			$this->xmlBody .= "<Rollup>0</Rollup>\n";
			$this->xmlBody .= "<BCWS>0.00</BCWS>\n";
			$this->xmlBody .= "<BCWP>0.00</BCWP>\n";
			$this->xmlBody .= "<PhysicalPercentComplete>0</PhysicalPercentComplete>\n";
			$this->xmlBody .= "<EarnedValueMethod>0</EarnedValueMethod>\n";
			
			if (isset($itm->parents) && !empty($itm->parents)){
				foreach ($new_predecessors[$pos] as $child){
					$this->xmlBody .= "<PredecessorLink>\n";
					$this->xmlBody .= "	<PredecessorUID>".$child."</PredecessorUID>\n";
					$this->xmlBody .= "	<Type>1</Type>\n";
					$this->xmlBody .= "	<CrossProject>0</CrossProject>\n";
					$this->xmlBody .= "	<LinkLag>0</LinkLag>\n";
					$this->xmlBody .= "	<LagFormat>7</LagFormat>\n";
					$this->xmlBody .= "</PredecessorLink>\n";
				}
			}
			
			$this->xmlBody .= "<IsPublished>1</IsPublished>\n";
			$this->xmlBody .= "<CommitmentType>0</CommitmentType>\n";
			$this->xmlBody .= "</Task>\n";
		}
		
		$this->xmlBody .= "</Tasks>\n";
		$this->xmlFooter ="<Resources/>		
				<Assignments/>		
			</Project>";

		header('Content-type: text/xml');
		header('Content-Disposition: attachment; filename="'.$this->item->alias.'.xml"');

		$this->xml_contents = $this->xmlHeader.$this->xmlBody.$this->xmlFooter;
		
		echo $this->xml_contents;
		exit;
	}

	protected function checkIfEmpty($id, $type)
	{
		//echo "<pre>"; print_r($this->items); echo "</pre>";
		$found = false;
		//echo $lid;
		foreach($this->items as $var => $val) {
			if ($type == 'tasklist') {		
				if ($val->type == 'task'){
					if ( $val->list_id == $lid ) {
						//echo $val->id."<br />";
						$found = true;
					}
				}
			}
			elseif ($type == 'milestone') {
				if ($val->type == 'task' || $val->type == 'tasklist') {
					if ($val->milestone_id == $id) {
						$found = true;
					}
				}
			}
		}
		
		return $found;
	}
	
	protected function hierarchySort($milestones, $lists, $tasks)
    {
        $items    = array();
        $structure = array('top' => array());

        foreach($milestones as $milestone) {
            $ms_key = 'm.' . $milestone->id;

            $structure['top'][] = $milestone;
            $structure[$ms_key] = array();

            foreach ($lists as $li => $list) {
                if ($list->milestone_id != $milestone->id) {
                    continue;
                }

                $l_key = 'l.' . $list->id;

                $structure[$ms_key][] = $list;
                $structure[$l_key]    = array();

                foreach ($tasks as $ti => $task) {
                    if ($task->list_id != $list->id) {
                        continue;
                    }

                    $t_key = 't.' . $task->id;

                    $structure[$l_key][] = $task;
                    $structure[$t_key]   = array();

                    unset($tasks[$ti]);
                }

                unset($lists[$li]);
            }

            foreach ($tasks as $ti => $task) {
                if ($task->milestone_id != $milestone->id || $task->list_id != 0) {
                    continue;
                }

                $t_key = 't.' . $task->id;

                $structure[$ms_key][] = $task;
                $structure[$t_key]    = array();

                unset($tasks[$ti]);
            }
        }
		
        foreach ($lists as $li => $list) {
            $l_key = 'l.' . $list->id;

            $structure['top'][] = $list;
            $structure[$l_key]  = array();

            foreach ($tasks as $ti => $task) {
                if ($task->list_id != $list->id) {
                    continue;
                }

                $t_key = 't.' . $task->id;

                $structure[$l_key][] = $task;
                $structure[$t_key]   = array();

                unset($tasks[$ti]);
            }
        }
		
        foreach ($tasks as $ti => $task) {
            $t_key = 't.' . $task->id;

            $structure['top'][] = $task;
            $structure[$t_key]  = array();

            unset($tasks[$ti]);
        }
		
        $top  = $this->sortByStartDate($structure['top']);
        $keys = array('milestone' => 'm.', 'tasklist' => 'l.', 'task' => 't.');

        foreach ($top as $i => $item) {
            if (!($item->type == 'milestone')) {
                $items[] = $item;
            }

			if ($item->type == 'milestone') {
                $items[] = $item;
            }
			
            $key = $keys[$item->type] . $item->id;
            if (isset($structure[$key]) && count($structure[$key])) {
                $children = $this->sortByStartDate($structure[$key]);
                foreach ($children as $child) {
                    $items[] = $child;
                    $key2 = $keys[$child->type] . $child->id;

                    if (isset($structure[$key2]) && count($structure[$key2])) {
                        $children2 = $this->sortByStartDate($structure[$key2]);
                        foreach ($children2 as $child2) {
                            $items[] = $child2;
                        }
                    }
                }
            }             
        }

        return $items;
    }
	
	protected function sortByStartDate($data, $duration = true)
    {
        $datas = array();
        $items  = array();

        foreach ($data as $i => $item) {
            if (!isset($datas[$item->start_time])) $datas[$item->start_time] = array();
            $datas[$item->start_time][] = $item;
        }

        if ($duration) {
            foreach ($datas as $key => $elements) {
                $datas[$key] = $this->sortByDuration($elements);
            }
        }

        ksort($datas);

        foreach ($datas as $key => $elements) {
            foreach ($elements as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }
	
	protected function sortByDuration($data)
    {
        $datas = array();
        $items  = array();

        foreach ($data as $i => $item) {
            if (!isset($datas[$item->duration])) $datas[$item->duration] = array();
            $datas[$item->duration][] = $item;
        }

        ksort($datas);

        foreach ($datas as $key => $elements) {
            foreach ($elements as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }
	
	protected function time2string($time) {
		$all = round(($time) / 60);
		$d = floor ($all / 1440);
		$h = floor (($d * 1440) / 60);

		return "PT".$h."H0M0S";
	}
	
	protected function getDateRange()
    {
        $db    = JFactory::getDbo();
        $nd    = $db->getNullDate();
        $start = $nd;
        $end   = $nd;

        if (!$this->pid) {
			return array($start, $end);
		}

        $query = $db->getQuery(true);
        $query->select('created, start_date, end_date')
              ->from('#__pf_projects')
              ->where('id = ' . $this->pid);

        $db->setQuery($query);
        $dates = $db->loadObject();

        if (empty($dates)) {
			return array($start, $end);
		}
		
        if ($dates->start_date == $nd) {
            $dates->start_date = $dates->created;
        }

        if ($dates->end_date == $nd) {
            $dates->end_date = $this->getCalculatedProjectEndDate();
        }

        $start = $dates->start_date;
        $end   = $dates->end_date;

        return array($start, $end);
    }
	
		protected function getCalculatedProjectEndDate()
    {
        $db    = JFactory::getDbo();
        $nd    = $db->getNullDate();
        $query = $db->getQuery(true);

        // Get the latest task deadline
        $query->select('end_date')
              ->from('#__pf_tasks')
              ->where('project_id = ' . (int) $this->pid)
              ->where('state != -2')
              ->order('end_date DESC');

        $db->setQuery($query, 0, 1);
        $task_end = $db->loadResult();

        if (empty($task_end)) {
			$task_end = $nd;
		}
		
        // Get the latest milestone deadline
        $query->clear();
        $query->select('end_date')
              ->from('#__pf_milestones')
              ->where('project_id = ' . (int) $this->pid)
              ->where('state != -2')
              ->order('end_date DESC');

        $db->setQuery($query, 0, 1);
        $ms_end = $db->loadResult();

        if (empty($ms_end)) {
			$ms_end = $nd;
		}
		
        $task_time = ($task_end == $nd ? 0 : strtotime($task_end));
        $ms_time   = ($ms_end == $nd   ? 0 : strtotime($ms_end));

        if (!$task_time && !$ms_time) {
			return $nd;
		}
		
        return ($task_time > $ms_time ? $task_end : $ms_end);
    }
}
?>