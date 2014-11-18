<?php
namespace Craft;

class ArtVandelayService extends BaseApplicationComponent
{
	public function exportFields(array $groups)
	{
		$result = array();

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

			$result[$group->name] = $fieldDefs;
		}

		return $result;
	}

	public function importFields($groupDefs)
	{
		$groups = craft()->fields->getAllGroups('name');
		$fields = craft()->fields->getAllFields('handle');

		if (!is_array($groupDefs))
		{
			return false;
		}

		foreach ($groupDefs as $groupName => $fieldDefs)
		{
			$group = array_key_exists($groupName, $groups)
				? $groups[$groupName]
				: new FieldGroupModel();

			$group->name = $groupName;

			if (!craft()->fields->saveGroup($group))
				return false;

			if (!is_array($fieldDefs))
			{
				return false;
			}

			foreach ($fieldDefs as $fieldHandle => $fieldDef)
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
					return false;
			}
		}

		return true;
	}

	public function exportSections(array $sections)
	{
		$result = array();

		foreach ($sections as $section)
		{
			$structure    = craft()->structures->getStructureById($section->structureId);

			$result[$section->handle] = array(
				'name'             => $section->name,
				'type'             => $section->type,
				'hasUrls'          => $section->hasUrls,
				'template'         => $section->template,
				'maxLevels'        => $section->maxLevels,
				'enableVersioning' => $section->enableVersioning,

				'structure'  => $structure ? $this->_exportStructure($structure) : null,
				'locales'    => $this->_exportLocales($section->getLocales()),
				'entryTypes' => $this->_exportEntryTypes($section->getEntryTypes())
			);
		}

		return $result;
	}

	private function _exportStructure(StructureModel $structure)
	{
		if ($structure)
		{
			return array(
				'maxLevels'      => $structure->maxLevels,
				'movePermission' => $structure->movePermission
			);
		}
		else
		{
			return null;
		}
	}

	private function _exportLocales(array $locales)
	{
		$result = array();

		foreach ($locales as $locale)
		{
			$result[$locale->locale] = array(
				'enabledByDefault' => $locale->enabledByDefault,
				'urlFormat'        => $locale->urlFormat,
				'nestedUrlFormat'  => $locale->nestedUrlFormat
			);
		}

		return $result;
	}

	private function _exportEntryTypes(array $entryTypes)
	{
		$result = array();

		foreach ($entryTypes as $entryType)
		{
			$result[$entryType->handle] = array(
				'name'          => $entryType->name,
				'hasTitleField' => $entryType->hasTitleField,
				'titleLabel'    => $entryType->titleLabel,
				'titleFormat'   => $entryType->titleFormat,

				'fieldLayout' => $this->_exportFieldLayout($entryType->getFieldLayout())
			);
		}

		return $result;
	}

	private function _exportFieldLayout(FieldLayoutModel $fieldLayout)
	{
		if ($fieldLayout->getTabs())
		{
			$result = array();

			foreach ($fieldLayout->getTabs() as $tab)
			{
				$result[$tab->name] = array();

				foreach ($tab->getFields() as $field)
				{
					$result[$tab->name][$field->getField()->handle] = $field->required;
				}
			}

			return array(
				'tabs' => $result
			);
		}
		else
		{
			$result = array();

			foreach ($fieldLayout->getFields() as $field)
			{
				$result[$field->getField()->handle] = $field->required;
			}

			return array(
				'fields' => $result
			);
		}
	}

	public function importSections($data)
	{
		return true;
	}
}
