<?php
/**
* @package      mod_pf_gantt
*
* @author       Tobias Kuhn (eaxs)
* @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
* @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
**/

defined('_JEXEC') or die();

$count = count($items);
$limit = (int) $params->get('limit', 25);

if ($count < $limit || $limit == 0) {
    $limit = $count;
}

// Bail out if we have nothing to display
if (!$count) {
    ?>
    <div class="alert"><?php echo JText::_('MOD_PF_GANTT_EMPTY'); ?></div>
    <?php
    return;
}

$months = array(
    JText::_('JANUARY'), JText::_('FEBRUARY'), JText::_('MARCH'),
    JText::_('APRIL'), JText::_('MAY'), JText::_('JUNE'),
    JText::_('JULY'), JText::_('AUGUST'), JText::_('SEPTEMBER'),
    JText::_('OCTOBER'), JText::_('NOVEMBER'), JText::_('DECEMBER')
);


modPFganttHelper::loadMedia();


$css = array();
$css[] = '#mod_pf_gantt_' . $module->id . ' .fn-gantt .leftPanel';
$css[] = '{';
$css[] = '    width: ' . (int) $params->get('column_width', 225) . 'px;';
$css[] = '}';
$css[] = '#mod_pf_gantt_' . $module->id . ' .fn-gantt .leftPanel .name';
$css[] = '{';
$css[] = '    width: ' . ((int) $params->get('column_width', 225) - 50) . 'px;';
$css[] = '}';
$css[] = '#mod_pf_gantt_' . $module->id . ' .fn-gantt .leftPanel .name .fn-label';
$css[] = '{';
$css[] = '    width: ' . ((int) $params->get('column_width', 225) - 50) . 'px;';
$css[] = '}';
$css[] = '#mod_pf_gantt_' . $module->id . ' .fn-gantt .navigate .nav-slider-content';
$css[] = '{';
$css[] = '    width: ' . (int) $params->get('slider_width', 300) . 'px;';
$css[] = '}';
$css[] = '#mod_pf_gantt_' . $module->id . ' .fn-gantt .navigate .nav-slider-bar';
$css[] = '{';
$css[] = '    width: ' . ((int) $params->get('slider_width', 300) - 5) . 'px;';
$css[] = '}';



if (in_array($params->get('task_dependencies'), array('0', '2'))) {
    $css[] = '.fn-gantt .dep';
    $css[] = '{';
    $css[] = '    display: none;';
    $css[] = '}';
}

$js = array();
$js[] = 'var ganttFT' . $module->id . ' = false;';
$js[] = 'jQuery(document).ready(function()';
$js[] = '{';
$js[] = '    jQuery("#mod_pf_gantt_' . $module->id . '").gantt(';
$js[] = '    {';
$js[] = '        source: ' . json_encode($items) . ',';
$js[] = '        scale: "days",';
$js[] = '        minScale: "days",';
$js[] = '        maxScale: "weeks",';
$js[] = '        months: ' . json_encode($months) . ',';
$js[] = '        itemsPerPage: ' . $limit . ', ';
$js[] = '        navigate: "scroll",';
$js[] = '        scrollOnDrag: true,';
$js[] = '        scrollOnWheel: true,';
$js[] = '        scrollToToday: true,';
$js[] = '        depHover: ' . ($params->get('task_dependencies') == '2' ? 'true' : 'false') . ',';
$js[] = '        onRender: function(element, core)';
$js[] = '        {';
$js[] = '            jQuery(".gantt-bs-tt").tooltip();';
$js[] = '            jQuery(".fn-popover").popover({placement: "top"});';
$js[] = '            if (!ganttFT' . $module->id . ') {';
$js[] = '                core.navigateTo(element, "now");';
$js[] = '                setTimeout(function() {core.synchronizeScroller(element);}, 500);';
$js[] = '                ganttFT' . $module->id . ' = true;';
$js[] = '            }';
$js[] = '        }';
$js[] = '    })';
$js[] = '});';

$print = array();

$print[] = 'jQuery(document).ready(function(){';
$print[] = '        jQuery("div.b1").click(function(){';
$print[] = '            var print = "div.PrintArea";';
$print[] = '            jQuery( print ).printArea();';
$print[] = '        });';
$print[] = '    });';


$jsoeverride = JURI::base() . 'templates/' . JFactory::getApplication()->getTemplate().'/html/'.$module->module.'/js/jquery.PrintArea.js';

JFactory::getDocument()->addScript($jsoeverride);
JFactory::getDocument()->addScriptDeclaration(implode("\n", $js));
JFactory::getDocument()->addScriptDeclaration(implode("\n", $print));
JFactory::getDocument()->addStyleDeclaration(implode("\n", $css));
?>
<div class="btn button b1">Print</div>
<div class="PrintArea" id="mod_pf_gantt_<?php echo $module->id; ?>">

</div>
