<?php namespace Craft;

class ArtVandelay_ExportController extends BaseController
{

	public function actionIndex()
	{
		$output = array(
			'fields' => array(),
			'sections' => array(),
		);


		$groups = craft()->request->getParam('groups', array());

		foreach($groups as $groupId => $value)
		{
			$group = craft()->fields->getGroupById($groupId);
			$fields = craft()->fields->getFieldsByGroupId($groupId);

			$output['fields'][$group->name] = [];

			foreach($fields as $field)
			{
				$output['fields'][$group->name][$field->handle] = array(
					'name' => $field->name,
					'context' => $field->context,
					'instructions' => $field->instructions,
					'translatable' => $field->translatable,
					'type' => $field->type,
					'settings' => $field->settings
				);
			}
		}

		//$this->returnJson($output);
		JsonHelper::sendJsonHeaders();
		echo json_encode($output, JSON_PRETTY_PRINT);

		craft()->end();
	}

}