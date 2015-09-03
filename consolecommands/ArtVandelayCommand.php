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
}
