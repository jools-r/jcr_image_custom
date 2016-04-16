<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'jcr_image_custom';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.1';
$plugin['author'] = 'jcr / txpbuilders';
$plugin['author_uri'] = 'http://txp.builders';
$plugin['description'] = 'Adds a custom field to the images panel';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '1';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
jcr_image_custom => Video ID
#@language de-de
jcr_image_custom => Video ID
EOT;
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
class jcr_image_custom
{
	/**
	 * Initialise.
	 */
	 function __construct()
	{
		register_callback(array(__CLASS__, 'lifecycle'), 'plugin_lifecycle.jcr_image_custom');
		register_callback(array(__CLASS__, 'ui'), 'image_ui', 'extend_detail_form');
		register_callback(array(__CLASS__, 'save'), 'image', 'image_save');
	}

  /**
   * Add and remove custom field from txp_image table.
   *
   * @param $event string
   * @param $step string  The lifecycle phase of this plugin
   */
  public static function lifecycle($event, $step)
  {
      switch ($step) {
          case 'enabled':
              break;
          case 'disabled':
              break;
          case 'installed':
              safe_alter(
                'txp_image',
                'ADD COLUMN jcr_image_custom VARCHAR(255) NULL AFTER thumb_h'
              );
              break;
          case 'deleted':
              safe_alter(
                'txp_image',
                'DROP COLUMN jcr_image_custom'
              );
              break;
      }
      return;
  }

	/**
	 * Paint additional fields for image custom field
	 *
	 * @param $event string
	 * @param $step string
	 * @param $dummy string
	 * @param $rs array The current image's data
	 * @return string
	 */
	public static function ui($event, $step, $dummy, $rs)
	{
		extract(lAtts(array(
			'jcr_image_custom' => ''
		), $rs, 0));

		return
			inputLabel('jcr_image_custom', fInput('text', 'jcr_image_custom', $jcr_image_custom, '', '', '', INPUT_REGULAR, '', 'jcr_image_custom'), 'jcr_image_custom').n;
	}

	/**
	 * Save additional image custom fields
	 *
	 * @param $event string
	 * @param $step string
	 */
	public static function save($event, $step)
	{
		extract(doSlash(psa(array('jcr_image_custom', 'id'))));
		$id = assert_int($id);
		safe_update('txp_image', 
		  "jcr_image_custom = '$jcr_image_custom'",
			"id = $id"
		);
	}
}

if (txpinterface === 'admin') {

    new jcr_image_custom;

} elseif (txpinterface === 'public') {

    if (class_exists('\Textpattern\Tag\Registry')) {
        Txp::get('\Textpattern\Tag\Registry')
            ->register('jcr_image_custom');
    }

}

  /**
   * Public tag: Output custom image field
   * @param  string $atts[escape] Convert special characters to HTML entities.
   * @return string  custom field output
   * <code>
   *        <txp:jcr_image_custom escape="html" />
   * </code>
   */

    function jcr_image_custom($atts)
    {
        global $thisimage;

        assert_image();

        extract(lAtts(array(
            'escape' => 'html',
        ), $atts));

        return ($escape == 'html')
            ? txpspecialchars($thisimage['jcr_image_custom'])
            : $thisimage['jcr_image_custom'];
    }
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN CSS ---

# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
h1. jcr_image_custom

Adds a single extra custom field of up to 255 characters to the "Content › Images":http://docs.textpattern.io/administration/images-panel panel and provides a corresponding tag to output the custom field.


h2. Use cases

Use whenever extra information needs to be stored with an image. For example:

* Store a youtube/vimeo link or video-ID with an image for generating custom video poster images.
* Store the download url of a corresponding hi-res press image not stored in txp.
* …


h2(#installation). Installation

Paste the code into the  _Admin › Plugins_ panel, install and enable the plugin.


h2(#tags). Tags

bc. <txp:jcr_image_custom />

Outputs the content of the image custom field.

h3. Tag attributes

*escape*
Escape HTML entities such as @<@, @>@ and @&@ prior to echoing the field contents. 
Example: Use @escape=""@ to suppress conversion. Default: @html@.


h2(#examples). Example

Produce a gallery of custom video poster-images (from images assigned to the image category "videos") that open a corresponding youtube video (defined in the image custom field) in a lightbox modal:

bc. <txp:images wraptag="ul" break="li" category="videos" class="video-gallery">
  <a href="//www.youtube.com/watch?v=<txp:jcr_image_custom />" title="<txp:image_info type="caption" />" data-lity>
    <txp:thumbnail />
  </a>
</txp:images>

p. where the image custom field is used to store the Video ID of the YouTube video. This example uses the "lity":http://sorgalla.com/lity/ lightbox script.


h2. Changing the label of the custom field

The name of custom field can be changed by specifying a new label using the _Install from Textpack_ field in the "Admin › Languages":http://docs.textpattern.io/administration/languages-panel.html panel. Enter your own information in the following pattern and click *Upload*:

bc.. #@admin
#@language en-gb
jcr_image_custom => Your label

p. replacing @en-gb@ with your own language and @Your label@ with your own desired label.


h2(#deinstallation). De-installation

The plugin cleans up after itself: deinstalling the plugin removes the extra column from the database. To stop using the plugin but keep the database tables, just disable (deactivate) the plugin but don't delete it.


h2(#changelog). Changelog

h3. Version 0.1 – 2016/04/16

* First release


h2(#credits). Credits

Robert Wetzlmayr’s "wet_profile":https://github.com/rwetzlmayr/wet_profile plugin for the starting point, and further examples by "Stef Dawson":http://www.stefdawson.com and "Jukka Svahn":https://github.com/gocom.
# --- END PLUGIN HELP ---
-->
<?php
}
?>
