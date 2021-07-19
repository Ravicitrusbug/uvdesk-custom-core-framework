<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Homepage\Items;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\HomepageSectionItem;
use Webkul\UVDesk\CoreFrameworkBundle\UIComponents\Dashboard\Homepage\Sections\Users;

class Companies extends HomepageSectionItem
{
    const SVG = <<<SVG
<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" width="60px" height="60px" viewBox="0 0 65.81 93.18"><defs><style>.cls-1{fill:#fff;}</style></defs><path class="cls-1" d="M117,129v-1.75H112.1V64.07h2.29V60.41h-.13v0l3.27-.18L94.2,40H78.38L55.05,60.2l3.27.18v0h-.13v3.66h2.29v63.21H55.57V129H53.39v4.14H119.2V129Zm-18.79-1.75H74.36V96.2a8,8,0,0,1,8-8h7.84a8,8,0,0,1,8,8Z" transform="translate(-53.39 -39.99)"/></svg>
SVG;

    public static function getIcon(): string
    {
        return self::SVG;
    }

    public static function getTitle(): string
    {
        return "Sites";
    }

    public static function getRouteName(): string
    {
        return 'helpdesk_member_support_company_collection';
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
