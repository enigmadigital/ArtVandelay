<?php namespace Craft;


class ArtVandelayPlugin extends BasePlugin
{
	public function getName()
	{
		return 'Art Vandelay';
	}

	public function getVersion()
	{
		return '1.0.2';
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
			'artVandelay' => array('action' => 'artVandelay/index'),
		);
	}
}
