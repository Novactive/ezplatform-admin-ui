<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformAdminUi\UI\Module\Search\ValueObjectVisitor;

use eZ\Publish\Core\REST\Common\Output\Generator;
use eZ\Publish\Core\REST\Common\Output\ValueObjectVisitor;
use eZ\Publish\Core\REST\Common\Output\Visitor;
use EzSystems\EzPlatformAdminUi\UI\Module\Subitems\Values\SubitemsList as SubitemsListValue;

class ItemsList extends ValueObjectVisitor
{
    /**
     * @param Visitor $visitor
     * @param Generator $generator
     * @param SubitemsListValue $data
     */
    public function visit(Visitor $visitor, Generator $generator, $data)
    {
        $generator->startObjectElement('ItemsList');
        $visitor->setHeader('Content-Type', $generator->getMediaType('ItemsList'));
        //@todo Needs refactoring, disabling certain headers should not be done this way
        $visitor->setHeader('Accept-Patch', false);

        $generator->startList('ItemsRow');
        foreach ($data->itemRows as $itemsRow) {
            $visitor->visitValueObject($itemsRow);
        }
        $generator->endList('ItemsRow');

        $generator->endObjectElement('ItemsList');
    }
}
