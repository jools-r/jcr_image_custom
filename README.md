# jcr_image_custom

Adds up to five extra custom fields of up to 255 characters to the
[Content ›
Images](http://docs.textpattern.io/administration/images-panel) panel
along with corresponding tags to output the custom field and to test if
it contains a value or matches a specific value.

### Use cases

Use whenever extra information needs to be stored with an image. For
example:

-   Store a youtube/vimeo link or video-ID with an image for generating
    custom video poster images.
-   Store the download url of a corresponding hi-res press image not
    stored in txp.
-   Store the year a picture / photograph was taken
-   Store the copyright owners name + link
-   ...

## Installation / Deinstallation / Upgrading

### Installation

Paste the `.txt` installer code into the *Admin › Plugins* panel, or
upload the plugin's `.php` file via the *Upload plugin* button, then
install and enable the plugin.

### Upgrading

The plugin automatically migrates custom field data and the database
structure from the earlier single custom field variant (v0.1) to the new
format. No changes are needed to the public tags as the new default
settings correspond to the old tag. Nevertheless, it is always advisable
to make a database backup before upgrading.

### De-installation

The plugin cleans up after itself: deinstalling (deleting) the plugin
removes the extra columns from the database as well as custom field
names and labels. To stop using the plugin but keep the custom field
data in the database, just disable (deactivate) the plugin but don't
delete it.

## Plugin tags

### jcr_image_custom

Outputs the content of the image custom field.

#### Tag attributes

`name`\
Specifies the name of the image custom field.\
Example: Use `name="copyright_author"` to output the copyright_author
custom field. Default: jcr_image_custom_1.

`escape`\
Escape HTML entities such as `<`, `>` and `&` prior to echoing the field
contents.\
Supports extended escape values in txp 4.8\
Example: Use `escape="textile"` to convert textile in the value.
Default: none.

`default`\
Specifies the default output if the custom field is empty\
Example: Use `default="Org Name"` to output "Org Name", e.g. for when no
copyright_author explicitly given. Default: empty.

`wraptag`\
Wrap the custom field contents in an HTML tag\
Example: Use `wraptag="h2"` to output `<h2>Custom field value</h2>`.
Default: empty.

`class`\
Specifies a class to be added to the `wraptag` attribute\
Example: Use `wraptag="p" class="copyright"` to output
`<p class="copyright">Custom field value</p>`. Default: empty

### jcr_if_image_custom

Tests for existence of an image custom field, or whether one or several
matches a value or pattern.

#### Tag attributes

`name`\
Specifies the name of the image custom field.\
Example: Use `name="copyright_author"` to output the copyright_author
custom field. Default: jcr_image_custom_1.

`value`\
Value to test against (optional).\
If not specified, the tag tests for the existence of any value in the
specified image custom field.\
Example: Use `value="english"` to output only those images whose
"language" image custom field is english. Default: none.

`match`\
Match testing: exact, any, all, pattern. See the docs for
"if_custom_field":https://docs.textpattern.com/tags/if_custom_field.\
Default: exact.

`separator`\
Item separator for match="any" or "all". Otherwise ignored.\
Default: empty.

## Examples

### Example 1

Output a gallery of custom video poster-images (from images assigned to
the image category "videos") that open a corresponding youtube video
(defined in the image custom field) in a lightbox modal:

    <txp:images wraptag="ul" break="li" category="videos" class="video-gallery">
      <a href="//www.youtube.com/watch?v=<txp:jcr_image_custom name="youtube_id" />" title="<txp:image_info type="caption" />" data-lity>
        <txp:thumbnail />
        <txp:jcr_image_custom name="youtube_author" wraptag="p" class="author" />
      </a>
    </txp:images>

where the image custom field is used to store the Video ID of the
YouTube video. This example uses the [lity](http://sorgalla.com/lity/)
lightbox script.

### Example 2

Outputs the copyright author with or without link:

    <txp:images wraptag="ul" break="li" class="photoset">
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

## Custom field labels

The label displayed alongside the custom field in the edit image panel
can be changed by specifying a new label using the *Install from
Textpack* field in the [Admin ›
Languages](http://docs.textpattern.io/administration/languages-panel.html)
panel. Enter your own information in the following pattern and click
**Upload**:

    #@owner jcr_image_custom
    #@language en, en-gb, en-us
    #@image
    jcr_image_custom_1 => Your label
    jcr_image_custom_2 => Your other label

replacing `en` with your own language and `Your label` with your own
desired label.

## Changelog and credits

### Changelog

-   Version 0.2.0 -- 2020/12/17 -- Expand to handle multiple custom
    fields
-   Version 0.1.1 -- 2016/12/05 -- Remedy table not being created on
    install
-   Version 0.1 -- 2016/04/16

### Credits

Robert Wetzlmayr's
[wet_profile](https://github.com/rwetzlmayr/wet_profile) plugin for the
starting point, and further examples by [Stef
Dawson](http://www.stefdawson.com) and [Jukka
Svahn](https://github.com/gocom).
