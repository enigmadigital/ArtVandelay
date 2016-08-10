<?php namespace Craft;


class ArtVandelay_GlobalsService extends BaseApplicationComponent
{

	/**
     * @param GlobalSetModel[] $globalSets
     * @return array
     */
	public function export(array $globalSets)
	{
		$globalDefinitions = array();

        foreach ($globalSets as $globalSet) {
            $globalDefinitions[$globalSet->handle] = $this->getGlobalDefinition($globalSet);
        }

        return $globalDefinitions;
	}

    /**
     * @param GlobalSetModel $globalSet
     * @return array
     */
    private function getGlobalDefinition(GlobalSetModel $globalSet)
    {
        return array(
            'name' => $globalSet->name,
			'fieldLayout' => craft()->artVandelay_fields->getFieldLayoutDefinition($globalSet->getFieldLayout())
        );
    }

	/**
     * Attempt to import globals.
     * @param array $globalSetDefinitions
     * @param bool $force If set to true globals not included in the import will be deleted
     * @return ArtVandelay_ResultModel
     */
    public function import($globalSetDefinitions, $force = false )
    {
        $result = new ArtVandelay_ResultModel();

        if (empty($globalSetDefinitions)) {
            // Ignore importing globals.
            return $result;
        }

        $globalSets = craft()->globals->getAllSets('handle');

        foreach ($globalSetDefinitions as $globalSetHandle => $globalSetDefinition) {

            $global = array_key_exists($globalSetHandle, $globalSets)
                ? $globalSets[$globalSetHandle]
                : new GlobalSetModel();

            $this->populateGlobalSet($global, $globalSetDefinition, $globalSetHandle);

            // Save globalset via craft
            if (!craft()->globals->saveSet($global)) {
                return $result->error($global->getAllErrors());
            }
            unset($globalSets[$globalSetHandle]);
        }

        if($force){
            foreach($globalSets as $globalSet){
                craft()->globals->deleteSetById($globalSet->id);
            }
        }

        return $result;
    }

    /**
     * @param GlobalSetModel $globalSet
     * @param array $globalSetDefinition
     * @param string $globalSetHandle
     */
    private function populateGlobalSet(GlobalSetModel $globalSet, array $globalSetDefinition, $globalSetHandle)
    {
        $globalSet->setAttributes(array(
            'handle' => $globalSetHandle,
            'name' => $globalSetDefinition['name']
        ));

        $fieldLayout = craft()->artVandelay_fields->getFieldLayout($globalSetDefinition['fieldLayout']);
        $globalSet->setFieldLayout($fieldLayout);
    }

}
