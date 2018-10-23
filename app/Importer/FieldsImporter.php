<?php

namespace App\Importer;

use App\Helpers\Helper;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Statuslabel;

class FieldsImporter extends ItemImporter
{
    protected $defaultStatusLabelId;

    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->defaultStatusLabelId = Statuslabel::first()->id;
    }

    protected function handle($row)
    {
        if ($this->customFields) {
            foreach ($this->customFields as $customField) {
                $customFieldValue = $this->array_smart_custom_field_fetch($row, $customField);
                if (strlen($customFieldValue) > 0) {
                    $values = $customField->formatFieldValuesAsArray();
                    //echo $customFieldValue . " - " . !in_array($customFieldValue, $values) . "\r\n";
                    if (!in_array($customFieldValue, $values)) {
                        $customField->field_values .= $customFieldValue . "\r\n";
                        $customField->save();
                    }
                }
            }
        }
    }
}