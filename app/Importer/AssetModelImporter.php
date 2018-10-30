<?php

namespace App\Importer;

use App\Helpers\Helper;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Statuslabel;
use App\Models\CustomFieldset;

class AssetModelImporter extends ItemImporter
{
    protected $defaultStatusLabelId;

    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->defaultStatusLabelId = Statuslabel::first()->id;
    }

    protected function handle($row)
    {
        $this->item["name"] = $this->findCsvMatch($row, "name");
        $asset_modelNumber = $this->findCsvMatch($row, "model_number");
        $this->item["model_number"] = $asset_modelNumber;

        $item_category = $this->findCsvMatch($row, "category");
        $category = Category::where(['name' => $item_category])->first();

        if ($category) {
            $this->item["category_id"] = $category->id;
        } else {
            //$this->logError($category, 'Category "' . $item_category . '"');
            return;
        }

        $item_manufacturer = $this->findCsvMatch($row, "manufacturer");
        $manufacturer = Manufacturer::where(['name'=> $item_manufacturer])->first();

        if ($manufacturer) {
            $this->item["manufacturer_id"] =  $manufacturer->id;
        } else {
            //$this->logError($manufacturer, 'Manufacturer "' .  $item_manufacturer . '"');
            return;
        }

        $this->item["name"] = $item_category . " " . $item_manufacturer . " " . $asset_modelNumber;
        $asset_model = AssetModel::where(['manufacturer_id' => $this->item["manufacturer_id"], 'model_number' => $asset_modelNumber, 'category_id' => $this->item["category_id"]])->first();
        //print_r($this->item);
        if ($asset_model) {
            $this->logError($asset_model, 'Asset Model "' . $this->item['model_number'].'"');
            return;
        } else {
            $asset_model = new AssetModel;
            $fieldset = CustomFieldset::where(['name' => '247 Fieldset'])->first();
            if($fieldset){
                $this->item['fieldset_id'] = $fieldset->id;
            }

            $item = $this->sanitizeItemForStoring($asset_model, false);
            $asset_model->fill($item);
            
            if ($asset_model->save()) {
                //$asset_model->logCreate('Imported using csv importer');
                $this->log('Asset Model ' . $this->item["name"] . ' with model number ' . $this->item['model_number'] . ' was created');
                return;
            }
        }
    }
}