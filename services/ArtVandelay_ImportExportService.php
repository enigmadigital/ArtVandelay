<?php
namespace Craft;


class ArtVandelay_ImportExportService extends BaseApplicationComponent
{
	/**
	 * @param string $json
	 * @return ArtVandelay_ResultModel
	 */
	public function importFromJson($json)
	{
		$exportedDataModel = ArtVandelay_ExportedDataModel::fromJson($json);
		return $this->importFromExportedDataModel($exportedDataModel);
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
	 * @param $array
	 * @return ArtVandelay_ResultModel
	 */
	public function importFromArray($array)
	{
		$exportedDataModel = new ArtVandelay_ExportedDataModel($array);
		return $this->importFromExportedDataModel($exportedDataModel);
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

	private function importFromExportedDataModel($model)
	{
		$result = new ArtVandelay_ResultModel();

		if ($model !== null) {
			$assetImportResult = craft()->artVandelay_assets->import($model->assets);
			$categoryImportResult = craft()->artVandelay_categories->import($model->categories);
			$fieldImportResult = craft()->artVandelay_fields->import($model->fields);
			$globalImportResult = craft()->artVandelay_globals->import($model->globals);
			$sectionImportResult = craft()->artVandelay_sections->import($model->sections);
			$tagImportResult = craft()->artVandelay_tags->import($model->tags);

			$result->consume($assetImportResult);
			$result->consume($categoryImportResult);
			$result->consume($fieldImportResult);
			$result->consume($globalImportResult);
			$result->consume($sectionImportResult);
			$result->consume($tagImportResult);
		}

		return $result;
	}
}