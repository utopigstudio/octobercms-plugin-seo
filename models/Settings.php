<?php namespace Utopigs\Seo\Models;

use October\Rain\Database\Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'utopigs_seo_settings';

    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {
        $this->prepend_append_in_pages_with_seo = 1;
        $this->events_type_to_launch = 'pages.menuitem';
    }
}
