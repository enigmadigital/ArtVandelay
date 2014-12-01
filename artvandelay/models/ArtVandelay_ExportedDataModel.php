<?php namespace Craft;


/**
 * Encapsulates data that has been exported via Art Vandelay.
 *
 * @author XO Digital
 */
class ArtVandelay_ExportedDataModel extends BaseModel
{

	/**
	 * Creates an ArtVandelay_ExportedDataModel from JSON input.
	 *
	 * @param string $json The input JSON.
	 *
	 * @return ArtVandelay_ExportedDataModel|null The new ArtVandelay_ExportedDataMode on success, null on invalid JSON.
	 */
	public static function fromJson($json)
	{
		$data = json_decode($json, true);
		return $data === null ? null : new static($data);
	}


	protected function defineAttributes()
	{
		return array(
			'assets'     => AttributeType::Mixed,
			'categories' => AttributeType::Mixed,
			'fields'     => AttributeType::Mixed,
			'globals'    => AttributeType::Mixed,
			'sections'   => AttributeType::Mixed,
			'tags'       => AttributeType::Mixed
		);
	}


	/**
	 * Returns a JSON representation of this model.
	 *
	 * @return string
	 */
	public function toJson()
	{
		return json_encode($this->getAttributes(), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);
	}

}