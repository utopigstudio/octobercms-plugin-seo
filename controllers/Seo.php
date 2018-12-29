<?php namespace Utopigs\Seo\Controllers;

use Backend;
use BackendMenu;
use File;
use Lang;
use Session;
use Str;
use System\Classes\SettingsManager;
use League\Csv\Reader as CsvReader;
use ApplicationException;
use October\Rain\Filesystem\Zip;

class Seo extends \Backend\Classes\Controller {

    public $implement = [
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ImportExportController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $importExportConfig = 'config_import_export.yaml';

    public $importColumns;
    protected $importUploadFormWidgetCustom;
    protected $importOptionsFormWidget;

    public $requiredPermissions = ['utopigs.seo.manage'];

    protected $backUrl = '';

	public function __construct()
	{
	    parent::__construct();
        BackendMenu::setContext('Utopigs.Seo', 'seo', 'seo');

        $this->backUrl = Backend::url('utopigs/seo/seo');

        $params = app()->request->all();
        if (!empty($params['back'])) {
            $this->backUrl = $params['back'];
        }

        $config = ($this->getConfig());
        $config->defaultRedirect = $this->backUrl;
        $config->update['redirectClose'] = $this->backUrl;
        $config->create['redirectClose'] = $this->backUrl;
        $this->setConfig($config);

        //custom import form widget that allows zip files
        if ($this->importUploadFormWidgetCustom = $this->makeImportUploadFormWidget()) {
            $this->importUploadFormWidgetCustom->bindToController();
        }

	}

    //override import method to allow zip files
    public function import()
    {
        if ($response = $this->checkPermissionsForType('import')) {
            return $response;
        }

        //js that calls our custom methods onImportCustomLoadColumnSampleForm and onImportCustom
        $this->addJs('/plugins/utopigs/seo/assets/js/october.import.js');
        $this->addCss('/modules/backend/behaviors/importexportcontroller/assets/css/import.css', 'core');

        $this->pageTitle = $this->pageTitle
            ?: Lang::get($this->getConfig('import[title]', 'Import records'));

        $this->prepareImportVars();

        $this->vars['importUploadFormWidgetCustom'] = $this->importUploadFormWidgetCustom;
    }

    //override method to allow zip files
    protected function makeImportUploadFormWidget()
    {
        if (!$this->getConfig('import')) {
            return null;
        }

        //field definition file that allows zip files
        $widgetConfig = $this->makeConfig('~/plugins/utopigs/seo/controllers/seo/fields_import.yaml');
        $widgetConfig->model = $this->importGetModel();
        $widgetConfig->alias = 'importUploadFormCustom';

        $widget = $this->makeWidget('Backend\Widgets\Form', $widgetConfig);

        $widget->bindEvent('form.beforeRefresh', function ($holder) {
            $holder->data = [];
        });

        return $widget;
    }

    //override method to unzip imported file if it's a zip
    protected function getImportFileColumns()
    {
        if (!$path = $this->getImportFilePath()) {
            return null;
        }

        $tempPath = temp_path() . '/'.uniqid('oc');

        $importFileExtension = pathinfo($path, PATHINFO_EXTENSION);

        if ($importFileExtension == 'zip') {
            Zip::extract($path, $tempPath);

            $files = File::files($tempPath);

            foreach ($files as $file) {
                if ($file->getExtension() == 'csv') {
                    $csvPath = $file->getPathname();
                }
            }

            if (empty($csvPath)) {
                throw new ApplicationException(Lang::get('utopigs.seo::lang.seo.zip_doesnt_have_csv_file'));
            }
        } else {
            $csvPath = $path;
        }

        //save csv file path in session to be able to access it again later
        Session::put('importCsvPath', $csvPath);

        $reader = $this->createCsvReader($csvPath);
        $firstRow = $reader->fetchOne(0);

        if (!post('first_row_titles')) {
            array_walk($firstRow, function (&$value, $key) {
                $value = 'Column #'.($key + 1);
            });
        }

        /*
         * Prevents unfriendly error to be thrown due to bad encoding at response time.
         */
        if (json_encode($firstRow) === false) {
            throw new ApplicationException(Lang::get('backend::lang.import_export.encoding_not_supported_error'));
        }

        return $firstRow;
    }

    //custom loadColumnSampleForm method that loads csv path from session
    public function onImportCustomLoadColumnSampleForm()
    {
        if (($columnId = post('file_column_id', false)) === false) {
            throw new ApplicationException(Lang::get('backend::lang.import_export.missing_column_id_error'));
        }

        $columns = $this->getImportFileColumns();
        if (!array_key_exists($columnId, $columns)) {
            throw new ApplicationException(Lang::get('backend::lang.import_export.unknown_column_error'));
        }

        $path = Session::get('importCsvPath');

        $reader = $this->createCsvReader($path);

        if (post('first_row_titles')) {
            $reader->setOffset(1);
        }

        $result = $reader->setLimit(50)->fetchColumn((int) $columnId);
        $data = iterator_to_array($result, false);

        /*
         * Clean up data
         */
        foreach ($data as $index => $sample) {
            $data[$index] = Str::limit($sample, 100);
            if (!strlen($data[$index])) {
                unset($data[$index]);
            }
        }

        $this->vars['columnName'] = array_get($columns, $columnId);
        $this->vars['columnData'] = $data;

        return $this->importExportMakePartial('column_sample_form');
    }

    //custom import method that uses our custom import form widget that allows zip files
    public function onImportCustom()
    {
        try {
            $model = $this->importGetModel();
            $matches = post('column_match', []);

            if ($optionData = post('ImportOptions')) {
                $model->fill($optionData);
            }

            $importOptions = $this->getFormatOptionsFromPost();
            $importOptions['sessionKey'] = $this->importUploadFormWidgetCustom->getSessionKey();
            $importOptions['firstRowTitles'] = post('first_row_titles', false);

            $model->import($matches, $importOptions);

            $this->vars['importResults'] = $model->getResultStats();
            $this->vars['returnUrl'] = $this->getRedirectUrlForType('import');
        }
        catch (MassAssignmentException $ex) {
            $this->handleError(new ApplicationException(Lang::get(
                'backend::lang.model.mass_assignment_failed',
                ['attribute' => $ex->getMessage()]
            )));
        }
        catch (Exception $ex) {
            $this->handleError($ex);
        }
        
        $this->vars['sourceIndexOffset'] = $this->getImportSourceIndexOffset($importOptions['firstRowTitles']);

        return $this->importExportMakePartial('import_result_form');
    }

    //all these methods need to be here because they are protected, and we need to use them from our overriden and custom methods
    public function prepareImportVars()
    {
        $this->vars['importUploadFormWidget'] = $this->importUploadFormWidget;
        $this->vars['importOptionsFormWidget'] = $this->importOptionsFormWidget;
        $this->vars['importDbColumns'] = $this->getImportDbColumns();
        $this->vars['importFileColumns'] = $this->getImportFileColumns();
    }

    protected function getImportDbColumns()
    {
        if ($this->importColumns !== null) {
            return $this->importColumns;
        }

        $columnConfig = $this->getConfig('import[list]');
        $columns = $this->makeListColumns($columnConfig);

        if (empty($columns)) {
            throw new ApplicationException(Lang::get('backend::lang.import_export.empty_import_columns_error'));
        }

        return $this->importColumns = $columns;
    }

    protected function getImportFilePath()
    {
        return $this
            ->importGetModel()
            ->getImportFilePath($this->importUploadFormWidgetCustom->getSessionKey());
    }

    protected function createCsvReader($path)
    {
        $reader = CsvReader::createFromPath($path);
        $options = $this->getFormatOptionsFromPost();

        if ($options['delimiter'] !== null) {
            $reader->setDelimiter($options['delimiter']);
        }

        if ($options['enclosure'] !== null) {
            $reader->setEnclosure($options['enclosure']);
        }

        if ($options['escape'] !== null) {
            $reader->setEscape($options['escape']);
        }

        if (
            $options['encoding'] !== null &&
            $reader->isActiveStreamFilter()
        ) {
            $reader->appendStreamFilter(sprintf(
                '%s%s:%s',
                TranscodeFilter::FILTER_NAME,
                strtolower($options['encoding']),
                'utf-8'
            ));
        }

        return $reader;
    }

    protected function getFormatOptionsFromPost()
    {
        $presetMode = post('format_preset');

        $options = [
            'delimiter' => null,
            'enclosure' => null,
            'escape' => null,
            'encoding' => null
        ];

        if ($presetMode == 'custom') {
            $options['delimiter'] = post('format_delimiter');
            $options['enclosure'] = post('format_enclosure');
            $options['escape'] = post('format_escape');
            $options['encoding'] = post('format_encoding');
        }

        return $options;
    }

    protected function getRedirectUrlForType($type)
    {
        $redirect = $this->getConfig($type.'[redirect]');

        if ($redirect !== null) {
            return $redirect ? Backend::url($redirect) : 'javascript:;';
        }

        return $this->actionUrl($type);
    }

    protected function checkPermissionsForType($type)
    {
        if (
            ($permissions = $this->getConfig($type.'[permissions]')) &&
            (!BackendAuth::getUser()->hasAnyAccess((array) $permissions))
        ) {
            return Response::make(View::make('backend::access_denied'), 403);
        }
    }

    protected function makeListColumns($config)
    {
        $config = $this->makeConfig($config);

        if (!isset($config->columns) || !is_array($config->columns)) {
            return null;
        }

        $result = [];
        foreach ($config->columns as $attribute => $column) {
            if (is_array($column)) {
                $result[$attribute] = array_get($column, 'label', $attribute);
            }
            else {
                $result[$attribute] = $column ?: $attribute;
            }
        }

        return $result;
    }

    protected function getImportSourceIndexOffset($firstRowTitles)
    {
        return $firstRowTitles ? 2 : 1;
    }

}