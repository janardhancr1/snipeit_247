<?php

namespace App\Importer;

use App\Helpers\Helper;
use App\Models\Statuslabel;

class CategoriesImporter extends ItemImporter
{
    protected $defaultStatusLabelId;

    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->defaultStatusLabelId = Statuslabel::first()->id;
    }

    protected function handle($row)
    {
        $item_category = $this->findCsvMatch($row, "category");
        $this->createOrFetchCategory($item_category);
    }
}