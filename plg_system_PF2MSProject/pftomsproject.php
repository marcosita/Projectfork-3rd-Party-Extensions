<?php
/**
 * @package      Projectfork Export to MS Project
 *
 * @author       Kon Angelopoulos (ANGEK DESIGN)
 * @copyright    Copyright (C) 2013 ANGEK DESIGN. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */
	defined('_JEXEC') or die;

	jimport( 'joomla.plugin.plugin' );

	require_once dirname(__FILE__) . '/helper.php';
	
	class plgSystemPFToMSProject extends JPlugin
	{
		protected $durationFormat;
		
		public function onAfterInitialise()
		{
			$this->duration_format		= (int) $this->params->get('msproject_duration');
		}
		
		public function onAfterRoute()
		{
			$option 	= JRequest::getVar('option');
			$view 		= JRequest::getVar('view');
			$filter		= JRequest::getVar('filter_project');
			
			$js = "jQuery(function(){\n";
			$js .= "\tjQuery('.export').click(function(event){\n";
			$js .= "\t\tevent.preventDefault();\n";
			$js .= "\t});\n";
			$js .= "});\n\n";
			$js .= "function MSExport(id){\n";
			$js .= "\t\talert('Export Project ' + id);\n";
			$js .= "}\n";
			
			if ($view == 'projects' && $option == 'com_pfprojects'){
				JFactory::getDocument()->addScriptDeclaration($js);
			}
		}
		
		public function onBeforeRender()
		{
			$option 	= JRequest::getVar('option');
			$view 		= JRequest::getVar('view');
			$action 	= JRequest::getVar('action');			
		}
		
		private function _getTagText($html){
			JPlugin::loadLanguage('plg_system_pftomsproject', JPATH_ADMINISTRATOR);
			
			$str2 	= null;
			$r 		= null;
			$p1 	= '%<dd class="owner-data">.*?</dd>%si';
			
			$str2 	= ' $0 
						<div class="btn-toolbar btn-toolbar-top">
							<div class="btn-group">
								<button class="btn export">
									<i class="cus-doc-pdf"></i> '.JText::_('PLG_PFTOMSPROJECT_LABEL_EXPORT').'
								</button>
								<button class="btn dropdown-toggle" data-toggle="dropdown">
									<span class="caret"></span>
								</button>
								<ul class="dropdown-menu">
									<li>
										<a href="index.php?option=com_pfprojects&view=projects&action=export&cid="><i class="cus-doc-excel-table"></i> '.JText::_('PLG_PFTOMSPROJECT_LABEL_MS2013').'</a>
									</li>									
								</ul>
							</div>
						</div>';
						
			$r 		= preg_replace($p1, $str2, $html);

			return $r;
		}
		
		private function _getProjectIds($subject)
		{
			preg_match_all('%<input type="checkbox" id=.*? name="cid\[\]" value="(?P<m2>.*?)".*?/>%si', $subject, $result, PREG_PATTERN_ORDER);
			$results = $result[1];
		
			return $results;
		}
		
		private function _addProjectIds($subject, $cids)
		{
			$r = $subject;
			
			foreach ($cids as $cid){
				$r = preg_replace('/view=projects&action=export&cid="/si','view=projects&action=export&cid='.$cid.'"',$r, 1);				
			}
			
			return $r;
		}
		
		public function onAfterRender()
		{
			$option 	= JRequest::getVar('option');
			$view 		= JRequest::getVar('view');
			$action 	= JRequest::getVar('action',null);
			$cid		= (int)JRequest::getVar('cid',null);
						
			$results	= null;
			$cids		= array();
			
			if ($option == 'com_pfprojects'){
				if ($view == 'projects'){
					$html		= JResponse::getBody();
				
					$cids 		= $this->_getProjectIds($html);
					$replaced 	= $this->_getTagText($html);
					$results	= $this->_addProjectIds($replaced, $cids);		

					if ($action && $action == 'export'){
						/* TODO: add to helper class: start: */
						if ($cid){
							$pcfg = PFApplicationHelper::getProjectParams();
						
							//$curr_code = $pcfg->get('currency_code');
							//$curr_sign = $pcfg->get('currency_sign');
							//$curr_del  = $pcfg->get('decimal_delimiter');
							//$curr_pos  = $pcfg->get('currency_position');
						/* TODO: add to helper class: end: */
							$helper = new PFtomsprojectHelper($cid);
							$helper->getProjectInfo();
							$helper->exportXML($this->duration_format);
						}
					}
				}
			}
			
			if ($results){					
				JResponse::setBody($results);
			}
		}
	}
?>