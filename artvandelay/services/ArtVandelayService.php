<?php
namespace Craft;

class ArtVandelayService extends BaseApplicationComponent
{
	public function exportFields(array $groups)
	{
		$result = array();

		foreach ($groups as $group)
		{
			$fields = craft()->fields->getFieldsByGroupId($group->id);

			$result[$group->name] = array();

			foreach ($fields as $field)
			{
				$result[$group->name][$field->handle] = array(
					'name'         => $field->name,
					'context'      => $field->context,
					'instructions' => $field->instructions,
					'translatable' => $field->translatable,
					'type'         => $field->type,
					'settings'     => $field->settings
				);
			}
		}

		return $result;
	}

	public function importFields($data)
	{
		$groups = craft()->fields->getAllGroups('name');
		$fields = craft()->fields->getAllFields('handle');

		if (!is_array($data))
		{
			return false;
		}

		foreach ($data as $groupName => $groupData)
		{
			$group = array_key_exists($groupName, $groups)
				? $groups[$groupName]
				: new FieldGroupModel();

			$group->name = $groupName;

			if (!craft()->fields->saveGroup($group))
				return false;

			if (!is_array($groupData))
			{
				return false;
			}

			foreach ($groupData as $fieldHandle => $fieldData)
			{
				$field = array_key_exists($fieldHandle, $fields)
					? $fields[$fieldHandle]
					: new FieldModel();

				$field->handle  = $fieldHandle;
				$field->groupId = $group->id;

				$field->name         = $fieldData->name;
				$field->context      = $fieldData->context;
				$field->instructions = $fieldData->instructions;
				$field->translatable = $fieldData->translatable;
				$field->type         = $fieldData->type;
				$field->settings     = (array) $fieldData->settings;

				if (!craft()->fields->saveField($field))
					return false;
			}
		}

		return true;
	}

	public function exportSections(array $sections)
	{
		return array();
	}

	public function importSections($data)
	{
		return true;
	}
}
