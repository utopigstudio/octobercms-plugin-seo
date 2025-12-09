<?php namespace Utopigs\Seo\Components;

use Cms\Classes\ComponentBase;
use Event;
use Utopigs\Seo\Models\Settings;

class SeoModel extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'utopigs.seo::lang.component_seomodel.name',
            'description' => 'utopigs.seo::lang.component_seomodel.description'
        ];
    }

    public function defineProperties()
    {
        return [
            'pageType' => [
                'title' => 'utopigs.seo::lang.component_seomodel.property_pageType.title',
                'description' => 'utopigs.seo::lang.component_seomodel.property_pageType.description',
                'default' => '',
                'required' => true,
            ],
            'pageProperty' => [
                'title' => 'utopigs.seo::lang.component_seomodel.property_pageProperty.title',
                'description' => 'utopigs.seo::lang.component_seomodel.property_pageProperty.description',
                'default' => '',
                'required' => true,
            ],
        ];
    }

    public function onRun()
    {
        $seo = NULL;
        $prepend = NULL;
        $append = NULL;

        if (isset($this->page->components['blogPost'])) {
            if (!$this->property('pageType')) {
                $this->properties['pageType'] = 'blog-post';
            }
            if (!$this->property('pageProperty')) {
                $this->properties['pageProperty'] = 'post';
            }
        } elseif (isset($this->page->components['blogPosts']) && isset($this->page->components['blogPosts']->properties['categoryFilter'])) {
            if (!$this->property('pageType')) {
                $this->properties['pageType'] = 'blog-category';
            }
            if (!$this->property('pageProperty')) {
                $this->properties['pageProperty'] = 'category';
            }
        }

        if (!$this->property('pageType') || !$this->property('pageProperty') || !$this->page[$this->property('pageProperty')]) {
            return;
        }

        //retrieve prepend and append properties from layout Seo component
        if ($this->page->meta_title_prepend) {
            $prepend = $this->page->meta_title_prepend;
        }
        
        if ($this->page->meta_title_append) {
            $append = $this->page->meta_title_append;
        }

        $seo = \Utopigs\Seo\Models\Seo::where('type', $this->property('pageType'))
            ->where('reference', $this->page[$this->property('pageProperty')]->getKey())->first();

        if ($seo) {
            if ($seo->no_index) {
                $seo = null;
                $this->page->noIndex = true;
            } else {
                $this->page->hasSeo = true;
            }
        }

        //if there's no manually entered SEO data, or image is missing, try to retrieve defaults from model
        if (!$seo || empty($seo->image)) {
            $seo_default_values = Event::fire('utopigs.seo.mapSeoData', [$this->property('pageType'), $this->page[$this->property('pageProperty')]->getKey()], true);

            if ($seo_default_values) {
                if (!$seo) {
                    $seo = new \Utopigs\Seo\Models\Seo;
                    if (isset($seo_default_values['title'])) {
                        $seo->title = ($prepend ? ($prepend . ' ') : '') . $seo_default_values['title'] . ($append ? (' ' . $append) : '');
                    }
                    if (isset($seo_default_values['description'])) {
                        $seo->description = $seo_default_values['description'];
                    }
                    if (isset($seo_default_values['keywords'])) {
                        $seo->keywords = $seo_default_values['keywords'];
                    }
                    if (isset($seo_default_values['image'])) {
                        $seo->image = $seo_default_values['image'];
                    }
                }
                //if there is seo data but image is no filled, try to fill from defaults
                elseif (empty($seo->image) && isset($seo_default_values['image'])) {
                    $seo->image = $seo_default_values['image'];
                }
            }
        }

        if (!$seo) {
            return;
        }

        if ($this->page->hasSeo && Settings::get('prepend_append_in_pages_with_seo', 1)) {
            $seo->title = ($prepend ? ($prepend . ' ') : '') . $seo->title . ($append ? (' ' . $append) : '');
        }

        $this->page->meta_title = $this->page->title = $seo->title;
        $this->page->meta_description = $this->page->description = $seo->description;
        if ($seo->keywords) {
            $this->page->meta_keywords = $this->page->keywords = $seo->keywords;
        }
        if ($seo->image) {
            $this->page->seo_image = $seo->image;
        }
    }
}