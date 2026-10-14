/* ============================================================
   jalali-datepicker.js — انتخابگر تاریخ شمسی (بدون وابستگی)
   الگوریتم jalaali (همان مرجع PHP) — خروجی: YYYY/MM/DD
   استفاده: <input class="form-control jalali-date" name="due_date">
   ============================================================ */
(function () {
  'use strict';

  var BREAKS = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
  var MONTHS = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  var WDAYS = ['ش','ی','د','س','چ','پ','ج'];
  var FA = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];

  function fa(v) { return String(v).replace(/\d/g, function (d) { return FA[+d]; }); }
  function div(a, b) { return ~~(a / b); }

  function jalCal(jy) {
    var bl = BREAKS.length, gy = jy + 621, leapJ = -14, jp = BREAKS[0], jm, jump = 0, i;
    for (i = 1; i < bl; i++) {
      jm = BREAKS[i]; jump = jm - jp;
      if (jy < jm) break;
      leapJ += div(jump, 33) * 8 + div(jump % 33, 4);
      jp = jm;
    }
    var n = jy - jp;
    leapJ += div(n, 33) * 8 + div((n % 33) + 3, 4);
    if (jump % 33 === 4 && jump - n === 4) leapJ++;
    var leapG = div(gy, 4) - div((div(gy, 100) + 1) * 3, 4) - 150;
    var march = 20 + leapJ - leapG;
    if (jump - n < 6) n = n - jump + div(jump + 4, 33) * 33;
    var leap = ((n + 1) % 33 - 1) % 4;
    if (leap === -1) leap = 4;
    return { leap: leap, gy: gy, march: march };
  }

  function g2d(gy, gm, gd) {
    var d = div((gy + div(gm - 8, 6) + 100100) * 1461, 4) + div(153 * ((gm + 9) % 12) + 2, 5) + gd - 34840408;
    return d - div(div(gy + 100100 + div(gm - 8, 6), 100) * 3, 4) + 752;
  }
  function d2g(jdn) {
    var j = 4 * jdn + 139361631;
    j += div(div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
    var i = div(j % 1461, 4) * 5 + 308;
    return [div(j, 1461) - 100100 + div(8 - div(div(i, 153) % 12 + 1 - 8 + 12, 6), 6) * 0 + 0, (div(i, 153) % 12) + 1, div(i % 153, 5) + 1];
  }
  // نسخهٔ دقیق d2g (مطابق الگوریتم مرجع)
  function d2gExact(jdn) {
    var j = 4 * jdn + 139361631;
    j = j + div(div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
    var i = div(j % 1461, 4) * 5 + 308;
    var gd = div(i % 153, 5) + 1;
    var gm = (div(i, 153) % 12) + 1;
    var gy = div(j, 1461) - 100100 + div(8 - gm, 6);
    return [gy, gm, gd];
  }
  function j2d(jy, jm, jd) {
    var r = jalCal(jy);
    return g2d(r.gy, 3, r.march) + (jm - 1) * 31 - div(jm, 7) * (jm - 7) + jd - 1;
  }
  function d2j(jdn) {
    var gy = d2gExact(jdn)[0];
    var jy = gy - 621;
    var r = jalCal(jy);
    var k = jdn - g2d(r.gy, 3, r.march);
    if (k >= 0) {
      if (k <= 185) return [jy, 1 + div(k, 31), k % 31 + 1];
      k -= 186;
    } else {
      jy--; k += 179;
      if (r.leap === 1) k++;
    }
    return [jy, 7 + div(k, 30), k % 30 + 1];
  }
  function todayJalali() {
    var now = new Date();
    return d2j(g2d(now.getFullYear(), now.getMonth() + 1, now.getDate()));
  }
  function monthLength(jy, jm) {
    if (jm <= 6) return 31;
    if (jm <= 11) return 30;
    return jalCal(jy).leap === 0 ? 30 : 29;
  }
  // شنبه=۰ … از تاریخ شمسی: jdn → weekday
  function weekdayOf(jy, jm, jd) {
    var d = new Date(d2gExact(j2d(jy, jm, jd)).join('-'));
    return (d.getDay() + 1) % 7; // getDay: Sun=0 → Sat=6 ; می‌خواهیم Sat=0
  }

  var openPop = null;
  function closePop() {
    if (openPop) { openPop.remove(); openPop = null; }
  }
  document.addEventListener('click', function (e) {
    if (openPop && !openPop.contains(e.target) && !e.target.classList.contains('jalali-date')) closePop();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePop(); });

  function render(input, jy, jm) {
    closePop();
    var pop = document.createElement('div');
    pop.className = 'jdp-pop';
    var len = monthLength(jy, jm);
    var startWd = weekdayOf(jy, jm, 1);

    var head = '<div class="jdp-head">' +
      '<button type="button" class="jdp-nav" data-nav="1" aria-label="ماه بعد">‹</button>' +
      '<b>' + MONTHS[jm - 1] + ' ' + fa(jy) + '</b>' +
      '<button type="button" class="jdp-nav" data-nav="-1" aria-label="ماه قبل">›</button>' +
      '</div>';
    var wd = '<div class="jdp-grid jdp-wd">' + WDAYS.map(function (w) { return '<span>' + w + '</span>'; }).join('') + '</div>';
    var cells = '';
    for (var i = 0; i < startWd; i++) cells += '<span></span>';
    var today = todayJalali();
    for (var d = 1; d <= len; d++) {
      var isToday = jy === today[0] && jm === today[1] && d === today[2];
      cells += '<button type="button" class="jdp-day' + (isToday ? ' jdp-today' : '') + '" data-day="' + d + '">' + fa(d) + '</button>';
    }
    var foot = '<div class="jdp-foot"><button type="button" class="jdp-today-btn">امروز</button><button type="button" class="jdp-clear">پاک‌کردن</button></div>';
    pop.innerHTML = head + wd + '<div class="jdp-grid">' + cells + '</div>' + foot;

    document.body.appendChild(pop);
    var r = input.getBoundingClientRect();
    pop.style.top = (window.scrollY + r.bottom + 6) + 'px';
    var left = window.scrollX + r.left;
    if (left + pop.offsetWidth > window.scrollX + document.documentElement.clientWidth - 8) {
      left = window.scrollX + document.documentElement.clientWidth - pop.offsetWidth - 8;
    }
    pop.style.left = Math.max(8, left) + 'px';
    openPop = pop;

    function setValue(y, m, dd) {
      input.value = dd === null ? '' : (y + '/' + String(m).padStart(2, '0') + '/' + String(dd).padStart(2, '0'));
      input.dispatchEvent(new Event('change'));
      closePop();
    }
    pop.querySelectorAll('.jdp-day').forEach(function (b) {
      b.addEventListener('click', function () { setValue(jy, jm, +b.getAttribute('data-day')); });
    });
    pop.querySelectorAll('.jdp-nav').forEach(function (b) {
      b.addEventListener('click', function () {
        var step = +b.getAttribute('data-nav');
        var m = jm + step, y = jy;
        if (m > 12) { m = 1; y++; } else if (m < 1) { m = 12; y--; }
        render(input, y, m);
      });
    });
    pop.querySelector('.jdp-today-btn').addEventListener('click', function () {
      var t = todayJalali(); setValue(t[0], t[1], t[2]);
    });
    pop.querySelector('.jdp-clear').addEventListener('click', function () { setValue(jy, jm, null); });
  }

  function parseValue(v) {
    var m = String(v || '').replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); }).match(/^(\d{4})[\/\-.](\d{1,2})/);
    if (!m) return null;
    var y = +m[1], mo = +m[2];
    if (y < 1300 || y > 1500 || mo < 1 || mo > 12) return null;
    return [y, mo];
  }

  function bind(input) {
    input.setAttribute('inputmode', 'numeric');
    input.setAttribute('placeholder', input.getAttribute('placeholder') || '۱۴۰۵/۰۷/۲۲');
    input.setAttribute('autocomplete', 'off');
    input.addEventListener('focus', function () {
      var parsed = parseValue(input.value) || todayJalali().slice(0, 2);
      render(input, parsed[0], parsed[1]);
    });
    input.addEventListener('click', function () {
      if (!openPop) {
        var parsed = parseValue(input.value) || todayJalali().slice(0, 2);
        render(input, parsed[0], parsed[1]);
      }
    });
  }

  document.querySelectorAll('input.jalali-date').forEach(bind);
  window.jalaliDatepicker = { bind: bind };
})();
