(function () {
  'use strict';

  var base = (function() {
    var s = document.currentScript || document.querySelector('script[src*="chatbot"]');
    var src = s ? s.src : '';
    if (src.indexOf('127.0.0.1') > -1 || src.indexOf('livepreview') > -1 || location.port === '3000') {
      return location.protocol + '//localhost/ProDo/nasdigital/chatbot/';
    }
    if (src) return src.replace(/assets\/chatbot\.js.*$/, '');
    var p = location.pathname.replace(/\/[^\/]*$/, '');
    return p + '/chatbot/';
  })();

  var C = {
    api: base + 'api/chat.php',
    history: base + 'api/history.php',
    human: base + 'api/human.php',
    name: 'Mr. Nas',
    avatar: 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2220%22 height=%2220%22 viewBox=%220 0 24 24%22 fill=%22none%22%3E%3Ccircle cx=%2212%22 cy=%2212%22 r=%2211%22 fill=%22%230a0a0a%22/%3E%3Cpath d=%22M7 8.5h10M7 12h6%22 stroke=%22%23c9a227%22 stroke-width=%222%22 stroke-linecap=%22round%22/%3E%3C/svg%3E',
    welcome: 'Hello! I\'m Mr. Nas\'s AI Assistant. How can I help you today? Feel free to ask me about his businesses, services, training programs, or anything else about Nas Digital.',
    questions: [
      'Who is Mr. Nas?',
      'Tell me about Nas Digital.',
      'What is NasHub?',
      'How can you help my business?',
      'Can you build my website?',
      'How do I contact Mr. Nas?',
      'Can I schedule a consultation?'
    ],
    key: 'nasc_chat_session',
  };

  var sid = localStorage.getItem(C.key) || '';
  var open = false;
  var busy = false;

  var icons = {
    user: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    bot: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    typing: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    send: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    close: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>',
    chat: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    minus: '<svg width="18" height="18" viewBox="0 0 16 16" fill="none"><path d="M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
    human: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
  };

  function init() {
    var d = document.createElement('div');
    d.id = 'nascBotWidget';
    d.innerHTML =
      '<div class="nasc-chat-window" id="nascWin">' +
        '<div class="nasc-chat-header">' +
          '<div class="nasc-header-left">' +
            '<div class="nasc-header-avatar"><img src="' + C.avatar + '" alt=""></div>' +
            '<div class="nasc-header-info"><div class="nasc-header-name">' + C.name + '</div><div class="nasc-header-status">Online</div></div>' +
          '</div>' +
          '<button class="nasc-header-btn" id="nascMin" title="Minimize">' + icons.minus + '</button>' +
        '</div>' +
        '<div class="nasc-chat-messages" id="nascMsgs"><div class="nasc-system-msg">Ask me anything about Mr. Nas and Nas Digital</div></div>' +
        '<div class="nasc-suggestions" id="nascSug"></div>' +
        '<div class="nasc-chat-input-area">' +
          '<input class="nasc-chat-input" id="nascInp" type="text" placeholder="Type your message..." autocomplete="off">' +
          '<button class="nasc-chat-send" id="nascSend" title="Send" disabled>' + icons.send + '</button>' +
        '</div>' +
        '<div class="nasc-human-wrap"><button class="nasc-human-btn" id="nascHuman">' + icons.human + ' Talk with Mr. Nas</button></div>' +
      '</div>' +
      '<div class="nasc-btn-wrap"><button class="nasc-chat-btn" id="nascBtn" aria-label="Chat"><span class="nasc-btn-icon nasc-btn-open">' + icons.chat + '</span><span class="nasc-btn-icon nasc-btn-close">' + icons.close + '</span></button></div>';

    document.body.appendChild(d);
    bind();
    if (sid) load();
  }

  function id(x) { return document.getElementById(x); }

  function bind() {
    id('nascBtn').addEventListener('click', toggle);
    id('nascSend').addEventListener('click', send);
    id('nascMin').addEventListener('click', toggle);
    id('nascHuman').addEventListener('click', human);

    var inp = id('nascInp');
    inp.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });
    inp.addEventListener('input', function () {
      id('nascSend').disabled = !inp.value.trim();
    });
  }

  function gid() {
    if (!sid) {
      var a = new Uint8Array(32);
      crypto.getRandomValues(a);
      sid = Array.from(a, function (b) { return b.toString(16).padStart(2, '0'); }).join('');
      localStorage.setItem(C.key, sid);
    }
    return sid;
  }

  function toggle() {
    var w = id('nascWin'), b = id('nascBtn');
    open = !open;
    if (open) {
      w.classList.add('is-open');
      b.classList.add('is-active');
      if (!sid) gid();
      if (!w._init) {
        w._init = true;
        botMsg(C.welcome);
        sug();
        fetch(C.history, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ session_id: gid(), current_page: location.href, page_title: document.title, screen_resolution: screen.width + 'x' + screen.height }) }).catch(function(){});
      }
      scroll();
      setTimeout(function () { id('nascInp').focus(); }, 350);
    } else {
      w.classList.remove('is-open');
      b.classList.remove('is-active');
    }
  }

  function load() {
    fetch(C.history, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ session_id: sid, current_page: location.href }),
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      if (res.success && res.history && res.history.length) {
        var el = id('nascMsgs');
        var sys = el.querySelector('.nasc-system-msg');
        if (sys) sys.remove();
        res.history.forEach(function (m) {
          if (m.role === 'user') addMsg(m.message, 'user', m.created_at);
          else if (m.role === 'assistant') addMsg(m.message, 'bot', m.created_at);
        });
        sug();
      }
    })
    .catch(function(){});
  }

  function send() {
    var inp = id('nascInp');
    var t = inp.value.trim();
    if (!t || busy) return;
    busy = true;
    inp.value = '';
    id('nascSend').disabled = true;
    addMsg(t, 'user');
    var typ = document.createElement('div');
    typ.id = 'nascTyp';
    typ.className = 'nasc-typing';
    typ.innerHTML = '<div class="nasc-typing-avatar">' + icons.typing + '</div><div class="nasc-typing-dots"><span></span><span></span><span></span></div>';
    id('nascMsgs').appendChild(typ);
    scroll();
    id('nascSug').innerHTML = '';

    fetch(C.api, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ session_id: gid(), message: t, current_page: location.href, page_title: document.title, screen_resolution: screen.width + 'x' + screen.height }),
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      var t = id('nascTyp');
      if (t) t.remove();
      busy = false;
      if (res.success && res.message) botMsg(res.message);
      else if (res.error) botMsg('Sorry, there was an error: ' + res.error);
      else botMsg('I\'m not completely sure about that. I\'ve notified Mr. Nas\'s team, and someone will contact you shortly.');
      sug();
      scroll();
    })
    .catch(function () {
      var t = id('nascTyp');
      if (t) t.remove();
      busy = false;
      botMsg('Sorry, I couldn\'t process your message. Please try again.');
      sug();
      scroll();
    });
  }

  function human() {
    if (busy) return;
    var el = id('nascMsgs');
    var sys = el.querySelector('.nasc-system-msg');
    if (sys) sys.remove();
    id('nascSug').innerHTML = '';

    var d = document.createElement('div');
    d.className = 'nasc-contact-form';
    d.id = 'nascContactForm';
    d.innerHTML =
      '<div class="nasc-cf-title">Talk with Mr. Nas</div>' +
      '<div class="nasc-cf-sub">Leave your details and Mr. Nas or a team member will contact you.</div>' +
      '<div class="nasc-cf-field"><input class="nasc-cf-input" id="nascCfName" type="text" placeholder="Your name" autocomplete="name"></div>' +
      '<div class="nasc-cf-field"><input class="nasc-cf-input" id="nascCfTelegram" type="text" placeholder="Telegram username" autocomplete="off"></div>' +
      '<div class="nasc-cf-actions">' +
        '<button class="nasc-cf-btn nasc-cf-cancel" id="nascCfCancel">Cancel</button>' +
        '<button class="nasc-cf-btn nasc-cf-submit" id="nascCfSubmit">Send Request</button>' +
      '</div>';
    el.appendChild(d);
    scroll();
    setTimeout(function () { id('nascCfName').focus(); }, 200);

    id('nascCfSubmit').addEventListener('click', submitContact);
    id('nascCfCancel').addEventListener('click', cancelContact);
    id('nascCfName').addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); id('nascCfTelegram').focus(); }
    });
    id('nascCfTelegram').addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); submitContact(); }
    });
  }

  function cancelContact() {
    busy = false;
    var f = id('nascContactForm');
    if (f) f.remove();
  }

  function submitContact() {
    if (busy) return;
    var name = id('nascCfName').value.trim();
    var tg = id('nascCfTelegram').value.trim();
    if (!name) { id('nascCfName').focus(); return; }
    if (!tg) { id('nascCfTelegram').focus(); return; }
    busy = true;

    var f = id('nascContactForm');
    if (f) { f.querySelector('.nasc-cf-submit').textContent = 'Sending...'; f.querySelector('.nasc-cf-submit').disabled = true; f.querySelector('.nasc-cf-cancel').disabled = true; }

    var msgs = id('nascMsgs');
    var last = msgs.querySelector('.nasc-message.is-user:last-child');
    var txt = last ? last.querySelector('.nasc-msg-bubble').textContent : 'No previous message';

    var typ = document.createElement('div');
    typ.id = 'nascTyp';
    typ.className = 'nasc-typing';
    typ.innerHTML = '<div class="nasc-typing-avatar">' + icons.typing + '</div><div class="nasc-typing-dots"><span></span><span></span><span></span></div>';
    msgs.appendChild(typ);

    fetch(C.human, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ session_id: gid(), latest_message: txt, current_page: location.href, caller_name: name, telegram: tg }),
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      var t = id('nascTyp');
      if (t) t.remove();
      var f = id('nascContactForm');
      if (f) f.remove();
      busy = false;
      botMsg(res.success ? (res.message || 'Our team has received your request. Mr. Nas or a team member will contact you soon.') : 'Sorry, there was an error submitting your request. Please try again.');
      sug();
      scroll();
    })
    .catch(function () {
      var t = id('nascTyp');
      if (t) t.remove();
      var f = id('nascContactForm');
      if (f) f.remove();
      busy = false;
      botMsg('Sorry, there was an error. Please try again or contact via Telegram directly.');
      sug();
      scroll();
    });
  }

  function addMsg(text, who, time) {
    var el = id('nascMsgs');
    var sys = el.querySelector('.nasc-system-msg');
    if (sys) sys.remove();
    var d = document.createElement('div');
    d.className = 'nasc-message is-' + who;
    d.innerHTML =
      '<div class="nasc-msg-body">' +
        '<div class="nasc-msg-bubble">' + esc(text) + '</div>' +
        '<div class="nasc-msg-time">' + (time ? fmt(time) : 'Just now') + '</div>' +
      '</div>' +
      '<div class="nasc-msg-avatar">' + icons[who] + '</div>';
    el.appendChild(d);
  }

  function botMsg(text, time) {
    var el = id('nascMsgs');
    var sys = el.querySelector('.nasc-system-msg');
    if (sys) sys.remove();
    var d = document.createElement('div');
    d.className = 'nasc-message is-bot';
    d.innerHTML =
      '<div class="nasc-msg-body">' +
        '<div class="nasc-msg-bubble">' + fmtMsg(text) + '</div>' +
        '<div class="nasc-msg-time">' + (time ? fmt(time) : 'Just now') + '</div>' +
      '</div>' +
      '<div class="nasc-msg-avatar">' + icons.bot + '</div>';
    el.appendChild(d);
  }

  function sug() {
    var c = id('nascSug');
    c.innerHTML = '<div class="nasc-suggestions-track"></div>';
    var t = c.firstChild;
    function addItem(q) {
      var b = document.createElement('button');
      b.className = 'nasc-suggestion';
      b.textContent = q;
      b.addEventListener('click', function () { id('nascInp').value = q; send(); });
      t.appendChild(b);
    }
    C.questions.forEach(function (q) { addItem(q); });
    C.questions.forEach(function (q) { addItem(q); });
  }

  function scroll() {
    var el = id('nascMsgs');
    setTimeout(function () { el.scrollTop = el.scrollHeight; }, 30);
  }

  function fmtMsg(t) {
    if (!t) return '';
    t = esc(t);
    t = t.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    t = t.replace(/\*(.*?)\*/g, '<em>$1</em>');
    t = t.replace(/`(.*?)`/g, '<code>$1</code>');
    t = t.replace(/\n/g, '<br>');
    t = t.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
    return t;
  }

  function esc(t) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(t));
    return d.innerHTML;
  }

  function fmt(dt) {
    if (!dt) return '';
    var d = new Date(dt.replace(' ', 'T') + 'Z');
    if (isNaN(d.getTime())) { d = new Date(dt); if (isNaN(d.getTime())) return ''; }
    var h = d.getHours(), m = d.getMinutes(), ap = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + (m < 10 ? '0' : '') + m + ' ' + ap;
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
