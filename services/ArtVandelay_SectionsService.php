<?php
namespace Craft;

class ArtVandelay_SectionsService extends BaseApplicationComponent
{

	/**
	 * @param SectionModel[] $sections
	 * @param null $allowedEntryTypeIds
	 * @return array
	 */
	public function export(array $sections, $allowedEntryTypeIds = null)
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

			// Create initial section record
			if (!$this->saveSection($section))
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

			// Save section via craft after entrytypes have been created
			if (!craft()->sections->saveSection($section))
			{
				return $result->error($section->getAllErrors());
			}
		}

		return $result;
	}

	/**
	 * Save the section manually if it is new to prevent craft from creating the default entry type
	 * @param SectionModel $section
	 * @return mixed
	 */
	private function saveSection(SectionModel $section)
	{
		if ($section->type != 'single' && !$section->id){
			$sectionRecord = new SectionRecord();

			// Shared attributes
			$sectionRecord->name             = $section->name;
			$sectionRecord->handle           = $section->handle;
			$sectionRecord->type             = $section->type;
			$sectionRecord->enableVersioning = $section->enableVersioning;

			if(!$sectionRecord->save()){
				$section->addErrors($sectionRecord->getErrors());
				return false;
			};
			$section->id = $sectionRecord->id;
			return true;
		}
		return craft()->sections->saveSection($section);
	}


	/**
	 * Attempt to import a field layout.
	 * @param array $fieldLayoutDef
	 * @return FieldLayoutModel
	 */
	private function _importFieldLayout(Array $fieldLayoutDef)
	{
		$layoutFields = array();
		$requiredFields = array();

		if (array_key_exists('tabs', $fieldLayoutDef))
		{

			foreach ($fieldLayoutDef['tabs'] as $tabName => $tabDef)
			{
				$layoutTabFields = $this->getPrepareFieldLayout($tabDef);
				$requiredFields = array_merge($requiredFields, $layoutTabFields['required']);
				$layoutFields[$tabName] = $layoutTabFields['fields'];
			}
		}
		elseif (array_key_exists('fields', $fieldLayoutDef))
		{
			$layoutTabFields = $this->getPrepareFieldLayout($fieldLayoutDef);
			$requiredFields = $layoutTabFields['required'];
			$layoutFields = $layoutTabFields['fields'];
		}

		$fieldLayout = craft()->fields->assembleLayout($layoutFields, $requiredFields);
		$fieldLayout->type = ElementType::Entry;

		return $fieldLayout;
	}

	/**
	 * Get a prepared fieldLayout for the craft assembleLayout function
	 * @param array $fieldLayoutDef
	 * @return array
	 */
	private function getPrepareFieldLayout(array $fieldLayoutDef)
	{
		$layoutFields = array();
		$requiredFields = array();

		foreach ($fieldLayoutDef as $fieldHandle => $required)
		{
			$field = craft()->fields->getFieldByHandle($fieldHandle);

			if ($field instanceof FieldModel) {
				$layoutFields[] = $field->id;

				if($required){
					$requiredFields[] = $field->id;
				}
			}
		}

		return array(
			'fields' => $layoutFields,
			'required' => $requiredFields
		);
	}
}
