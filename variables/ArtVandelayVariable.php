<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 10/30/2014
 * Time: 10:13 AM
 */

namespace Craft;


class ArtVandelayVariable
{
	public function FieldGroupStatus($name)
	{
		foreach (craft()->fields->getAllGroups() as $group)
		{
			if ($name == $group->name)
				return '<p class="exists">Field group already exists</p>';
		}

		return "<p class='new'>New Field Group</p>";
	}

	public function TabFieldStatus($sectionhandle, $entrytypehandle, $tabName, $fieldHandle)
	{

		foreach (craft()->sections->getAllSections() as $section)
		{
			if ($sectionhandle == $section->handle) {
				foreach ($section->getEntryTypes() as $entryType) {
					if ($entrytypehandle == $entryType->handle)
						foreach ($this->_exportFieldLayout($entryType->getFieldLayout()) as $contenttype) {
							foreach ($contenttype as $key => $value) {
								if ($key == $tabName) {
									foreach ($value as $fieldkey => $fieldvalue) {
										if ($fieldkey == $fieldHandle)
											return '<p class="exists">Field already exists in tab.</p>';
									}
								}
							}
						}
				}
			}
		}

		return "<p class='new'>Field will be added to tab</p>";
	}


	public function EntryTypeHasTab($sectionHandle, $entryTypeHandle, $tabName)
	{
		$section = craft()->sections->getSectionByHandle($sectionHandle);
		foreach ($section->getEntryTypes() as $entryType) {
			if ($entryType->handle == $entryTypeHandle) {
				foreach ($this->_exportFieldLayout($entryType->getFieldLayout()) as $contenttype) {
					foreach ($contenttype as $key => $value) {
						if ($key == $tabName) {
							return true;
							break;
						}
					}
				}
			}
		}

		return false;
	}

	public function WhatTabIsFieldIn($sectionHandle, $entryTypeHandle, $field)
	{
		$section = craft()->sections->getSectionByHandle($sectionHandle);
		foreach ($section->getEntryTypes() as $entryType) {
			if ($entryType->handle == $entryTypeHandle) {
				foreach ($this->_exportFieldLayout($entryType->getFieldLayout()) as $contenttype) {
					foreach ($contenttype as $key => $value) {
						foreach ($value as $fieldkey => $fieldvalue) {
							if ($fieldkey == $field) {
								return $key;
							}
						}
					}
				}
			}
		}
		return '';
	}


	public function EntryTypes()
	{

		$list = array();
		foreach (craft()->sections->getAllSections() as $section) {
			foreach ($section->getEntryTypes() as $entryType) {
					$list[] = $section->handle . '||' . $entryType->handle;
			}
		}

		return $list;
	}


	public function TabStatus($tabName)
	{

		$names = '';
		foreach (craft()->sections->getAllSections() as $section) {
			foreach ($section->getEntryTypes() as $entryType) {
				foreach ($this->_exportFieldLayout($entryType->getFieldLayout()) as $contenttype) {
					foreach ($contenttype as $key => $value) {
						if ($key == $tabName) {
							if ($section->handle !=  $entryType->handle)
								$names .= '<li>'. $section->handle . '-' . $entryType->handle . '</li>';
							else
								$names .= '<li>'. $entryType->handle . '</li>';
						}
					}
				}
			}
		}

		if (strlen($names) > 0)
			return "<p class='exists'>A Tab with this name already exists in these templates:</p><ul>" . $names . "</ul";

		return 'New tab';
	}


	public function EntryTypeTabStatus($sectionhandle, $entrytypehandle, $tabName)
	{
		foreach (craft()->sections->getAllSections() as $section)
		{
			if ($sectionhandle == $section->handle) {
				foreach ($section->getEntryTypes() as $entryType) {
					if ($entrytypehandle == $entryType->handle)
						foreach ($this->_exportFieldLayout($entryType->getFieldLayout()) as $contenttype) {
							foreach ($contenttype as $key => $value) {
								if ($key == $tabName) {
									return '<p class="exists">Tab already exists, will be overwritten.</p>';
								}
							}
						}
				}
			}
		}

		return "<p class='new'>New Tab</p>";
	}

	public function EntryTypeStatus($sectionhandle, $handle)
	{
		foreach (craft()->sections->getAllSections() as $section)
		{
			if ($sectionhandle == $section->handle) {
				foreach ($section->getEntryTypes() as $entryType) {
					if ($handle == $entryType->handle)
						return '<p class="exists">Entry Type already exists, will be overwritten.</p>';
				}
			}
		}

		return "<p class='new'>New Entry Type</p>";
	}


	public function SectionStatus($name, $type)
	{
		foreach (craft()->sections->getAllSections() as $section)
		{
			if ($name == $section->handle) {
				if ($type != $section->type)
					return '<p class="exists">Section already exists, will be overwritten. Type has changed from ' . $section->type . ' to ' . $type . '</p>';
				else
					return '<p class="exists">Section already exists, will be overwritten</p>';
			}
		}

		return "<p class='new'>New Section</p>";
	}

	public function FieldStatus($impgroup, $handle, $type)
	{
		foreach (craft()->fields->getAllGroups() as $group) {
			foreach ($group->getFields() as $field) {
				if ($field->handle == $handle) {
					$msg = "Field already exists will be overwritten";
					if ($type != $field->type)
						$msg .= ', field type will change from ' . $field->type . ' to ' . $type;
					if ($group != $impgroup)
						$msg .= ', field will move from group ' . $group . ' to ' . $group;
					return '<p class="exists">' . $msg . '</p>';
				}
			}
		}

		return "<p class='new'>New Field</p>";
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

}
