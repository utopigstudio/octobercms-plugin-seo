<?php namespace Utopigs\Seo\Models;

use Url;
use Cms;
use Model;
use Event;
use Request;
use DOMDocument;
use Config;
use Cms\Classes\Theme;
use Cms\Classes\Page;
use Utopigs\Seo\Classes\SitemapItem;
use October\Rain\Router\Router as RainRouter;

/**
 * Definition Model
 */
class Sitemap extends Model
{
    /**
     * Maximum URLs allowed (Protocol limit is 50k)
     */
    const MAX_URLS = 50000;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'utopigs_seo_sitemaps';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var integer A tally of URLs added to the sitemap
     */
    protected $urlCount = 0;

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['data'];

    /**
     * @var array The sitemap items.
     * Items are objects of the \Utopigs\Seo\Classes\SitemapItem class.
     */
    public $items;

    /**
     * @var DOMDocument element
     */
    protected $urlSet;

    /**
     * @var DOMDocument
     */
    protected $xmlObject;

    /**
     * @var string The protocol used to refer to the schema and namespace attributes
     */
    protected $protocol = 'http';

    public function beforeSave()
    {
        $this->data = (array) $this->items;
    }

    public function afterFetch()
    {
        $this->items = SitemapItem::initFromArray($this->data);
    }

    public function generateSitemap($protocol = 'http')
    {
        if (!$this->items) {
            return;
        }

        $this->protocol = $protocol;

        $currentUrl = Request::path();
        $theme = Theme::load($this->theme);

        $alternateLocales = [];

        $translator = \RainLab\Translate\Classes\Translator::instance();
        $defaultLocale = \RainLab\Translate\Models\Locale::getDefault()->code;
        $alternateLocales = array_keys(\RainLab\Translate\Models\Locale::listEnabled());
        $translator->setLocale($defaultLocale, false);

        /*
         * Cycle each page and add its URL
         */
        foreach ($this->items as $item) {

            /*
             * Explicit URL
             */
            if ($item->type == 'url') {
                $this->addItemToSet($item, Url::to($item->url));
            }
            /*
             * Registered sitemap type
             */
            else {
                if ($item->type == 'blog-category' || $item->type == 'all-blog-categories' && class_exists("\RainLab\Blog\Models\Category")) {
                    $apiResult = self::blogCategoryResolveMenuItem($item, $theme);
                }
                elseif ($item->type == 'blog-post' || $item->type == 'all-blog-posts' && class_exists("\RainLab\Blog\Models\Post")) {
                    $apiResult = self::blogPostResolveMenuItem($item, $theme);
                }
                elseif ($item->type == 'static-page' || $item->type == 'all-static-pages' && class_exists("\RainLab\Pages\Classes\Page")) {
                    $apiResult = self::staticPageResolveMenuItem($item, $theme);
                }
                else {
                    $apiResult = Event::fire('pages.menuitem.resolveItem', [$item->type, $item, $currentUrl, $theme]);
                }

                if (!is_array($apiResult)) {
                    continue;
                }
                
                foreach ($apiResult as $itemInfo) {
                    if (!is_array($itemInfo)) {
                        continue;
                    }

                    /*
                     * Single item
                     */
                    if (isset($itemInfo['url'])) {
                        $url = $itemInfo['url'];
                        $alternateLocaleUrls = [];

                        if ($item->type == 'cms-page') {
                            $page = Page::loadCached($theme, $item->reference);
                            $router = new RainRouter;

                            if ($page->hasTranslatablePageUrl($defaultLocale)) {
                                $page->rewriteTranslatablePageUrl($defaultLocale);
                            }

                            $url = $translator->getPathInLocale($page->url, $defaultLocale);
                            $url = $router->urlFromPattern($url);
                            $url = Url::to($url);

                            if (count($alternateLocales) > 1) {
                                foreach ($alternateLocales as $locale) {
                                    if ($page->hasTranslatablePageUrl($locale)) {
                                        $page->rewriteTranslatablePageUrl($locale);
                                    }
                                    $altUrl = $translator->getPathInLocale($page->url, $locale);
                                    $altUrl = $router->urlFromPattern($altUrl);
                                    $alternateLocaleUrls[$locale] = Url::to($altUrl);
                                }
                            }
                        }

                        if (isset($itemInfo['alternate_locale_urls'])) {
                            $alternateLocaleUrls = $itemInfo['alternate_locale_urls'];
                        }

                        $this->addItemToSet($item, $url, array_get($itemInfo, 'mtime'), $alternateLocaleUrls);
                    }

                    /*
                     * Multiple items
                     */
                    if (isset($itemInfo['items'])) {

                        $parentItem = $item;

                        $itemIterator = function($items) use (&$itemIterator, $parentItem)
                        {
                            foreach ($items as $item) {
                                if (isset($item['url'])) {
                                    $alternateLocaleUrls = [];
                                    if (isset($item['alternate_locale_urls'])) {
                                        $alternateLocaleUrls = $item['alternate_locale_urls'];
                                    }
                                    $this->addItemToSet($parentItem, $item['url'], array_get($item, 'mtime'), $alternateLocaleUrls);
                                }

                                if (isset($item['items'])) {
                                    $itemIterator($item['items']);
                                }
                            }
                        };

                        $itemIterator($itemInfo['items']);
                    }
                }
            }

        }

        $urlSet = $this->makeUrlSet();
        $xml = $this->makeXmlObject();
        $xml->appendChild($urlSet);

        return $xml->saveXML();
    }

    protected function makeXmlObject()
    {
        if ($this->xmlObject !== null) {
            return $this->xmlObject;
        }

        $xml = new DOMDocument;
        $xml->encoding = 'UTF-8';

        return $this->xmlObject = $xml;
    }

    protected function makeUrlSet()
    {
        if ($this->urlSet !== null) {
            return $this->urlSet;
        }

        $xml = $this->makeXmlObject();
        $urlSet = $xml->createElement('urlset');
        $urlSet->setAttribute('xmlns', $this->protocol . '://www.sitemaps.org/schemas/sitemap/0.9');
        $urlSet->setAttribute('xmlns:xhtml', $this->protocol . '://www.w3.org/1999/xhtml');
        $urlSet->setAttribute('xmlns:xsi', $this->protocol . '://www.w3.org/2001/XMLSchema-instance');
        $urlSet->setAttribute('xsi:schemaLocation', $this->protocol . '://www.sitemaps.org/schemas/sitemap/0.9 ' . $this->protocol . '://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');

        return $this->urlSet = $urlSet;
    }

    protected function addItemToSet($item, $url, $mtime = null, $alternateLocaleUrls = [])
    {
        if ($mtime instanceof \DateTime) {
            $mtime = $mtime->getTimestamp();
        }

        $xml = $this->makeXmlObject();
        $urlSet = $this->makeUrlSet();
        $mtime = $mtime ? date('c', $mtime) : date('c');

        if ($alternateLocaleUrls) {
            foreach ($alternateLocaleUrls as $alternateLocaleUrl) {
                $urlElement = $this->makeUrlElement(
                    $xml,
                    $alternateLocaleUrl,
                    $mtime,
                    $item->changefreq,
                    $item->priority,
                    $alternateLocaleUrls
                );
                if ($urlElement) {
                    $urlSet->appendChild($urlElement);
                }
            }
        } else {
            $urlElement = $this->makeUrlElement(
                $xml,
                $url,
                $mtime,
                $item->changefreq,
                $item->priority
            );
            if ($urlElement) {
                $urlSet->appendChild($urlElement);
            }
        }

        return $urlSet;
    }

    protected function makeUrlElement($xml, $pageUrl, $lastModified, $frequency, $priority, $alternateLocaleUrls = [])
    {
        if ($this->urlCount >= self::MAX_URLS) {
            return false;
        }

        $this->urlCount++;

        $url = $xml->createElement('url');
        $url->appendChild($xml->createElement('loc', $pageUrl));
        $url->appendChild($xml->createElement('lastmod', $lastModified));
        $url->appendChild($xml->createElement('changefreq', $frequency));
        $url->appendChild($xml->createElement('priority', $priority));
        foreach ($alternateLocaleUrls as $locale => $locale_url) {
            $alternateUrl = $xml->createElement('xhtml:link');
            $alternateUrl->setAttribute('rel', 'alternate');
            $alternateUrl->setAttribute('hreflang', $locale);
            $alternateUrl->setAttribute('href', $locale_url);
            $url->appendChild($alternateUrl);
        }

        return $url;
    }

    protected static function blogCategoryResolveMenuItem($item, $theme)
    {
        if ($item->type == 'blog-category') {
            if (!$item->reference || !$item->cmsPage) {
                return;
            }
            $category = \RainLab\Blog\Models\Category::find($item->reference);
            if (!$category) {
                return;
            }
            $page = Page::loadCached($theme, $item->cmsPage);
            if (!$page) {
                return;
            }
            $paramName = self::getPageParamName($page, 'blogPosts', 'categoryFilter');
            if (!$paramName) {
                return;
            }
            $result = self::getMenuItem($page, $category, $paramName);
        }
        elseif ($item->type == 'all-blog-categories') {
            $result = [
                'items' => []
            ];
            $categories = \RainLab\Blog\Models\Category::orderBy('name')->get();
            if (empty($categories)) {
                return;
            }
            $page = Page::loadCached($theme, $item->cmsPage);
            if (!$page) {
                return;
            }
            $paramName = self::getPageParamName($page, 'blogPosts', 'categoryFilter');
            if (!$paramName) {
                return;
            }
            foreach ($categories as $category) {
                $result['items'][] = self::getMenuItem($page, $category, $paramName);
            }
        }

        return [$result];
    }

    protected static function blogPostResolveMenuItem($item, $theme)
    {
        if ($item->type == 'blog-post') {
            if (!$item->reference || !$item->cmsPage) {
                return;
            }
            $post = \RainLab\Blog\Models\Post::find($item->reference);
            if (!$post) {
                return;
            }
            $page = Page::loadCached($theme, $item->cmsPage);
            if (!$page) {
                return;
            }
            $paramName = self::getPageParamName($page, 'blogPost', 'slug');
            if (!$paramName) {
                return;
            }
            $result = self::getMenuItem($page, $post, $paramName);
        }
        elseif ($item->type == 'all-blog-posts') {
            $result = [
                'items' => []
            ];
            $posts = \RainLab\Blog\Models\Post::isPublished()
            ->orderBy('title')
            ->get();
            if (empty($posts)) {
                return;
            }
            $page = Page::loadCached($theme, $item->cmsPage);
            if (!$page) {
                return;
            }
            $paramName = self::getPageParamName($page, 'blogPost', 'slug');
            if (!$paramName) {
                return;
            }
            foreach ($posts as $post) {
                $result['items'][] = self::getMenuItem($page, $post, $paramName);
            }
        }

        return [$result];
    }

    protected static function getMenuItem($page, $menuItem, $paramName)
    {
        $result = [];

        $defaultLocale = \RainLab\Translate\Models\Locale::getDefault()->code;
        $pageUrl = self::getPageLocaleUrl($page, $menuItem, $defaultLocale, [$paramName => 'slug']);

        $alternateLocales = array_keys(\RainLab\Translate\Models\Locale::listEnabled());

        if (count($alternateLocales) > 1) {
            foreach ($alternateLocales as $locale) {
                $result['alternate_locale_urls'][$locale] = self::getPageLocaleUrl($page, $menuItem, $locale, [$paramName => 'slug']);
            }
        }

        $result['title'] = $menuItem->name;
        $result['url'] = $pageUrl;
        $result['mtime'] = $menuItem->updated_at;

        return $result;
    }

    protected static function getPageParamName($page, $component, $property)
    {
        if (!isset($page->settings['components'][$component][$property])) {
            return;
        }

        $propertyName = $page->settings['components'][$component][$property];
        if (!preg_match('/^\{\{([^\}]+)\}\}$/', $propertyName, $matches)) {
            return;
        }

        $paramName = substr(trim($matches[1]), 1);            

        return $paramName;
    }

    protected static function staticPageResolveMenuItem($item, $theme)
    {
        $result = [];
        if ($item->type == 'static-page') {
            if (!$item->reference) {
                return;
            }
            $page = \RainLab\Pages\Classes\Page::find($item->reference);
            $result = self::getStaticPageMenuItem($page);
        }
        elseif ($item->type == 'all-static-pages') {
            $pages = \RainLab\Pages\Classes\Page::all();
            if (empty($pages)) {
                return;
            }
            foreach ($pages as $page) {
                $result['items'][] = self::getStaticPageMenuItem($page);
            }            
        }

        return [$result];
    }

    protected static function getStaticPageMenuItem($page)
    {
        $translator = \RainLab\Translate\Classes\Translator::instance();
        $defaultLocale = \RainLab\Translate\Models\Locale::getDefault()->code;
        $page->rewriteTranslatablePageUrl($defaultLocale);
        $pageUrl = Url::to($translator->getPathInLocale(array_get($page->attributes, 'viewBag.url'), $defaultLocale));
        $alternateLocales = array_keys(\RainLab\Translate\Models\Locale::listEnabled());
        if (count($alternateLocales) > 1) {
            foreach ($alternateLocales as $locale) {
                $page->rewriteTranslatablePageUrl($locale);
                $result['alternate_locale_urls'][$locale] = Url::to($translator->getPathInLocale(array_get($page->attributes, 'viewBag.url'), $locale));
            }
        }
        $result['url'] = $pageUrl;
        $result['mtime'] = $page->mtime;

        return $result;
    }

    /**
     * Returns the localized URL of a page, translating the page params.
     * @param \Cms\Classes\Page $page
     * @param Model $item Object
     * @param string $locale Code of the locale
     * @param array $paramMap Array containing the equivalence between page parameters and model attributes ['slug' => 'slug']
     * @return string Returns an string with the localized page url
     */
    protected static function getPageLocaleUrl($page, $item, $locale, $paramMap)
    {
        $translator = \RainLab\Translate\Classes\Translator::instance();

        if ($page->hasTranslatablePageUrl($locale)) {
            $page->rewriteTranslatablePageUrl($locale);
        }

        $item->lang($locale);

        $params = [];
        foreach ($paramMap as $paramName => $fieldName) {
            $params[$paramName] = $item->$fieldName;
        }

        $url = $translator->getPathInLocale($page->url, $locale);
        $url = (new RainRouter)->urlFromPattern($url, $params);
        $url = Url::to($url);

        return $url;
    }
}
