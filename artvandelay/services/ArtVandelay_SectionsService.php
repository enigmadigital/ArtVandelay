<?php
namespace Craft;

class ArtVandelay_SectionsService extends BaseApplicationComponent
{
	public function export(array $sections, $allowedEntryTypeIds)
	{
		$sectionDefs = array();

		foreach ($sections as $section)
		{
			$localeDefs = array();

			foreach ($section->getLocales() as $locale)
			{
				$localeDefs[$locale->locale] = array(
					'enabledByDefault' => $locale->enabledByDefault,
					'urlFormat'        => $locale->urlFormat,
					'nestedUrlFormat'  => $locale->nestedUrlFormat
				);
			}

			$entryTypeDefs = array();

			foreach ($section->getEntryTypes() as $entryType)
			{
				if ($allowedEntryTypeIds === null || in_array($entryType->id, $allowedEntryTypeIds))
				{
					$entryTypeDefs[$entryType->handle] = array(
						'name'          => $entryType->name,
						'hasTitleField' => $entryType->hasTitleField,
						'titleLabel'    => $entryType->titleLabel,
						'titleFormat'   => $entryType->titleFormat,

						'fieldLayout' => $this->_exportFieldLayout($entryType->getFieldLayout())
					);
				}
			}

			$sectionDefs[$section->handle] = array(
				'name'             => $section->name,
				'type'             => $section->type,
				'hasUrls'          => $section->hasUrls,
				'template'         => $section->template,
				'maxLevels'        => $section->maxLevels,
				'enableVersioning' => $section->enableVersioning,

				'locales'    => $localeDefs,
				'entryTypes' => $entryTypeDefs
			);
		}

		return $sectionDefs;
	}

	private function _exportFieldLayout(FieldLayoutModel $fieldLayout)
	{
		if ($fieldLayout->getTabs())
		{
			$tabDefs = array();

			foreach ($fieldLayout->getTabs() as $tab)
			{
				$tabDefs[$tab->name] = array();

				foreach ($tab->getFields() as $field)
				{
					$tabDefs[$tab->name][$field->getField()->handle] = $field->required;
				}
			}

			return array(
				'tabs' => $tabDefs
			);
		}
		else
		{
			$fieldDefs = array();

			foreach ($fieldLayout->getFields() as $field)
			{
				$fieldDefs[$field->getField()->handle] = $field->required;
			}

			return array(
				'fields' => $fieldDefs
			);
		}
	}

	public function import($sectionDefs)
	{
		$sections = craft()->sections->getAllSections('handle');

		if (!is_object($sectionDefs))
		{
			return array('ok' => false, 'errors' => array(
				'`sections` must exist and be an object'
			));
		}

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

			if (!property_exists($sectionDef, 'locales') || !is_object($sectionDef->locales))
			{
				return array('ok' => false, 'errors' => array(
					'`sections[handle].locales` must exist and be an object'
				));
			}

			$locales = $section->getLocales();

			foreach ($sectionDef->locales as $localeId => $localeDef)
			{
				$locale = array_key_exists($localeId, $locales)
					? $locales[$localeId]
					: new SectionLocaleModel();

				$locale->locale = $localeId;
				$locale->enabledByDefault = $localeDef->enabledByDefault;
				$locale->urlFormat = $localeDef->urlFormat;
				$locale->nestedUrlFormat = $localeDef->nestedUrlFormat;
			}

			$section->setLocales($locales);

			if (!craft()->sections->saveSection($section))
			{
				return array('ok' => false, 'errors' => $section->getAllErrors());
			}

			$entryTypes = $section->getEntryTypes('handle');

			if (!property_exists($sectionDef, 'entryTypes') || !is_object($sectionDef->entryTypes))
			{
				return array('ok' => false, 'errors' => array(
					'`sections[handle].entryTypes` must exist and be an object'
				));
			}

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

				$result = $this->_importFieldLayout($entryTypeDef->fieldLayout);
				if ($result['fieldLayout'])
				{
					$entryType->setFieldLayout($result['fieldLayout']);

					if (!craft()->sections->saveEntryType($entryType))
					{
						return array('ok' => false, 'errors' => $entryType->getAllErrors());
					}
				}
				else
				{
					return array('ok' => false, $result['errors']);
				}
			}
		}

		return array('ok' => true, 'errors' => array());
	}

	private function _importFieldLayout($fieldLayoutDef)
	{
		$layoutTabs   = array();
		$layoutFields = array();

		if (property_exists($fieldLayoutDef, 'tabs'))
		{
			$tabSortOrder = 0;

			if (!is_object($fieldLayoutDef->tabs))
			{
				return array('fieldLayout' => null, 'errors' => array('`sections[handle].entryTypes[handle].fieldLayout.tabs` must be an object'));
			}

			foreach ($fieldLayoutDef->tabs as $tabName => $tabDef)
			{
				if (!is_object($tabDef))
				{
					return array('fieldLayout' => null, 'errors' => array('`sections[handle].entryTypes[handle].fieldLayout.tabs[handle]` must be an object'));
				}

				$layoutTabFields = array();

				foreach ($tabDef as $fieldHandle => $required)
				{
					$fieldSortOrder = 0;

					$field = craft()->fields->getFieldByHandle($fieldHandle);
					if ($field)
					{
						$layoutField = new FieldLayoutFieldModel();
						$layoutField->fieldId   = $field->id;
						$layoutField->required  = $required;
						$layoutField->sortOrder = ++$fieldSortOrder;

						$layoutTabFields[] = $layoutField;
						$layoutFields[] = $layoutField;
					}
				}

				$layoutTab = new FieldLayoutTabModel();
				$layoutTab->name      = $tabName;
				$layoutTab->sortOrder = ++$tabSortOrder;
				$layoutTab->setFields($layoutTabFields);

				$layoutTabs[] = $layoutTab;
			}
		}
		else if (property_exists($fieldLayoutDef, 'fields'))
		{
			$fieldSortOrder = 0;

			if (!is_object($fieldLayoutDef->fields))
			{
				return array('fieldLayout' => null, 'errors' => array('`sections[handle].entryTypes[handle].fieldLayout.fields` must be an object'));
			}

			foreach ($fieldLayoutDef->fields as $fieldHandle => $required)
			{
				$field = craft()->fields->getFieldByHandle($fieldHandle);
				if ($field)
				{
					$layoutField = new FieldLayoutFieldModel();
					$layoutField->fieldId   = $field->id;
					$layoutField->required  = $required;
					$layoutField->sortOrder = ++$fieldSortOrder;

					$layoutFields[] = $layoutField;
				}
			}
		}

		$fieldLayout = new FieldLayoutModel();
		$fieldLayout->type = ElementType::Entry;
		$fieldLayout->setTabs($layoutTabs);
		$fieldLayout->setFields($layoutFields);

		return array('fieldLayout' => $fieldLayout, 'errors' => array());
	}
}
