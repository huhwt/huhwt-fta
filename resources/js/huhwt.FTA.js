/**
 * HuH Extensions for webtrees - FamilyTreeAssistant
 * Extensions for webtrees admin actions
 *
 * Copyright (C) 2026 huhwt. All rights reserved.
 *
 * webtrees: online genealogy / web based family history software
 * Copyright (C) 2025 webtrees development team.
 *
 * common actions
 * 
 */

function updateCCEcount(XREFcnt, button) {
    let pto = typeof XREFcnt;
    switch (pto) {
        case 'object':
            showCountPop(XREFcnt, button);
            break;
        case 'number':
        default:
            break;
    }
}
function showCountPop(XREFcnt, elem_main) {
    let vCntS = XREFcnt[0];
    let vCntN = XREFcnt[1];
    let vCntStxt = XREFcnt[2];
    let vCntNtxt = XREFcnt[3];
    let elem_pop = document.getElementById('CCEpopUp');
    if (!elem_pop) {
        // let elem_main = document.getElementsByClassName('CCE_Menue')[0];
        let elem_dpop = document.createElement('div');
        elem_dpop.id = 'CCEpopUp';
        elem_dpop.classList = 'CCEpopup hidden';

        let elem_dlineS = document.createElement('div');
        elem_dlineS.className = 'pop-line lineS';
        elem_dpop.appendChild(elem_dlineS);
        let elem_dlineN = document.createElement('div');
        elem_dlineN.className = 'pop-line lineN';
        elem_dpop.appendChild(elem_dlineN);

        elem_main.appendChild(elem_dpop);

        elem_pop = document.getElementById('CCEpopUp');
    }
    let elem_dlineS = elem_pop.firstElementChild;
    elem_dlineS.textContent = vCntStxt;
    let elem_dlineN = elem_pop.lastElementChild;
    elem_dlineN.textContent = vCntNtxt;
    if (elem_pop.classList.contains('hidden'))
        elem_pop.classList.remove('hidden');
    elem_pop.style.opacity = 1;
    setTimeout(fadeOut,2400);
}
function fadeOut() {
    let elem_pop = document.getElementById('CCEpopUp');
    var op = 1;  // initial opacity
    var timer = setInterval(function () {
        if (op <= 0.1){
            clearInterval(timer);
            elem_pop.classList.add('hidden');
            elem_par = elem_pop.parentNode;
            elem_par.removeChild(elem_pop);
        }
        elem_pop.style.opacity = op;
        elem_pop.style.filter = 'alpha(opacity=' + op * 100 + ")";
        op -= op * 0.2;
    }, 100);
}


/**
 * @param {string} name
 * @param {string} value
 * @param {number} days
 */
function createCookie (name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + value + '; expires=' + date.toGMTString() + '; path=/';
    } else {
        document.cookie = name + '=' + value + '; path=/';
    }
}
/**
 * @param   {string} name
 * @returns {string|null}
 */
function readCookie (name) {
    var name_equals = name + '=';
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1, c.length);
        }
        if (c.indexOf(name_equals) === 0) {
            return c.substring(name_equals.length, c.length);
        }
    }
    return null;
}