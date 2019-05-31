<?php namespace Utopigs\Seo;

use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use Backend;
use Utopigs\Seo\Classes\EventsManager;

class Plugin extends PluginBase
{
    /**
     * @var array Plugin dependencies
     */
    public $require = ['RainLab.Translate'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
	{
		return [
			'name'		  => 'utopigs.seo::lang.plugin.name',
			'description' => 'utopigs.seo::lang.plugin.description',
			'author'	  => 'Utopig Studio',
			'icon'		  => 'icon-line-chart',
            'homepage'    => 'https://github.com/utopigstudio/octobercms-plugin-seo'
		];
	}

    /**
     * Attach the Rainlab.Blog events
     * Auto create and delete the Seo records for blog posts and categories
     * @author Erwan Regnier
     * @link https://eregnier.it
     */
    public function boot()
    {
        $eventmanager = new EventsManager;
        $eventmanager->subscribeBlogPostEvents();
        $eventmanager->subscribeBlogCategoryEvents();
    }

	public function registerComponents()
	{
		return [
			'\Utopigs\Seo\Components\Seo' => 'seo',
			'\Utopigs\Seo\Components\SeoModel' => 'seoModel',
    	];
	}

    public function registerPermissions()
    {
        return [
            'utopigs.seo.manage'  => [
                'tab'   => 'utopigs.seo::lang.plugin.name',
                'label' => 'utopigs.seo::lang.plugin.manage_seo'
            ],
            'utopigs.seo.sitemap' => [
                'tab'   => 'utopigs.seo::lang.plugin.name',
                'label' => 'utopigs.seo::lang.plugin.manage_sitemap',
            ],
        ];
    }

    public function registerNavigation() {
        $menu =  [
            'seo' => [
                'label'       => 'utopigs.seo::lang.plugin.name',
                'description' => 'utopigs.seo::lang.plugin.description',
                'icon'        => 'icon-line-chart',
                'iconSvg'     => 'plugins/utopigs/seo/assets/images/seo-icon.svg',
                'url'         => Backend::url('utopigs/seo/seo'),
                'permissions' => ['utopigs.seo.manage'],
                'sideMenu' => [
                    'seo' => [
                        'label' => 'utopigs.seo::lang.seo.menu',
                        'icon' => 'icon-line-chart',
                        'url' => Backend::url('utopigs/seo/seo'),
                        'permissions' => ['utopigs.seo.manage'],
                    ],
                    'sitemap' => [
                        'label' => 'utopigs.seo::lang.sitemap.menu',
                        'icon' => 'icon-sitemap',
                        'url' => Backend::url('utopigs/seo/sitemap'),
                        'permissions' => ['utopigs.seo.sitemap'],
                    ],
                ]
            ]
        ];
        
        return $menu;
    }

    public function registerFormWidgets()
    {
        return [
            'Utopigs\Seo\FormWidgets\SeoTab' => 'utopigs_seotab',
        ];
    }

}