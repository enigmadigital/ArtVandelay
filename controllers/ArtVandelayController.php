<?php namespace Craft;

class ArtVandelayController extends BaseController
{

	public function actionIndex()
	{
		$this->renderTemplate('artVandelay/_index', array(
			'groupOptions'     => $this->_getGroupOptions(),
			'entryTypeOptions' => $this->_getEntryTypeOptions(),
		));
	}

	public function actionImportStep1()
	{
		$this->renderTemplate('artVandelay/_import1', array());
	}

	public function actionImportStep2()
	{
		$this->requirePostRequest();

		$json = craft()->request->getParam('data', '{}');

		$result = craft()->artVandelay_importExport->loadFromJson($json);

		$this->renderTemplate('artVandelay/_import2', array(
				'model' => $result,
				'rawdata' => $json)
		);
	}

	public function actionImportStep3()
	{
		$this->requirePostRequest();

		$json = craft()->request->getParam('data', '{}');

		$result = craft()->artVandelay_importExport->importFromJson($json);

		if ($result->ok) {
			$this->renderTemplate('artVandelay/_import3', array());
		}

		craft()->userSession->setError('Get *out*! ' . implode(', ', $result->errors));
	}

	public function actionImportStep4()
	{
		$this->requirePostRequest();

		$json = craft()->request->getParam('data', '{}');
		$applyTo = craft()->request->getParam('applyTo', '{}');

		$result = craft()->artVandelay_importExport->importFromJson($json);

		if ($result->ok) {
			$result = craft()->artVandelay_importExport->importTabsFromJson($json, $applyTo);
			if ($result->ok) {
				$this->renderTemplate('artVandelay/_import3', array());
			}
		}

		craft()->userSession->setError('Get *out*! ' . implode(', ', $result->errors));
	}

	public function actionExportTabs()
	{
		$this->renderTemplate('artVandelay/_tabExport', array(
			'entryTypeTabOptions'			 => $this->_getEntryTypeTabOptions(),
		));
	}

	public function actionExportFields()
	{
		$this->renderTemplate('artVandelay/_fieldExport', array(
			'GroupOptions'			 => $this->_getGroupOptions(),
		));
	}

	public function actionExportSections()
	{
		$this->renderTemplate('artVandelay/_sectionExport', array(
			'entryTypeOptions'			 => $this->_getEntryTypeOptions(),
		));
	}



	public function actionImport()
	{
		$this->requirePostRequest();

		$json = craft()->request->getParam('data', '{}');

		$result = craft()->artVandelay_importExport->importFromJson($json);

		if ($result->ok) {
			craft()->userSession->setNotice('All done.');
			$this->redirectToPostedUrl();
			return;
		}

		craft()->userSession->setError('Get *out*! ' . implode(', ', $result->errors));

		craft()->urlManager->setRouteVariables(array(
			'groupOptions'     => $this->_getGroupOptions(),
			'entryTypeOptions' => $this->_getEntryTypeOptions()
		));
	}


	public function actionFieldExport()
	{
		$this->requirePostRequest();

		$result = new ArtVandelay_ExportedDataModel(array(
			'assets'     => [],
			'categories' => [],
			'fields'     => $this->_exportFields(),
			'globals'    => [],
			'sections'   => [],
			'contenttabs'=> [],
			'tags'       => []
		));

		$json = $result->toJson();

		if (craft()->request->getParam('download'))
		{
			HeaderHelper::setDownload('export.json', strlen($json));
		}

		JsonHelper::sendJsonHeaders();
		echo $json;
		craft()->end();
	}

	public function actionSectionExport()
	{
		$this->requirePostRequest();

		$result = new ArtVandelay_ExportedDataModel(array(
			'assets'     => [],
			'categories' => [],
			'fields'     => $this->_exportSectionFields(),
			'globals'    => [],
			'sections'   => $this->_exportSections(),
			'contenttabs'=> [],
			'tags'       => []
		));

		$json = $result->toJson();

		if (craft()->request->getParam('download'))
		{
			HeaderHelper::setDownload('export.json', strlen($json));
		}

		JsonHelper::sendJsonHeaders();
		echo $json;
		craft()->end();
	}


	public function actionExport()
	{
		$this->requirePostRequest();

		$result = new ArtVandelay_ExportedDataModel(array(
			'assets1'     => $this->_exportAssets(),
			'categories' => $this->_exportCategories(),
			'fields'     => $this->_exportFields(),
			'globals'    => $this->_exportGlobals(),
			'sections'   => $this->_exportSections(),
			'contenttabs'=> [],
			'tags'       => $this->_exportTags()
		));

		$json = $result->toJson();

		if (craft()->request->getParam('download'))
		{
			HeaderHelper::setDownload('export.json', strlen($json));
		}

		JsonHelper::sendJsonHeaders();
		echo $json;
		craft()->end();
	}

	public function actionTabExport()
	{
		$this->requirePostRequest();

		$result = new ArtVandelay_ExportedDataModel(array(
			'assets'     => [],
			'categories' => [],
			'fields'     => $this->_exportSectionTabFields(),
			'globals'    => [],
			'sections'   => [],
			'contenttabs'=> $this->_exportSectionTabs(),
			'tags'       => []
		));

		$json = $result->toJson();

		if (craft()->request->getParam('download'))
		{
			HeaderHelper::setDownload('export.json', strlen($json));
		}

		JsonHelper::sendJsonHeaders();
		echo $json;
		craft()->end();
	}


	private function _getGroupOptions()
	{
		$groupOptions = array();

		foreach (craft()->fields->getAllGroups() as $group)
		{
			$groupOptions[$group->id] = $group->name;
		}

		return $groupOptions;
	}


	private function _getEntryTypeOptions()
	{
		$entryTypeOptions = array();

		foreach (craft()->sections->getAllSections() as $section)
		{
			foreach ($section->getEntryTypes() as $entryType)
			{
				$entryTypeOptions[$entryType->id] = $section->name.' - '.$entryType->name;
			}
		}

		return $entryTypeOptions;
	}


	private function _getEntryTypeTabOptions()
	{
		$entryTypeTabOptions = array();

		foreach (craft()->sections->getAllSections() as $section)
		{
			foreach ($section->getEntryTypes() as $entryType)
			{
					$fieldLayout = $entryType->getFieldLayout();
					foreach ($fieldLayout->getTabs() as $tab)
						$entryTypeTabOptions[$entryType->id . '|' . $tab->name] = '<div class="esection">' . $section->name.'</div><div class="contenttype">'.$entryType->name . '</div><div class="contenttab">' . $tab->name . '</div>';
			}
		}

		return $entryTypeTabOptions;
	}


	private function _exportAssets()
	{
		return array();
	}


	private function _exportCategories()
	{
		return array();
	}


	private function _exportFields()
	{
		$selectedIds = craft()->request->getParam('selectedGroups', '*');

		if ($selectedIds == '*')
		{
			$groups = craft()->fields->getAllGroups();
		}
		else
		{
			$groups = array();

			if (is_array($selectedIds))
			{
				foreach ($selectedIds as $id)
				{
					$groups[] = craft()->fields->getGroupById($id);
				}
			}
		}

		return craft()->artVandelay_fields->export($groups);
	}


	private function _exportGlobals()
	{
		return array();
	}


	private function _exportSections()
	{
		$selectedIds = craft()->request->getParam('selectedEntryTypes', '*');

		if ($selectedIds == '*')
		{
			$sections     = craft()->sections->getAllSections();
			$entryTypeIds = null;
		}
		else
		{
			$sections     = array();
			$entryTypeIds = array();

			if (is_array($selectedIds))
			{
				foreach ($selectedIds as $id)
				{
					$entryType = craft()->sections->getEntryTypeById($id);

					$sections[]     = $entryType->getSection();
					$entryTypeIds[] = $entryType->id;
				}
			}
		}

		return craft()->artVandelay_sections->export($sections, $entryTypeIds);
	}

	private function _exportSectionFields()
	{
		$selectedIds = craft()->request->getParam('selectedEntryTypes', '*');

		if ($selectedIds == '*')
		{
			$entryTypeIds = null;
		}
		else
		{
			$sections     = array();
			$entryTypeIds = array();

			if (is_array($selectedIds))
			{
				foreach ($selectedIds as $id)
				{
					$entryType = craft()->sections->getEntryTypeById($id);

					$entryTypeIds[] = $entryType->id;
				}
			}
		}

		$sections     = craft()->sections->getAllSections();

		return craft()->artVandelay_fields->exportSectionFields($sections, $entryTypeIds);
	}

	private function _exportSectionTabs()
	{
		$selectedTabIds = craft()->request->getParam('selectedEntryTypeTabs', '*');

		if (strlen($selectedTabIds) == 0) {
			return null;
		}


		$sectionData = explode('|', $selectedTabIds);

		$sections = array();
		$entryTypeIds = array();

		$entryType = craft()->sections->getEntryTypeById($sectionData[0]);
		$sections[] = $entryType->getSection();
		$entryTypeIds[] = $entryType->id;

		return craft()->artVandelay_contentTabs->export($sections, $entryType, $sectionData[1]);
	}

	private function _exportSectionTabFields()
	{
		$selectedTabIds = craft()->request->getParam('selectedEntryTypeTabs', '*');

		if (strlen($selectedTabIds) == 0) {
			return null;
		}


		$sectionData = explode('|', $selectedTabIds);

		$sections = array();
		$entryTypeIds = array();

		$entryType = craft()->sections->getEntryTypeById($sectionData[0]);
		$sections[] = $entryType->getSection();
		$entryTypeIds[] = $entryType->id;

		return craft()->artVandelay_fields->exportTabFields($sections, $entryType, $sectionData[1]);
	}




	private function _exportTags()
	{
		return array();
	}

}
