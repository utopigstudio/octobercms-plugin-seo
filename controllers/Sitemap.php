<?php namespace Utopigs\Seo\Controllers;

use Url;
use Backend;
use Request;
use Redirect;
use BackendMenu;
use Cms\Classes\Theme;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;
use Utopigs\Seo\Models\Sitemap as SitemapModel;
use ApplicationException;
use Utopigs\Seo\Classes\SitemapItem;
use Exception;

/**
 * Definitions Back-end Controller
 */
class Sitemap extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController'
    ];

    public $requiredPermissions = ['utopigs.seo.sitemap'];

    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Utopigs.Seo', 'seo', 'sitemap');

        $this->addJs('/modules/backend/assets/js/october.treeview.js', 'core');
        $this->addJs('/plugins/utopigs/seo/assets/js/sitemap-definitions.js');
    }

    /**
     * Index action. Find or create a new Sitemap model,
     * then redirect to the update form.
     */
    public function index()
    {
        try {
            if (!$theme = Theme::getEditTheme()) {
                throw new ApplicationException('Unable to find the active theme.');
            }

            return $this->redirectToThemeSitemap($theme);
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    /**
     * Update action. Add the theme object to the page vars.
     */
    public function update($recordId = null, $context = null)
    {
        $this->bodyClass = 'compact-container';

        try {
            if (!$editTheme = Theme::getEditTheme()) {
                throw new ApplicationException('Unable to find the active theme.');
            }

            $result = $this->asExtension('FormController')->update($recordId, $context);

            $model = $this->formGetModel();
            $theme = Theme::load($model->theme);

            /*
             * Not editing the active sitemap definition
             */
            if ($editTheme->getDirName() != $theme->getDirName()) {
                return $this->redirectToThemeSitemap($editTheme);
            }

            $this->vars['theme'] = $theme;
            $this->vars['themeName'] = $theme->getConfigValue('name', $theme->getDirName());
            $this->vars['sitemapUrl'] = Url::to('/sitemap.xml');

            return $result;
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    public function onGetItemTypeInfo()
    {
        $type = Request::input('type');

        return [
            'sitemapItemTypeInfo' => SitemapItem::getTypeInfo($type)
        ];
    }

    //
    // Helpers
    //

    protected function redirectToThemeSitemap($theme)
    {
        $model = SitemapModel::firstOrCreate(['theme' => $theme->getDirName()]);
        $updateUrl = sprintf('utopigs/seo/sitemap/update/%s', $model->getKey());

        return Backend::redirect($updateUrl);
    }
}
