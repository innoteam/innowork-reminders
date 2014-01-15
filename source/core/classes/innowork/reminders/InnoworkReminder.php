<?php

require_once('innowork/core/InnoworkItem.php');

require_once('innomatic/application/ApplicationDependencies.php');
$app_dep = new ApplicationDependencies(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess());

/*!
 @class InnoworkReminder

 @abstract reminderitem type handler.
 */
class InnoworkReminder extends InnoworkItem {
	var $mTable = 'innowork_reminders';
	var $mNoTrash = false;
	var $mNewDispatcher = 'view';
	var $mNewEvent = 'newreminder';
	var $mConvertible = true;
	const ITEM_TYPE = 'reminder';

	public function __construct($rrootDb, $rdomainDA, $itemId = 0) {
		parent::__construct($rrootDb, $rdomainDA, InnoworkReminder::ITEM_TYPE, $itemId);

		$this->mKeys['reminder'] = 'text';
		$this->mKeys['description'] = 'text';
		$this->mKeys['projectid'] = 'table:innowork_projects:name:integer';
		$this->mKeys['reminderdate'] = 'timestamp';
		$this->mKeys['done'] = 'boolean';
		$this->mKeys['priority'] = 'integer';
		$this->mKeys['spenttime'] = 'integer';

		$this->mSearchResultKeys[] = 'reminder';
		$this->mSearchResultKeys[] = 'description';
		$this->mSearchResultKeys[] = 'projectid';
		$this->mSearchResultKeys[] = 'reminderdate';
		$this->mSearchResultKeys[] = 'done';
		$this->mSearchResultKeys[] = 'priority';
		$this->mSearchResultKeys[] = 'spenttime';

		$this->mViewableSearchResultKeys[] = 'reminder';
		$this->mViewableSearchResultKeys[] = 'description';
		$this->mViewableSearchResultKeys[] = 'projectid';
		$this->mViewableSearchResultKeys[] = 'reminderdate';
		$this->mViewableSearchResultKeys[] = 'priority';
		$this->mViewableSearchResultKeys[] = 'spenttime';

		$this->mSearchOrderBy = 'reminderdate ASC,priority DESC,reminder';
		$this->mShowDispatcher = 'view';
		$this->mShowEvent = 'showreminder';

		$this->mGenericFields['companyid'] = 'customerid';
		$this->mGenericFields['projectid'] = 'projectid';
		$this->mGenericFields['title'] = 'reminder';
		$this->mGenericFields['content'] = 'description';
		$this->mGenericFields['binarycontent'] = '';
	}

	function doCreate($params, $userId) {
		$result = FALSE;

		if (count($params)) {
			if ($params['done'] == 'true')
			$params['done'] = $this->mrDomainDA->fmttrue;
			else
			$params['done'] = $this->mrDomainDA->fmtfalse;

			$params['trashed'] = $this->mrDomainDA->fmtfalse;

			$item_id = $this->mrDomainDA->getNextSequenceValue($this->mTable.'_id_seq');

			$key_pre = $value_pre = $keys = $values = '';

			require_once('innomatic/locale/LocaleCountry.php');
			$country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

			while (list ($key, $val) = each($params)) {
				$key_pre = ',';
				$value_pre = ',';

				switch ($key) {
					case 'reminder' :
					case 'done' :
					case 'trashed' :
					case 'description' :
						$keys.= $key_pre.$key;
						$values.= $value_pre.$this->mrDomainDA->formatText($val);
						break;

					case 'projectid' :
					case 'priority' :
					case 'spenttime' :
						if (!strlen($val))
						$val = 0;
						$keys.= $key_pre.$key;
						$values.= $value_pre.$val;
						break;

					case 'reminderdate' :
						$date_array = $country->GetDateArrayFromShortDateStamp($val);
						$val = $this->mrDomainDA->GetTimestampFromDateArray($date_array);

						$keys.= $key_pre.$key;
						$values.= $value_pre.$this->mrDomainDA->formatText($val);
						break;
				}

				$key_pre = ',';
				$value_pre = ',';
			}

			if (strlen($values)) {
				if ($this->mrDomainDA->execute('INSERT INTO '.$this->mTable.' '.'(id,ownerid'.$keys.') '.'VALUES ('.$item_id.','.$userId.$values.')'))
				$result = $item_id;
			}
		}

		return $result;
	}

	function doEdit($params, $userId) {
		$result = FALSE;

		if ($this->mItemId) {
			if (count($params)) {
				$start = 1;
				$update_str = '';

				require_once('innomatic/locale/LocaleCountry.php');
				$country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

				if (isset($params['done'])) {
					if ($params['done'] == 'true')
					$params['done'] = $this->mrDomainDA->fmttrue;
					else
					$params['done'] = $this->mrDomainDA->fmtfalse;
				}

				while (list ($field, $value) = each($params)) {
					if ($field != 'id') {
						if (!$start)
						$update_str.= ',';

						switch ($field) {
							case 'reminder' :
							case 'done' :
							case 'trashed' :
							case 'description' :
								$update_str.= $field.'='.$this->mrDomainDA->formatText($value);
								break;

							case 'reminderdate' :
								$date_array = $country->GetDateArrayFromShortDateStamp($value);
								$value = $this->mrDomainDA->GetTimestampFromDateArray($date_array);

								$update_str.= $field.'='.$this->mrDomainDA->formatText($value);
								break;

							case 'projectid' :
							case 'priority' :
							case 'spenttime' :
								if (!strlen($value))
								$value = 0;
								$update_str.= $field.'='.$value;
								break;

							default :
								break;
						}

						$start = 0;
					}
				}

				$query = $this->mrDomainDA->execute('UPDATE '.$this->mTable.' SET '.$update_str.' WHERE id='.$this->mItemId);

				if ($query)
				$result = TRUE;
			}
		}

		return $result;
	}

	function doRemove($userId) {
		$result = FALSE;

		$result = $this->mrDomainDA->execute('DELETE FROM '.$this->mTable.' WHERE id='.$this->mItemId);

		return $result;
	}

	function doGetItem($userId) {
		$result = FALSE;

		$item_query = $this->mrDomainDA->execute('SELECT * FROM '.$this->mTable.' WHERE id='.$this->mItemId);

		if (is_object($item_query) and $item_query->getNumberRows()) {
			$result = $item_query->getFields();
		}

		return $result;
	}

	function doTrash() {
		return true;
	}

	function doGetSummary() {
		$result = false;

		$search_result = $this->Search(array('done' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse,), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId(), false, false, 0, 0, InnoworkItem::SEARCH_RESTRICT_TO_RESPONSIBLE);

		if (is_array($search_result)) {
			$definition = '';

			while (list ($id, $fields) = each($search_result)) {
				if (strlen($fields['reminder']) > 25)
				$reminder = substr($fields['reminder'], 0, 22).'...';
				else
				$reminder = $fields['reminder'];

				require_once('innomatic/wui/dispatch/WuiEventsCall.php');
				require_once('innomatic/wui/dispatch/WuiEvent.php');
				$reminder_action = new WuiEventsCall('innoworkreminders');
				$reminder_action->addEvent(new WuiEvent('view', 'showreminder', array('id' => $id)));
				$definition.= '<horizgroup><name>reminderhgroup</name><args></args><children>';
				$definition.= '<label><name>reminderlabel</name><args><compact>true</compact><label>- </label></args></label>';
				$definition.= '<link><name>reminderlink</name>
                                                      <args>
                                                        <compact>true</compact>
                                                        <label type="encoded">'.urlencode($reminder).'</label>
                                                        <title type="encoded">'.urlencode($fields['reminder']).'</title>
                                                        <link type="encoded">'.urlencode($reminder_action->GetEventsCallString()).'</link>
                                                      </args>
                                                    </link>';
				$definition.= '</children></horizgroup>';
			}

			$definition = '<vertgroup><name>remindergroup</name><children>'.$definition.'</children></vertgroup>';

			$result = $definition;
		}

		return $result;

	}
}
?>
