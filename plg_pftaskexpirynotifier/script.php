<?php
/**
 * @package      Projectfork Task Expiry Notifier
 *
 * @author       Kon Angelopoulos (ANGEK DESIGN)
 * @copyright    Copyright (C) 2014 ANGEK DESIGN. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();

jimport('joomla.filesystem.file');

class plgSystemPftaskexpirynotifierInstallerScript
{
    /**
     * Called before any type of action
     *
     * @param     string              $route      Which action is happening (install|uninstall|discover_install)
     * @param     jadapterinstance    $adapter    The object responsible for running this script
     *
     * @return    boolean                         True on success
     */
    public function preflight($route, JAdapterInstance $adapter)
    {
        if (strtolower($route) == 'install' || strtolower($route) == 'update') {
            if (!defined('PF_LIBRARY')) {
                jimport('projectfork.library');
            }

            $name = htmlspecialchars($adapter->get('manifest')->name, ENT_QUOTES, 'UTF-8');

            // Check if the library is installed
            if (!defined('PF_LIBRARY')) {
                JError::raiseWarning(1, JText::_('This extension (' . $name . ') requires the Projectfork Library to be installed!'));
                return false;
            }

            // Check if the projectfork component is installed
            if (!PFApplicationHelper::exists('com_projectfork')) {
                JError::raiseWarning(1, JText::_('This extension (' . $name . ') requires the Projectfork Component to be installed!'));
                return false;
            }
        }
		
		if (strtolower($route) == 'uninstall') {
			$dest 	= JPATH_SITE . "/plugins/system/pftaskexpirynotifier/cron_pf_notifier.php";
			$src 	= JPATH_SITE . "/cli/cron_pf_notifier.php";
			
			//move the file back into its plugin folder otherwise the installer will complain when uninstalling.
			JFile::move($src, $dest);
		}

        return true;
    }


    /**
     * Called after any type of action
     *
     * @param     string              $route      Which action is happening (install|uninstall|discover_install)
     * @param     jadapterinstance    $adapter    The object responsible for running this script
     *
     * @return    boolean                         True on success
     */
    public function postflight($route, JAdapterInstance $adapter)
    {		
		$src 	= JPATH_SITE . "/plugins/system/pftaskexpirynotifier/cron_pf_notifier.php";
		$dest 	= JPATH_SITE . "/cli/cron_pf_notifier.php";
		
        if (strtolower($route) == 'install') {
            // Get the XML manifest data
            $manifest = $adapter->get('manifest');

            // Get plugin published state
            $name  = $manifest->name;
            $state = (isset($manifest->published) ? (int) $manifest->published : 0);

            if (!$state) {
				$state = 1;
                $this->_publishPlugin($name, $state);
            }
			//let's move the cron file into Joomla's cli directory.
			JFile::move($src, $dest);		
        }		

        return true;
    }
	
	private function _publishPlugin($name, $state = 0)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Get the plugin id
		$query->select('extension_id')
				->from('#__extensions')
				->where('name = ' . $db->quote($name))
				->where('type = ' . $db->quote('plugin'));

		$db->setQuery((string) $query);
		$id = (int) $db->loadResult();

		if (!$id) return false;

		// Update params
		$query->clear();
		$query->update('#__extensions')
				->set('enabled = ' . $db->quote($state))
				->where('extension_id = ' . $db->quote($id));

		$db->setQuery((string) $query);
		$db->execute();

		return true;
	}	
}
