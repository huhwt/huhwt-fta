/**
 * HuH Extensions for webtrees - FamilyTreeAssistant
 * Extensions for webtrees admin actions
 * Check and display unrelated individuals in the database - UIC - Unconnected(IndividualsCheck)
 *
 * Copyright (C) 2026 huhwt. All rights reserved.
 *
 * webtrees: online genealogy / web based family history software
 * Copyright (C) 2025 webtrees development team.
 *
 * This is the client side of cart functions
 * 
 */

var s_wt                = window.webtrees;                              // grep the webtrees js standard object

let elemUIChead = document.getElementById('uic-header');
const ajaxCCE = elemUIChead.dataset.urlCceadapter;

/**
 * Some areas of the tables will have to be provided with 'click' events ...
 * - any of them will be enabled to toggle showing the rows
 * - the explicitely by their names identified ones will also get functions to show higlighted values and/or to delete depending rows
 */
function UIC_prepPevents() {
    let belems = document.getElementsByClassName('dropdown-item p-1');      // we collect significant nodes ...
    for ( const belem of belems ) {                                     // ... and grep for each:
         belem.addEventListener( 'click', event => {
            let elemev = event.target;
            let gid    = elemev.getAttribute('gid');
            let a_key  = elemev.getAttribute('action-key');
            let ghElem = elemev.parentElement.parentElement.parentElement;  // -> the superior group headline
            let ulElem = ghElem.nextElementSibling;                         // -> the list containing the xrefs
            let xrefs  = [];
            for (const liElem of ulElem.children) {
                let xref = liElem.getAttribute('pid');
                xrefs.push(xref);
            }
            let _ajaxCCE = ajaxCCE + '&action=UIC_G-' + encodeURIComponent(gid) + '&action-key=' + a_key;
            CCEadapter(xrefs, elemev, _ajaxCCE);
        });
    }
}

function CCEadapter(XREF_ar, actElem, _ajaxCCE) {
    if (XREF_ar.length > 0) {
        jQuery.ajax({
            method: 'POST',
            url: _ajaxCCE,
            dataType: 'json',
            data: 'xrefs=' + XREF_ar.join(';'),
            success: function (ret) {
                var _ret = ret;
                let cElem = actElem.parentElement.parentElement.parentElement; //.firstElementChild;
                updateCCEcount(_ret, cElem);
                return true;
                },
            complete: function () {
            },
            timeout: function () {
            }
        });
    }
    return false;
};