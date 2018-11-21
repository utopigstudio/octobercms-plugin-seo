<?php

return [
    'plugin' => [
        'name' => 'SEO',
        'description' => 'SEO multidioma',
        'manage' => 'Gestionar el SEO',
    ],
    'component_seo' => [
        'name' => 'SEO',
        'description' => 'Contenido SEO (enlazar al layout)',
        'property_prepend' => [
            'title' => 'Prefijar',
            'description' => 'Prefijar al título de la página (ej. Nombre Sitio | )',
        ],
        'property_append' => [
            'title' => 'Anexar',
            'description' => 'Anexar al título de la página (ej. | Nombre Sitio)',
        ],
    ],
    'component_seomodel' => [
        'name' => 'SEO del modelo',
        'description' => 'Contenido SEO de modelos (enlazar a las páginas)',
        'property_pageType' => [
            'title' => 'Tipo de página',
            'description' => 'Código del tipo de página del modelo',
        ],
        'property_pageProperty' => [
            'title' => 'Propiedad de la página',
            'description' => 'Propiedad de la página que contiene los datos del modelo',
        ],
    ],
    'seo' => [
        'update_title' => 'Actualizar SEO',
        'create_title' => 'Crear SEO para página',
        'type' => 'Tipo de página',
        'page' => 'Nombre de página',
        'title' => 'Título',
        'description' => 'Descripción',
        'keywords' => 'Palabras clave',
        'keywords_hint' => '(separadas con ,)',
        'image' => 'Imagen para redes sociales',
        'import' => 'Importar',
        'export' => 'Exportar',
    ],
    'seotab' => [
        'title' => 'SEO',
        'button' => 'Editar SEO',
        'no_seo_data' => 'No hay SEO',
        'must_save_model' => 'Por favor guarda el modelo primero',
        'missing_property' => 'Debes definir una propiedad $seoPageType en :model para usar la extensión SeoModel.',
        'mapped' => 'Datos SEO automáticamente establecidos de los campos de :model',
        'zip_doesnt_have_csv_file' => 'El archivo ZIP debe contener un archivo csv con datos para poder importar',
    ]
];