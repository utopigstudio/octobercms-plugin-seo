<?php

return [
    'plugin' => [
        'name' => 'SEO',
        'description' => 'Multilanguage SEO',
        'manage' => 'Manage SEO',
    ],
    'component_seo' => [
        'name' => 'SEO',
        'description' => 'SEO content (attach to layout)',
        'property_prepend' => [
            'title' => 'Prepend',
            'description' => 'Prepend to the page title (e.g. Site Name | )',
        ],
        'property_append' => [
            'title' => 'Append',
            'description' => 'Append to the page title (e.g. | Site Name)',
        ],
    ],
    'component_seomodel' => [
        'name' => 'SEO model',
        'description' => 'SEO content for custom models (attach to pages)',
        'property_pageType' => [
            'title' => 'Page type',
            'description' => 'Page type code of your model',
        ],
        'property_pageProperty' => [
            'title' => 'Page property',
            'description' => 'Page property that contains your model data',
        ],
    ],
    'seo' => [
        'update_title' => 'Update SEO',
        'create_title' => 'Create SEO Page',
        'type' => 'Page type',
        'page' => 'Page name',
        'title' => 'Title',
        'description' => 'Description',
        'keywords' => 'Keywords',
        'keywords_hint' => '(separated with a ,)',
        'image' => 'Image for social networks',
        'import' => 'Import',
        'export' => 'Export',
        'upload_csv_or_zip_file' => 'Upload a CSV or ZIP file'
    ],
    'seotab' => [
        'title' => 'SEO',
        'button' => 'Edit SEO',
        'no_seo_data' => 'No SEO data',
        'must_save_model' => 'Please save the model first',
        'missing_property' => 'You must define a $seoPageType property in :model to use the SeoModel extension.',
        'mapped' => 'SEO data automatically retrieved from :model fields',
        'zip_doesnt_have_csv_file' => 'ZIP file must contain a csv file with import data',
    ]
];