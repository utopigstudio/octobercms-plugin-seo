<?php namespace Utopigs\Seo\Components;

use Cms\Classes\ComponentBase;
use Event;

class SeoModel extends ComponentBase
{
    public function componentDetails()
	{
		return [
			'name'			=> 'utopigs.seo::lang.component_seomodel.name',
			'description'	=> 'utopigs.seo::lang.component_seomodel.description'
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
        $seo_defaults = NULL;
        $prepend = NULL;
        $append = NULL;

        if (!$this->property('pageType') || !$this->property('pageProperty') || !$this->page[$this->property('pageProperty')]) {
            return;
        }

        $seo = \Utopigs\Seo\Models\Seo::where('type', $this->property('pageType'))
            ->where('reference', $this->page[$this->property('pageProperty')]->getKey())->first();

        if (!$seo) {
            $seo_defaults = Event::fire('utopigs.seo.mapSeoData', [$this->property('pageType'), $this->page[$this->property('pageProperty')]->getKey()], true);

            if ($seo_defaults) {
                $seo = new \Utopigs\Seo\Models\Seo;
                if (isset($seo_defaults['title'])) {
                    $seo->title = $seo_defaults['title'];
                }
                if (isset($seo_defaults['description'])) {
                    $seo->description = $seo_defaults['description'];
                }
                if (isset($seo_defaults['keywords'])) {
                    $seo->keywords = $seo_defaults['keywords'];
                }
                if (isset($seo_defaults['image'])) {
                    $seo->image = $seo_defaults['image'];
                }
            }
        }

        if (!$seo) {
            return;
        }

        //retrieve prepend and append properties from layout Seo component
        if ($this->page->meta_title_prepend) {
            $prepend = $this->page->meta_title_prepend;
        }
        if ($this->page->meta_title_append) {
            $append = $this->page->meta_title_append;
        }

        $this->page->hasSeo = true;
        $this->page->meta_title = $this->page->title = ($prepend ? ($prepend . ' ') : '') . $seo->title . ($append ? (' ' . $append) : '');
        $this->page->meta_description = $this->page->description = $seo->description;
        if ($seo->keywords) {
            $this->page->meta_keywords = $this->page->keywords = $seo->keywords;
        }
        if ($seo->seo_image) {
            $this->page->seo_image = $seo->image;
        }

    }

}