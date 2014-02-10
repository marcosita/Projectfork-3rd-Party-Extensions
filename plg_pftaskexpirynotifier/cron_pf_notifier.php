<?php
/**
 * @package      Projectfork Task Expiry Notifier
 *
 * @author       Kon Angelopoulos (ANGEK DESIGN)
 * @copyright    Copyright (C) 2014 ANGEK DESIGN. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

// Initialize Joomla framework
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php')) {
    require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', dirname(__DIR__));
    require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';


/**
 * Cron job 
 *
 * @since      PF 4.2
 */
 class PFTaskExpiryNotifierCron extends JApplicationCli
{
	private $mailfrom;
	private $fromname;
	private $subject;
	private $mailcc;
	private $mailbcc;
	private $message;
	private	$alert;
	private	$alertinterval;
	private	$alertcondition;
	private	$pname;
	private	$mname;
	private	$tname;
	private	$priority;
	private	$deadline;		
	private	$overduealert;
	private	$overduetasks;
	private	$duein;
	
	public function doExecute()
	{
		$db       	= JFactory::getDbo();
		$config 	= JFactory::getConfig();
		$lang 		= JFactory::getLanguage();
		$lang->load('plg_system_pftaskexpirynotifier', JPATH_ADMINISTRATOR, 'en-GB', true);
		
		$query 		= $db->getQuery(true);

		$query->select('params')
				->from('#__extensions')
				->where('element = ' . $db->quote('pftaskexpirynotifier'))
				->where('type = ' . $db->quote('plugin'));

		$db->setQuery($query);
		$plg_params = $db->loadResult();

		$params = new JRegistry();
		$params->loadString($plg_params);
		
		$query->clear();
		
		$this->mailfrom 		= $params->get('cron_mail_from') ? $params->get('cron_mail_from') : $config->get( 'mailfrom' );
		$this->fromname			= $params->get('cron_from_name') ? $params->get('cron_from_name') : $config->get( 'fromname' );
		$this->subject 			= $params->get('cron_task_email_subject');
		$this->mailcc 			= $params->get('cron_mail_cc');
		$this->mailbcc 			= $params->get('cron_mail_bcc');
		
		$this->message 			= $params->get('cron_task_email_message');
		$this->alert 			= $params->get('cron_default_alert');
		$this->alertinterval 	= $params->get('cron_default_interval');
		$this->alertcondition 	= $params->get('cron_default_condition');
		$this->pname 			= $params->get('task_project_name');
		$this->mname 			= $params->get('task_milestone_name');
		$this->tname 			= $params->get('task_tasklist_name');
		$this->priority 		= $params->get('task_priority');
		$this->deadline 		= $params->get('task_deadline');		
		$this->overduealert 	= $params->get('cron_overdue_alert');
		$this->overduetasks 	= $params->get('include_overdue_tasks');
		$this->duein			= $params->get('task_due_in');
				
		$cols = 0;
		
		if ($this->priority == 1) { 
			$cols++; 
		}

		if ($this->deadline == 1) { 
			$cols++; 
		}
		
		if ($this->duein == 1) {
			$cols++;
		}
		// and one more for the task title :)
		$cols++;

		//initialise some vars
		$filter 		= null;
		$headers 		= null;
		$messagehtml	= null;
		
		//begin by retrieving all the user info for those who have assigned tasks.
		$query = $db->getQuery(true);
		$query->select('u.id, u.name, u.username, u.email')
				->from('#__users as u')
				->join('inner','#__pf_ref_users AS tu ON tu.user_id = u.id')
				->where('tu.item_type = "com_pftasks.task"')
				->group('tu.user_id');
				
		$db->setQuery($query);
		$users = $db->loadObjectList();
		
		$query->clear();		

		if ($users) {
			foreach ($users as $u) {
				$p = array();
				
				$query = $db->getQuery(true);
				
				$query->select('distinct(t.project_id)')
						->from('#__pf_tasks as t')
						->join('inner','#__pf_ref_users as u on u.item_id = t.id')
						->where('u.user_id = "'.$u->id.'"');
				
				$db->setQuery($query);
				$projects = $db->loadColumn();

				$query->clear();
				
				$projects = implode(',', $projects);

				$condition = $this->_MapCondition();
				
				$filter .= "\n AND tu.user_id = ".$db->Quote($u->id);
				$filter .= "\n AND t.complete = 0";
				$filter .= $condition;
				
				$query = "SELECT t.*, p.title as ptitle,m.title as mtitle, tl.title as ltitle, tu.user_id as user, datediff(t.end_date, CURDATE()) as due
					FROM #__pf_tasks as t
					LEFT JOIN #__pf_ref_users as tu on tu.item_id = t.id
					RIGHT JOIN #__pf_projects as p on p.id = t.project_id AND p.state = 1
					LEFT JOIN #__pf_milestones as m on m.id = t.milestone_id
					LEFT JOIN #__pf_task_lists as tl on tl.id = t.list_id
					WHERE p.id IN ($projects)
					AND t.id = t.id
					AND p.id = t.project_id
					$filter					
					GROUP BY t.id
					ORDER BY t.project_id ASC, t.id ASC, t.milestone_id ASC, tl.id ASC";
				
				$db->setQuery($query);
				$task_details = $db->loadObjectList();
				
				if ($task_details) {
					foreach ($task_details as $task){
						$p[$task->project_id][] = (array) $task;
					}
				}
				
				if (!empty($p)){
					$prevmile 	= null;
					$prevtlist 	= null;
					$preproj 	= null;
					$counter 	= 0;
					
					$html 	= "<table width=\"90%\" border=\"1\" align=\"center\" cellpadding=\"3\" cellspacing=\"3\">\n";

					foreach($p as $a=>$b){
						foreach ($b as $c){
							$c['priority'] = $this->_MapPriority($c['priority']);
							if ($this->pname == 1){
								if ($preproj != $c['project_id']){
									$html .= "<tr>"
											. "\n<td colspan=\"".$cols."\"><div align=\"center\">".JText::_('CRON_PROJECT_LABEL')." ".$c['ptitle']."</div></td>"
											. "\n</tr>";									
								}
								$preproj = $c['project_id'];
							}
							if ($counter == 0) {
								$html .= "\n<tr>";
								$html .= "\n<td width=\"60%\">".JText::_('CRON_TASK_LABEL')."</td>";
								if ($this->priority == 1){
									$html .= "\n<td>".JText::_('CRON_PRIO_LABEL')."</td>";
								}
								
								if ($this->deadline == 1){
									$html .= "\n<td>".JText::_('CRON_DEADLINE_LABEL')."</td>";
								}	

								if ($this->duein == 1){
									$html .="\n<td>".JText::_('CRON_DUEIN_LABEL')."</td>";
								}
								$html .="</tr>";
							}
							
							if ($this->mname == 1) {
								if ($prevmile != $c['milestone_id']){
									if ($c['milestone_id']) {
										$html .= "<tr>"
											. "\n<td colspan=\"".$cols."\"><div align=\"left\">".JText::_('CRON_MILESTONE_LABEL')." ".$c['mtitle']."</div></td>"
											. "\n</tr>";
									}
								}
								$prevmile = $c['milestone_id'];
							}
							if ($this->tname == 1) {
								if ($prevtlist != $c['list_id']){
									if ($c['list_id']) {
										$html .="<tr>"
											."\n<td colspan=\"".$cols."\"><div align=\"left\">".JText::_('CRON_TASKLIST_LABEL')." ".$c['ltitle']."</div></td>"
											. "\n</tr>";
										}
								}
								$prevtlist = $c['list_id'];
							}
							
							$html .= "<tr>";
							$html .= "\n<td width=\"60%\">".$c['title']."</td>";
							if ($this->priority == 1){
								$html .= "\n<td>".$c['priority']."</td>";
							}
							
							if ($this->deadline == 1){
								$html .= "\n<td>".$c['end_date']."</td>";
							}
							
							if ($this->duein == 1){
								$interval = $this->_MapInterval($c['due']);
								
								$html .= "\n<td>".$c['due']." ".$interval."</td>";
							}
							$counter++;
							$html .= "\n</tr>";
						}
					}
					$html .= "\n</table>";
					
					$messagehtml = $this->message."\n\n".$html;
					
					//echo $messagehtml;
					
					$this->_SendCronEmail($messagehtml, $u->email);			
				}
				$html 	= "";
				$filter = "";
			}
		}
	}

	private function _MapInterval($due)
	{
		$interval = strtolower($this->alertinterval);
		$interval = ucfirst($interval);
		
		if (abs(intval($due)) > 1 || $due == 0) {
			$s = 'CRON_'.$this->alertinterval.'_PLURAL';
			$interval = JText::_($s);
		}
		return $interval;
	}
	
	private function _MapCondition()
	{
		$prefix = null;
		$postfix = null;
		$inc = null;
		
		if ($this->overduetasks == 1){
			$prefix = "(";
			$postfix = ")";
			
			if ($this->overduealert == 0){
				$this->overduealert = 5;
			}
			
			$inc = "\n OR t.end_date BETWEEN DATE_ADD(CURDATE(), INTERVAL -".$this->overduealert." DAY) AND CURDATE()";
		}
		
		switch ($this->alertcondition){
			case 'less':
				$filter = "\n AND $prefix t.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ".$this->alert." ".$this->alertinterval.")";
			break;
			case 'equal':
				$filter = "\n AND $prefix t.end_date = DATE_ADD(CURDATE(), INTERVAL ".$this->alert." ".$this->alertinterval.")";
			break;
		}
		
		$filter = $filter.$inc.$postfix;
		
		return $filter;
	}
	
	private function _MapPriority($taskpriority)
	{
		switch($taskpriority){
			case 0:
			default:
				$tp = "Not Set";
			break;
			case 1:
				$tp = "Very Low";
			break;
			case 2:
				$tp = "Low";
			break;
			case 3:
				$tp = "Medium";
			break;
			case 4:
				$tp = "High";
			break;
			case 5:
				$tp = "Very High";
			break;
		}
		
		return $tp;
	}
	
	private function _SendCronEmail($theMessage, $theUser)
	{
		$mailer = JFactory::getMailer();

		$sender = array( 
				$this->mailfrom,
				$this->fromname
			);
		
		$mailer->setSender($sender);
		
		if (!empty($this->mailcc)){
			$cc = array($this->mailcc);
			$mailer->addCC($cc);
		}

		if (!empty($this->mailbcc)) {
			$bcc = array($this->mailbcc);
			$mailer->addBCC($bcc);
		}
		$mailer->addRecipient($theUser);
		$mailer->setSubject($this->subject);
		$mailer->isHTML(true);
		$mailer->setBody($theMessage);

		$mailer->Send();

	}
}

JApplicationCli::getInstance('PFTaskExpiryNotifierCron')->execute();