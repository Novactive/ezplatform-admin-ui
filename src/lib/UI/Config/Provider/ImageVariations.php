<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAdminUi\UI\Config\Provider;

class ImageVariations extends Value
{
    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function getConfig()
    {
        return array_filter($this->config, function ($variationConfig) {
            return $variationConfig['expose'] ?? true;
        }, ARRAY_FILTER_USE_BOTH);
    }
}
