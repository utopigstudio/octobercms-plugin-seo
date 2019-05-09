<?php

use Cms\Classes\Theme;
use Cms\Classes\Controller;
use Utopigs\Seo\Models\Sitemap;
use Illuminate\Database\Eloquent\ModelNotFoundException as ModelNotFound;

Route::get('sitemap.xml', function()
{
    $themeActive = Theme::getActiveTheme()->getDirName();

    try {
    	$definition = Sitemap::where('theme', $themeActive)->firstOrFail();
    } catch (ModelNotFound $e) {
    	Log::info(trans('utopigs.seo::lang.sitemap.not_found'));

    	return App::make(Controller::class)->setStatusCode(404)->run('/404');
    }

    return Response::make($definition->generateSitemap())
        ->header("Content-Type", "application/xml");
});

Route::get('sitemap-debug.xml', function()
{
    $themeActive = Theme::getActiveTheme()->getDirName();

    try {
    	$definition = Sitemap::where('theme', $themeActive)->firstOrFail();
    } catch (ModelNotFound $e) {
    	Log::info(trans('utopigs.seo::lang.sitemap.not_found'));

    	return App::make(Controller::class)->setStatusCode(404)->run('/404');
    }

    return Response::make($definition->generateSitemap('https'))
        ->header("Content-Type", "application/xml");
});
