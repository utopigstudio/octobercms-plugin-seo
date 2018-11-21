# Multilanguage SEO plugin

This plugin allows to create multilanguage meta content for CMS pages, Static pages and any OctoberCMS model.

It needs Rainlab Translate plugin to work.

## Using the multilanguage SEO component

First you must place the Seo component in theme layout head section.

The component has a configurable property called "append", if you fill it, the content will be appended to all pages titles. For example, if the page title is "Projects" and the append property is "| Site Name", the page title will show "Projects | Site Name".

```
description = "Default layout"

[localePicker]
forceUrl = 1

...

[seo]
append = "| Site Name"
==
<!DOCTYPE html>
...
```

Then you should create a SEO register for every page in your theme. Fill the title, description, (optional) keywords and (optional) image in each language.

Finally, show the metas in your layout like this:

```
<title>{{ this.page.meta_title }}</title>
<meta name="description" content="{{ this.page.meta_description }}" />
<meta name="keywords" content="{{ this.page.meta_keywords }}" />
<meta name="title" content="{{ this.page.meta_title }}" />

<meta property="og:title" content="{{ this.page.meta_title }}" />
<meta property="og:description" content="{{ this.page.meta_description }}" />
{% if this.page.seo_image %}
<meta property="og:image" content="{{ this.page.seo_image.getPath() }}" />
{% endif %}
```

## Support for static pages plugin

If you use Rainlab.Pages plugin, you can also create SEO data for this static pages. You will need to add the SEO component to the static layout.

## Support for custom models

You can create SEO Data for your own models.

Your plugin has to listen to the following events that Rainlab.Pages uses to build dynamic menus:

* `pages.menuitem.listType` event handler should return a list of types of objects that can have SEO data attached.
* `pages.menuitem.getTypeInfo` event handler returns all items for a type of object.

An example of this listeners (this code is simplified for this plugin purposes, more data needs to be returned for Rainlab.Pages menu to work):

```php
public function boot()
{
    Event::listen('pages.menuitem.listTypes', function() {
        return [
            'acme-post' => 'Post page',
        ];
    });

    Event::listen('pages.menuitem.getTypeInfo', function($type) {
        if ($type == 'acme-post')
            return YourModel::getMenuTypeInfo($type);
    });
}
```

In YourModel the implementation might look like this (this code is simplified for this plugin purposes, more data needs to be returned for Rainlab.Pages menu to work):

```php
public static function getMenuTypeInfo($type)
{
    $result = [];

    if ($type == 'acme-post') {
        $references = [];
        $posts = self::get();
        foreach ($posts as $post) {
            $references[$post->id] = $post->title;
        }
        $result = [
            'references'   => $references
        ];
    }

    return $result;
}
```

To see your model SEO on the frontend, you will need to use the component SeoModel in your page:

```
[seoModel]
pageType='acme-post'
pageProperty='post'
```

The property `pageType` should be the same YourModel property `$seoPageType`

The property  `pageProperty` tells the plugin in which page property can find your model. It's usually filled by a component on the onRun method:

```
public function onRun()
{
    $slug = $this->param('slug');

    $post = new YourModel;

    $post = YourModel::where('slug', $slug)->first();

    $this->page['post'] = $post;
}
```

If you already use some fields in your model for SEO purposes, you can map them listening to `utopigs.seo.mapSeoData` event:

```php
Event::listen('utopigs.seo.mapSeoData', function($type, $reference) {
    if ($type == 'acme-post') {
        return YourModel::mapSeoData($reference);
    }
});
```

In YourModel, one possible implementation might look like this:

```php
public static function mapSeoData($reference)
{
    $item = self::find($reference);

    $seo_data = [
        'title' => $item->title,
        'description' => str_limit(strip_tags($item->description), 155),
        'image' => $item->image,
    ];

    return $seo_data;
}
```

## Entending your own models

A SEO tab with easier access to SEO data creation can be attached to your model. You need to implement the Seo Model Behavior in your model class:

```php
class Post
{
    public $implement = ['Utopigs.Seo.Behaviors.SeoModel'];

    //required: menu item type that your plugin expects in pages.menuitem.getTypeInfo event
    public $seoPageType = 'acme-post';

    //optional: where to put the SEO tab, default is 'primary'
    public $seoTab = 'secondary';
}
```

This code will insert a new tab in your models for, previewing, creating or editing the SEO data.
