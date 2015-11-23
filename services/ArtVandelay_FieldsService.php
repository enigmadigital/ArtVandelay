<?php namespace Craft;

class ArtVandelay_FieldsService extends BaseApplicationComponent
{

	public function exportSectionFields($sections, $allowedEntryTypeIds)
	{
		$fieldNames = array();

		foreach ($sections as $section) {
			$entryTypeDefs = array();

			foreach ($section->getEntryTypes() as $entryType) {
				if ($allowedEntryTypeIds === null || in_array($entryType->id, $allowedEntryTypeIds)) {
					foreach ($this->_exportFieldLayout($entryType->getFieldLayout()) as $contenttype) {

						foreach ($contenttype as $key => $value) {
							$entryTypeDefs[$key] = $value;
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

				if ($field->type == 'Matrix')
				{
					$blockTypeDefs = array();

					$blockTypes = craft()->matrix->getBlockTypesByFieldId($field->id);
					foreach ($blockTypes as $blockType)
					{
						$blockTypeFieldDefs = array();

						foreach ($blockType->getFields() as $blockTypeField)
						{
							$blockTypeFieldDefs[$blockTypeField->handle] = array(
								'name'         => $blockTypeField->name,
								'required'     => $blockTypeField->required,
								'translatable' => $blockTypeField->translatable,
								'type'         => $blockTypeField->type,
								'instructions' => $blockTypeField->instructions
							);
						}

						$blockTypeDefs[$blockType->handle] = array(
							'name'   => $blockType->name,
							'fields' => $blockTypeFieldDefs
						);
					}

					$fieldDefs[$field->handle]['blockTypes'] = $blockTypeDefs;
				}
			}

			$groupDefs[$group->name] = $fieldDefs;
		}

		return $groupDefs;
	}

	public function exportTabFields($section, $entryType, $tabName)
	{

		//get list of fieldnames in selected
		$fieldnames = array();
		foreach ($this->_exportFieldLayout($entryType->getFieldLayout()) as $contenttype) {
			foreach ($contenttype as $key => $value) {
				if ($key == $tabName) {
					$entryTypeDefs[$key] = $value;
					foreach ($value as $fieldkey => $fieldvalue) {
						$fieldnames[] = $fieldkey;
					}
				}
			}
		}

		return $this->exportFieldNames($fieldnames);
	}

	public function exportFieldNames($fieldnames)
	{
		$groupDefs = array();

		$groups = craft()->fields->getAllGroups();

		foreach ($groups as $group) {
			$fieldDefs = array();
			foreach ($group->getFields() as $field) {
				if (in_array($field->handle, $fieldnames)) {
					$fieldDefs[$field->handle] = array(
						'name' => $field->name,
						'context' => $field->context,
						'instructions' => $field->instructions,
						'translatable' => $field->translatable,
						'type' => $field->type,
						'settings' => $field->settings
					);

					if ($field->type == 'Matrix') {
						$blockTypeDefs = array();

						$blockTypes = craft()->matrix->getBlockTypesByFieldId($field->id);
						foreach ($blockTypes as $blockType) {
							$blockTypeFieldDefs = array();

							foreach ($blockType->getFields() as $blockTypeField) {
								$blockTypeFieldDefs[$blockTypeField->handle] = array(
									'name' => $blockTypeField->name,
									'required' => $blockTypeField->required,
									'translatable' => $blockTypeField->translatable,
									'type' => $blockTypeField->type,
									'instructions' => $blockTypeField->instructions
								);
							}

							$blockTypeDefs[$blockType->handle] = array(
								'name' => $blockType->name,
								'fields' => $blockTypeFieldDefs
							);
						}

						$fieldDefs[$field->handle]['blockTypes'] = $blockTypeDefs;
					}
				}
			}
			if (sizeOf($fieldDefs) > 0) {
				$groupDefs[$group->name] = $fieldDefs;
			}
		}
		return $groupDefs;
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
	 * Attempt to import fields.
	 *
	 * @param array $groupDefs
	 *
	 * @return ArtVandelay_ResultModel
	 */
	public function import($groupDefs)
	{
		$result = new ArtVandelay_ResultModel();

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

				if (!craft()->fields->saveField($field))
				{
					return $result->error($field->getAllErrors());
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
							$blockTypeField->instructions = $blockTypeFieldDef['instructions'];
							$newBlockTypeFields[] = $blockTypeField;
						}

						$blockType->setFields($newBlockTypeFields);

						if (!craft()->matrix->saveBlockType($blockType))
						{
							return $result->error($blockType->getAllErrors());
						}
					}
				}
			}
		}

		return $result;
	}
}
