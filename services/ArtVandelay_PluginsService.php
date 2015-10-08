<?php

namespace Craft;

/**
 * Class ArtVandelay_PluginsService
 */
class ArtVandelay_PluginsService extends BaseApplicationComponent
{

    /**
     * @param array $pluginDefinitions
     * @return ArtVandelay_ResultModel
     */
    public function import(array $pluginDefinitions)
    {
        $result = new ArtVandelay_ResultModel();
        foreach ($pluginDefinitions as $handle => $pluginDefinition) {
            $plugin = craft()->plugins->getPlugin($handle, false);

            if (!$plugin) {
                return $result->error("Plugin $handle could not be found, make sure it's files are located in the plugins folder");
            }

            if ($pluginDefinition['isInstalled']) {
                try {
                    craft()->plugins->installPlugin($handle);
                } catch (\Exception $e) {
                    echo "An error occurred while installing plugin $handle, continuing anyway" . PHP_EOL;
                }

                if ($pluginDefinition['isEnabled']) {
                    craft()->plugins->enablePlugin($handle);
                } else {
                    craft()->plugins->disablePlugin($handle);
                }

                craft()->plugins->savePluginSettings($plugin, $pluginDefinition['settings']);

            } else {
                craft()->plugins->uninstallPlugin($handle);
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    public function export()
    {
        $plugins = craft()->plugins->getPlugins(false);
        $pluginDefinitions = array();

        foreach ($plugins as $handle => $plugin) {
            $pluginDefinitions[$handle] = $this->getPluginDefinition($plugin);
        }

        return $pluginDefinitions;
    }

    /**
     * @param BasePlugin $plugin
     * @return array
     */
    private function getPluginDefinition(BasePlugin $plugin)
    {
        return array(
            'isInstalled' => $plugin->isInstalled,
            'isEnabled' => $plugin->isEnabled,
            'settings' => $plugin->getSettings()->attributes
        );
    }

}