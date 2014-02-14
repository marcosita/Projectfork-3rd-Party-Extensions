<?php
class PFtomsprojectHelperTasks extends PFtomsprojectHelper
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

        $query->select('a.id, a.milestone_id, a.list_id, a.title')
              ->select('a.created, a.complete, a.start_date, a.end_date')
              ->select('l.milestone_id AS l_ms')
              ->select('m.start_date AS m_start, m.end_date AS m_end')
              ->from('#__pf_tasks AS a')
              ->join('LEFT', '#__pf_task_lists AS l ON l.id = a.list_id')
              ->join('LEFT', '#__pf_milestones AS m ON m.id = a.milestone_id')
              ->join('LEFT', '#__pf_projects AS p ON p.id = a.project_id')
              ->where('a.project_id = ' . $this->pid)
              ->where('a.state != -2');

        // Filter access
        if (!$user->authorise('core.admin')) {
            $query->where('a.access IN(' . implode(', ', $user->getAuthorisedViewLevels()) . ')');
        }

        $query->order('a.id ASC');

        $db->setQuery($query);
        $data = $db->loadObjectList();

        if (!is_array($data)) return array();

        $datas       = array();
        $dependencies = $this->getDependencies();
        $children     = $dependencies['children'];
        $parents      = $dependencies['parents'];

        $pks   = JArrayHelper::getColumn($data, 'id');

        foreach ($data AS $i => $item) {
		
            // Check start date
            if ($item->start_date == $nd) {
                $item->start_date = $this->getStartDate($item->id, $item->milestone_id, $item->m_start);
            }

            // Check end date
            if ($item->end_date == $nd) {
                $item->end_date = $this->getEndDate($item->id, $item->milestone_id, $item->m_end);
            }

            // Skip item if no start or end is set
            if ($item->start_date == $nd || $item->end_date == $nd) {
                continue;
            }

            // dependencies
            $item->children 		= (isset($children[$item->id]) ? $children[$item->id] : array());
            $item->parents  		= (isset($parents[$item->id])  ? $parents[$item->id]  : array());

            $item->start_time 		= floor(strtotime($item->start_date) / 86400) * 86400;
            $item->end_time   		= floor(strtotime($item->end_date) / 86400) * 86400;

            $start_date 			= new JDate($item->start_date);
            $end_date   			= new JDate($item->end_date);

            $item->start_date 		= $start_date->format('Y-m-d H:i:s');
            $item->end_date   		= $end_date->format('Y-m-d H:i:s');
           
            $item->duration 		= $item->end_time - $item->start_time;
            $duration 				= strtotime($item->end_date) - strtotime($item->start_date);
			$item->fduration 		= $this->time2string($duration);

            $item->type 			= 'task';

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
        $db 	= JFactory::getDbo();
        $nd 	= $db->getNullDate();

        if (!empty($ms_date) && $ms_date != $nd) {
            return $ms_date;
        }
        elseif ($ms) {
			$mhelper = new modPFganttHelperMilestones($this->pid);
			$date = $mhelper->getStartDate($ms);
        }
        else {
			$dates = $this->getDateRange($id);
            $date = $dates[0];
        }
        return $date;
    }

    public function getEndDate($id, $ms = 0, $ms_date = null)
    {
		$db 	= JFactory::getDbo();
        $nd 	= $db->getNullDate();

        if (!empty($ms_date) && $ms_date != $nd) {
            return $ms_date;
        }
        elseif ($ms) {
			$mhelper = new modPFganttHelperMilestones($this->pid);
            $date = $mhelper->getEndDate($ms);
        }
        else {
			$dates = $this->getDateRange($id);
            $date = $dates[1];
        }

        return $date;
    }

    protected function getDependencies()
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->clear()
              ->select('task_id, parent_id')
              ->from('#__pf_ref_tasks')
              ->where('project_id = ' . (int) $this->pid);

        $db->setQuery($query);
        $items = $db->loadObjectList();

        if (!is_array($items)) $items = array();

        $map_parents  = array();
        $map_children = array();

        foreach ($items AS $item)
        {
            if (!isset($map_parents[$item->task_id])) {
                $map_parents[$item->task_id] = array();
            }

            if (!isset($map_children[$item->parent_id])) {
                $map_children[$item->parent_id] = array();
            }

            $map_parents[$item->task_id][]    = $item->parent_id;
            $map_children[$item->parent_id][] = $item->task_id;
        }

        return array('parents' => $map_parents, 'children' => $map_children);
    }
}
?>