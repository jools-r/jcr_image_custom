<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin["name"] = "jcr_image_custom";

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin["version"] = "0.2.0";
$plugin["author"] = "jcr / txpbuilders";
$plugin["author_uri"] = "http://txp.builders";
$plugin["description"] = "Adds multiple custom fields to the images panel";

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin["order"] = "5";

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin["type"] = "1";

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined("PLUGIN_HAS_PREFS")) {
    define("PLUGIN_HAS_PREFS", 0x0001);
} // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined("PLUGIN_LIFECYCLE_NOTIFY")) {
    define("PLUGIN_LIFECYCLE_NOTIFY", 0x0002);
} // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin["flags"] = "3";

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

// Customise the custom field display name as follows:
// jcr_image_custom_1 => Year
// jcr_image_custom_2 => Image ref
// jcr_image_custom_3 => Photographer
// jcr_image_custom_4 => Photographer link
// jcr_image_custom_5 => Background color

$plugin["textpack"] = <<<EOT
#@admin
#@language en
jcr_image_custom => Image custom fields
#@language de
jcr_image_custom => Bilder Custom-Felder
EOT;
// End of textpack

if (!defined("txpinterface")) {
    @include_once "zem_tpl.php";
}

# --- BEGIN PLUGIN CODE ---
class jcr_image_custom
{
    /**
     * Initialise.
     */
    function __construct()
    {
        register_callback([__CLASS__, "lifecycle"], "plugin_lifecycle.jcr_image_custom");
        register_callback([__CLASS__, "ui"], "image_ui", "extend_detail_form");
        register_callback([__CLASS__, "save"], "image", "image_save");

        // Prefs pane for custom fields
        add_privs("prefs.jcr_image_custom", "1");

        // Redirect 'Options' link on plugins panel to preferences pane
        add_privs("plugin_prefs.jcr_image_custom", "1");
        register_callback([__CLASS__, "options_prefs_redirect"], "plugin_prefs.jcr_image_custom");
    }

    /**
     * Add and remove custom fields from txp_image table.
     *
     * @param $event string
     * @param $step string  The lifecycle phase of this plugin
     */
    public static function lifecycle($event, $step)
    {
        switch ($step) {
            case "enabled":
                add_privs("prefs.jcr_image_custom", "1");
                break;
            case "disabled":
                break;
            case "installed":
                // Add image custom fields to txp_image table
                $cols_exist = safe_query("SHOW COLUMNS FROM " . safe_pfx("txp_image") . " LIKE 'jcr_image_custom_1'");
                if (@numRows($cols_exist) == 0) {
                    safe_alter(
                        "txp_image",
                        "ADD COLUMN jcr_image_custom_1 VARCHAR(255) NOT NULL DEFAULT '' AFTER thumb_h,
						 ADD COLUMN jcr_image_custom_2 VARCHAR(255) NOT NULL DEFAULT '' AFTER jcr_image_custom_1,
						 ADD COLUMN jcr_image_custom_3 VARCHAR(255) NOT NULL DEFAULT '' AFTER jcr_image_custom_2,
						 ADD COLUMN jcr_image_custom_4 VARCHAR(255) NOT NULL DEFAULT '' AFTER jcr_image_custom_3,
						 ADD COLUMN jcr_image_custom_5 VARCHAR(255) NOT NULL DEFAULT '' AFTER jcr_image_custom_4"
                    );
                }

                // Add prefs for image custom field names
                create_pref("image_custom_1_set", "", "jcr_image_custom", "0", "image_custom_set", "1");
                create_pref("image_custom_2_set", "", "jcr_image_custom", "0", "image_custom_set", "2");
                create_pref("image_custom_3_set", "", "jcr_image_custom", "0", "image_custom_set", "3");
                create_pref("image_custom_4_set", "", "jcr_image_custom", "0", "image_custom_set", "4");
                create_pref("image_custom_5_set", "", "jcr_image_custom", "0", "image_custom_set", "5");

                // Insert initial value for cf1 if none already exists (so that upgrade works)
                $cf_pref = get_pref("image_custom_1_set");
                if ($cf_pref === "") {
                    set_pref("image_custom_1_set", "custom1");
                }

                // Upgrade: Migrate v1 plugin legacy column
                $legacy = safe_query("SHOW COLUMNS FROM " . safe_pfx("txp_image") . " LIKE 'jcr_image_custom'");
                if (@numRows($legacy) > 0) {
                    // Copy contents of jcr_image_custom to jcr_image_custom_1 (where not empty/NULL)
                    safe_update("txp_image", "`jcr_image_custom_1` = `jcr_image_custom`", "jcr_image_custom IS NOT NULL");
                    // Delete jcr_image_custom column
                    safe_alter("txp_image", "DROP COLUMN `jcr_image_custom`");
                }
                break;
            case "deleted":
                // Remove columns from image table
                safe_alter(
                    "txp_image",
                    'DROP COLUMN jcr_image_custom_1,
					 DROP COLUMN jcr_image_custom_2,
					 DROP COLUMN jcr_image_custom_3,
					 DROP COLUMN jcr_image_custom_4,
					 DROP COLUMN jcr_image_custom_5'
                );
                // Remove all prefs from event 'jcr_image_custom'.
                remove_pref(null, "jcr_image_custom");
                break;
        }
        return;
    }

    /**
     * Paint additional fields for image custom fields
     *
     * @param $event string
     * @param $step string
     * @param $dummy string
     * @param $rs array The current image's data
     * @return string
     */
    public static function ui($event, $step, $dummy, $rs)
    {
        global $prefs;

        extract(
            lAtts(
                [
                    "jcr_image_custom_1" => "",
                    "jcr_image_custom_2" => "",
                    "jcr_image_custom_3" => "",
                    "jcr_image_custom_4" => "",
                    "jcr_image_custom_5" => "",
                ],
                $rs,
                0
            )
        );

        $out = "";

        $cfs = preg_grep("/^image_custom_\d+_set/", array_keys($prefs));
		asort($cfs);

        foreach ($cfs as $name) {
            preg_match("/(\d+)/", $name, $match);

            if ($prefs[$name] !== "") {
                $out .= inputLabel("jcr_image_custom_" . $match[1], fInput("text", "jcr_image_custom_" . $match[1], ${"jcr_image_custom_" . $match[1]}, "", "", "", INPUT_REGULAR, "", "jcr_image_custom_" . $match[1]), "jcr_image_custom_" . $match[1]) . n;
            }
        }

        return $out;
    }

    /**
     * Save additional image custom fields
     *
     * @param $event string
     * @param $step string
     */
    public static function save($event, $step)
    {
        extract(doSlash(psa(["jcr_image_custom_1", "jcr_image_custom_2", "jcr_image_custom_3", "jcr_image_custom_4", "jcr_image_custom_5", "id"])));
        $id = assert_int($id);
        safe_update(
            "txp_image",
            "jcr_image_custom_1 = '$jcr_image_custom_1',
	         jcr_image_custom_2 = '$jcr_image_custom_2',
	         jcr_image_custom_3 = '$jcr_image_custom_3',
	         jcr_image_custom_4 = '$jcr_image_custom_4',
	         jcr_image_custom_5 = '$jcr_image_custom_5'",
            "id = $id"
        );
    }

    /**
     * Renders a HTML image custom field in the prefs.
     *
     * Can be altered by plugins via the 'prefs_ui > image_custom_set'
     * pluggable UI callback event.
     *
     * @param  string $name HTML name of the widget
     * @param  string $val  Initial (or current) content
     * @return string HTML
     * @todo   deprecate or move this when CFs are migrated to the meta store
     */
    public static function image_custom_set($name, $val)
    {
        return pluggable_ui("prefs_ui", "image_custom_set", text_input($name, $val, INPUT_REGULAR), $name, $val);
    }

    /**
     * Re-route 'Options' link on Plugins panel to Admin › Preferences panel
     *
     */
    public static function options_prefs_redirect()
    {
        header("Location: index.php?event=prefs#prefs_group_jcr_image_custom");
    }
}

if (txpinterface === "admin") {
    new jcr_image_custom();
} elseif (txpinterface === "public") {
    if (class_exists("\Textpattern\Tag\Registry")) {
        Txp::get("\Textpattern\Tag\Registry")
            ->register("jcr_image_custom")
            ->register("jcr_if_image_custom");
    }
}

/**
 * Gets a list of image custom fields.
 *
 * @return  array
 */
function jcr_get_image_custom_fields()
{
    global $prefs;
    static $out = null;
    // Have cache?
    if (!is_array($out)) {
        $cfs = preg_grep("/^image_custom_\d+_set/", array_keys($prefs));
        $out = [];
        foreach ($cfs as $name) {
            preg_match("/(\d+)/", $name, $match);
            if ($prefs[$name] !== "") {
                $out[$match[1]] = strtolower($prefs[$name]);
            }
        }
    }
    return $out;
}

/**
 * Maps 'txp_image' table's columns to article data values.
 *
 * This function returns an array of 'data-value' => 'column' pairs.
 *
 * @return array
 */
function jcr_image_column_map()
{
    $image_custom = jcr_get_image_custom_fields();
    $image_custom_map = [];

    if ($image_custom) {
        foreach ($image_custom as $i => $name) {
            $image_custom_map[$name] = "jcr_image_custom_" . $i;
        }
    }

    return $image_custom_map;
}

/**
 * Public tag: Output image custom field
 * @param  string $atts[name] Name of custom field.
 * @param  string $atts[escape] Convert special characters to HTML entities.
 * @param  string $atts[default] Default output if field is empty.
 * @return string custom field output
 * <code>
 *        <txp:jcr_image_custom name="title_image" escape="html" />
 * </code>
 */
function jcr_image_custom($atts, $thing = null)
{
    global $thisimage;

    assert_image();

    extract(
        lAtts(
            [
                "class" => "",
                "name" => get_pref("image_custom_1_set"),
                "escape" => null,
                "default" => "",
                "wraptag" => "",
            ],
            $atts
        )
    );

    $name = strtolower($name);

    // Populate image custom field data;
    foreach (jcr_image_column_map() as $key => $column) {
        $thisimage[$key] = isset($column) ? $column : null;
    }

    if (!isset($thisimage[$name])) {
        trigger_error(gTxt("field_not_found", ["{name}" => $name]), E_USER_NOTICE);
        return "";
    }
    $cf_num = $thisimage[$name];
    $cf_val = $thisimage[$cf_num];

    if (!isset($thing)) {
        $thing = $cf_val !== "" ? $cf_val : $default;
    }

    $thing = $escape === null ? txpspecialchars($thing) : parse($thing);

    return !empty($thing) ? doTag($thing, $wraptag, $class) : "";
}

/**
 * Public tag: Check if custom image field exists
 * @param  string $atts[name]    Name of custom field.
 * @param  string $atts[value]   Value to test against (optional).
 * @param  string $atts[match]   Match testing: exact, any, all, pattern.
 * @param  string $atts[separator] Item separator for match="any" or "all". Otherwise ignored.
 * @return string custom field output
 * <code>
 *        <txp:jcr_if_image_custom name="menu_title" /> … <txp:else /> … </txp:jcr_if_image_custom>
 * </code>
 */
function jcr_if_image_custom($atts, $thing = null)
{
    global $thisimage;

    extract(
        $atts = lAtts(
            [
                "name" => get_pref("image_custom_1_set"),
                "value" => null,
                "match" => "exact",
                "separator" => "",
            ],
            $atts
        )
    );

    $name = strtolower($name);

    // Populate image custom field data;
    foreach (jcr_image_column_map() as $key => $column) {
        $thisimage[$key] = isset($column) ? $column : null;
    }

    if (!isset($thisimage[$name])) {
        trigger_error(gTxt("field_not_found", ["{name}" => $name]), E_USER_NOTICE);
        return "";
    }
    $cf_num = $thisimage[$name];
    $cf_val = $thisimage[$cf_num];

    if ($value !== null) {
        $cond = txp_match($atts, $cf_val);
    } else {
        $cond = $cf_val !== "";
    }

    return isset($thing) ? parse($thing, !empty($cond)) : !empty($cond);
}
# --- END PLUGIN CODE ---
if (0) { ?>
<!--
# --- BEGIN PLUGIN CSS ---

# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
h1. jcr_image_custom

Adds up to five extra custom fields of up to 255 characters to the "Content › Images":http://docs.textpattern.io/administration/images-panel panel along with corresponding tags to output the custom field and to test if it contains a value or matches a specific value.

h3(#installation). Installation

Paste the code into the _Admin › Plugins_ panel, install and enable the plugin.


h2. Use cases

Use whenever extra information needs to be stored with an image. For example:

* Store a youtube/vimeo link or video-ID with an image for generating custom video poster images.
* Store the download url of a corresponding hi-res press image not stored in txp.
* Store the year a picture / photograph was taken
* Store the copyright owners name + link
* …


h2(#tags). Tags

h3. jcr_image_custom

Outputs the content of the image custom field.

h4. Tag attributes

@name@
Specifies the name of the image custom field.
Example: Use @name="copyright_author"@ to output the copyright_author custom field. Default: jcr_image_custom_1.

@escape@
Escape HTML entities such as @<@, @>@ and @&@ prior to echoing the field contents.
Supports extended escape values in txp 4.8
Example: Use @escape="textile"@ to convert textile in the value. Default: none.

@default@
Specifies the default output if the custom field is empty
Example: Use @default="Org Name"@ to output "Org Name", e.g. for when no copyright_author explicitly given. Default: empty.

@wraptag@
Wrap the custom field contents in an HTML tag
Example: Use @wraptag="h2"@ to output @<h2>Custom field value</h2>@. Default: empty.

@class@
Specifies a class to be added to the @wraptag@ attribute
Example: Use @wraptag="p" class="copyright"@ to output @<p class="copyright">Custom field value</p>@. Default: empty

h3. jcr_if_image_custom

Tests for existence of an image custom field, or whether one or several matches a value or pattern.

h4. Tag attributes

@name@
Specifies the name of the image custom field.
Example: Use @name="copyright_author"@ to output the copyright_author custom field. Default: jcr_image_custom_1.

@value@
Value to test against (optional).
If not specified, the tag tests for the existence of any value in the specified image custom field.
Example: Use @value="english"@ to output only those images whose “language” image custom field is english. Default: none.

@match@
Match testing: exact, any, all, pattern. See the docs for “if_custom_field”:https://docs.textpattern.com/tags/if_custom_field.
Default: exact.

@separator@
Item separator for match="any" or "all". Otherwise ignored.
Default: empty.


h2(#examples). Examples

1. Output a gallery of custom video poster-images (from images assigned to the image category "videos") that open a corresponding youtube video (defined in the image custom field) in a lightbox modal:

bc. <txp:images wraptag="ul" break="li" category="videos" class="video-gallery">
  <a href="//www.youtube.com/watch?v=<txp:jcr_image_custom name="youtube_id" />" title="<txp:image_info type="caption" />" data-lity>
    <txp:thumbnail />
    <txp:jcr_image_custom name="youtube_author" wraptag="p" class="author" />
  </a>
</txp:images>

p. where the image custom field is used to store the Video ID of the YouTube video. This example uses the "lity":http://sorgalla.com/lity/ lightbox script.


2. Outputs the copyright author with or without link:

bc. <txp:images wraptag="ul" break="li" class="photoset">
  <figure class="photo">
    <txp:image />
    <figcaption>
      <txp:image_info type="caption" wraptag="p" />
      <txp:jcr_if_image_custom name="copyright_link">
        <a href="<txp:jcr_image_custom name="copyright_link" />" class="img-owner"><txp:jcr_image_custom   name="copyright_author" /></a>
      <txp:else />
        <txp:jcr_image_custom name="copyright_author" wraptag="span" class="img-owner" />
      </txp:jcr_if_image_custom>
    </figcaption>
  </figure>
</txp:images>


h2. Changing the label of the custom field

The name of custom field can be changed by specifying a new label using the _Install from Textpack_ field in the "Admin › Languages":http://docs.textpattern.io/administration/languages-panel.html panel. Enter your own information in the following pattern and click *Upload*:

bc.. #@admin
#@language en
jcr_image_custom_1 => Your label
jcr_image_custom_2 => Your other label

p. replacing @en-gb@ with your own language and @Your label@ with your own desired label.


h2(#deinstallation). De-installation

The plugin cleans up after itself: deinstalling the plugin removes the extra column from the database. To stop using the plugin but keep the database tables, just disable (deactivate) the plugin but don't delete it.


h2(#changelog). Changelog + Credits

h3. Changelog

* Version 0.2.0 – 2020/12/17 – Expand to handle multiple custom fields
* Version 0.1.1 – 2016/12/05 – Remedy table not being created on install
* Version 0.1 – 2016/04/16

h3. Credits

Robert Wetzlmayr’s "wet_profile":https://github.com/rwetzlmayr/wet_profile plugin for the starting point, and further examples by "Stef Dawson":http://www.stefdawson.com and "Jukka Svahn":https://github.com/gocom.
# --- END PLUGIN HELP ---
-->
<?php }
?>
