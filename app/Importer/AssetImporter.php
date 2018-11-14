<?php

namespace App\Importer;

use App\Helpers\Helper;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Statuslabel;
use App\Models\CustomField;
use League\Csv\Writer;

class AssetImporter extends ItemImporter
{
    protected $defaultStatusLabelId;

    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->defaultStatusLabelId = Statuslabel::first()->id;
    }

    protected function handle($row)
    {
        // ItemImporter handles the general fetching.
        parent::handle($row);
        $createAsset = true;

        $filename = config('app.private_uploads') . '/imports/importerror.csv';
        $writer = Writer::createFromPath($filename, 'a');
        $row["Erros"] = "Invalid data for these master : ";
        if ($this->customFields) {
            foreach ($this->customFields as $customField) {
                $customFieldValue = $this->array_smart_custom_field_fetch($row, $customField);
                if (strlen($customFieldValue) > 0) {
                    $this->item['custom_fields'][$customField->db_column_name()] = $customFieldValue;
                    $this->log('Custom Field '. $customField->name .': '.$customFieldValue);
                    if($customField->element == 'listbox'){
                        $values = $customField->formatFieldValuesAsArray();
                        if(!in_array(strtolower(trim($customFieldValue)), array_map('strtolower', $values))) {
                            $this->log('Custom Field ' . $customField->name . ' value not found in msater data.');
                            $row["Erros"] .= ", " .$customField->name;
                            $createAsset = false;
                        }
                    }
                } else {
                    // Clear out previous data.
                    //$this->item['custom_fields'][$customField->db_column_name()] = null;
                    // Data not found in the custom fields master log and throws error
                    $this->log('Custom Field ' . $customField->name . ' value not found.');
                    $createAsset = false;
                }
            }
        }

        if($createAsset) {
            $this->createAssetIfNotExists($row);
        } else {
            $writer->insertOne($row);
            //$asset = new Asset;
            //$this->logError($asset,  'Asset "' . $this->item['name'].'"');
        }
    }

    /**
     * Create the asset if it does not exist.
     *
     * @author Daniel Melzter
     * @since 3.0
     * @param array $row
     * @return Asset|mixed|null
     */
    public function createAssetIfNotExists(array $row)
    {
        $editingAsset = false;
        $asset_tag = $this->findCsvMatch($row, "asset_tag");
        $asset = Asset::where(['asset_tag'=> $asset_tag])->first();
        if ($asset) {
            if (!$this->updating) {
                $this->log('A matching Asset ' . $asset_tag . ' already exists');
                return;
            }

            $this->log("Updating Asset");
            $editingAsset = true;
        } else {
            $this->log("No Matching Asset, Creating a new one");
            $asset = new Asset;
            $asset_tag = Asset::autoincrement_asset();
        }
        
        $this->item['image'] = $this->findCsvMatch($row, "image");
        $this->item['warranty_months'] = intval($this->findCsvMatch($row, "warranty_months"));
        $this->item['model_id'] = $this->fetchAssetModel($row);

        // If no status ID is found
        if (!array_key_exists('status_id', $this->item) && !$editingAsset) {
            $this->log("No status field found, defaulting to first status.");
            $this->item['status_id'] = $this->defaultStatusLabelId;
        }

        $this->item['asset_tag'] = $asset_tag;

        // We need to save the user if it exists so that we can checkout to user later.
        // Sanitizing the item will remove it.
        if(array_key_exists('checkout_target', $this->item)) {
            $target = $this->item['checkout_target'];
        }
        $item = $this->sanitizeItemForStoring($asset, $editingAsset);
        // The location id fetched by the csv reader is actually the rtd_location_id.
        // This will also set location_id, but then that will be overridden by the
        // checkout method if necessary below.
        if (isset($this->item["location_id"])) {
            $item['rtd_location_id'] = $this->item['location_id'];
        }

        //if no serial number do not import
        if(empty($item["serial"])){
            $this->log("No serail found, ignoring this asset.");
            return;
        }

        $item_category = $this->findCsvMatch($row, "category");
        $techCustomField = CustomField::where(["name" => "Major Category"])->first();
        $this->item['custom_fields'][$techCustomField->db_column_name()] = $asset->updateMajorCategory($item_category);

        if ($editingAsset) {
            $asset->update($item);
        } else {
            $asset->fill($item);
        }
        

        // If we're updating, we don't want to overwrite old fields.
        if (array_key_exists('custom_fields', $this->item)) {
            foreach ($this->item['custom_fields'] as $custom_field => $val) {
                $asset->{$custom_field} = $val;
            }
        }

        //FIXME: this disables model validation.  Need to find a way to avoid double-logs without breaking everything.
        // $asset->unsetEventDispatcher();
        if ($asset->save()) {
            $asset->logCreate('Imported using csv importer');
            $this->log('Asset ' . $this->item["name"] . ' with serial number ' . $this->item['serial'] . ' was created');
            $settings = \App\Models\Setting::getSettings();
            $settings->next_auto_tag_base ++;
            $settings->save();
            // If we have a target to checkout to, lets do so.
            if(isset($target)) {
                $asset->fresh()->checkOut($target);
            }
            return;
        }
        $this->logError($asset, 'Asset "' . $this->item['name'].'"');
    }
}
