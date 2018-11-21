<?php

namespace Utopigs\Seo\Behaviors;

use Illuminate\Support\Facades\Event;
use System\Classes\ModelBehavior;
use ApplicationException;
use Yaml;

class SeoModel extends ModelBehavior
{
    protected $seoPageType;

    protected $seoTab;

    protected $modelClass = '';

    public function __construct($model)
    {
        parent::__construct($model);

        $this->setProperties($model);

        $this->extendFields();

        $model->bindEvent('model.saveInternal', function ($attributes, $options) use ($model) {
            unset($model->utopigs_seotab);
        });
    }

    protected function setProperties($model)
    {
        if (!isset($model->seoPageType)) {
            throw new ApplicationException(trans('utopigs.seo::lang.seotab.missing_property', ['model' => class_basename($model)]));
        }

        $this->seoPageType = $model->seoPageType;

        $this->modelClass = get_class($model);

        $this->seoTab = isset($model->seoTab) ? $model->seoTab : 'primary';
    }

    protected function extendFields()
    {
        Event::listen('backend.form.extendFields', function ($widget) {
            if (isset($widget->model) && get_class($widget->model) == $this->modelClass && !$widget->isNested) {
                $seoFields = Yaml::parseFile(plugins_path('utopigs/seo/models/seo/tab.yaml'));

                $seoFields['utopigs_seotab@update']['pageType'] = $this->seoPageType;

                $widget->addFields($seoFields, $this->seoTab);
            }
        });
    }

}