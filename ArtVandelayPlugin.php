<?php namespace Craft;


class ArtVandelayPlugin extends BasePlugin
{
	public function getName()
	{
		return 'Art Vandelay';
	}

	public function getVersion()
	{
		return '2.0.0a';
	}

	public function getDeveloper()
	{
		return 'XO Digital';
	}

	public function getDeveloperUrl()
	{
		return 'http://www.xodigital.com.au';
	}

	public function hasCpSection()
	{
		return false;
	}

	public function getSettingsUrl()
	{
		return 'artVandelay';
	}

	public function registerCpRoutes()
	{
		return array(
			'artVandelay' => array('action' => 'artVandelay/exportFields'),
			'artVandelay/version1' => array('action' => 'artVandelay/index'),
			'artVandelay/export/tabs' => array('action' => 'artVandelay/exportTabs'),
			'artVandelay/export/fields' => array('action' => 'artVandelay/exportFields'),
			'artVandelay/export/sections' => array('action' => 'artVandelay/exportSections'),
			'artVandelay/import/1' => array('action' => 'artVandelay/importStep1'),
		);
	}
}
