<?php namespace Utopigs\Seo\Models;

use \Backend\Models\ExportModel;

class SeoExport extends ExportModel
{
    public function exportData($columns, $sessionKey = null)
    {
        $seo = \Utopigs\Seo\Models\Seo::all();
        $exportdata = [];
        // october 2
        if (class_exists('\RainLab\Translate\Models\Locale')){
            $alternateLocales = array_keys(\RainLab\Translate\Models\Locale::listEnabled());
        } else { // october 3
            $alternateLocales = array_keys(\RainLab\Translate\Classes\Locale::listEnabled());
        }

        foreach ($alternateLocales as $locale) {
            $seo->each(function($page) use ($columns, $locale, &$exportdata) {
                $page->addVisible($columns);
                $page->locale = $locale;
                $page->translateContext($locale);
                $exportdata[] = $page->toArray();
            });
        }

        return $exportdata;
    }

}
