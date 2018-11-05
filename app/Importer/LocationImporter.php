<?php

namespace App\Importer;

use App\Helpers\Helper;
use App\Models\Statuslabel;

class LocationImporter extends ItemImporter
{
    protected $defaultStatusLabelId;

    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->defaultStatusLabelId = Statuslabel::first()->id;
    }

    protected function handle($row)
    {
        $item_location = $this->findCsvMatch($row, "location");
        $item_country = $this->findCsvMatch($row, "country");
        $this->createLocations($item_location, $item_country);
    }
}