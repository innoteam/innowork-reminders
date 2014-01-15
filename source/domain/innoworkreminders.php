<?php

// ----- Initialization -----
//

require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/widgets/WuiWidget.php');
require_once('innomatic/wui/widgets/WuiContainerWidget.php');
require_once('innomatic/wui/dispatch/WuiEventsCall.php');
require_once('innomatic/wui/dispatch/WuiEvent.php');
require_once('innomatic/wui/dispatch/WuiEventRawData.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('innomatic/locale/LocaleCatalog.php');
require_once('innomatic/locale/LocaleCountry.php'); 
require_once('innowork/reminders/InnoworkReminder.php');

global $gXml_def, $gLocale, $gPage_title, $gPage_status, $priorities;

require_once('innowork/core/InnoworkCore.php');
$gInnowork_core = InnoworkCore::instance('innoworkcore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
$gLocale = new LocaleCatalog('innowork-reminders::reminder_main', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());

$gWui = Wui::instance('wui');
$gWui->LoadWidget('xml');
$gWui->LoadWidget('innomaticpage');
$gWui->LoadWidget('innomatictoolbar');

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr('reminder.title');
$gCore_toolbars = $gInnowork_core->GetMainToolBar();
$gToolbars['reminder'] = array('reminderlist' => array('label' => $gLocale->getStr('reminderlist.toolbar'), 'themeimage' => 'listdetailed', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('done' => 'false'))))), 'donereminderlist' => array('label' => $gLocale->getStr('donereminderlist.toolbar'), 'themeimage' => 'listdetailed2', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('done' => 'true'))))), 'newreminder' => array('label' => $gLocale->getStr('newreminder.toolbar'), 'themeimage' => 'mathadd', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'newreminder', '')))));

$priorities[1] = '#ffe5e5';
$priorities[2] = '#ffcbcb';
$priorities[3] = '#ffb2b2';
$priorities[4] = '#ff9898';
$priorities[5] = '#ff7f7f';
$priorities[6] = '#ff6565';
$priorities[7] = '#ff4c4c';
$priorities[8] = '#ff3232';
$priorities[9] = '#ff1919';
$priorities[10] = '#ff0000';

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher('action');

$gAction_disp->addEvent('newreminder', 'action_newreminder');
function action_newreminder($eventData) {
    global $gLocale, $gPage_status;

    $innowork_reminder = new InnoworkReminder(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

    if ($innowork_reminder->Create($eventData, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId())) {
        $GLOBALS['innowork-reminder']['newreminderid'] = $innowork_reminder->mItemId;
        $gPage_status = $gLocale->getStr('reminder_added.status');
    }
    else
        $gPage_status = $gLocale->getStr('reminder_not_added.status');
}

$gAction_disp->addEvent('editreminder', 'action_editreminder');
function action_editreminder($eventData) {
    global $gLocale, $gPage_status;

    $innowork_reminder = new InnoworkReminder(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($innowork_reminder->Edit($eventData, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()))
        $gPage_status = $gLocale->getStr('reminder_updated.status');
    else
        $gPage_status = $gLocale->getStr('reminder_not_updated.status');
}

$gAction_disp->addEvent('removereminder', 'action_removereminder');
function action_removereminder($eventData) {
    global $gLocale, $gPage_status;

    $innowork_reminder = new InnoworkReminder(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($innowork_reminder->trash(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()))
        $gPage_status = $gLocale->getStr('reminder_removed.status');
    else
        $gPage_status = $gLocale->getStr('reminder_not_removed.status');
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher('view');

function reminder_list_action_builder($pageNumber) {
    return WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('pagenumber' => $pageNumber))));
}

$gMain_disp->addEvent('default', 'main_default');
function main_default($eventData) {
    global $gXml_def, $gLocale, $gPage_title;
    
    require_once('shared/wui/WuiSessionkey.php');

    $tab_sess = new WuiSessionKey('innoworkreminderstab');

    if (!strlen($eventData['done']))
        $eventData['done'] = $tab_sess->mValue;
    if (!strlen($eventData['done']))
        $eventData['done'] = 'false';

    $tab_sess = new WuiSessionKey('innoworkreminderstab', array('value' => isset($eventData['done']) ? $eventData['done'] : ''));

    if (isset($eventData['done']) and $eventData['done'] == 'true') {
        $done_check = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue;
        $done_icon = 'misc3';
        $done_action = 'false';
        $done_label = 'setundone.button';
    }
    else {
        $done_check = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse;
        $done_icon = 'drawer';
        $done_action = 'true';
        $done_label = 'setdone.button';
    }

    if (isset($eventData['filter_restrictto'])) {
        // Restrict

        $restrictto_filter_sk = new WuiSessionKey('restrictto_filter', array('value' => $eventData['filter_restrictto']));
    }
    else {
        // Restrict

        $restrictto_filter_sk = new WuiSessionKey('restrictto_filter');

        $eventData['filter_restrictto'] = $restrictto_filter_sk->mValue;
    }

    $country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

    $headers[2]['label'] = $gLocale->getStr('reminderdate.header');
    $headers[3]['label'] = $gLocale->getStr('reminder.header');

    $reminder = new InnoworkReminder(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
    $search_results = $reminder->Search(array('done' => $done_check), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId(), false, false, 0, 0, $eventData['filter_restrictto']);

    $num_reminder = count($search_results);

    /*
    $search_results = $reminder->Search(
        array( 'done' => $eventData['done'] == 'true' ?
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue :
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse
            ),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()
        );
        */

    $restrictto_array[InnoworkItem::SEARCH_RESTRICT_NONE] = $gLocale->getStr('restrictto_none.label');
    $restrictto_array[InnoworkItem::SEARCH_RESTRICT_TO_OWNER] = $gLocale->getStr('restrictto_owner.label');
    $restrictto_array[InnoworkItem::SEARCH_RESTRICT_TO_RESPONSIBLE] = $gLocale->getStr('restrictto_responsible.label');
    $restrictto_array[InnoworkItem::SEARCH_RESTRICT_TO_PARTICIPANT] = $gLocale->getStr('restrictto_participants.label');

    $reminder_list = array();

    $gXml_def = '
    <vertgroup><name>reminderlist</name>
              <children>
            
                <label>
                  <args>
                    <label>'.WuiXml::cdata($gLocale->getStr('filter.label')).'</label>
                    <bold>true</bold>
                  </args>
                </label>
            
                <form><name>filter</name>
                  <args>
                        <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('filter' => 'true'))))).'</action>
                  </args>
                  <children>
                <grid>
                  <children>
            
                    <label row="0" col="0">
                      <args>
                        <label>'.WuiXml::cdata($gLocale->getStr('restrictto.label')).'</label>
                      </args>
                    </label>
            
                    <combobox row="0" col="1"><name>filter_restrictto</name>
                      <args>
                        <disp>view</disp>
                        <elements type="array">'.WuiXml::encode($restrictto_array).'</elements>
                        <default>'.$eventData['filter_restrictto'].'</default>
                      </args>
                    </combobox>
            
                    <button row="0" col="2"><name>filter</name>
                      <args>
                        <themeimage>zoom</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <formsubmit>filter</formsubmit>
                        <label type="encoded">'.urlencode($gLocale->getStr('filter.button')).'</label>
                        <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('filter' => 'true'))))).'</action>
                      </args>
                    </button>
            
                  </children>
                </grid>
                  </children>
                </form>
            
                <horizbar/>
            
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr(($eventData['done'] == 'true' ? 'done' : '').'reminderlist.label')).'</label>
                    <bold>true</bold>
                  </args>
                </label>
                <table><name>reminder</name>
                  <args>
                    <rowsperpage>15</rowsperpage>
                    <pagesactionfunction>reminder_list_action_builder</pagesactionfunction>
                    <pagenumber>'. (isset($eventData['pagenumber']) ? $eventData['pagenumber'] : '').'</pagenumber>
                    <headers type="array">'.WuiXml::encode($headers).'</headers>
                    <rows>'.$num_reminder.'</rows>
                  </args>
                  <children>';

    $row = 0;

    $page = 1;

    if (isset($eventData['pagenumber'])) {
        $page = $eventData['pagenumber'];
    }
    else {
		require_once('shared/wui/WuiTable.php');
    	
        $table = new WuiTable('reminder');

        $page = $table->mPageNumber;
    }
    if ($page > ceil($num_reminder / 15))
        $page = ceil($num_reminder / 15);

    global $priorities;

    $from = ($page * 15) - 15;
    $to = $from +15 - 1;
    foreach ($search_results as $id => $fields) {
        if ($row >= $from and $row <= $to) {
            switch ($fields['_acl']['type']) {
                case InnoworkAcl::TYPE_PRIVATE :
                    $image = 'user';
                    break;

                case InnoworkAcl::TYPE_PUBLIC :
                case InnoworkAcl::TYPE_ACL :
                    $image = 'useradd';
                    break;
            }

            if (!strlen($fields['priority']))
                $fields['priority'] = '1';

            if (strlen($fields['reminderdate'])) {
                $date_array = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetDateArrayFromTimestamp($fields['reminderdate']);
                $date = $country->FormatShortArrayDate($date_array);
            }
            else
                $date = '';

            $gXml_def.= '<button row="'.$row.'" col="0"><name>acl</name>
                                      <args>
                                        <themeimage>'.$image.'</themeimage>
                                        <themeimagetype>mini</themeimagetype>
                                        <compact>true</compact>
                                      </args>
                                    </button>
                                    <vertframe row="'.$row.'" col="1">
                                      <args>
                                        <bgcolor>'.$priorities[$fields['priority']].'</bgcolor>
                                      </args>
                                      <children>
                                        <horizgroup>
                                          <children>
                                            <label>
                                              <args>
                                                <label>     </label>
                                              </args>
                                            </label>
                                          </children>
                                        </horizgroup>
                                      </children>
                                    </vertframe>
                                    <label row="'.$row.'" col="2"><name>comp</name>
                                      <args>
                                        <label>'.WuiXml::cdata($date).'</label>
                                        <compact>true</compact>
                                      </args>
                                    </label>
                                    <link row="'.$row.'" col="3"><name>comp</name>
                                      <args>
                                        <label type="encoded">'.WuiXml::cdata(urlencode(strlen($fields['reminder']) > 50 ? substr($fields['reminder'], 0, 47).'...' : $fields['reminder'])).'</label>
                                        <title type="encoded">'.WuiXml::cdata(urlencode($fields['reminder'])).'</title>
                                        <link>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'showreminder', array('id' => $id))))).'</link>
                                        <compact>true</compact>
                                        <nowrap>false</nowrap>
                                      </args>
                                    </link>
                                    <innomatictoolbar row="'.$row.'" col="4"><name>tb</name>
                                      <args>
                                        <frame>false</frame>
                                        <toolbars type="array">'.WuiXml::encode(array('view' => array('show' => array('label' => $gLocale->getStr('showreminder.button'), 'themeimage' => 'zoom', 'themeimagetype' => 'mini', 'compact' => 'true', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'showreminder', array('id' => $id))))), 'done' => array('label' => $gLocale->getStr($done_label), 'themeimage' => $done_icon, 'themeimagetype' => 'mini', 'compact' => 'true', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'editreminder', array('id' => $id, 'done' => $done_action))))), 'remove' => array('label' => $gLocale->getStr('removereminder.button'), 'needconfirm' => 'true', 'confirmmessage' => $gLocale->getStr('removereminder.confirm'), 'horiz' => 'true', 'compact' => 'true', 'themeimage' => 'trash', 'themeimagetype' => 'mini', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'removereminder', array('id' => $id)))))))).'</toolbars>
                                      </args>
                                    </innomatictoolbar>';

        }
        $row ++;
    }

    $gXml_def.= '      </children>
                </table>
              </children>
            </vertgroup>';
}

$gMain_disp->addEvent('newreminder', 'main_newreminder');
function main_newreminder($eventData) {
    global $gXml_def, $gLocale, $gPage_title;

    $core = InnoworkCore::instance('innoworkcore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
    $summ = $core->getSummaries();

    if (isset($summ['project'])) {
        $innowork_projects = new InnoworkProject(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        $search_results = $innowork_projects->Search(array('done' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());

        $projects['0'] = $gLocale->getStr('noproject.label');

        while (list ($id, $fields) = each($search_results)) {
            $projects[$id] = $fields['name'];
        }
    }

    $locale_country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

    $curr_date = $locale_country->getDateArrayFromSafeTimestamp($locale_country->SafeFormatTimestamp());

    $priorities_desc[10] = '10';
    $priorities_desc[9] = '9';
    $priorities_desc[8] = '8';
    $priorities_desc[7] = '7';
    $priorities_desc[6] = '6';
    $priorities_desc[5] = '5';
    $priorities_desc[4] = '4';
    $priorities_desc[3] = '3';
    $priorities_desc[2] = '2';
    $priorities_desc[1] = '1';

    $gXml_def.= '
    <vertgroup><name>newreminder</name>
              <children>
                <table><name>company</name>
                  <args>
                    <headers type="array">'.WuiXml::encode(array('0' => array('label' => $gLocale->getStr('newreminder.label')))).'</headers>
                  </args>
                  <children>
                <form row="0" col="0"><name>reminder</name>
                  <args>
                    <method>post</method>
                    <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'showreminder', ''), array('action', 'newreminder', '')))).'</action>
                  </args>
                  <children>
            
                    <vertgroup>
                      <children>

                        <label>
                          <args>
                            <label>'.WuiXml::cdata($gLocale->getStr('reminder.label')).'</label>
                          </args>
                        </label>
                    		<text><name>reminder</name>
                          <args>
                            <disp>action</disp>
                            <cols>80</cols>
                            <rows>3</rows>
                          </args>
                        </text>
            
                        <label>
                          <args>
                            <label type="encoded">'.urlencode($gLocale->getStr('description.label')).'</label>
                          </args>
                        </label>
                        <text><name>description</name>
                          <args>
                            <disp>action</disp>
                            <cols>80</cols>
                            <rows>7</rows>
                          </args>
                        </text>
            
                      </children>
                    </vertgroup>
            
                    <horizgroup>
                      <args>
                        <align>middle</align>
                      </args>
                      <children>
            
                        <label><name>reminderdate</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale->getStr('reminderdate.label')).'</label>
                          </args>
                        </label>
                        <date><name>reminderdate</name>
                          <args>
                            <disp>action</disp>
                            <value type="array">'.WuiXml::encode($curr_date).'</value>
                          </args>
                        </date>';

    if (isset($summ['project'])) {
        $gXml_def.= '            <label><name>project</name>
                                      <args>
                                        <label type="encoded">'.urlencode($gLocale->getStr('project.label')).'</label>
                                      </args>
                                    </label>
                                    <combobox><name>projectid</name>
                                      <args>
                                        <disp>action</disp>
                                        <elements type="array">'.WuiXml::encode($projects).'</elements>
                                      </args>
                                    </combobox>';
    }

    $gXml_def.= '
                  </children>
                    </horizgroup>
    
                    <horizgroup><args><width>0%</width></args>
                      <children>
                    <label>
                                  <args>
                                    <label type="encoded">'.urlencode($gLocale->getStr('priority.label')).'</label>
                                  </args>
                                </label>
                                <combobox><name>priority</name>
                                  <args>
                                    <disp>action</disp>
                                    <elements type="array">'.WuiXml::encode($priorities_desc).'</elements>
                                    <default>5</default>
                                  </args>
                                </combobox>
                                
                      </children>
                    </horizgroup>
    
                    </children>
                    </form>
            
                    <button row="1" col="0"><name>apply</name>
                      <args>
                        <themeimage>buttonok</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'showreminder', ''), array('action', 'newreminder', '')))).'</action>
                        <label type="encoded">'.urlencode($gLocale->getStr('newreminder.submit')).'</label>
                        <formsubmit>reminder</formsubmit>
                      </args>
                    </button>
            
                  </children>
                </table>
              </children>
            </vertgroup>';
}

$gMain_disp->addEvent('showreminder', 'main_showreminder');
function main_showreminder($eventData) {
    global $gXml_def, $gLocale, $gPage_title;

    if (isset($GLOBALS['innowork-reminder']['newreminderid'])) {
        $eventData['id'] = $GLOBALS['innowork-reminder']['newreminderid'];
    }

    $innowork_reminder = new InnoworkReminder(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    $reminder_data = $innowork_reminder->GetItem(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());

    if ($reminder_data['done'] == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue) {
        $done_icon = 'misc3';
        $done_action = 'false';
        $done_label = 'setundone.button';
    }
    else {
        $done_icon = 'drawer';
        $done_action = 'true';
        $done_label = 'setdone.button';
    }

    $priorities_desc[10] = '10';
    $priorities_desc[9] = '9';
    $priorities_desc[8] = '8';
    $priorities_desc[7] = '7';
    $priorities_desc[6] = '6';
    $priorities_desc[5] = '5';
    $priorities_desc[4] = '4';
    $priorities_desc[3] = '3';
    $priorities_desc[2] = '2';
    $priorities_desc[1] = '1';

    $core = InnoworkCore::instance('innoworkcore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
    $summ = $core->getSummaries();

    if (isset($summ['project'])) {
        $innowork_projects = new InnoworkProject(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        $search_results = $innowork_projects->Search('', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());

        $projects['0'] = $gLocale->getStr('noproject.label');

        while (list ($id, $fields) = each($search_results)) {
            $projects[$id] = $fields['name'];
        }
    }

    $country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());
    $empty_date_array = $country->GetDateArrayFromShortDateStamp('');
    $empty_date_text = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetTimestampFromDateArray($empty_date_array);

    if (!strlen($reminder_data['priority']))
        $reminder_data['priority'] = '1';

    $gXml_def.= '<horizgroup><name>reminder</name>
              <children>
            <vertgroup><name>reminder</name>
              <children>
                <table><name>company</name>
                  <args>
                    <headers type="array">'.WuiXml::encode(array('0' => array('label' => $gLocale->getStr('reminder.label')))).'</headers>
                  </args>
                  <children>
            
                <form row="0" col="0"><name>reminder</name>
                  <args>
                    <method>post</method>
                    <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'editreminder', array('id' => $eventData['id']))))).'</action>
                  </args>
                  <children>
            
                    <vertgroup>
                      <children>
            
                        <label><name>reminderdate</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale->getStr('reminder.label')).'</label>
                          </args>
                        </label>
                        <text><name>reminder</name>
                          <args>
                            <disp>action</disp>
                            <cols>80</cols>
                            <rows>3</rows>
                            <value>'.WuiXml::cdata($reminder_data['reminder']).'</value>
                          </args>
                        </text>
            
                        <label><name>reminderdate</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale->getStr('description.label')).'</label>
                          </args>
                        </label>
                        <text><name>description</name>
                          <args>
                            <disp>action</disp>
                            <cols>80</cols>
                            <rows>7</rows>
                            <value type="encoded">'.WuiXml::cdata(urlencode($reminder_data['description'])).'</value>
                          </args>
                        </text>
            
                      </children>
                    </vertgroup>
            
                    <horizgroup>
                      <args>
                        <align>middle</align>
                      </args>
                      <children>
            
                        <label><name>reminderdate</name>
                          <args>
                            <label type="encoded">'.urlencode($gLocale->getStr('reminderdate.label')).'</label>
                          </args>
                        </label>
                        <date><name>reminderdate</name>
                          <args>
                            <disp>action</disp>
                            <value type="array">'.WuiXml::encode(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetDateArrayFromTimestamp($reminder_data['reminderdate'])).'</value>
                          </args>
                        </date>';

    if (isset($summ['project'])) {
        $gXml_def.= '            <label><name>project</name>
                                      <args>
                                        <label type="encoded">'.urlencode($gLocale->getStr('project.label')).'</label>
                                      </args>
                                    </label>
                                    <combobox><name>projectid</name>
                                      <args>
                                        <disp>action</disp>
                                        <elements type="array">'.WuiXml::encode($projects).'</elements>
                                        <default>'.$reminder_data['projectid'].'</default>
                                      </args>
                                    </combobox>';
    }

    $gXml_def.= '
                  </children>
                    </horizgroup>
    
                    <horizgroup><args><width>0%</width></args>
                      <children>
                    <label>
                                  <args>
                                    <label type="encoded">'.urlencode($gLocale->getStr('priority.label')).'</label>
                                  </args>
                                </label>
                                <combobox><name>priority</name>
                                  <args>
                                    <disp>action</disp>
                                    <elements type="array">'.WuiXml::encode($priorities_desc).'</elements>
                                    <default>'.$reminder_data['priority'].'</default>
                                  </args>
                                </combobox>

                    <label>
                                  <args>
                                    <label type="encoded">'.urlencode($gLocale->getStr('spenttime.label')).'</label>
                                  </args>
                                </label>
                                <string>
                                  <name>spenttime</name>
                                  <args>
                                    <disp>action</disp>
                                    <size>5</size>
                                    <value>'.$reminder_data['spenttime'].'</value>
                                  </args>
                                </string>
                                
                      </children>
                    </horizgroup>
    
                    </children>
                    </form>
            
                    <horizgroup row="1" col="0">
                      <children>
            
                    <button><name>apply</name>
                      <args>
                        <themeimage>buttonok</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'editreminder', array('id' => $eventData['id']))))).'</action>
                        <label type="encoded">'.urlencode($gLocale->getStr('editreminder.submit')).'</label>
                        <formsubmit>reminder</formsubmit>
                      </args>
                    </button>
            
                    <button>
                      <args>
                        <themeimage>listdetailed</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', '')))).'</action>
                        <label type="encoded">'.urlencode($gLocale->getStr('close.button')).'</label>
                      </args>
                    </button>
            
                    <button>
                      <args>
                        <themeimage>'.$done_icon.'</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'editreminder', array('id' => $eventData['id'], 'done' => $done_action))))).'</action>
                        <label type="encoded">'.urlencode($gLocale->getStr($done_label)).'</label>
                        <formsubmit>reminder</formsubmit>
                      </args>
                    </button>
            
                    <button><name>remove</name>
                      <args>
                        <themeimage>trash</themeimage>
                        <horiz>true</horiz>
                        <frame>false</frame>
                        <confirmmessage>'.$gLocale->getStr('removereminder.confirm').'</confirmmessage>
                        <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'removereminder', array('id' => $eventData['id']))))).'</action>
                        <label type="encoded">'.urlencode($gLocale->getStr('removereminder.button')).'</label>
                      </args>
                    </button>
            
                      </children>
                    </horizgroup>
            
                  </children>
                </table>
              </children>
            </vertgroup>
            
              <innoworkitemacl><name>itemacl</name>
                <args>
                  <itemtype>reminder</itemtype>
                  <itemid>'.$eventData['id'].'</itemid>
                  <itemownerid>'.$reminder_data['ownerid'].'</itemownerid>
                  <defaultaction>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'showreminder', array('id' => $eventData['id']))))).'</defaultaction>
                </args>
              </innoworkitemacl>
            
              </children>
            </horizgroup>';
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//

$gWui->addChild(new WuiInnomaticPage('page', array('pagetitle' => $gPage_title, 'icon' => 'listdetailed2', 'toolbars' => array(
		new WuiInnomaticToolbar('view', array('toolbars' => $gToolbars, 'toolbar' => 'true')),
		new WuiInnomaticToolBar('core', array('toolbars' => $gCore_toolbars, 'toolbar' => 'true')),
		), 'maincontent' => new WuiXml('page', array('definition' => $gXml_def)), 'status' => $gPage_status)));

$gWui->render();

?>
