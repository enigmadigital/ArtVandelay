<?php namespace Craft;


class ArtVandelayPlugin extends BasePlugin
{
	public function getName()
	{
		return 'Art Vandelay';
	}

	public function getVersion()
	{
		return '0.0.1';
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
		return 'artVandelay/index';
	}

	public function registerCpRoutes()
	{
		return array(
			'artVandelay/index' => array('action' => 'artVandelay/index'),
		);
	}
}
