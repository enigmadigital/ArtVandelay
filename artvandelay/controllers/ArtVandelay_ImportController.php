<?php namespace Craft;

class ArtVandelay_ImportController extends BaseController
{

	public function actionIndex()
	{
		$data = json_decode(craft()->request->getParam('data'));

		if(!$data)
		{
			$this->returnJson(array('success' => false, 'error' => 'Invalid JSON.'));
		}
		else
		{
			$requiredStructure = array('fields', 'fields.groups', 'fields.data');

			if(property_exists($data, 'fields') &&
			   property_exists($data->fields, 'groups') &&
			   property_exists($data->fields, 'data'))
			{
				$this->returnJson(array('success' => true));
			}

			else $this->returnJson(array('success' => false, 'error' => 'Invalid JSON structure.'));
		}
	}

}