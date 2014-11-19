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

		if (!is_array($sectionDefs))
		{
			return false;
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

			if (!is_array($sectionDef->locales))
			{
				return false;
			}

			$locales = array();

			foreach ($sectionDef->locales as $localeId => $localeDef)
			{
				$locales[$localeId] = new SectionLocaleModel(array(
					'enabledByDefault' => $localeDef->enabledByDefault,
					'urlFormat'        => $localeDef->urlFormat,
					'nestedUrlFormat'  => $localeDef->nestedUrlFormat
				));
			}

			$section->setLocales($locales);

			if (!craft()->sections->saveSection($section))
				return false;

			$entryTypes = $section->getEntryTypes('handle');

			if (!is_array($sectionDef->entryTypes))
			{
				return false;
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

				$fieldLayout = $this->_importFieldLayout($entryTypeDef->fieldLayout);
				$entryType->setFieldLayout($fieldLayout);

				if (!craft()->sections->saveEntryType($entryType))
					return false;
			}
		}

		return true;
	}

	private function _importFieldLayout($fieldLayoutDef)
	{
		$layoutTabs   = array();
		$layoutFields = array();

		if (property_exists($fieldLayoutDef, 'tabs'))
		{
			$tabSortOrder = 0;

			if (!is_array($fieldLayoutDef->tabs))
			{
				return false;
			}

			foreach ($fieldLayoutDef->tabs as $tabName => $tabDef)
			{
				if (!is_array($tabDef))
				{
					return false;
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

			if (!is_array($fieldLayoutDef->fields))
			{
				return false;
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

		return $fieldLayout;
	}
}
