(function () {
  'use strict';

  var cfg = JSON.parse(document.getElementById('cfg').textContent);
  var $ = function (id) { return document.getElementById(id); };

  var T = {
    hy: {
      ask: function (n) { return (n ? n + ', կ' : 'Կ') + 'գա՞ս ինձ հետ ժամադրության'; },
      yes: 'Այո, իհարկե', no: 'Ոչ',
      whatTitle: 'Ի՞նչ անենք միասին', whatSub: 'Կարող ես ընտրել մի քանիսը',
      acts: { dinner: 'Ընթրիք', cinema: 'Կինո', coffee: 'Սուրճ', walk: 'Զբոսանք', concert: 'Համերգ', surprise: 'Անակնկալ' },
      next: 'Շարունակել',
      whenTitle: 'Ե՞րբ հանդիպենք', whenSub: 'Ընտրիր օրը և ժամը', time: 'Ժամը',
      send: 'Ուղարկել պատասխանը', sending: 'Ուղարկվում է…',
      months: ['Հունվար', 'Փետրվար', 'Մարտ', 'Ապրիլ', 'Մայիս', 'Հունիս', 'Հուլիս', 'Օգոստոս', 'Սեպտեմբեր', 'Հոկտեմբեր', 'Նոյեմբեր', 'Դեկտեմբեր'],
      monthsGen: ['հունվարի', 'փետրվարի', 'մարտի', 'ապրիլի', 'մայիսի', 'հունիսի', 'հուլիսի', 'օգոստոսի', 'սեպտեմբերի', 'հոկտեմբերի', 'նոյեմբերի', 'դեկտեմբերի'],
      wd: ['Երկ', 'Երք', 'Չրք', 'Հնգ', 'Ուրբ', 'Շբթ', 'Կիր'],
      wdLong: ['Կիրակի', 'Երկուշաբթի', 'Երեքշաբթի', 'Չորեքշաբթի', 'Հինգշաբթի', 'Ուրբաթ', 'Շաբաթ'],
      fmt: function (wd, d, m) { return wd + ', ' + this.monthsGen[m] + ' ' + d; },
      prev: 'Նախորդ ամիս', nextMonth: 'Հաջորդ ամիս',
      doneTitle: 'Պայմանավորվեցինք', doneSub: 'Սպասում եմ անհամբեր',
      kicker: 'ՏՈՄՍ ԵՐԿՈՒՍԻ ՀԱՄԱՐ', ticket: 'Մեր ժամադրությունը', what: 'Ի՞նչ', when: 'Ե՞րբ',
      blockedErr: 'Այդ օրն այլևս ազատ չէ։ Ընտրիր ուրիշ օր։',
      sent: 'Պատասխանդ արդեն ուղարկված է', error: 'Չստացվեց ուղարկել։ Փորձիր նորից։'
    },
    ru: {
      ask: function (n) { return (n ? n + ', п' : 'П') + 'ойдёшь со мной на свидание?'; },
      yes: 'Да, конечно', no: 'Нет',
      whatTitle: 'Чем займёмся вместе?', whatSub: 'Можно выбрать несколько',
      acts: { dinner: 'Ужин', cinema: 'Кино', coffee: 'Кофе', walk: 'Прогулка', concert: 'Концерт', surprise: 'Сюрприз' },
      next: 'Продолжить',
      whenTitle: 'Когда встретимся?', whenSub: 'Выбери день и время', time: 'Время',
      send: 'Отправить ответ', sending: 'Отправляем…',
      months: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
      monthsGen: ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'],
      wd: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
      wdLong: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
      fmt: function (wd, d, m) { return wd + ', ' + d + ' ' + this.monthsGen[m]; },
      prev: 'Предыдущий месяц', nextMonth: 'Следующий месяц',
      doneTitle: 'Договорились', doneSub: 'Жду с нетерпением',
      kicker: 'БИЛЕТ НА ДВОИХ', ticket: 'Наше свидание', what: 'Что', when: 'Когда',
      blockedErr: 'Этот день уже занят. Выбери другой.',
      sent: 'Твой ответ уже отправлен', error: 'Не получилось отправить. Попробуй ещё раз.'
    },
    en: {
      ask: function (n) { return (n ? n + ', w' : 'W') + 'ill you go on a date with me?'; },
      yes: 'Yes, of course', no: 'No',
      whatTitle: 'What shall we do together?', whatSub: 'You can pick more than one',
      acts: { dinner: 'Dinner', cinema: 'Cinema', coffee: 'Coffee', walk: 'A walk', concert: 'Concert', surprise: 'Surprise' },
      next: 'Continue',
      whenTitle: 'When shall we meet?', whenSub: 'Pick a day and time', time: 'Time',
      send: 'Send my answer', sending: 'Sending…',
      months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
      wd: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      wdLong: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
      fmt: function (wd, d, m) { return wd + ', ' + this.months[m] + ' ' + d; },
      prev: 'Previous month', nextMonth: 'Next month',
      doneTitle: 'It’s a date', doneSub: 'I can’t wait',
      kicker: 'TICKET FOR TWO', ticket: 'Our date', what: 'What', when: 'When',
      blockedErr: 'That day is no longer free. Please pick another.',
      sent: 'Your answer has been sent', error: 'Couldn’t send. Please try again.'
    }
  };

  var ICONS = {
    dinner: '<path d="M6 3v6a3 3 0 0 0 6 0V3M9 3v18"/><path d="M17 3c-2 2-2.5 5-2.5 8H17v10"/>',
    cinema: '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M10 9.5v5l4.5-2.5z"/>',
    coffee: '<path d="M5 9h11v5a5 5 0 0 1-5 5h-1a5 5 0 0 1-5-5z"/><path d="M16 10h1.5a2.5 2.5 0 0 1 0 5H16"/><path d="M8 3v3M12 3v3"/>',
    walk: '<path d="M12 21s-6-5.2-6-10a6 6 0 0 1 12 0c0 4.8-6 10-6 10z"/><circle cx="12" cy="11" r="2"/>',
    concert: '<path d="M9 18V6l10-2v12"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="16.5" cy="16" r="2.5"/>',
    surprise: '<rect x="4" y="10" width="16" height="10" rx="1.5"/><path d="M3 7h18v3H3z"/><path d="M12 7v13"/><path d="M12 7c-1.5-3-5-3-5-1s3 1 5 1zm0 0c1.5-3 5-3 5-1s-3 1-5 1z"/>'
  };
  var ACT_KEYS = ['dinner', 'cinema', 'coffee', 'walk', 'concert', 'surprise'];
  var TIMES = ['12:00', '14:00', '16:00', '18:00', '19:00', '19:30', '20:00', '21:00'];

  var state = { lang: T[cfg.lang] ? cfg.lang : 'hy', acts: [], date: null, time: null };
  var L = T[state.lang];

  function api(payload) {
    payload.token = cfg.token;
    return fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      keepalive: true,
      body: JSON.stringify(payload)
    }).then(function (r) { return r.json(); });
  }

  function show(id) {
    var screens = document.querySelectorAll('.screen');
    for (var i = 0; i < screens.length; i++) { screens[i].classList.remove('active'); }
    $(id).classList.add('active');
    document.body.classList.toggle('is-done', id === 's-done');
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) { meta.setAttribute('content', id === 's-done' ? '#C8154F' : '#FFEEF1'); }
    window.scrollTo(0, 0);
  }

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function ymd(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }
  function prettyDate(s) {
    var p = s.split('-');
    var dt = new Date(+p[0], +p[1] - 1, +p[2]);
    return L.fmt(L.wdLong[dt.getDay()], dt.getDate(), dt.getMonth());
  }

  /* ---------- texts ---------- */
  function applyLang() {
    L = T[state.lang];
    document.documentElement.lang = state.lang;
    $('ask-title').textContent = L.ask(cfg.name);
    $('yes-label').textContent = L.yes;
    $('btn-no').textContent = L.no;
    $('what-title').textContent = L.whatTitle;
    $('what-sub').textContent = L.whatSub;
    $('btn-what-next').textContent = L.next;
    $('when-title').textContent = L.whenTitle;
    $('when-sub').textContent = L.whenSub;
    $('time-title').textContent = L.time;
    $('btn-send').textContent = L.send;
    $('cal-prev').setAttribute('aria-label', L.prev);
    $('cal-next').setAttribute('aria-label', L.nextMonth);
    $('done-title').textContent = L.doneTitle;
    $('done-sub').textContent = L.doneSub;
    $('t-kicker').textContent = L.kicker;
    $('t-title').textContent = L.ticket;
    $('t-what-k').textContent = L.what;
    $('t-when-k').textContent = L.when;
    $('t-time-k').textContent = L.time;
    $('sent-label').textContent = L.sent;
    renderActs();
    renderCal();
    renderTimes();
  }

  /* ---------- 1. language ---------- */
  var langBtns = document.querySelectorAll('[data-lang]');
  for (var i = 0; i < langBtns.length; i++) {
    langBtns[i].addEventListener('click', function () {
      state.lang = this.getAttribute('data-lang');
      applyLang();
      api({ action: 'lang', lang: state.lang }).catch(function () {});
      show('s-ask');
    });
  }

  /* ---------- 2. the question, with a "No" that cannot be pressed ---------- */
  var noBtn = $('btn-no'), yesBtn = $('btn-yes'), noSlot = $('no-slot');
  var tries = 0, MAX_TRIES = 5, locked = false, lastDodge = 0;

  function overlaps(x, y, w, h, r, gap) {
    return x < r.right + gap && x + w > r.left - gap && y < r.bottom + gap && y + h > r.top - gap;
  }

  function dodge(ev) {
    if (locked) { return; }
    if (ev && ev.cancelable) { ev.preventDefault(); }
    var now = Date.now();
    if (now - lastDodge < 250) { return; }
    lastDodge = now;
    tries++;
    if (tries >= MAX_TRIES) { land(); return; }

    var base = noSlot.getBoundingClientRect();
    var yes = yesBtn.getBoundingClientRect();
    var box = $('app').getBoundingClientRect();
    var w = base.width, h = base.height, m = 16;
    var minX = box.left + m, maxX = box.right - w - m;
    var minY = m, maxY = window.innerHeight - h - m;
    var px = ev && typeof ev.clientX === 'number' && (ev.clientX || ev.clientY) ? ev.clientX : base.left + w / 2;
    var py = ev && typeof ev.clientY === 'number' && (ev.clientX || ev.clientY) ? ev.clientY : base.top + h / 2;
    var x = minX, y = minY, k;
    for (k = 0; k < 40; k++) {
      x = minX + Math.random() * Math.max(0, maxX - minX);
      y = minY + Math.random() * Math.max(0, maxY - minY);
      var dx = x + w / 2 - px, dy = y + h / 2 - py;
      if (Math.sqrt(dx * dx + dy * dy) > 150 && !overlaps(x, y, w, h, yes, 12)) { break; }
    }
    noBtn.style.transform = 'translate(' + Math.round(x - base.left) + 'px,' + Math.round(y - base.top) + 'px)';
  }

  // The last attempt: "No" slides onto "Yes" and presses it.
  function land() {
    locked = true;
    var base = noSlot.getBoundingClientRect();
    var yes = yesBtn.getBoundingClientRect();
    var dx = yes.left + yes.width / 2 - (base.left + base.width / 2);
    var dy = yes.top + yes.height / 2 - (base.top + base.height / 2);
    noBtn.style.transform = 'translate(' + Math.round(dx) + 'px,' + Math.round(dy) + 'px) scale(0.6)';
    noBtn.classList.add('gone');
    noBtn.setAttribute('tabindex', '-1');
    setTimeout(function () { yesBtn.classList.add('pressed'); }, 380);
    setTimeout(sayYes, 900);
  }

  var saidYes = false;
  function sayYes() {
    if (saidYes) { return; }
    saidYes = true;
    locked = true;
    api({ action: 'yes', tries: tries }).catch(function () {});
    show('s-what');
  }

  noBtn.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') { dodge(e); } });
  noBtn.addEventListener('pointerdown', dodge);
  noBtn.addEventListener('touchstart', dodge, { passive: false });
  noBtn.addEventListener('click', dodge);
  yesBtn.addEventListener('click', sayYes);

  /* ---------- 3. what to do ---------- */
  function renderActs() {
    var box = $('acts');
    box.innerHTML = '';
    ACT_KEYS.forEach(function (key) {
      var on = state.acts.indexOf(key) !== -1;
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'act' + (on ? ' on' : '');
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
      b.innerHTML = '<svg class="act-icon" viewBox="0 0 24 24" aria-hidden="true">' + ICONS[key] + '</svg>' +
        '<span class="act-label"></span>' +
        '<span class="act-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>';
      b.querySelector('.act-label').textContent = L.acts[key];
      b.addEventListener('click', function () {
        var i = state.acts.indexOf(key);
        if (i === -1) { state.acts.push(key); } else { state.acts.splice(i, 1); }
        renderActs();
      });
      box.appendChild(b);
    });
    $('btn-what-next').disabled = state.acts.length === 0;
  }
  $('btn-what-next').addEventListener('click', function () { show('s-when'); });

  /* ---------- 4. date and time ---------- */
  var tp = cfg.today.split('-');
  var today = { y: +tp[0], m: +tp[1] - 1, d: +tp[2] };
  var view = { y: today.y, m: today.m };
  var MAX_AHEAD = 6;
  var blocked = cfg.blocked || [];

  function monthsFromToday() { return (view.y - today.y) * 12 + (view.m - today.m); }

  function renderCal() {
    $('cal-month').textContent = L.months[view.m] + ' ' + view.y;
    var wd = $('cal-wd');
    wd.innerHTML = '';
    L.wd.forEach(function (s) { var e = document.createElement('div'); e.textContent = s; wd.appendChild(e); });

    var grid = $('cal-grid');
    grid.innerHTML = '';
    var first = (new Date(view.y, view.m, 1).getDay() + 6) % 7; // Monday first
    var count = new Date(view.y, view.m + 1, 0).getDate();
    var todayStr = ymd(today.y, today.m, today.d);
    for (var d = 1; d <= count; d++) {
      (function (d) {
        var s = ymd(view.y, view.m, d);
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'day' + (s === state.date ? ' on' : '') + (s === todayStr ? ' today' : '');
        b.textContent = d;
        if (d === 1) { b.style.gridColumnStart = first + 1; }
        if (s < todayStr) { b.disabled = true; }
        if (blocked.indexOf(s) !== -1) { b.disabled = true; b.classList.add('blocked'); }
        b.setAttribute('aria-pressed', s === state.date ? 'true' : 'false');
        b.addEventListener('click', function () { state.date = s; renderCal(); syncSend(); });
        grid.appendChild(b);
      })(d);
    }
    var off = monthsFromToday();
    $('cal-prev').disabled = off <= 0;
    $('cal-next').disabled = off >= MAX_AHEAD;
  }

  $('cal-prev').addEventListener('click', function () {
    if (monthsFromToday() <= 0) { return; }
    view.m--; if (view.m < 0) { view.m = 11; view.y--; }
    renderCal();
  });
  $('cal-next').addEventListener('click', function () {
    if (monthsFromToday() >= MAX_AHEAD) { return; }
    view.m++; if (view.m > 11) { view.m = 0; view.y++; }
    renderCal();
  });

  function renderTimes() {
    var box = $('times');
    box.innerHTML = '';
    TIMES.forEach(function (t) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'time' + (t === state.time ? ' on' : '');
      b.textContent = t;
      b.setAttribute('aria-pressed', t === state.time ? 'true' : 'false');
      b.addEventListener('click', function () { state.time = t; renderTimes(); syncSend(); });
      box.appendChild(b);
    });
  }

  function syncSend() { $('btn-send').disabled = !(state.date && state.time); }

  $('btn-send').addEventListener('click', function () {
    var btn = this, err = $('send-error');
    var answer = { activities: state.acts.slice(), date: state.date, time: state.time };
    btn.disabled = true;
    err.hidden = true;
    // Show the ticket straight away; the answer is saved in the background.
    setSent(false);
    showTicket(answer);
    api({ action: 'answer', activities: answer.activities, date: answer.date, time: answer.time })
      .then(function (res) {
        if (!res || !res.ok) { var e = new Error('failed'); e.res = res; throw e; }
        if (res.answer) { showTicket(res.answer); }
        setSent(true);
      })
      .catch(function (e) {
        var isBlocked = e.res && e.res.error === 'blocked';
        if (isBlocked) {
          blocked = e.res.blocked || blocked.concat([answer.date]);
          state.date = null;
          renderCal();
        }
        err.textContent = isBlocked ? L.blockedErr : L.error;
        err.hidden = false;
        show('s-when');
        syncSend();
      });
  });

  function setSent(done) {
    $('sent-label').textContent = done ? L.sent : L.sending;
    document.querySelector('.sent').classList.toggle('pending', !done);
  }

  /* ---------- 5. ticket ---------- */
  function showTicket(a) {
    $('t-what').textContent = a.activities.map(function (k) { return L.acts[k] || k; }).join(', ');
    $('t-when').textContent = prettyDate(a.date);
    $('t-time').textContent = a.time;
    show('s-done');
  }

  /* ---------- start ---------- */
  applyLang();
  if (cfg.answer) {
    showTicket(cfg.answer);
  } else {
    show('s-lang');
  }
  api({ action: 'open' }).catch(function () {});
})();
