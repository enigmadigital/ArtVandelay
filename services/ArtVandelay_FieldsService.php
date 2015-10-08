<?php

namespace Craft;

/**
 * Class ArtVandelay_FieldsService
 */
class ArtVandelay_FieldsService extends BaseApplicationComponent
{
	//==============================================================================================================
	//=================================================  EXPORT  ===================================================
	//==============================================================================================================

	/**
	 * @param FieldGroupModel[] $groups
	 * @return array
	 */
	public function export(array $groups)
	{
		$groupDefinitions = array();

		foreach ($groups as $group)
		{
			$fieldDefinitions = array();

			foreach ($group->getFields() as $field)
			{
				$fieldDefinitions[$field->handle] = $this->getFieldDefinition($field);
			}

			$groupDefinitions[$group->name] = $fieldDefinitions;
		}

		return $groupDefinitions;
	}

	/**
	 * @param SectionModel[] $sections
	 * @param EntryTypeModel $entryType
	 * @param string $tabName
	 * @return array
	 */
	public function exportTabFields(array $sections, EntryTypeModel $entryType, $tabName)
	{
		//get list of fieldnames in selected
		$fieldNames = array();
		foreach ($this->getFieldLayoutDefinition($entryType->getFieldLayout()) as $contentType) {
			foreach ($contentType as $key => $value) {
				if ($key == $tabName) {
					$entryTypeDefs[$key] = $value;
					foreach (array_keys($value) as $fieldName) {
						$fieldNames[] = $fieldName;
					}
				}
			}
		}
		return $this->exportFieldNames($fieldNames);
	}

	/**
	 * @param SectionModel[] $sections
	 * @param array $allowedEntryTypeIds
	 * @return array
	 */
	public function exportSectionFields(array $sections, array $allowedEntryTypeIds)
	{
		$fieldNames = array();

		foreach ($sections as $section) {
			$entryTypeDefinitions = array();

			foreach ($section->getEntryTypes() as $entryType) {
				if ($allowedEntryTypeIds === null || in_array($entryType->id, $allowedEntryTypeIds)) {
					foreach ($this->getFieldLayoutDefinition($entryType->getFieldLayout()) as $contenttype) {

						foreach ($contenttype as $key => $value) {
							$entryTypeDefinitions[$key] = $value;
							foreach ($value as $fieldkey => $fieldvalue) {
								$fieldNames[] = $fieldkey;
							}
						}
					}
				}
			}
		}

		return $this->exportFieldNames($fieldNames);
	}
	/**
	 * @param $fieldNames
	 * @return array
	 */
	public function exportFieldNames($fieldNames)
	{
		$groupDefinitions = array();

		$groups = craft()->fields->getAllGroups();

		foreach ($groups as $group) {
			$fieldDefinitions = array();
			foreach ($group->getFields() as $field) {
				if (in_array($field->handle, $fieldNames)) {
					$fieldDefinitions[$field->handle] = $this->getFieldDefinition($field);
				}
			}
			if (sizeOf($fieldDefinitions) > 0) {
				$groupDefinitions[$group->name] = $fieldDefinitions;
			}
		}
		return $groupDefinitions;
	}
	
	/**
	 * @param FieldModel $field
	 * @param bool $includeContext
	 * @return array
	 */
    private function getFieldDefinition(FieldModel $field, $includeContext = true)
    {
        $definition =  array(
            'name'         => $field->name,
			'required'     => $field->required,
            'instructions' => $field->instructions,
            'translatable' => $field->translatable,
            'type'         => $field->type,
            'settings'     => $field->settings
        );

		if($includeContext){
			$definition['context'] = $field->context;
		}

		switch($field->type)
		{
			case 'Entries':
				$definition['settings']['sources'] =  $this->getSourceHandles($definition['settings']['sources']);
				break;
			case 'Matrix':
				$definition['blockTypes'] = $this->getBlockTypeDefinitions($field);
				break;
		}

		return $definition;
    }

	/**
	 * @param array $sources
	 * @return array
	 */
	private function getSourceHandles(array $sources)
	{
		$handleSources = [];
		foreach ($sources as $source) {
			$sectionId = explode(':', $source)[1];
			$handleSources[] = craft()->sections->getSectionById($sectionId)->handle;
		}
		return $handleSources;
	}

	/**
	 * @param FieldModel $field
	 * @return array
	 */
	private function getBlockTypeDefinitions(FieldModel $field)
	{
		$blockTypeDefinitions = array();

		$blockTypes = craft()->matrix->getBlockTypesByFieldId($field->id);
		foreach ($blockTypes as $blockType) {
			$blockTypeFieldDefinitions = array();

			foreach ($blockType->getFields() as $blockTypeField) {
				$blockTypeFieldDefinitions[$blockTypeField->handle] = $this->getFieldDefinition($blockTypeField, false);
			}

			$blockTypeDefinitions[$blockType->handle] = array(
				'name' => $blockType->name,
				'fields' => $blockTypeFieldDefinitions
			);
		}
		return $blockTypeDefinitions;
	}

	//==============================================================================================================
	//=================================================  IMPORT  ===================================================
	//==============================================================================================================


	/**
	 * Attempt to import fields.
	 *
	 * @param array $groupDefs
	 *
	 * @return ArtVandelay_ResultModel
	 */
	public function import($groupDefs)
	{
		$result = new ArtVandelay_ResultModel();
		$sections = craft()->sections->getAllSections('handle');

		if(empty($groupDefs))
		{
			// Ignore importing fields.
			return $result;
		}

		$groups     = craft()->fields->getAllGroups('name');
		$fields     = craft()->fields->getAllFields('handle');

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

				if (!$field->getFieldType())
				{
					if ($field->type == 'Matrix')
					{
						return $result->error("One of the field's types does not exist. Are you missing a plugin?");
					}
					else
					{
						return $result->error("Field type '$field->type' does not exist. Are you missing a plugin?");
					}
				}

				if($field->type == 'Entries')
				{
					$settings = $fieldDef['settings'];
					$sources = [];
					foreach($settings['sources'] as $sourceHandle)
					{
						if(array_key_exists($sourceHandle, $sections)) {
							$sources[] = 'section:' . $sections[$sourceHandle]->id;
						}
					}
					$settings['sources'] = $sources;
					$field->settings = $settings;
				}

				if ($field->type == 'Matrix')
				{
					$blockTypes = craft()->matrix->getBlockTypesByFieldId($field->id, 'handle');

					if (!array_key_exists('blockTypes', $fieldDef))
					{
						return $result->error('`fields[handle].blockTypes` must exist');
					}

					foreach ($fieldDef['blockTypes'] as $blockTypeHandle => $blockTypeDef)
					{
						$blockType = array_key_exists($blockTypeHandle, $blockTypes)
							? $blockTypes[$blockTypeHandle]
							: new MatrixBlockTypeModel();

						$blockType->fieldId = $field->id;
						$blockType->name    = $blockTypeDef['name'];
						$blockType->handle  = $blockTypeHandle;

						if (!array_key_exists('fields', $blockTypeDef))
						{
							return $result->error('`fields[handle].blockTypes[handle].fields` must exist');
						}

						$blockTypeFields = array();
						foreach ($blockType->getFields() as $blockTypeField)
						{
							$blockTypeFields[$blockTypeField->handle] = $blockTypeField;
						}

						$newBlockTypeFields = array();

						foreach ($blockTypeDef['fields'] as $blockTypeFieldHandle => $blockTypeFieldDef)
						{
							$blockTypeField = array_key_exists($blockTypeFieldHandle, $blockTypeFields)
								? $blockTypeFields[$blockTypeFieldHandle]
								: new FieldModel();

							$blockTypeField->name         = $blockTypeFieldDef['name'];
							$blockTypeField->handle       = $blockTypeFieldHandle;
							$blockTypeField->required     = $blockTypeFieldDef['required'];
							$blockTypeField->translatable = $blockTypeFieldDef['translatable'];
							$blockTypeField->type         = $blockTypeFieldDef['type'];
							$blockTypeField->settings     = $blockTypeFieldDef['settings'];

							$newBlockTypeFields[] = $blockTypeField;
						}

						$blockType->setFields($newBlockTypeFields);

						$blockTypes[$blockTypeHandle] = $blockType;
					}
					$field->settings = $field->getFieldType()->getSettings();
					$field->settings->setBlockTypes($blockTypes);

				}
				if (!craft()->fields->saveField($field))
				{
					return $result->error($field->getAllErrors());
				}
			}
		}

		return $result;
	}

	//==============================================================================================================
	//==============================================  FIELD LAYOUT  ================================================
	//==============================================================================================================

	/**
	 * @param FieldLayoutModel $fieldLayout
	 * @return array
	 */
	public function getFieldLayoutDefinition(FieldLayoutModel $fieldLayout)
	{
		if ($fieldLayout->getTabs()) {
			$tabDefinitions = array();

			foreach ($fieldLayout->getTabs() as $tab) {
				$tabDefinitions[$tab->name] = $this->getFieldLayoutFieldsDefinition($tab->getFields());
			}

			return array('tabs' => $tabDefinitions);
		}

		return array('fields' => $this->getFieldLayoutFieldsDefinition($fieldLayout->getFields()));
	}

	/**
	 * @param FieldLayoutFieldModel[] $fields
	 * @return array
	 */
	private function getFieldLayoutFieldsDefinition(array $fields)
	{
		$fieldDefinitions = array();

		foreach ($fields as $field) {
			$fieldDefinitions[$field->getField()->handle] = $field->required;
		}

		return $fieldDefinitions;
	}
}
