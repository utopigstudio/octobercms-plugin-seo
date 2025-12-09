<?php namespace Utopigs\Seo\Models;

use Model;
use Event;
use Validator;

Validator::extend('type_unique', function($attribute, $value, $parameters, $validator) {
    $presenceVerifier = $validator->getPresenceVerifier();
    $data = $validator->getDatA();
    $count = $presenceVerifier->getCount(
        'utopigs_seo_data',
        $attribute,
        $value,
        isset($data['id']) ? $data['id'] : NULL,
        isset($data['id']) ? 'id' : NULL,
        ['type' => $validator->getData()['type']]
    );
    
    return $count == 0;
});

class Seo extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'utopigs_seo_data';

    public $guarded = [];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'type' => 'required',
        'reference' => 'required|type_unique',
        'title' => 'required_unless:no_index,1|max:70',
        'description' => 'required_unless:no_index,1|max:155',
        'keywords' => 'max:255',
    ];

    public $customMessages = [
        'type_unique' => 'utopigs.seo::validation.type_unique',
        'title.required_unless' => 'utopigs.seo::validation.title_required_unless',
        'description.required_unless' => 'utopigs.seo::validation.description_required_unless',
    ];

    public $translatable = [
        'title',
        'description',
        'keywords'
    ];

    public $attachOne = [
        'image' => 'System\Models\File'
    ];

    private $firstTypeInDropdown = NULL;

    public function getTypeOptions()
    {
        $eventType = Settings::get('events_type_to_launch', 'pages.menuitem');

        $filterValue = NULL;
        if (!$this->exists) {
            $params = app()->request->all();

            if (!empty($params['type'])) {
                $filterValue = $params['type'];
            }
        }

        $result = [];
        $apiResult = Event::fire($eventType.'.listTypes');

        if (is_array($apiResult)) {
            foreach ($apiResult as $typeList) {
                if (!is_array($typeList)) {
                    continue;
                }

                foreach ($typeList as $typeCode => $typeName) {
                    if (is_null($this->firstTypeInDropdown)) {
                        $this->firstTypeInDropdown = $typeCode;
                    }
                    $apiResult2 = Event::fire($eventType.'.getTypeInfo', [$typeCode]);
                    if (is_array($apiResult2)) {
                        foreach ($apiResult2 as $typeInfo) {
                            if (isset($typeInfo['references'])) {
                                if (!$filterValue || $filterValue == $typeCode) {
                                    $result[$typeCode] = $typeName;
                                    if ($filterValue == $typeCode) {
                                        return $result;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function getReferenceOptions()
    {
        $eventType = Settings::get('events_type_to_launch', 'pages.menuitem');

        $type = $this->type ?? $this->firstTypeInDropdown;
        if (is_null($type)) {
            $type = 'cms-page';
        }

        $filterValue = NULL;
        if (!$this->exists) {
            $params = app()->request->all();

            if (!empty($params['type'])) {
                $type = $params['type'];
            }
            if (!empty($params['reference'])) {
                $filterValue = $params['reference'];
            }
        }

        $apiResult = Event::fire($eventType.'.getTypeInfo', [$type]);

        $items = [];

        $iterator = function($children) use (&$iterator, $filterValue) {
            $child_items = [];

            foreach ($children as $child_key => $child) {
                if (is_array($child)) {
                    if (!$filterValue || $filterValue == $child_key) {
                        $child_items[$child_key] = $child['title'];
                        if ($filterValue == $child_key) {
                            return $child_items;
                        }
                    }
                    if (!empty($child['items'])) {
                        $child_items = array_replace($child_items, $iterator($child['items'], $filterValue));
                    }
                } else {
                    if (!$filterValue || $filterValue == $child_key) {
                        $child_items[$child_key] = $child;
                        if ($filterValue == $child_key) {
                            return $child_items;
                        }
                    }
                }
            }

            return $child_items;
        };

        if (is_array($apiResult)) {
            foreach ($apiResult as $typeInfo) {
                if (isset($typeInfo['references'])) {
                    foreach ($typeInfo['references'] as $key => $item) {
                        if (is_array($item)) {
                            if (!$filterValue || $filterValue == $key) {
                                $items[$key] = $item['title'];
                                if ($filterValue == $key) {
                                    return $items;
                                }
                            }
                            if (!empty($item['items'])) {
                                $items = array_replace($items, $iterator($item['items'], $filterValue));
                            }
                        } else {
                            if (!$filterValue || $filterValue == $key) {
                                $items[$key] = $item;
                                if ($filterValue == $key) {
                                    return $items;
                                }
                            }
                        }

                    }
                    return $items;
                }
            }
        }

    }

    public function getModelTitleAttribute()
    {
        $eventType = Settings::get('events_type_to_launch', 'pages.menuitem');

        $apiResult = Event::fire($eventType.'.getTypeInfo', [$this->type]);

        if (is_array($apiResult)) {

            $itemReference = $this->reference;

            $iterator = function($items, $nesting = true) use (&$iterator, $itemReference) {
                if (isset($items[$itemReference])) {
                    if (is_array($items[$itemReference])) {
                        if (isset($items[$itemReference]['title'])) {
                            return $items[$itemReference]['title'];
                        }
                        return '#' . $itemReference . ' [no title]';
                    } else {
                        return ($items[$itemReference]);
                    }
                } elseif ($nesting) {
                    foreach ($items as $item) {
                        if (isset($item['items']) && is_array($item['items']) && !empty($item['items'])) {
                            return $iterator($item['items']);
                        }
                    }
                }
            };

            foreach ($apiResult as $typeInfo) {
                if (isset($typeInfo['references']) && is_array($typeInfo['references']) && !empty($typeInfo['references'])) {
                    $nesting = !empty($typeInfo['nesting']);
                    $title = $iterator($typeInfo['references'], $nesting);
                    if ($title) return $title;
                }
            }
        }

        return '[deleted]';
    }

    public function filterFields($fields, $context = null)
    {
        if ($context == 'update') {
            $fields->type->disabled = true;
            $fields->reference->disabled = true;
            return;
        }
    }
}
