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
						'fieldLayout'   => $this->_exportFieldLayout($entryType->getFieldLayout())
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
				'locales'          => $localeDefs,
				'entryTypes'       => $entryTypeDefs
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


	/**
	 * Attempt to import sections.
	 *
	 * @param array $sectionDefs
	 *
	 * @return ArtVandelay_ResultModel
	 */
	public function import($sectionDefs)
	{
		$result = new ArtVandelay_ResultModel();

		if(empty($sectionDefs))
		{
			// Ignore importing sections.
			return $result;
		}


		$sections = craft()->sections->getAllSections('handle');

		foreach ($sectionDefs as $sectionHandle => $sectionDef)
		{
			$section = array_key_exists($sectionHandle, $sections)
				? $sections[$sectionHandle]
				: new SectionModel();

			$section->setAttributes(array(
				'handle'           => $sectionHandle,
				'name'             => $sectionDef['name'],
				'type'             => $sectionDef['type'],
				'hasUrls'          => $sectionDef['hasUrls'],
				'template'         => $sectionDef['template'],
				'maxLevels'        => $sectionDef['maxLevels'],
				'enableVersioning' => $sectionDef['enableVersioning']
			));


			if (!array_key_exists('locales', $sectionDef))
			{
				return $result->error('`sections[handle].locales` must be defined');
			}

			$locales = $section->getLocales();

			foreach ($sectionDef['locales'] as $localeId => $localeDef)
			{
				$locale = array_key_exists($localeId, $locales)
					? $locales[$localeId]
					: new SectionLocaleModel();

				$locale->setAttributes(array(
					'locale'           => $localeId,
					'enabledByDefault' => $localeDef['enabledByDefault'],
					'urlFormat'        => $localeDef['urlFormat'],
					'nestedUrlFormat'  => $localeDef['nestedUrlFormat']
				));

				// Todo: Is this a hack? I don't see another way.
				// Todo: Might need a sorting order as well? It's NULL at the moment.
				craft()->db->createCommand()->insertOrUpdate('locales', array(
					'locale' => $locale->locale
				), array());

				$locales[$localeId] = $locale;
			}

			$section->setLocales($locales);

			if (!craft()->sections->saveSection($section))
			{
				return $result->error($section->getAllErrors());
			}


			$entryTypes = $section->getEntryTypes('handle');

			if (!array_key_exists('entryTypes', $sectionDef))
			{
				return $result->error('`sections[handle].entryTypes` must exist be defined');
			}

			foreach ($sectionDef['entryTypes'] as $entryTypeHandle => $entryTypeDef)
			{
				$entryType = array_key_exists($entryTypeHandle, $entryTypes)
					? $entryTypes[$entryTypeHandle]
					: new EntryTypeModel();

				$entryType->setAttributes(array(
					'sectionId'     => $section->id,
					'handle'        => $entryTypeHandle,
					'name'          => $entryTypeDef['name'],
					'hasTitleField' => $entryTypeDef['hasTitleField'],
					'titleLabel'    => $entryTypeDef['titleLabel'],
					'titleFormat'   => $entryTypeDef['titleFormat']
				));

				$fieldLayout = $this->_importFieldLayout($entryTypeDef['fieldLayout']);

				if($fieldLayout !== null)
				{
					$entryType->setFieldLayout($fieldLayout);

					if (!craft()->sections->saveEntryType($entryType))
					{
						return $result->error($entryType->getAllErrors());
					}
				}
				else
				{
					// Todo: Too ambiguous.
					return $result->error('Failed to import field layout.');
				}
			}
		}

		return $result;
	}


	/**
	 * Attempt to import a field layout.
	 *
	 * @param array $fieldLayoutDef
	 *
	 * @return FieldLayoutModel
	 */
	private function _importFieldLayout(Array $fieldLayoutDef)
	{
		$layoutTabs   = array();
		$layoutFields = array();

		if (array_key_exists('tabs', $fieldLayoutDef))
		{
			$tabSortOrder = 0;

			foreach ($fieldLayoutDef['tabs'] as $tabName => $tabDef)
			{
				$layoutTabFields = array();

				foreach ($tabDef as $fieldHandle => $required)
				{
					$fieldSortOrder = 0;

					$field = craft()->fields->getFieldByHandle($fieldHandle);

					if ($field)
					{
						$layoutField = new FieldLayoutFieldModel();

						$layoutField->setAttributes(array(
							'fieldId'   => $field->id,
							'required'  => $required,
							'sortOrder' => ++$fieldSortOrder
						));

						$layoutTabFields[] = $layoutField;
						$layoutFields[] = $layoutField;
					}
				}

				$layoutTab = new FieldLayoutTabModel();

				$layoutTab->setAttributes(array(
					'name' => $tabName,
					'sortOrder' => ++$tabSortOrder
				));

				$layoutTab->setFields($layoutTabFields);

				$layoutTabs[] = $layoutTab;
			}
		}

		else if (array_key_exists('fields', $fieldLayoutDef))
		{
			$fieldSortOrder = 0;

			foreach ($fieldLayoutDef['fields'] as $fieldHandle => $required)
			{
				$field = craft()->fields->getFieldByHandle($fieldHandle);

				if ($field)
				{
					$layoutField = new FieldLayoutFieldModel();

					$layoutField->setAttributes(array(
						'fieldId'   => $field->id,
						'required'  => $required,
						'sortOrder' => ++$fieldSortOrder
					));

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
