<?php namespace Craft;


class ArtVandelay_GlobalsService extends BaseApplicationComponent
{

	public function import($globals)
	{
		return new ArtVandelay_ResultModel();
	}


	public function export()
	{
		//
	}

}