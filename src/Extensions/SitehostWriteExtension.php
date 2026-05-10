<?php

declare(strict_types=1);

namespace Sunnysideup\SitehostCachePurge\Extensions;

use SilverStripe\Core\Extension;
use Sunnysideup\SitehostCachePurge\Api\SitehostPurgeCache;

/**
 * SiteHost API Client
 *
 * A minimal PHP class for interacting with the SiteHost API v1.5.
 *
 * @see https://docs.sitehost.nz/api/v1.5/
 */
class SitehostWriteExtension extends Extension
{
    public function onAfterWrite()
    {
        SitehostPurgeCache::create()->purgeCache();
    }


}
