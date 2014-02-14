<?php
class PFtomsprojectHelperLists extends PFtomsprojectHelper
{
    public function getItems()
    {
        if (!$this->pid) {
			return array();
		}
		
        $user  = JFactory::getUser();
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);
        $nd    = $db->getNullDate();

        $query->select('a.id, a.milestone_id, a.title, a.created')
              ->select('m.start_date AS m_start, m.end_date AS m_end')
              ->from('#__pf_task_lists AS a')
              ->join('LEFT', '#__pf_projects AS p ON p.id = a.project_id')
              ->join('LEFT', '#__pf_milestones AS m ON m.id = a.milestone_id')
              ->where('a.project_id = ' . $this->pid)
              ->where('a.state != -2');

        // Filter access
        if (!$user->authorise('core.admin')) {
            $query->where('a.access IN(' . implode(', ', $user->getAuthorisedViewLevels()) . ')');
        }

        $query->order('a.id ASC');

        $db->setQuery($query);
        $data = $db->loadObjectList();

        if (!is_array($data)) {
			return array();
		}
		
        // Prepare data
        $datas		= array();
        $keys       = JArrayHelper::getColumn($data, 'id');
        $completed 	= $this->getCompleted($keys);

        foreach ($data as $i => $item) {
		
            // Set dates
            $item->start_date = $this->getStartDate($item->id, $item->milestone_id, $item->m_start);
            $item->end_date   = $this->getEndDate($item->id, $item->milestone_id, $item->m_end);

            // Skip item if no start or end is set
            if ($item->start_date == $nd || $item->end_date == $nd) {
				continue;
			}
			
            $item->complete 	= $completed[$item->id];

            $item->start_time 	= floor(strtotime($item->start_date) / 86400) * 86400;
            $item->end_time   	= floor(strtotime($item->end_date) / 86400) * 86400;

            $start_date 		= new JDate($item->start_date);
            $end_date   		= new JDate($item->end_date);
			
            $item->start_date 	= $start_date->format('Y-m-d H:i:s');
            $item->end_date   	= $end_date->format('Y-m-d H:i:s');
           
            $item->duration 	= $item->end_time - $item->start_time;
            $duration 			= strtotime($item->end_date) - strtotime($item->start_date);
			$item->fduration 	= $this->time2string($duration);
			
            $item->type 		= 'tasklist';

            if (!isset($datas[$item->start_time])) {
                $datas[$item->start_time] = array();
            }

            $datas[$item->start_time][] = $item;
        }

        ksort($datas, SORT_NUMERIC);

        $items = array();

        foreach ($datas as $key => $vals) {
            foreach ($vals as $val) {
                $items[] = $val;
            }
        }

        return $items;
    }

    public function getStartDate($id, $ms = 0, $ms_date = null)
    {
        $db    = JFactory::getDbo();
        $nd    = $db->getNullDate();
        $query = $db->getQuery(true);

        // Get the start date from the task
        $query->select('a.id, a.start_date, a.milestone_id')
              ->select('m.start_date AS m_start')
              ->from('#__pf_tasks AS a')
              ->join('left', '#__pf_milestones AS m ON m.id = a.milestone_id')
              ->where('a.list_id = ' . (int) $id)
              ->where('a.state != -2')
              ->order('a.start_date, a.id ASC');

        $db->setQuery($query, 0, 1);
        $task = $db->loadObject();

        if (empty($task)) {
            if (!empty($ms_date) && $ms_date != $nd) {
                $date 	= $ms_date;
            }
            elseif ($ms) {
				$mhelper 	= new modPFganttHelperMilestones($this->pid);
                $date 		= $mhelper->getStartDate($ms);
            }
            else {
				$dates 		= $this->getDateRange($id);
                $date 		= $dates[0];
            }
        }
        elseif ($task->start_date == $nd) {
			$thelper 	= new modPFganttHelperTasks($this->pid);
            $date 		= $thelper->getStartDate($task->id, $task->milestone_id, $task->m_start);
        }
        else {
            $date 		= $task->start_date;
        }

        return $date;
    }

    public function getEndDate($id, $ms = 0, $ms_date = null)
    {
        $db    = JFactory::getDbo();
        $nd    = $db->getNullDate();
        $query = $db->getQuery(true);

        // Get the end date from the task
        $query->select('a.id, a.end_date, a.milestone_id')
              ->select('m.end_date AS m_end')
              ->from('#__pf_tasks AS a')
              ->join('left', '#__pf_milestones AS m ON m.id = a.milestone_id')
              ->where('a.list_id = ' . (int) $id)
              ->where('a.state != -2')
              ->order('a.end_date DESC, a.id ASC');

        $db->setQuery($query, 0, 1);
        $task = $db->loadObject();

        if (empty($task)) {
            if (!empty($ms_date) && $ms_date != $nd) {
                $date = $ms_date;
            }
            elseif ($ms) {
				$mhelper 	= modPFganttHelperMilestones($this->pid);
                $date 		= $mhelper->getEndDate($ms);
            }
            else {
				$dates 		= $this->getDateRange($id);
                $date 		= $dates[1];
            }
        }
        elseif ($task->end_date == $nd) {
			$thelper 	= new modPFganttHelperTasks($this->pid);
            $date 		= $thelper->getEndDate($task->id, $task->milestone_id, $task->m_end);
        }
        else {
            $date 		= $task->end_date;
        }

        return $date;
    }

    protected function getCompleted($keys)
    {
        if (!is_array($keys) || count($keys) == 0) {
            return array();
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Count completed tasks
        $query->select('list_id, COUNT(id) AS complete')
              ->from('#__pf_tasks')
              ->where('list_id IN(' . implode(',', $keys) . ') ')
              ->where('state != -2')
              ->where('complete = 1')
              ->group('list_id')
              ->order('id ASC');

        $db->setQuery($query);
        $completed = $db->loadAssocList('list_id', 'complete');

        // Count total tasks
        $query->clear();
        $query->select('list_id, COUNT(id) AS total')
              ->from('#__pf_tasks')
              ->where('list_id IN(' . implode(',', $keys) . ') ')
              ->where('state != -2')
              ->group('list_id')
              ->order('id ASC');

        $db->setQuery($query);
        $total = $db->loadAssocList('list_id', 'total');

        $items = array();

        foreach ($keys as $key) {
            $count_complete = (int) (isset($completed[$key]) ? $completed[$key] : 0);
            $count_total    = (int) (isset($total[$key])     ? $total[$key]   : 0);

            if (!$count_total || $count_complete == $count_total) {
                $items[$key] = true;
            }
            else {
                $items[$key] = false;
            }
        }

        return $items;
    }
}
?>