<?php namespace Utopigs\Seo\Classes;

/**
* Events manager class
* @author Erwan Regnier
* @license MIT https://opensource.org/licenses/MIT
* @link https://eregnier.it
*/

use Event;
use October\Rain\Support\Str;
use RainLab\Translate\Models\Locale;
use Utopigs\Seo\Models\Seo as UtopigsSeo;

class EventsManager
{
    protected $locales = [];

    public function __construct()
    {
        //Get all enabled languages except the default one
        $this->locales = array_diff(array_keys(Locale::listEnabled()), array(Locale::getDefault()->code));
    }

    /* Register the event to fire when a blog post is created */
    public function subscribeBlogPostEvents()
    {
        Event::listen('eloquent.created: RainLab\Blog\Models\Post', function($model) {$this->blogPostCreatedHandler($model);});
        Event::listen('eloquent.deleted: RainLab\Blog\Models\Post', function($model) {$this->blogPostDeletedHandler($model);});

    }

    /* Register the event to fire when a blog category is created */
    public function subscribeBlogCategoryEvents()
    {
        Event::listen('eloquent.created: RainLab\Blog\Models\Category', function($model) {$this->blogCategoryCreatedHandler($model);});
        Event::listen('eloquent.deleted: RainLab\Blog\Models\Category', function($model) {$this->blogCategoryDeletedHandler($model);});
    }


    /* Create the SEO record by default when a blog post is created*/
    protected function blogPostCreatedHandler($model)
    {
        $description = (!empty($model->excerpt)) ? Str::limit($model->excerpt, 155, '') : Str::limit(strip_tags($model->content), 155, '');
        $seodatas = [
            'type' => 'blog-post',
            'reference' => $model->id,
            'title' => Str::limit($model->title, 70),
            'description' => $description
        ];

        $seo = UtopigsSeo::create($seodatas);

        $model->noFallbackLocale(); //Avoid the return of default texts if the excerpt isn't translated

        foreach ($this->locales as $lang):
            if ($model->hasTranslation('title', $lang) && $model->hasTranslation('content', $lang)) {
                $tr_description = (!empty($model->getAttributeTranslated('excerpt', $lang))) ? Str::limit($model->getAttributeTranslated('excerpt', $lang), 155, '') : Str::limit(strip_tags($model->getAttributeTranslated('content', $lang)), 155, '');
               $tr_title = Str::limit($model->getAttributeTranslated('title', $lang), 70);
                $seo->setAttributeTranslated('title', $tr_title, $lang);
                $seo->setAttributeTranslated('description', $tr_description, $lang);
            }
        endforeach;

        $seo->save();
    }

    /* Delete the SEO record when a blog post is deleted*/
    protected function blogPostDeletedHandler($model)
    {
        $seo = UtopigsSeo::where('type', 'blog-post')->where('reference', $model->id)->first();
        if ($seo) {
            $seo->delete();
        }
    }

    /* Create the SEO record by default when a blog category is created*/
    protected function blogCategoryCreatedHandler($model)
    {
        $description = Str::limit($model->description, 155, '');
        $seodatas = [
            'type' => 'blog-category',
            'reference' => $model->id,
            'title' => Str::limit($model->name, 70),
            'description' => $description
        ];

        $seo = UtopigsSeo::create($seodatas);

        foreach ($this->locales as $lang):
            if ($model->hasTranslation('name', $lang) && $model->hasTranslation('description', $lang)) {
                $tr_description = Str::limit($model->getAttributeTranslated('description', $lang), 155, '');
                $tr_title = Str::limit($model->getAttributeTranslated('name', $lang), 70);
                $seo->setAttributeTranslated('title', $tr_title, $lang);
                $seo->setAttributeTranslated('description', $tr_description, $lang);
            }
        endforeach;

        $seo->save();
    }

    /* Delete the SEO record when a blog category is deleted*/
    protected function blogCategoryDeletedHandler($model)
    {
        $seo = UtopigsSeo::where('type', 'blog-category')->where('reference', $model->id)->first();
        if ($seo) {
            $seo->delete();
        }
    }


}