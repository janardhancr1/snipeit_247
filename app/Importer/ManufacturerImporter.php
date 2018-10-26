<?php

namespace App\Importer;

use App\Helpers\Helper;
use App\Models\Statuslabel;

class ManufacturerImporter extends ItemImporter
{
    protected $defaultStatusLabelId;

    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->defaultStatusLabelId = Statuslabel::first()->id;
    }

    protected function handle($row)
    {
        $item_manufacturer = $this->findCsvMatch($row, "manufacturer");
        $this->createOrFetchManufacturer($item_manufacturer);
    }
}