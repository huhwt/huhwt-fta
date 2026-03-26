<?php

/**
 * HuH Extensions for webtrees - FamilyTreeAssistant
 * Extensions for webtrees to check and display duplicate Individuals in the database.
 * Copyright (C) 2026 EW.Heinrich
 * 
 * Functions used by more than 1 module
 */

declare(strict_types=1);

namespace HuHwt\WebtreesMods\FamilyTreeAssistant\Traits;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

trait FTAhelpers {

    /**
    * EW.H - MOD ... we need root of extension for explicitly referencing styles and scripts in generated HTML
    *
    * Get root of Module
    *       huhwt-mtv/          <- we don't know what to preset here to identify the location in page-hierarchy
    *       - Http/
    *         - RequestHandlers/
    *           - (thisFile)
    *       - resources/        <- here we want to point to later
    */
    private function modRoot(): string
    {
        $file_path = e(asset('snip/'));
        $file_path = str_replace("/public/snip/", "", $file_path) . "/modules_v4/huhwt-fta";
        return $file_path;
    }

    private function test_CCE_ () : bool
    {
        $retval = false;
        $module_service = new ModuleService();
        $CCE_activ = $module_service->findByName('_huhwt-cce_');
        if ($CCE_activ !== null ) {
            $retval = true;
        }
        return $retval;
    }


}