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
	 * @param $groupDefs
	 *
	 * @return ArtVandelay_ResultModel
	 */
	public function import($groupDefs)
	{
		$result = new ArtVandelay_ResultModel();

		if($groupDefs === null) return $result;

		$groups     = craft()->fields->getAllGroups('name');
		$fields     = craft()->fields->getAllFields('handle');
		$fieldTypes = craft()->fields->getAllFieldTypes();

		if (!is_object($groupDefs))
		{
			return $result->error('`fields` must be an object');
		}

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

			if (!is_object($fieldDefs))
			{
				return $result->error('`fields[handle]` must be an object');
			}

			foreach ($fieldDefs as $fieldHandle => $fieldDef)
			{
				if (array_key_exists($fieldDef->type, $fieldTypes))
				{
					$field = array_key_exists($fieldHandle, $fields)
						? $fields[$fieldHandle]
						: new FieldModel();

					$field->handle  = $fieldHandle;
					$field->groupId = $group->id;

					$field->name         = $fieldDef->name;
					$field->context      = $fieldDef->context;
					$field->instructions = $fieldDef->instructions;
					$field->translatable = $fieldDef->translatable;
					$field->type         = $fieldDef->type;
					$field->settings     = (array) $fieldDef->settings;

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
