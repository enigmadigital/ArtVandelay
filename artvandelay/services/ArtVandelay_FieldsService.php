<?php namespace Craft;

class ArtVandelay_FieldsService extends BaseApplicationComponent
{

	public function export(array $groups)
	{
		$groupDefs = array();

		foreach ($groups as $group)
		{
			$fieldDefs = array();

			foreach ($group->getFields() as $field)
			{
				$fieldDefs[$field->handle] = array(
					'name'         => $field->name,
					'context'      => $field->context,
					'instructions' => $field->instructions,
					'translatable' => $field->translatable,
					'type'         => $field->type,
					'settings'     => $field->settings
				);
			}

			$groupDefs[$group->name] = $fieldDefs;
		}

		return $groupDefs;
	}


	/**
	 * Attempt to import fields.
	 *
	 * @param Array $groupDefs
	 *
	 * @return ArtVandelay_ResultModel
	 */
	public function import(Array $groupDefs)
	{
		$result = new ArtVandelay_ResultModel();

		if(empty($groupDefs))
		{
			// Ignore importing fields.
			return $result;
		}

		$groups     = craft()->fields->getAllGroups('name');
		$fields     = craft()->fields->getAllFields('handle');
		$fieldTypes = craft()->fields->getAllFieldTypes();

		foreach ($groupDefs as $groupName => $fieldDefs)
		{
			$group = array_key_exists($groupName, $groups)
				? $groups[$groupName]
				: new FieldGroupModel();

			$group->name = $groupName;

			if (!craft()->fields->saveGroup($group))
			{
				return $result->error($group->getAllErrors());
			}

			foreach ($fieldDefs as $fieldHandle => $fieldDef)
			{
				if (array_key_exists($fieldDef['type'], $fieldTypes))
				{
					$field = array_key_exists($fieldHandle, $fields)
						? $fields[$fieldHandle]
						: new FieldModel();

					$field->setAttributes(array(
						'handle'       => $fieldHandle,
						'groupId'      => $group->id,
						'name'         => $fieldDef['name'],
						'context'      => $fieldDef['context'],
						'instructions' => $fieldDef['instructions'],
						'translatable' => $fieldDef['translatable'],
						'type'         => $fieldDef['type'],
						'settings'     => $fieldDef['settings']
					));

					if (!craft()->fields->saveField($field))
					{
						return $result->error($field->getAllErrors());
					}
				}
			}
		}

		return $result;
	}
}
