<?php
/*
 * HuH Extensions for webtrees - FamilyTreeAssistant
 *
 * Copyright (C) 2026 huhwt. All rights reserved.
 *
 * webtrees: online genealogy / web based family history software
 * Copyright (C) 2021-2026 webtrees development team.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; If not, see <https://www.gnu.org/licenses/>.
 */

namespace HuHwt\WebtreesMods\FamilyTreeAssistant\Http\RequestHandlers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Contracts\UserInterface;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Http\Exceptions\HttpAccessDeniedException;

use Fisharebest\Webtrees\Gedcom;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Location;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Note;
use Fisharebest\Webtrees\Repository;
use Fisharebest\Webtrees\Source;
use Fisharebest\Webtrees\Submitter;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Validator;

use Illuminate\Support\Collection;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Throwable;

/**
 * class FTA_ClippingsCartReq
 *
 * handles transfer to standard ClippingsCart as well as CCE - huhwt Clippings Cart Enhanced
 * 
 */
class FTA_ClippingsCartReq implements RequestHandlerInterface
{

    private const TYPES_OF_RECORDS = [
        'Individual' => Individual::class,
        'Family'     => Family::class,
        'Media'      => Media::class,
        'Location'   => Location::class,
        'Note'       => Note::class,
        'Repository' => Repository::class,
        'Source'     => Source::class,
        'Submitter'  => Submitter::class,
    ];

    private $user;

    private $cartAction;

    private bool $CCE_OK;

    private bool $do_media;

    private bool $do_links;

    public function __construct(UserInterface $user)
    {
        $this->user     = $user;
        $this->do_media = false;
        $this->do_links = false;
    }
    public function handle( ServerRequestInterface $request ): ResponseInterface
    {
        // default attributes
        $tree           = Validator::attributes($request)->tree();
        $user           = Validator::attributes($request)->user();
        // test if CCE is installed and active and accessible in user context
        $this->CCE_OK   = $this->test_CCE_($tree, $user);

        // with media?
        $this->do_media = (Validator::queryParams($request)->string('doMedia', 'no') == 'yes');
        // with other links?
        $this->do_links = (Validator::queryParams($request)->string('doLinks', 'no') == 'yes');

        $action_key     = '';
        if ($this->CCE_OK) {
            // when to use CCE it's mandantory to have a significant marker for the origin of transferred xrefs
            $action         = Validator::queryParams($request)->string('action');
            if ($action) {
                $action_key     = Validator::queryParams($request)->string('action-key');
                if ($action_key > '') {
                    $action     = $action . '~' . $action_key;
                }
                $this->put_CartActs( $tree, $action);
            } else {
                $ts_request = $_SERVER['REQUEST_TIME'];
                $new_action = '_Other_CC_' . date('Y-m-d_H-i-s', $ts_request);
                $new_actKey = 'SHOW~' . $new_action;
                $this->put_CartActs( $tree, $new_actKey);
            }
        } 

        // the XREFs
        $xrefs = Validator::parsedBody($request)->string('xrefs', '');

        if ($xrefs > '') {
            $XREFs = explode(';', $xrefs);
        } else {
            $XREFs = [];
        }

        // eventually we'll have to rebuild the calling url ...
        // index.php?route= /tree/{tree}/individual/{xref}/{name}
        $do_redirect    = (Validator::queryParams($request)->string('do_redirect', 'no') == 'yes');
        if ( $do_redirect ) {
            $called_by      = Session::pull('FTA_CCR_caller');
            if ( $called_by ) {
                $url            = Validator::attributes($request)->string('base_url');
                $redUri         = $url . '/' . 'index.php?route=' . $called_by;
            }
        }

        $xrefsCold      = $this->count_CartTreeXrefs($tree);                // Count of xrefs actual in stock


        if ($action_key == '') {
                foreach ( $XREFs as $xref) {
                    $this->put_Cart($tree, $xref);            
                }
        } else {
            $this->FTAccr_add($tree, $action_key, $XREFs);
        }

        $Cinfo_struct   = $this->count_CartTreeXrefsReport($tree, $xrefsCold);

        if ( $do_redirect ) {
            $Cinfo          = json_decode($Cinfo_struct);
            $Cinfo_message  = $Cinfo[2] . ' <---> ' . $Cinfo[3];
            $Cinfo_message  = I18N::translate('Clippings cart') . ' : ' . $Cinfo_message;
            FlashMessages::addMessage($Cinfo_message);
            // we want a redirect to the calling view - a reload will then be performed
            return redirect($redUri);
        } else {
            // we have an ajax call - only the stats shall be shown
            return response( $Cinfo_struct );
        }
    }

    private function FTAccr_add(Tree $tree, string $action_key, array $XREFs): void
    {
        $individuals = $this->make_GedcomRecords($tree, $XREFs);

        foreach ( $individuals as $individual) {
            switch ($action_key) {
                case 'wp':
                    $this->toCartParents($individual);
                    break;
                case 'ws':
                    foreach ($individual->spouseFamilies() as $family) {
                        $this->addFamilyToCart($family);
                    }
                    break;
                case 'wc':
                    foreach ($individual->spouseFamilies() as $family) {
                        $this->addFamilyAndChildrenToCart($family);
                    }
                    break;
                case 'wa':
                    foreach($individuals  as $individual) {
                        $this->toCartParents($individual);
                        foreach ($individual->spouseFamilies() as $family) {
                            $this->addFamilyAndChildrenToCart($family);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * @param Tree              $tree
     */
    private function count_CartTreeXrefs(Tree $tree) : int
    {
        $S_cart = Session::get('cart', []);
        $xrefs  = $S_cart[$tree->name()] ?? [];
        return count($xrefs);
    }

    /**
     * @param Tree              $tree
     * @param int               $xrefsCold
     */
    private function count_CartTreeXrefsReport(Tree $tree, int $xrefsCold) : string
    {
        $SinfoCstock = $this->count_CartTreeXrefs($tree);                // Count of xrefs actual in stock - updated
        $SinfoCadded = $SinfoCstock - $xrefsCold;
        $Sinfo = [];
        $Sinfo[] = $SinfoCstock;
        $Sinfo[] = $SinfoCadded;
        $Sinfo[] = I18N::translate('Total number of entries: %s', (string) $SinfoCstock);
        $Sinfo[] = I18N::translate('of which new entries: %s', (string) $SinfoCadded);
        $SinfoJson = json_encode($Sinfo);
        return $SinfoJson;
    }


    /**
     * @param Family $family
     */
    public function addFamilyToCart(Family $family): void
    {
        $tree = $family->tree();
        $xref = $family->xref();

        $do_cart = $this->put_Cart($tree, $xref);
        if ($do_cart) {
            foreach ($family->spouses() as $spouse) {
                $this->addIndividualToCart($spouse);
            }
            if ($this->do_media) {
                $this->addMediaLinksToCart($family);
            }
            if ($this->do_links) {
                $this->addLocationLinksToCart($family);
                $this->addNoteLinksToCart($family);
                $this->addSourceLinksToCart($family);
                $this->addSubmitterLinksToCart($family);
            }
        }
    }

    /**
     * @param Individual $individual
     */
    public function addIndividualToCart(Individual $individual): void
    {
        $tree = $individual->tree();
        $xref = $individual->xref();

        $do_cart = $this->put_Cart($tree, $xref);
        if ($do_cart) {
            if ($this->do_media) {
                $this->addMediaLinksToCart($individual);
            }
            if ($this->do_links) {
                $this->addLocationLinksToCart($individual);
                $this->addNoteLinksToCart($individual);
                $this->addSourceLinksToCart($individual);
            }
        }
    }

    /**
     * @param Individual $individual
     */
    public function toCartParents(Individual $individual): void
    {
        foreach ($individual->childFamilies() as $family) {
            $this->addFamilyToCart($family);
        }
    }
    /**
     * Recursive function to traverse the tree and add the ancestors
     *
     * @param Individual $individual
     * @param int $level
     */
    public function addAncestorsToCart(Individual $individual, int $level = PHP_INT_MAX): void
    {
        $this->addIndividualToCart($individual);

        foreach ($individual->childFamilies() as $family) {
            $this->addFamilyToCart($family);

            foreach ($family->spouses() as $parent) {
                if ($level > 1) {
                    $this->addAncestorsToCart($parent, $level - 1);
                }
            }
        }
    }
    /**
     * @param Family $family
     */
    public function addFamilyAndChildrenToCart(Family $family): void
    {
        $this->addFamilyToCart($family);

        foreach ($family->children() as $child) {
            $this->addIndividualToCart($child);
        }
    }


    /**
     * @param Location $location
     */
    public function addLocationToCart(Location $location): void
    {
        $tree = $location->tree();
        $xref = $location->xref();

        $do_cart = $this->put_Cart($tree, $xref);
        if ($do_cart) {
            if ($this->do_media) {
                $this->addMediaLinksToCart($location);
            }
            if ($this->do_links) {
                $this->addLocationLinksToCart($location);
                $this->addNoteLinksToCart($location);
                $this->addSourceLinksToCart($location);
            }
        }
    }

    /**
     * @param Media $media
     */
    public function addMediaToCart(Media $media): void
    {
        $tree = $media->tree();
        $xref = $media->xref();

        $do_cart = $this->put_Cart($tree, $xref);
        if ($do_cart) {
            $this->addNoteLinksToCart($media);
        }
    }

    /**
     * @param Note $note
     */
    public function addNoteToCart(Note $note): void
    {
        $tree = $note->tree();
        $xref = $note->xref();

        $do_cart = $this->put_Cart($tree, $xref);
    }

    /**
     * @param Repository $repository
     */
    public function addRepositoryToCart(Repository $repository): void
    {
        $tree = $repository->tree();
        $xref = $repository->xref();

        $do_cart = $this->put_Cart($tree, $xref);
        if ($do_cart) {
            $this->addNoteLinksToCart($repository);
        }
    }

    /**
     * @param Source $source
     */
    public function addSourceToCart(Source $source): void
    {
        $tree = $source->tree();
        $xref = $source->xref();

        $do_cart = $this->put_Cart($tree, $xref);
        if ($do_cart) {
            $this->addNoteLinksToCart($source);
            $this->addRepositoryLinksToCart($source);
        }
    }

    /**
     * @param GedcomRecord $record
     */
    public function addLocationLinksToCart(GedcomRecord $record): void
    {
        preg_match_all('/\n\d _LOC @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);

        foreach ($matches[1] as $xref) {
            $location = Registry::locationFactory()->make($xref, $record->tree());

            if ($location instanceof Location && $location->canShow()) {
                $this->addLocationToCart($location);
            }
        }
    }

    /**
     * @param GedcomRecord $record
     */
    public function addMediaLinksToCart(GedcomRecord $record): void
    {
        preg_match_all('/\n\d OBJE @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);

        foreach ($matches[1] as $xref) {
            $media = Registry::mediaFactory()->make($xref, $record->tree());

            if ($media instanceof Media && $media->canShow()) {
                $this->addMediaToCart($media);
            }
        }
    }

    /**
     * @param GedcomRecord $record
     */
    public function addNoteLinksToCart(GedcomRecord $record): void
    {
        preg_match_all('/\n\d NOTE @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);

        foreach ($matches[1] as $xref) {
            $note = Registry::noteFactory()->make($xref, $record->tree());

            if ($note instanceof Note && $note->canShow()) {
                $this->addNoteToCart($note);
            }
        }
    }

    /**
     * @param GedcomRecord $record
     */
    public function addSourceLinksToCart(GedcomRecord $record): void
    {
        preg_match_all('/\n\d SOUR @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);

        foreach ($matches[1] as $xref) {
            $source = Registry::sourceFactory()->make($xref, $record->tree());

            if ($source instanceof Source && $source->canShow()) {
                $this->addSourceToCart($source);
            }
        }
    }

    /**
     * @param GedcomRecord $record
     */
    public function addRepositoryLinksToCart(GedcomRecord $record): void
    {
        preg_match_all('/\n\d REPO @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);      // Fix #4986

        foreach ($matches[1] as $xref) {
            $repository = Registry::repositoryFactory()->make($xref, $record->tree());

            if ($repository instanceof Repository && $repository->canShow()) {
                $this->addRepositoryToCart($repository);
            }
        }
    }

    /**
     * @param Submitter $submitter
     */
    public function addSubmitterToCart(Submitter $submitter): void
    {
        $tree = $submitter->tree();
        $xref = $submitter->xref();

        $do_cart = $this->put_Cart($tree, $xref);
        if ($do_cart) {
            $this->addNoteLinksToCart($submitter);
        }
    }

    /**
     * @param GedcomRecord $record
     */
    public function addSubmitterLinksToCart(GedcomRecord $record): void
    {
        preg_match_all('/\n\d SUBM @(' . Gedcom::REGEX_XREF . ')@/', $record->gedcom(), $matches);

        foreach ($matches[1] as $xref) {
            $submitter = Registry::submitterFactory()->make($xref, $record->tree());

            if ($submitter instanceof Submitter && $submitter->canShow()) {
                $this->addSubmitterToCart($submitter);
            }
        }
    }

    /**
     * get the webtrees entities corresponding to xref-ids
     *
     * @param Tree            $tree
     * @param array<string>   $XREFs
     * 
     * @return array
     */
    public function make_GedcomRecords(Tree $tree, array $XREFs): array
    {
        $records = array_map(static function (string $xref) use ($tree): ?GedcomRecord {
            return Registry::gedcomRecordFactory()->make($xref, $tree);
        }, $XREFs);


        return $records;
    }

    /**
     * @param Tree $tree
     * @param string $xref
     * 
     * @return bool
     */

    public function put_Cart(Tree $tree, string $xref): bool
    {
        $cart = Session::get('cart');
        $cart = is_array($cart) ? $cart : [];

        $_tree = $tree->name();

        if ($this->CCE_OK) {                                    // CCE installed and accessible - build the structure
            if (($cart[$_tree][$xref] ?? '_NIX_') === '_NIX_') {
                $cartAct = $this->cartAction;
                $cart[$_tree][$xref] = $cartAct;
                Session::put('cart', $cart);
                return true;
            } else {
                $cartAct = $cart[$_tree][$xref];
                if (!str_contains($cartAct, $this->cartAction)) {
                    $cartAct = $cartAct . ';' . $this->cartAction;
                    $cart[$_tree][$xref] = $cartAct;
                    Session::put('cart', $cart);
                } else {
                    $cart[$_tree][$xref] = $this->cartAction;
                    Session::put('cart', $cart);
                }
                return false;
            }
        } else {                                                // put it in the default cart - simply true
            if (($cart[$_tree][$xref] ?? false) === false) {
                $cart[$_tree][$xref] = true;
                Session::put('cart', $cart);
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param Tree      $tree
     * @param string    $action
     * 
     * @return bool
     */

    private function put_CartActs(Tree $tree, string $action) : string
    {
        $S_cartActs = Session::get('cartActs', []);
        $retval = $action;

        $this->cartAction = $action;

        if (($S_cartActs[$tree->name()][$action] ?? false) === false) {
            $S_cartActs[$tree->name()][$action] = true;
            Session::put('cartActs', $S_cartActs);
        }
        return $retval;
    }
    /**
     * Test if _huhwt-cce_ is installed and accessible
     *
     * @param Tree              $tree
     * @param UserInterface     $user
     *
     * @return bool
     */
    public static function test_CCE_ (Tree $tree, UserInterface $user) : bool
    {
        $retval = false;
        $module_service = new ModuleService();
        $CCE_module = $module_service->findByName('_huhwt-cce_');
        if ($CCE_module !== null ) {
            $retval =  $CCE_module->accessLevel($tree, 'Fisharebest\Webtrees\Module\ModuleMenuInterface') >= Auth::accessLevel($tree, $user);
        }
        return $retval;
    }

}