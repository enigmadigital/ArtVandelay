<?php
namespace Craft;

class ArtVandelayCommand extends BaseCommand
{
	/**
	 * @param string $file json file containing the schema definition
	 */
	public function actionImport($file = 'craft/config/schema.json')
	{
		if (!file_exists($file)) {
			echo "File not found\n";
			return;
		}

		$json = file_get_contents($file);

		$result = craft()->artVandelay_importExport->importFromJson($json);

		if ($result->ok) {
			echo "Loaded schema from $file.\n";
		} else {
			echo "There was an error loading schema from $file\n";
			print_r($result->errors);
		}
	}

	/**
	 * Exports the datamodel of craft (fields and sections)
	 * @param string $file file to write the schema to
	 */
	public function actionExport($file = 'craft/config/schema.json')
	{
		$schema = craft()->artVandelay_importExport->export();

		file_put_contents($file, json_encode($schema, JSON_PRETTY_PRINT, JSON_NUMERIC_CHECK));
	}
}
