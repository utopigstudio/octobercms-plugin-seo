<?php namespace Utopigs\Seo\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Theme;
use File;
use Utopigs\Seo\Models\Settings;

class Seo extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'utopigs.seo::lang.component_seo.name',
            'description' => 'utopigs.seo::lang.component_seo.description'
        ];
    }

    public function defineProperties()
    {
        return [
            'prepend' => [
                'title' => 'utopigs.seo::lang.component_seo.property_prepend.title',
                'description' => 'utopigs.seo::lang.component_seo.property_prepend.description',
                'default' => ''
            ],
            'append' => [
                'title' => 'utopigs.seo::lang.component_seo.property_append.title',
                'description' => 'utopigs.seo::lang.component_seo.property_append.description',
                'default' => ''
            ],
        ];
    }

    public function onRun()
    {
        $seo = NULL;
        $prepend = NULL;
        $append = NULL;

        //Load SEO data for this page
        if (isset($this->page->apiBag['staticPage'])) {
            $seo = \Utopigs\Seo\Models\Seo::where('type', 'static-page')
            ->where('reference', $this->page->apiBag['staticPage']->getBaseFileName())->first();
        } else {
            $seo = \Utopigs\Seo\Models\Seo::where('type', 'cms-page')
                ->where('reference', $this->page->baseFileName)->first();
        }

        if ($this->property('prepend')) {
            $prepend = $this->page->meta_title_prepend = $this->property('prepend');
        }
        if ($this->property('append')) {
            $append = $this->page->meta_title_append = $this->property('append');
        }

        if ($seo) {
            $this->page->hasSeo = true;
            if (Settings::get('prepend_append_in_pages_with_seo', 1)) {
                $seo->title = ($prepend ? ($prepend . ' ') : '') . $seo->title . ($append ? (' ' . $append) : '');
            }
            $this->page->meta_title = $this->page->title = $seo->title;
            $this->page->meta_description = $this->page->description = $seo->description;
            $this->page->meta_keywords = $this->page->keywords = $seo->keywords;
            $this->page->seo_image = $seo->image;
        } else {
            if (!$this->page->meta_title) {
                $this->page->meta_title = $this->page->title;
            }
            if ($this->page->meta_title && ($prepend || $append)) {
                $this->page->meta_title = $this->page->title = ($prepend ? ($prepend . ' ') : '') . ($this->page->meta_title ? $this->page->meta_title : $this->page->title) . ($append ? (' ' . $append) : '');
            }
        }

        // load critical css for this page if it exists
        $this->page->critical_styles = '';
        $theme = Theme::getActiveTheme();
        $critical_styles_path = $theme->getPath().('/assets/css/'.$this->page->baseFileName.'_critical.min.css');
        if (File::exists($critical_styles_path)) {
            $this->page->critical_styles = File::get($critical_styles_path);
        }
    }

}