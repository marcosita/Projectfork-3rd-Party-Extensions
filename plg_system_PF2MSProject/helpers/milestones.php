<?php
class PFtomsprojectHelperMilestones extends PFtomsprojectHelper
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

        $query->select('a.id, a.title, a.created, a.start_date, a.end_date')
				->from('#__pf_milestones AS a')
				->join('LEFT', '#__pf_projects AS p ON p.id = a.project_id')
				->where('a.project_id = ' . $this->pid)
				->where('a.state != -2');

        // Filter access
        if (!$user->authorise('core.admin')) {
            $query->where('a.access IN(' . implode(', ', $user->getAuthorisedViewLevels()) . ')');
        }

        $query->order('a.id ASC');

        $db->setQuery($query);
        $milestones = $db->loadObjectList();

        if (!is_array($milestones)) return array();

        // Prepare data
        $datas    = array();
        $keys       = JArrayHelper::getColumn($milestones, 'id');
        $completed = $this->getCompleted($keys);

        foreach ($milestones AS $i => $item) {
		
            // Check start date
            if ($item->start_date == $nd) {
				$item->start_date = $this->getStartDate($item->id);
			}
            // Check end date
            if ($item->end_date == $nd) {
				$item->end_date = $this->getEndDate($item->id);
			}
			
            // Skip item if no start or end is set
            if ($item->start_date == $nd || $item->end_date == $nd) {
				continue;
			}
            // Set completition state
            $item->complete = $completed[$item->id];

			// Floor the start and end date
            $item->start_time 	= floor(strtotime($item->start_date) / 86400) * 86400;
            $item->end_time   	= floor(strtotime($item->end_date) / 86400) * 86400;

            $start_date 		= new JDate($item->start_date);
            $end_date   		= new JDate($item->end_date);

            $item->start_date 	= $start_date->format('Y-m-d H:i:s');
            $item->end_date   	= $end_date->format('Y-m-d H:i:s');
           
            // Calculate the duration
            $item->duration 	= $item->end_time - $item->start_time;
            $duration 			= strtotime($item->end_date) - strtotime($item->start_date);
			$item->fduration 	= $this->time2string($duration);
		
            // Set item type
            $item->type 		= 'milestone';
		
            // Add item to time frame
            if (!isset($datas[$item->start_time])) {
                $datas[$item->start_time] = array();
            }

            $datas[$item->start_time][] = $item;
        }

        ksort($datas, SORT_NUMERIC);

        $mitems = array();

        foreach ($datas AS $key => $vals) {
            foreach ($vals AS $val) {
                $mitems[] = $val;
            }
        }

        return $mitems;
    }


    public function getStartDate($id)
    {
        $db    = JFactory::getDbo();
        $nd    = $db->getNullDate();
        $query = $db->getQuery(true);

        $query->select('start_date')
              ->from('#__pf_tasks')
              ->where('milestone_id = ' . (int) $id)
              ->where('state != -2')
              ->order('start_date ASC');

        $db->setQuery($query, 0, 1);
        $date = $db->loadResult();

        // Fallback: use project start_date if the task has no start set
        if (empty($date) || $date == $nd) {
			$dates = $this->getDateRange($id);
            $date = $date[0];
        }

        return $date;
    }

    public function getEndDate($id, $date = null)
    {
        $db    = JFactory::getDbo();
        $nd    = $db->getNullDate();
        $query = $db->getQuery(true);

        $query->select('end_date')
              ->from('#__pf_tasks')
              ->where('milestone_id = ' . (int) $id)
              ->where('state != -2')
              ->order('end_date DESC');

        $db->setQuery($query, 0, 1);
        $date = $db->loadResult();

        // Fallback: use project end_date if task has no date set
        if (empty($date) || $date == $nd) {
            $dates = $this->getDateRange($id);
			$date = $date[1];
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
        $query->select('milestone_id, COUNT(id) AS complete')
              ->from('#__pf_tasks')
              ->where('milestone_id IN(' . implode(',', $keys) . ') ')
              ->where('state != -2')
              ->where('complete = 1')
              ->group('milestone_id')
              ->order('id ASC');

        $db->setQuery($query);
        $completed = $db->loadAssocList('milestone_id', 'complete');

        // Count total tasks
        $query->clear();
        $query->select('milestone_id, COUNT(id) AS total')
              ->from('#__pf_tasks')
              ->where('milestone_id IN(' . implode(',', $keys) . ') ')
              ->where('state != -2')
              ->group('milestone_id')
              ->order('id ASC');

        $db->setQuery($query);
        $total = $db->loadAssocList('milestone_id', 'total');

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