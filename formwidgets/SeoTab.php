<?php namespace Utopigs\Seo\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Backend;
use Event;

class SeoTab extends FormWidgetBase
{
    protected $defaultAlias = 'utopigs_seotab';

    public $pageType = 'cms-page';

    protected $partial = '';

    public function init()
    {
        $this->fillFromConfig([
            'pageType',
        ]);
    }
    
    public function render()
    {
        $this->prepareVars();
        
        return $this->makePartial($this->partial);
    }

    private function prepareVars()
    {
        $this->vars['alternateLocales'] = $alternateLocales = array_keys(\RainLab\Translate\Models\Locale::listEnabled());

        $seo = \Utopigs\Seo\Models\Seo::where('type', $this->pageType)
            ->where('reference', $this->model->getKey())->first();

        if ($seo) {
            $this->vars['seoData'] = $seo;
            $this->vars['editSeoUrl'] = Backend::url('utopigs/seo/seo/update/'.$seo->id . '?back='.url()->current());
            $this->partial = 'seotab_seodata';
            return;
        }

        $this->vars['editSeoUrl'] = Backend::url('utopigs/seo/seo/create?type='.$this->pageType.'&reference='.$this->model->getKey().'&back='.url()->current());

        $currentLocale = \App::getLocale();
        $defaultLocale = \RainLab\Translate\Models\Locale::getDefault()->code;
        $translator = \RainLab\Translate\Classes\Translator::instance();
        $translator->setLocale($defaultLocale, false);
        $seo_defaults_default_locale = Event::fire('utopigs.seo.mapSeoData', [$this->pageType, $this->model->getKey()], true);
        
        if (!$seo_defaults_default_locale) {
            $this->partial = 'seotab_empty';
            return;
        }

        $this->vars['seoMappedData'] = [];
        $this->vars['modelName'] = class_basename($this->model);
        $this->partial = 'seotab_mapped';

        foreach ($alternateLocales as $locale) {
            if ($locale == $defaultLocale) {
                $seo_defaults = $seo_defaults_default_locale;
            } else {
                $translator->setLocale($locale, false);
                $seo_defaults = Event::fire('utopigs.seo.mapSeoData', [$this->pageType, $this->model->getKey()], true);
            }
            $seoData = new \Utopigs\Seo\Models\Seo;
            if (isset($seo_defaults['title'])) {
                $seoData->title = $seo_defaults['title'];
            }
            if (isset($seo_defaults['description'])) {
                $seoData->description = $seo_defaults['description'];
            }
            if (isset($seo_defaults['keywords'])) {
                $seoData->keywords = $seo_defaults['keywords'];
            }
            if (isset($seo_defaults['image'])) {
                $seoData->image = $seo_defaults['image'];
            }
            $this->vars['seoMappedData'][$locale] = $seoData;
        }

        $translator->setLocale($currentLocale, false);

    }

}