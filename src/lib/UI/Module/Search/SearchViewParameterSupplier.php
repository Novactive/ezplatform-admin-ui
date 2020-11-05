<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformAdminUi\UI\Module\Search;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\REST\Common\Output\Visitor;
use eZ\Publish\Core\REST\Server\Output\ValueObjectVisitor\ContentTypeInfoList as ContentTypeInfoListValueObjectVisitor;
use eZ\Publish\Core\REST\Server\Values\ContentTypeInfoList;
use eZ\Publish\Core\REST\Server\Values\RestContent;
use eZ\Publish\Core\REST\Server\Values\RestLocation;
use EzSystems\EzPlatformAdminUi\UI\Config\Provider\ContentTypeMappings;
use EzSystems\EzPlatformAdminUi\UI\Module\Search\Values\ItemsList;
use EzSystems\EzPlatformAdminUi\UI\Module\Search\Values\ItemsRow;
use EzSystems\EzPlatformAdminUi\UI\Module\Search\ValueObjectVisitor\ItemsList as itemsListValueObjectVisitor;
use eZ\Publish\Core\REST\Common\Output\Generator\Json as JsonOutputGenerator;
use EzSystems\EzPlatformUser\UserSetting\UserSettingService;

/**
 * @internal
 */
class SearchViewParameterSupplier
{
    /** @var \eZ\Publish\Core\REST\Common\Output\Visitor */
    private $outputVisitor;

    /** @var \eZ\Publish\Core\REST\Common\Output\Generator\Json */
    private $outputGenerator;

    /** @var \eZ\Publish\Core\REST\Server\Output\ValueObjectVisitor\ContentTypeInfoList */
    private $contentTypeInfoListValueObjectVisitor;

    /** @var \EzSystems\EzPlatformAdminUi\UI\Module\Subitems\ValueObjectVisitor\SubitemsList */
    private $itemsListValueObjectVisitor;

    /** @var \eZ\Publish\API\Repository\LocationService */
    private $locationService;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    private $contentTypeService;

    /** @var \eZ\Publish\API\Repository\PermissionResolver */
    private $permissionResolver;

    /** @var \EzSystems\EzPlatformAdminUi\UI\Config\Provider\ContentTypeMappings */
    private $contentTypeMappings;

    /** @var \EzSystems\EzPlatformUser\UserSetting\UserSettingService */
    private $userSettingService;

    /**
     * @param \eZ\Publish\Core\REST\Common\Output\Visitor $outputVisitor
     * @param \eZ\Publish\Core\REST\Common\Output\Generator\Json $outputGenerator
     * @param \eZ\Publish\Core\REST\Server\Output\ValueObjectVisitor\ContentTypeInfoList $contentTypeInfoListValueObjectVisitor
     * @param \EzSystems\EzPlatformAdminUi\UI\Module\Search\ValueObjectVisitor\itemsList $itemsListValueObjectVisitor
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \eZ\Publish\API\Repository\PermissionResolver $permissionResolver
     * @param \EzSystems\EzPlatformAdminUi\UI\Config\Provider\ContentTypeMappings $contentTypeMappings
     * @param \EzSystems\EzPlatformUser\UserSetting\UserSettingService $userSettingService
     */
    public function __construct(
        Visitor $outputVisitor,
        JsonOutputGenerator $outputGenerator,
        ContentTypeInfoListValueObjectVisitor $contentTypeInfoListValueObjectVisitor,
        ItemsListValueObjectVisitor $itemsListValueObjectVisitor,
        LocationService $locationService,
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        ContentTypeMappings $contentTypeMappings,
        UserSettingService $userSettingService
    )
    {
        $this->outputVisitor = $outputVisitor;
        $this->outputGenerator = $outputGenerator;
        $this->contentTypeInfoListValueObjectVisitor = $contentTypeInfoListValueObjectVisitor;
        $this->itemsListValueObjectVisitor = $itemsListValueObjectVisitor;
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->permissionResolver = $permissionResolver;
        $this->contentTypeMappings = $contentTypeMappings;
        $this->userSettingService = $userSettingService;
    }

    /**
     * Fetches data for Subitems module to populate it with preloaded data.
     *
     * Why are we using REST stuff here?
     *
     * This is not so elegant but to preload data in Subitems module
     * we are using the same data structure it would use while
     * fetching data from the REST.
     *
     * @param \eZ\Publish\Core\MVC\Symfony\View\ContentView $view
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function supply(array $searchHits)
    {
        /** @var \eZ\Publish\API\Repository\Values\ContentType\ContentType[] $contentTypes */
        $contentTypes = [];
        $itemsRows = [];

        /** @var SearchHit $searchHit */
        foreach ($searchHits as $searchHit) {
            /** @var Content $content */
            $content = $searchHit->valueObject;
            $contentType = $content->getContentType();

            if (!isset($contentTypes[$contentType->identifier])) {
                $contentTypes[$contentType->identifier] = $contentType;
            }

            $mainLocation = $this->locationService->loadLocation($content->getVersionInfo()->contentInfo->mainLocationId);
            $itemsRows[] = $this->createItemsRow($mainLocation, $contentType);
        }

        $itemsList = new ItemsList($itemsRows);
        $contentTypeInfoList = new ContentTypeInfoList($contentTypes, '');

        $itemsListJson = $this->visitSubitemsList($itemsList);
        $contentTypeInfoListJson = $this->visitContentTypeInfoList($contentTypeInfoList);

        return [
            'items' => $itemsListJson,
            'content_type_info_list' => $contentTypeInfoListJson,
        ];
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     *
     * @return \eZ\Publish\Core\REST\Server\Values\RestContent
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    private function createRestContent(
        Location $location,
        ContentType $contentType
    ): RestContent
    {
        return new RestContent(
            $location->getContentInfo(),
            $location,
            $location->getContent(),
            $contentType,
            []
        );
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     *
     * @return \eZ\Publish\Core\REST\Server\Values\RestLocation
     */
    private function createRestLocation(Location $location): RestLocation
    {
        return new RestLocation(
            $location,
            $this->locationService->getLocationChildCount($location)
        );
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     *
     * @return \EzSystems\EzPlatformAdminUi\UI\Module\Search\Values\ItemsRow
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    private function createItemsRow(
        Location $location,
        ContentType $contentType
    ): ItemsRow
    {
        $restLocation = $this->createRestLocation($location);
        $restContent = $this->createRestContent($location, $contentType);

        return new ItemsRow($restLocation, $restContent);
    }

    /**
     * @param \EzSystems\EzPlatformAdminUi\UI\Module\Search\Values\ItemsList $itemsList
     *
     * @return string
     */
    private function visitSubitemsList(ItemsList $itemsList): string
    {
        $this->outputGenerator->reset();
        $this->outputGenerator->startDocument($itemsList);
        $this->itemsListValueObjectVisitor->visit($this->outputVisitor, $this->outputGenerator, $itemsList);

        return $this->outputGenerator->endDocument($itemsList);
    }

    /**
     * @param \eZ\Publish\Core\REST\Server\Values\ContentTypeInfoList $contentTypeInfoList
     *
     * @return string
     */
    private function visitContentTypeInfoList(ContentTypeInfoList $contentTypeInfoList): string
    {
        $this->outputGenerator->reset();
        $this->outputGenerator->startDocument($contentTypeInfoList);
        $this->contentTypeInfoListValueObjectVisitor->visit($this->outputVisitor, $this->outputGenerator, $contentTypeInfoList);

        return $this->outputGenerator->endDocument($contentTypeInfoList);
    }
}
