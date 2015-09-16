# Art Vandelay 2.0.0a

Art Vandelay allows you to import and export fields and section and tabs to and from a [Craft](http://buildwithcraft.com) site.

#What's in version 2?

This is the alpha release of version 2.

In version 2 we upgraded the interface so it is no longer a single page. There are now 3 types of export you can do:

1. Export Field Groups - export an entire group of fields
2. Export Section - note version 2 automatically exports the fields that belong to a section.
3. Export a Tab - You can export a single entry Type tab, the fields that belong to that tab are also exported.

The import now has a couple of steps:

1. Paste your export file into the textbox
2. You are presented with a report page indicating what the import will do and potential issues. eg. if a field type has changed.
3. For tab imports you can select which entry types you want to add the tab to

## Installing

1. Copy the `artvandelay` directory into your `craft/plugins` directory
2. Browse to Settings > Plugins in the Craft CP
3. Click on the Install button next to Art Vandelay

## Usage

* Navigate to your plugins in the admin interface and click the 'Art Vandelay' link on the left.
* To import data, paste previously exported JSON into the text field and click *Import*.
* To export data, select the field groups you would like to export fields from, the sections you would like to export, then hit *Export*. The exported data will appear in a text field for you to copy.

## Command Line

Make sure you have your latest export stored at `./craft/config/schema.json`.

Then just run to import...

```
php ./craft/app/etc/console/yiic artvandelay
```

For composer-managed projects, check out [craft-console plugin](https://github.com/evolution7/craft-console) for a CLI runner with composer support.

## Updates

* 2.0.0a
  * Redo interface, add tab exporting / importing
* 1.0.3
  * Add CLI support @thoaionline
* 1.0.2
	* Include fields in an exported matrix.
* 1.0.1
	* Fix error when importing section translations.
* 1.0.0
	* Initial release!

## Todo

* Allow importing and exporting for:
	* Assets
	* Globals
	* Categories
	* Tags
* Improve the UI to allow file upload and download.
