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
			$result[$section->handle] = array(
				'name'             => $section->name,
				'type'             => $section->type,
				'hasUrls'          => $section->hasUrls,
				'template'         => $section->template,
				'maxLevels'        => $section->maxLevels,
				'enableVersioning' => $section->enableVersioning,

				'locales'    => $this->_exportLocales($section->getLocales()),
				'entryTypes' => $this->_exportEntryTypes($section->getEntryTypes())
			);
		}

		return $result;
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

	public function importSections($sectionDefs)
	{
		$sections   = craft()->sections->getAllSections('handle');
		$fields     = craft()->fields->getAllFields('handle');

		foreach ($sectionDefs as $sectionHandle => $sectionDef)
		{
			$section = array_key_exists($sectionHandle, $sections)
				? $sections[$sectionHandle]
				: new SectionModel();

			$section->handle = $sectionHandle;

			$section->name             = $sectionDef->name;
			$section->type             = $sectionDef->type;
			$section->hasUrls          = $sectionDef->hasUrls;
			$section->template         = $sectionDef->template;
			$section->maxLevels        = $sectionDef->maxLevels;
			$section->enableVersioning = $sectionDef->enableVersioning;

			$locales = array();

			foreach ($sectionDef->locales as $locale => $localeDef)
			{
				$locales[$locale] = new SectionLocaleModel(array(
					'enabledByDefault' => $localeDef->enabledByDefault,
					'urlFormat'        => $localeDef->urlFormat,
					'nestedUrlFormat'  => $localeDef->nestedUrlFormat
				));
			}

			$section->setLocales($locales);

			if (!craft()->sections->saveSection($section))
				return false;

			$entryTypes = $section->getEntryTypes('handle');

			foreach ($sectionDef->entryTypes as $entryTypeHandle => $entryTypeDef)
			{
				$entryType = array_key_exists($entryTypeHandle, $entryTypes)
					? $entryTypes[$entryTypeHandle]
					: new EntryTypeModel();

				$entryType->sectionId = $section->id;
				$entryType->handle    = $entryTypeHandle;

				$entryType->name          = $entryTypeDef->name;
				$entryType->hasTitleField = $entryTypeDef->hasTitleField;
				$entryType->titleLabel    = $entryTypeDef->titleLabel;
				$entryType->titleFormat   = $entryTypeDef->titleFormat;

				$layoutTabs   = array();
				$layoutFields = array();

				if (property_exists($entryTypeDef->fieldLayout, 'tabs'))
				{
					$tabSortOrder = 0;

					foreach ($entryTypeDef->fieldLayout->tabs as $tabName => $tabDef)
					{
						$layoutTabFields = array();

						foreach ($tabDef as $fieldHandle => $required)
						{
							$fieldSortOrder = 0;

							if (array_key_exists($fieldHandle, $fields))
							{
								$field = $fields[$fieldHandle];

								$layoutField = new FieldLayoutFieldModel();
								$layoutField->fieldId   = $field->id;
								$layoutField->required  = $required;
								$layoutField->sortOrder = ++$fieldSortOrder;

								$layoutTabFields[] = $layoutField;
								$layoutFields[]    = $layoutField;
							}
						}

						$layoutTab = new FieldLayoutTabModel();
						$layoutTab->name      = $tabName;
						$layoutTab->sortOrder = ++$tabSortOrder;
						$layoutTab->setFields($layoutTabFields);

						$layoutTabs[] = $layoutTab;
					}
				}
				else if (property_exists($entryTypeDef->fieldLayout, 'fields'))
				{
					$fieldSortOrder = 0;

					foreach ($entryTypeDef->fieldLayout->fields as $fieldHandle => $required)
					{
						$field = $fields[$fieldHandle];

						$layoutField = new FieldLayoutFieldModel();
						$layoutField->fieldId   = $field->id;
						$layoutField->required  = $required;
						$layoutField->sortOrder = ++$fieldSortOrder;

						$layoutFields[] = $layoutField;
					}
				}

				$layout = new FieldLayoutModel();
				$layout->type = ElementType::Entry;
				$layout->setTabs($layoutTabs);
				$layout->setFields($layoutFields);

				$entryType->setFieldLayout($layout);

				if (!craft()->sections->saveEntryType($entryType))
					return false;
			}
		}

		return true;
	}
}
