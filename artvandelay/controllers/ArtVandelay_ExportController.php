<?php namespace Craft;

class ArtVandelay_ExportController extends BaseController
{

	public function actionIndex()
	{
		$output = array(
			'fields' => array(
				'groups' => array(),
				'data' => array()
			),
			'sections' => array(
				'groups' => array(),
				'data' => array()
			),
		);


		$groups = craft()->request->getParam('groups', array());

		foreach($groups as $groupId => $value)
		{
			$group = craft()->fields->getGroupById($groupId);
			$fields = craft()->fields->getFieldsByGroupId($groupId);

			$output['fields']['groups'][$groupId] = $group->name;

			foreach($fields as $field)
			{
				$output['fields']['data'][$field->handle] = array(
					'groupId' => $groupId,
					'name' => $field->name,
					'context' => $field->context,
					'instructions' => $field->instructions,
					'translatable' => $field->translatable,
					'type' => $field->type,
					'settings' => $field->settings
				);
			}
		}

		$this->returnJson($output);
	}

}