<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformAdminUi\UI\Module\Search\Values;

use eZ\Publish\Core\REST\Common\Value as RestValue;

class ItemsList extends RestValue
{
    /** @var ItemsRow[] */
    public $itemRows;

    /**
     * @param ItemsRow[] $subitemRows
     */
    public function __construct(array $itemRows)
    {
        $this->itemRows = $itemRows;
    }
}
