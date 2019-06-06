# Multi-lingual SEO plugin

This plugin allows to create SEO meta content for multi-lingual websites.

It also generates a multi-lingual `sitemap.xml` file based on desired CMS pages and others.

CMS pages, [Static pages](http://octobercms.com/plugin/rainlab-pages) and [Rainlab Blog](http://octobercms.com/plugin/rainlab-blog) Posts and Category pages are supported _out of the box_. Custom models can be easily extended. Sitemap functionality is forked from [Rainlab Sitemap](http://octobercms.com/plugin/rainlab-sitemap) plugin.

It needs [Rainlab Translate](http://octobercms.com/plugin/rainlab-translate) plugin to work.

## Using the multilanguage SEO component

First you must place the Seo component in theme layout head section.

The component has two configurable properties called "prepend" and "append", if you fill them, the content will be prepended/appended to all pages titles. For example, if the page title is "Projects" and the append property is "| Site Name", the page title will show "Projects | Site Name".

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

### Support for static pages plugin

If you use Rainlab.Pages plugin, you can also create SEO data for this static pages. You will need to add the SEO component to the static layout.

### Support for Blog plugin

Blog plugin Post and Category pages are automatically supported. After installing the Blog plugin and creating post and category pages, just select the types "Blog Post" or "Blog Category" from the Type dropdown, then select one of the pages and fill the data.

You will also need to add the SeoModel component to the post or category pages (no need to set tye pageType and pageProperty options):

```
[seoModel]
```

### Support for custom models

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

The property `pageProperty` tells the plugin in which page property can find your model. It's usually filled by a component on the onRun method:

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

### Extending your own models

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

This code will insert a new tab in your models for previewing, creating or editing the SEO data.

---

# Sitemap generator

This plugin will generate a `sitemap.xml` file based on desired CMS pages and others. The generated sitemap follows [Google guidelines for multi-lingual sitemaps](https://support.google.com/webmasters/answer/189077#sitemap).

## Viewing the sitemap

Once this plugin the sitemap has been configured, it can be viewed by accessing the file relative to the website base path. For example, if the website is hosted at http://example.com it can be viewed by opening this URL:

    http://example.com/sitemap.xml

You should add this url to your robots.txt file.

This sitemap will not render in browsers, this is a known issue. Unfortunately, the only known way to solve this issue makes the sitemap incompatible with Google Search Console. As a workaround, a sitemap-debug.xml is also generated. This sitemap renders ok in browsers and it can be viewed by opening this URL:

    http://example.com/sitemap-debug.xml

THIS SITEMAP IS ONLY FOR DEBUG PURPOSES, DON'T SUBMIT THIS URL TO GOOGLE SEARCH CONSOLE.

## Managing a sitemap definition

The sitemap is managed by selecting Sitemap from the Seo plugin menu. There is a single sitemap definition for each theme and it will be created automatically.

A sitemap definition can contain multiple items and each item has a number of properties. There are common properties for all item types, and some properties depend on the item type. The common item properties are **Priority** and **Change frequency**. The Priority defines the priority of this item relative to other items in the sitemap. The Change frequency defines how frequently the page is likely to change. Depending on the selected item type you might need to provide other properties of the item. The available properties are described below.

##### Reference
A drop-down list of objects the item should refer to. The list content depends on the item type. For the **Static page** item type the list displays all static pages defined in the system. For the **Blog category** item type the list displays a list of blog categories.

##### CMS Page
This drop-down is available for item types that require a special CMS page to refer to. For example, the **Blog category** item type requires a CMS page that hosts the `blogPosts` component. The CMS Page drop-down for this item type will only display pages that include this component.

### Standard item types
The available item types depend on the installed plugins, but there are some basic item types that are supported out of the box.

##### URL
Items of this type are links to a specific fixed URL. That could be an URL of an or internal page. Items of this type don't have any other properties - just the title and URL.

##### CMS page
Items of this type refer to CMS pages. The page should be selected in the **Reference** drop-down list described below.

### Custom item types
Other plugins can supply new item types. Some are supported _out of the box_:

#### [Rainlab Pages plugin](http://octobercms.com/plugin/rainlab-pages)
This plugin supplies two new item types:

##### Static page
Items of this type refer to static pages. The static page should be selected in the **Reference** drop-down.

##### All static pages
Items of this type expand to create links to all static pages defined in the theme. 

#### [Rainlab Blog plugin](http://octobercms.com/plugin/rainlab-blog)
This plugin supplies four new item types:

##### Blog category
An item of this type represents a link to a specific blog category. The category should be selected in the **Reference** drop-down. This type also requires selecting a **CMS page** that outputs a blog category.

##### All blog categories
An item of this type expands into multiple items representing all blog existing categories. This type also requires selecting a **CMS page**.

##### Blog post
An item of this type represents a link to a specific blog post. The post should be selected in the **Reference** drop-down. This type also requires selecting a **CMS page** that outputs a blog post.

##### All blog posts
An item of this type expands into multiple items representing all blog existing posts. This type also requires selecting a **CMS page**.

### Registering new sitemap definition item types

The Sitemap plugin shares the same events for registering item types as the [Pages plugin](http://octobercms.com/plugin/rainlab-pages). See the documentation provided by this plugin for more information.

When resolving an item, via the `pages.menuitem.resolveItem` event handler, each item should return an extra key in the array called `mtime`. This should be a Date object (see `Carbon\Carbon`) or a timestamp value compatible with PHP's `date()` function and represent the last time the link was modified.

Each item should also append all alternate language (including the default language) urls in the array called `alternate_locale_urls`.

Expected result format:

```
Array (
    [url] => http://example.com/en/blog/article/article-slug-in-english
    [mtime] => '2018-12-01T14:08:09+00:00',
    [alternate_locale_urls] => Array (
        [en] => http://example.com/en/blog/article/article-slug-in-english
        [en] => http://example.com/es/blog/articulo/article-slug-in-spanish
    )
)
```

Example of how to do this in your own models (simplified):

```
protected static function resolveMenuItem($item, $url, $theme)
{
    if ($item->type == 'acme-post') {

        $post = self::find($item->reference);

        $page = Page::loadCached($theme, $item->cmsPage);

        $defaultLocale = \RainLab\Translate\Models\Locale::getDefault()->code;

        $pageUrl = \Utopigs\Seo\Models\Sitemap::getPageLocaleUrl($page, $post, $defaultLocale, ['slug' => 'slug']);

        $alternateLocales = array_keys(\RainLab\Translate\Models\Locale::listEnabled());

        if (count($alternateLocales) > 1) {
            foreach ($alternateLocales as $locale) {
                $result['alternate_locale_urls'][$locale] = \Utopigs\Seo\Models\Sitemap::getPageLocaleUrl($page, $menuItem, $locale, ['slug' => 'slug']);
            }
        }

        $result['title'] = $post->title;
        $result['url'] = $pageUrl;
        $result['mtime'] = $post->updated_at;

        return $result;
    }

    return [$result];
}
```
