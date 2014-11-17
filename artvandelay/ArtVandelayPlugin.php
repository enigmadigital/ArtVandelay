<?php namespace Craft;


class ArtVandelayPlugin extends BasePlugin
{

	public function defineSettings()
	{
		return array(
			// ??
		);
	}


	public function prepSettings($settings)
	{
		// ??

		return $settings;
	}


	public function getSettingsHtml()
	{
		return craft()->templates->render('artvandelay/settings/_index', array(
			'settings' => $this->getSettings(),
			'groups' => craft()->fields->getAllGroups()
		));
	}


	public function registerCpRoutes()
	{
		return array(
			// ?
		);
	}

	public function getName(){ return 'Art Vandelay'; }
	public function getVersion(){ return '0.0.1'; }
	public function getDeveloper(){ return 'XO Digital'; }
	public function getDeveloperUrl(){ return 'http://www.xodigital.com.au'; }
	public function hasCpSection(){ return false; }

}