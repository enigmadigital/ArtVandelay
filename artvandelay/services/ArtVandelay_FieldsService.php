<?php
namespace Craft;

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

	public function import($groupDefs)
	{
		$groups     = craft()->fields->getAllGroups('name');
		$fields     = craft()->fields->getAllFields('handle');
		$fieldTypes = craft()->fields->getAllFieldTypes();

		if (!is_object($groupDefs))
		{
			return array('ok' => false, 'errors' => array('`fields` must be an object'));
		}

		foreach ($groupDefs as $groupName => $fieldDefs)
		{
			$group = array_key_exists($groupName, $groups)
				? $groups[$groupName]
				: new FieldGroupModel();

			$group->name = $groupName;

			if (!craft()->fields->saveGroup($group))
			{
				return array('ok' => false, 'errors' => $group->getAllErrors());
			}

			if (!is_object($fieldDefs))
			{
				return array('ok' => false, 'errors' => array('`fields[handle]` must be an object'));
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
						return array('ok' => false, 'errors' => $field->getAllErrors());
					}
				}
			}
		}

		return array('ok' => true, 'errors' => array());
	}
}
