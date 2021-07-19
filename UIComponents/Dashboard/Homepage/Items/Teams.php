<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Homepage\Items;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\HomepageSectionItem;
use Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Homepage\Sections\Users;

class Teams extends HomepageSectionItem
{
    const SVG = <<<SVG
<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 98.95 113.78"><defs><style>.cls-1{fill:none;stroke-width:2.61px;}.cls-1,.cls-2{stroke:#fff;stroke-miterlimit:10;}.cls-2{fill:#fff;stroke-width:1.2px;}</style></defs><polyline class="cls-1" points="91.1 36.76 83.05 40.61 58.16 52.5"/><line class="cls-1" x1="40.6" y1="60.89" x2="15.72" y2="72.78"/><path class="cls-2" d="M52.84,101.59a8,8,0,1,1-.1-.2C52.77,101.45,52.81,101.52,52.84,101.59Z" transform="translate(-37.02 -28.61)"/><path class="cls-2" d="M130.83,73.18a8,8,0,0,1-10.67-3.77l-.09-.19a8,8,0,1,1,10.76,4Z" transform="translate(-37.02 -28.61)"/><path class="cls-2" d="M129.14,122.26A8,8,0,1,1,128.3,111,8,8,0,0,1,129.14,122.26Z" transform="translate(-37.02 -28.61)"/><polyline class="cls-1" points="49.7 100.13 49.7 97.2 49.7 66.62"/><line class="cls-1" x1="49.7" y1="47.15" x2="49.7" y2="16.59"/><path class="cls-2" d="M94.5,37.2a8,8,0,1,1-8-8A8,8,0,0,1,94.5,37.2Z" transform="translate(-37.02 -28.61)"/><path class="cls-2" d="M94.5,133.8a8,8,0,1,1-8-8h.22A8,8,0,0,1,94.5,133.8Z" transform="translate(-37.02 -28.61)"/><polyline class="cls-1" points="16.59 28.82 18.81 30.73 41.96 50.7"/><line class="cls-1" x1="79.86" y1="83.37" x2="56.71" y2="63.41"/><path class="cls-2" d="M93.73,92a10.7,10.7,0,0,1-.9.88,9.74,9.74,0,0,1-15.21-3.4A9.75,9.75,0,0,1,79,79.31a9.06,9.06,0,0,1,1.19-1.21A9.73,9.73,0,0,1,93.9,79.17a9.17,9.17,0,0,1,1.28,1.94A9.7,9.7,0,0,1,93.73,92Z" transform="translate(-37.02 -28.61)"/><path class="cls-2" d="M56,59.18l-.15.16a7.78,7.78,0,1,1,.15-.16Z" transform="translate(-37.02 -28.61)"/></svg>
SVG;

    public static function getIcon(): string
    {
        return self::SVG;
    }

    public static function getTitle(): string
    {
        return "Teams";
    }

    public static function getRouteName(): string
    {
        return 'helpdesk_member_support_team_collection';
    }

    public static function getRoles(): array
    {
        return ['ROLE_AGENT_MANAGE_SUB_GROUP'];
    }

    public static function getSectionReferenceId(): string
    {
        return Users::class;
    }
}
