<?php namespace Utopigs\Seo\Classes;

/**
* Events manager class
* @author Erwan Regnier
* @license MIT https://opensource.org/licenses/MIT
* @link https://eregnier.it
*/

use Event;
use October\Rain\Support\Str;
use Utopigs\Seo\Models\Seo as UtopigsSeo;

class EventsManager
{
    /* Register the event to fire when a blog post is created */
    public function subscribeBlogPostEvents()
    {
        Event::listen('eloquent.created: RainLab\Blog\Models\Post', function($model) {$this->blogPostCreatedHandler($model);});

        //Can also create or update, but it will replace previous manual changes from SEO
        //Event::listen('eloquent.saved: RainLab\Blog\Models\Post', function($model) {$this->blogPostCreatedOrUpdatedHandler($model);});

        Event::listen('eloquent.deleted: RainLab\Blog\Models\Post', function($model) {$this->blogPostDeletedHandler($model);});

    }

    /* Register the event to fire when a blog category is created */
    public function subscribeBlogCategoryEvents()
    {
        Event::listen('eloquent.created: RainLab\Blog\Models\Category', function($model) {$this->blogCategoryCreatedHandler($model);});

        //Can also create or update, but it will replace previous manual changes from SEO
        //Event::listen('eloquent.saved: RainLab\Blog\Models\Category', function($model) {$this->blogCategoryCreatedOrUpdatedHandler($model);});

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
    }

    /* Delete the SEO record when a blog category is deleted*/
    protected function blogCategoryDeletedHandler($model)
    {

        $seo = UtopigsSeo::where('type', 'blog-category')->where('reference', $model->id)->first();
        if ($seo) {
            $seo->delete();
        }
    }

    /* Create or update versions

    protected function blogPostCreatedOrUpdatedHandler($model)
    {
        $description = (!empty($model->excerpt)) ? Str::limit($model->excerpt, 155, '') : Str::limit(strip_tags($model->content), 155, '');
        $seodatas = [
            'type' => 'blog-post',
            'reference' => $model->id,
            'title' => Str::limit($model->title, 70),
            'description' => $description
        ];

        $seo = UtopigsSeo::updateOrCreate(['type' => 'blog-post', 'reference' => $model->id], $seodatas);
    }

    protected function blogCategoryCreatedOrUpdatedHandler($model)
    {
        $description = Str::limit($model->description, 155, '');
        $seodatas = [
            'type' => 'blog-category',
            'reference' => $model->id,
            'title' => Str::limit($model->name, 70),
            'description' => $description
        ];

        $seo = UtopigsSeo::updateOrCreate(['type' => 'blog-category', 'reference' => $model->id], $seodatas);
    }

    */

}