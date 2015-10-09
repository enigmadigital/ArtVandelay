<?php
namespace Craft;


class ArtVandelay_ImportExportService extends BaseApplicationComponent
{
	/**
	 * @param string $json
	 * @param bool $force if set to true items not included in import will be deleted
	 * @return ArtVandelay_ResultModel
	 */
	public function importFromJson($json, $force = false)
	{
		$exportedDataModel = ArtVandelay_ExportedDataModel::fromJson($json);
		return $this->importFromExportedDataModel($exportedDataModel, $force);
	}

	public function importTabsFromJson($json, $applyTo)
	{
		$exportedDataModel = ArtVandelay_ExportedDataModel::fromJson($json);
		$applyToModel = json_decode($applyTo, false);
		return $this->importTabsFromExportedDataModel($exportedDataModel, $applyToModel);
	}

	public function loadFromJson($json)
	{
		$data = ArtVandelay_ExportedDataModel::fromJson($json);

		foreach ($data->fields as $group)
		{
			$group['notes'] = "HEY";
		}

		return $data;
	}


	/**
	 * @param array $array
	 * @param bool $force if set to true items not included in import will be deleted
	 * @return ArtVandelay_ResultModel
	 */
	public function importFromArray(array $array, $force = false)
	{
		$exportedDataModel = new ArtVandelay_ExportedDataModel($array);
		return $this->importFromExportedDataModel($exportedDataModel, $force);
	}

	/**
	 * @param $model
	 * @return ArtVandelay_ResultModel
	 */
	private function importTabsFromExportedDataModel($model, $applyTo)
	{
		$result = new ArtVandelay_ResultModel();

		if ($model !== null) {
			$contentTabsImportResult = craft()->artVandelay_contentTabs->import($model->contenttabs, $applyTo);

			//$result->consume($contentTabsImportResult);
		}

		return $result;
	}

	/**
	 * @param ArtVandelay_ExportedDataModel $model
	 * @param bool $force if set to true items not in the import will be deleted
	 * @return ArtVandelay_ResultModel
	 */
	private function importFromExportedDataModel(ArtVandelay_ExportedDataModel $model, $force)
	{
		$result = new ArtVandelay_ResultModel();

		if ($model !== null) {
			$pluginImportResult = craft()->artVandelay_plugins->import($model->plugins);
			$assetImportResult = craft()->artVandelay_assets->import($model->assets);
			$categoryImportResult = craft()->artVandelay_categories->import($model->categories);
			$fieldImportResult = craft()->artVandelay_fields->import($model->fields, $force);
			$globalImportResult = craft()->artVandelay_globals->import($model->globals);
			$sectionImportResult = craft()->artVandelay_sections->import($model->sections, $force);
			$tagImportResult = craft()->artVandelay_tags->import($model->tags);

			$result->consume($pluginImportResult);
			$result->consume($assetImportResult);
			$result->consume($categoryImportResult);
			$result->consume($fieldImportResult);
			$result->consume($globalImportResult);
			$result->consume($sectionImportResult);
			$result->consume($tagImportResult);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function export()
	{
		$fieldGroups = craft()->fields->getAllGroups();
		$sections = craft()->sections->getAllSections();

		return array(
			'assets' => craft()->artVandelay_assets->export(),
			'fields' => craft()->artVandelay_fields->export($fieldGroups),
			'plugins' => craft()->artVandelay_plugins->export(),
			'sections' => craft()->artVandelay_sections->export($sections),
		);
	}
}