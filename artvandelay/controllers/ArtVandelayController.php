<?php namespace Craft;

class ArtVandelayController extends BaseController
{

	public function actionIndex()
	{
		$this->renderTemplate('artVandelay/_index', array(
			'groupOptions'     => $this->_getGroupOptions(),
			'entryTypeOptions' => $this->_getEntryTypeOptions()
		));
	}


	public function actionImport()
	{
		$this->requirePostRequest();

		$json = craft()->request->getParam('data', '{}');
		$data = ArtVandelay_ExportedDataModel::fromJson($json);

		$result = new ArtVandelay_ResultModel();

		if ($data !== null)
		{
			$assetImportResult    = craft()->artVandelay_assets->import($data->assets);
			$categoryImportResult = craft()->artVandelay_categories->import($data->categories);
			$fieldImportResult    = craft()->artVandelay_fields->import($data->fields);
			$globalImportResult   = craft()->artVandelay_globals->import($data->globals);
			$sectionImportResult  = craft()->artVandelay_sections->import($data->sections);
			$tagImportResult      = craft()->artVandelay_tags->import($data->tags);

			$result->consume($assetImportResult);
			$result->consume($categoryImportResult);
			$result->consume($fieldImportResult);
			$result->consume($globalImportResult);
			$result->consume($sectionImportResult);
			$result->consume($tagImportResult);

			if ($result->ok)
			{
				craft()->userSession->setNotice('All done.');
				$this->redirectToPostedUrl();

				return;
			}
		}
		else
		{
			$errors[] = 'Invalid JSON.';
		}

		craft()->userSession->setError('Get *out*! ' . implode(', ', $result->errors));

		craft()->urlManager->setRouteVariables(array(
			'groupOptions'     => $this->_getGroupOptions(),
			'entryTypeOptions' => $this->_getEntryTypeOptions()
		));
	}


	public function actionExport()
	{
		$this->requirePostRequest();

		$result = new ArtVandelay_ExportedDataModel(array(
			'assets'     => $this->_exportAssets(),
			'categories' => $this->_exportCategories(),
			'fields'     => $this->_exportFields(),
			'globals'    => $this->_exportGlobals(),
			'sections'   => $this->_exportSections(),
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


	private function _exportTags()
	{
		return array();
	}

}