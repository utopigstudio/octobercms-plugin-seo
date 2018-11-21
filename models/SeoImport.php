<?php namespace Utopigs\Seo\Models;

use \Backend\Models\ImportModel;
use Utopigs\Seo\Models\Seo;
use Session;

class SeoImport extends ImportModel
{
    public $rules = [];

    public function importData($results, $sessionKey = null)
    {
        $defaultLocale = \RainLab\Translate\Models\Locale::getDefault()->code;
        //load path from session to be able to load images if they exist
        $path = pathinfo(Session::get('importCsvPath'), PATHINFO_DIRNAME);

        foreach ($results as $row => $data) {

            try {
                $locale = $data['locale'];
                unset($data['locale']);

                $image = NULL;
                if (isset($data['image'])) {
                    $image = $data['image'];
                    unset($data['image']);
                }

                $seo = Seo::where('type', $data['type'])->where('reference', $data['reference'])->first();

                if (!$seo) {
                    $seo = new Seo;
                    $seo->fill($data);
                }

                if ($locale != $defaultLocale) {
                    $seo->translateContext($locale);
                }
                
                if ($locale != $defaultLocale || !$seo->wasRecentlyCreated) {
                    $seo->fill($data);
                }

                $seo->save();

                if ($image) {
                    $seo->image()->create(['data' => $path . '/' . $image]);
                }

                if ($seo->wasRecentlyCreated) {
                    $this->logCreated();
                } else {
                    $this->logUpdated();
                }
            }
            catch (\Exception $ex) {
                $this->logError($row, $ex->getMessage());
            }

        }
    }

    //override import method to load csv path from session
    public function import($matches, $options = [])
    {
        $sessionKey = array_get($options, 'sessionKey');
        $path = Session::get('importCsvPath');
        $data = $this->processImportData($path, $matches, $options);
        return $this->importData($data, $sessionKey);
    }

}
