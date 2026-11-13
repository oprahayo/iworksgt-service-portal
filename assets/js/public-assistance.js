/* iWorks public assistance. Uses existing website_lead, not a fake live operator. */
(function () {
  'use strict';
  function init() {
    var root = document.getElementById('iwAssist');
    if (!root) return;
    if (root.dataset.assistReady === '1') return;
    root.dataset.assistReady = '1';
    var chat = document.getElementById('iwAssistChat');
    var nudge = document.getElementById('iwAssistNudge');
    var launcher = root.querySelector('.iw-assist-launcher');
    var identity = document.getElementById('iwAssistIdentity');
    var conversation = document.getElementById('iwAssistConversation');
    var ended = document.getElementById('iwAssistEnded');
    var startForm = document.getElementById('iwAssistStartForm');
    var messageForm = document.getElementById('iwAssistMessageForm');
    var messages = document.getElementById('iwAssistMessages');
    var messageInput = document.getElementById('iwAssistMessage');
    var compose = document.getElementById('iwAssistCompose');
    var errorBox = document.getElementById('iwAssistMessageError');
    var endTrigger = root.querySelector('[data-assist-end-open]');
    var endSheet = document.getElementById('iwAssistEndSheet');
    var confirmSheet = root.querySelector('[data-assist-end-confirm]');
    var rateSheet = root.querySelector('[data-assist-end-rate]');
    var book = document.getElementById('iwAssistBook');
    var backdrop = document.getElementById('iwAssistBookBackdrop');
    var bookForm = document.getElementById('iwAssistBookForm');
    var bookError = document.getElementById('iwAssistBookError');
    var bookSuccess = document.getElementById('iwAssistBookSuccess');
    var guestList = document.getElementById('iwAssistGuestList');
    var timezoneLabel = document.getElementById('iwAssistTimezone');
    var endpoint = root.dataset.endpoint;
    var liveEndpoint = root.dataset.liveEndpoint || '';
    var onlineStatus = document.getElementById('iwAssistLiveStatus');
    var startError = document.getElementById('iwAssistStartError');
    var csrf = root.dataset.csrf;
    var storagePrefix = 'iworks-assistance-v1:';
    var session = { name: '', email: '', id: '', token: '', items: [], lastMessageId: 0, status: 'new' };
    var pollInterval = null;
    var busy = false;
    var focusReturn = null;
    var oldOverflow = '';
    var tz = 'Local time';
    try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || tz; } catch (ignore) {}
    timezoneLabel.textContent = tz;

    function getStore(key) {
      try { return window.sessionStorage.getItem(storagePrefix + key); } catch (ignore) { return null; }
    }
    function setStore(key, value) {
      try { window.sessionStorage.setItem(storagePrefix + key, value); } catch (ignore) {}
    }
    function clearStore(key) {
      try { window.sessionStorage.removeItem(storagePrefix + key); } catch (ignore) {}
    }
    function persist() {
      if (session.status === 'active') setStore('chat', JSON.stringify(session));
      else clearStore('chat');
    }
    function hideNudge(dismiss) {
      nudge.hidden = true;
      if (dismiss) setStore('nudge-dismissed', 'yes');
    }
    function hideError(box) {
      if (!box) return;
      box.hidden = true;
      box.textContent = '';
    }
    function showError(box, message) {
      if (!box) return;
      box.textContent = message;
      box.hidden = false;
    }
    function localDateISO(d) {
      return [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-');
    }
    // Request candidates, not actual diary availability. All displayed start
    // times are explicitly in Lagos WAT (UTC+1), without an implicit local
    // timezone conversion or a false confirmed-booking promise.
    var dateField = bookForm.elements.namedItem('date');
    var timeField = bookForm.elements.namedItem('time');
    var dateOptions = document.getElementById('iwAssistDateOptions');
    var timeOptions = document.getElementById('iwAssistTimeOptions');
    var dateRange = document.getElementById('iwAssistDateRange');
    var prevDates = root.querySelector('[data-assist-dates-prev]');
    var nextDates = root.querySelector('[data-assist-dates-next]');
    var datePage = 0;
    var requestDays = [];
    var requestTimes = [];
    for (var hr = 9; hr <= 16; hr++) {
      for (var mn = 0; mn < 60; mn += 30) {
        if (hr === 16 && mn > 30) break;
        requestTimes.push(String(hr).padStart(2, '0') + ':' + String(mn).padStart(2, '0'));
      }
    }
    function rebuildRequestDays() {
      requestDays = [];
      var today = new Date();
      today.setHours(12, 0, 0, 0);
      for (var n = 1; n <= 48 && requestDays.length < 25; n++) {
        var next = new Date(today);
        next.setDate(today.getDate() + n);
        if (next.getDay() === 0 || next.getDay() === 6) continue;
        requestDays.push({
          iso: localDateISO(next),
          dow: new Intl.DateTimeFormat('en', {weekday: 'short'}).format(next),
          day: String(next.getDate()),
          month: new Intl.DateTimeFormat('en', {month: 'short'}).format(next),
          full: new Intl.DateTimeFormat('en', {weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'}).format(next)
        });
      }
      if (!requestDays.some(function (d) { return d.iso === dateField.value; })) {
        dateField.value = '';
        timeField.value = '';
      }
      datePage = 0;
      renderRequestDays();
      renderRequestTimes();
    }
    function renderRequestDays() {
      dateOptions.replaceChildren();
      var current = requestDays.slice(datePage * 5, datePage * 5 + 5);
      if (!current.length) return;
      dateRange.textContent = current[0].month + ' ' + current[0].day + ' – ' + current[current.length - 1].month + ' ' + current[current.length - 1].day;
      prevDates.disabled = datePage === 0;
      nextDates.disabled = (datePage + 1) * 5 >= requestDays.length;
      current.forEach(function (day) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'iw-assist-date-choice';
        button.setAttribute('aria-label', day.full);
        button.setAttribute('aria-pressed', String(dateField.value === day.iso));
        button.innerHTML = '<small>' + day.dow + '</small><strong>' + day.day + '</strong><span>' + day.month + '</span>';
        button.addEventListener('click', function () {
          dateField.value = day.iso;
          timeField.value = '';
          hideError(bookError);
          renderRequestDays();
          renderRequestTimes();
        });
        dateOptions.appendChild(button);
      });
    }
    function renderRequestTimes() {
      timeOptions.replaceChildren();
      if (!dateField.value) {
        var hint = document.createElement('p');
        hint.className = 'iw-assist-time-hint';
        hint.textContent = 'Select a day first to see suggested times.';
        timeOptions.appendChild(hint);
        return;
      }
      requestTimes.forEach(function (time) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'iw-assist-time-choice';
        button.textContent = time;
        button.setAttribute('aria-label', time + ' West Africa Time');
        button.setAttribute('aria-pressed', String(timeField.value === time));
        button.addEventListener('click', function () {
          timeField.value = time;
          hideError(bookError);
          renderRequestTimes();
        });
        timeOptions.appendChild(button);
      });
    }
    if (prevDates) prevDates.addEventListener('click', function () {
      if (datePage > 0) { datePage--; renderRequestDays(); }
    });
    if (nextDates) nextDates.addEventListener('click', function () {
      if ((datePage + 1) * 5 < requestDays.length) { datePage++; renderRequestDays(); }
    });
    rebuildRequestDays();

    try {
      var saved = JSON.parse(getStore('chat') || 'null');
      if (saved && saved.status === 'active' && typeof saved.name === 'string' && typeof saved.email === 'string' && Array.isArray(saved.items)) {
        if (typeof saved.token === 'string' && /^[a-f0-9]{64}$/.test(saved.token)) {
          session = {name: saved.name.slice(0, 150), email: saved.email.slice(0, 254),
            id: String(saved.id || '').slice(0, 50), token: saved.token,
            lastMessageId: Number(saved.lastMessageId || 0), items: saved.items.slice(-80), status: 'active'};
        }
      }
    } catch (ignore) {}
    if (!getStore('first-seen')) setStore('first-seen', String(Date.now()));

    function renderMessage(item) {
      var bubble = document.createElement('div');
      bubble.className = 'iw-assist-bubble ' + (item.kind === 'user' ? 'is-user' : item.kind === 'staff' ? 'is-staff' : 'is-system');
      var body = document.createElement('span');
      body.textContent = String(item.text || '');
      bubble.appendChild(body);
      if (item.when) {
        var time = document.createElement('time');
        time.textContent = item.when;
        bubble.appendChild(time);
      }
      messages.appendChild(bubble);
    }
    function addMessage(kind, text) {
      var when = new Intl.DateTimeFormat(undefined, {hour: '2-digit', minute: '2-digit'}).format(new Date());
      var item = {kind: kind, text: String(text), when: when};
      session.items.push(item);
      if (session.items.length > 30) session.items.shift();
      renderMessage(item);
      persist();
      messages.scrollTop = messages.scrollHeight;
      conversation.parentElement.scrollTop = conversation.parentElement.scrollHeight;
    }
    async function liveApi(action, extra) {
      if (!liveEndpoint) throw new Error('Live chat is not configured yet. Please use Contact.');
      var form = new FormData();
      form.append('csrf_token', csrf);
      form.append('action', action);
      if (session.token) form.append('token', session.token);
      Object.keys(extra || {}).forEach(function (key) { form.append(key, String(extra[key])); });
      var result = await fetch(liveEndpoint, {
        method: 'POST', credentials: 'same-origin', cache: 'no-store',
        headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: form
      });
      var data;
      try { data = await result.json(); }
      catch (ignore) { throw new Error('Chat is unavailable. Please use our Contact page.'); }
      if (!result.ok || !data || !data.ok) throw new Error((data && data.message) || 'Message was not delivered.');
      return data;
    }
    function ingestMessages(items) {
      (Array.isArray(items) ? items : []).forEach(function (m) {
        var id = Number(m.id || 0);
        if (!id || session.items.some(function (x) { return Number(x.id) === id; })) return;
        var item = { id:id, kind:m.sender === 'staff' ? 'staff' : 'user',
          text:String(m.body || ''), when:String(m.created_at || '').slice(11,16) };
        session.items.push(item);
        if (session.items.length > 80) session.items.shift();
        session.lastMessageId = Math.max(Number(session.lastMessageId || 0), id);
        if (!conversation.hidden) renderMessage(item);
      });
      persist();
      if (!conversation.hidden) messages.scrollTop = messages.scrollHeight;
    }
    function updatePresence(data) {
      if (!onlineStatus) return;
      var online = Boolean(data.staff_online);
      onlineStatus.textContent = online ? 'Support is online' : 'Support is currently offline. Leave your message here.';
      onlineStatus.classList.toggle('is-online',online);
    }
    async function pollLive() {
      if (session.status !== 'active' || !session.token ||
          document.visibilityState === 'hidden' || chat.hidden) return;
      try {
        var data = await liveApi('poll',{after_id:session.lastMessageId || 0});
        ingestMessages(data.messages);
        updatePresence(data);
        if (data.status === 'ended') { session.status='ended'; persist(); showChatStage(); }
      } catch (error) {
        if (onlineStatus) { onlineStatus.textContent = 'Connection interrupted. Retrying…'; }
      }
    }
    function startLivePoller() {
      if (pollInterval !== null) window.clearInterval(pollInterval);
      pollInterval = window.setInterval(pollLive, 5000);
      pollLive();
    }
    function showChatStage() {
      var active = session.status === 'active';
      var done = session.status === 'ended';
      identity.hidden = active || done;
      conversation.hidden = !active;
      ended.hidden = !done;
      compose.hidden = !active;
      endTrigger.hidden = !active;
      if (active) {
        updatePresence({staff_online:false});
        messages.replaceChildren();
        session.items.forEach(renderMessage);
        conversation.parentElement.scrollTop = conversation.parentElement.scrollHeight;
      }
    }
    function openChat(trigger) {
      if (!book.hidden) closeBook();
      focusReturn = trigger || document.activeElement;
      hideNudge(true);
      chat.hidden = false;
      document.body.classList.add('iw-assist-chat-open');
      launcher.hidden = true;
      launcher.setAttribute('aria-expanded', 'true');
      showChatStage();
      startLivePoller();
      window.requestAnimationFrame(function () {
        if (session.status === 'active') messageInput.focus();
        else if (session.status === 'ended') root.querySelector('[data-assist-restart]').focus();
        else startForm.elements.namedItem('full_name').focus();
      });
    }
    function closeChat() {
      endSheet.hidden = true;
      chat.hidden = true;
      document.body.classList.remove('iw-assist-chat-open');
      launcher.hidden = false;
      launcher.setAttribute('aria-expanded', 'false');
      if (focusReturn && typeof focusReturn.focus === 'function') focusReturn.focus();
    }
    function restartChat() {
      session = {name: '', email: '', id: '', token: '', items: [], lastMessageId: 0, status: 'new'};
      clearStore('chat');
      startForm.reset();
      showChatStage();
      startForm.elements.namedItem('full_name').focus();
    }
    async function finishChat(rating) {
      try {
        if (session.token) await liveApi('end', rating ? {rating:rating} : {});
      } catch (error) {
        showError(errorBox, error.message || 'Unable to end chat. Retry.');
        return;
      }
      session.status = 'ended';
      session.items = [];
      session.token = '';
      clearStore('chat');
      endSheet.hidden = true;
      showChatStage();
    }
    function closeBook() {
      book.hidden = true;
      document.body.classList.remove('iw-assist-book-open');
      backdrop.hidden = true;
      document.body.style.overflow = oldOverflow;
      if (focusReturn && typeof focusReturn.focus === 'function') focusReturn.focus();
    }
    function openBook(trigger) {
      if (!chat.hidden) closeChat();
      focusReturn = trigger || document.activeElement;
      hideNudge(true);
      rebuildRequestDays();
      book.hidden = false;
      document.body.classList.add('iw-assist-book-open');
      backdrop.hidden = false;
      oldOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
      window.requestAnimationFrame(function () {
        var first = book.querySelector('[data-assist-book-close]');
        if (first) first.focus();
      });
    }
    async function postLead(name, email, message) {
      var data = new FormData();
      data.append('csrf_token', csrf);
      data.append('form_action', 'website_lead');
      data.append('full_name', name);
      data.append('email', email);
      data.append('message', message);
      var response;
      try {
        response = await fetch(endpoint, {
          method: 'POST', credentials: 'same-origin',
          redirect: 'error',
          cache: 'no-store',
          headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
          body: data
        });
      } catch (networkError) {
        throw new Error('Could not connect to our support form. Please check your connection and retry.');
      }
      var result;
      try { result = await response.json(); }
      catch (ignore) {
        throw new Error(response.status === 403 || response.status === 419
          ? 'Your session expired. Refresh this page, then try again.'
          : 'The support form is temporarily unavailable. Please use the Contact page.');
      }
      if (!response.ok || !result || !result.ok) {
        throw new Error((result && result.message) || 'Unable to send your message. Please try again.');
      }
      return result;
    }

    root.addEventListener('click', function (event) {
      var el = event.target.closest('button');
      if (!el) return;
      if (el.matches('[data-assist-chat-open]')) { openChat(el); return; }
      if (el.matches('[data-assist-chat-minimize], [data-assist-chat-close]')) { closeChat(); return; }
      if (el.matches('[data-assist-nudge-dismiss]')) { hideNudge(true); return; }
      if (el.matches('[data-assist-book-open]')) { openBook(el); return; }
      if (el.matches('[data-assist-book-close]')) { closeBook(); return; }
      if (el.matches('[data-assist-restart]')) { restartChat(); return; }
      if (el.matches('[data-assist-end-open]')) {
        endSheet.hidden = false;
        confirmSheet.hidden = false;
        rateSheet.hidden = true;
        root.querySelector('[data-assist-end-accept]').focus();
        return;
      }
      if (el.matches('[data-assist-end-cancel]')) { endSheet.hidden = true; return; }
      if (el.matches('[data-assist-end-accept]')) {
        confirmSheet.hidden = true;
        rateSheet.hidden = false;
        root.querySelector('[data-assist-rate="1"]').focus();
        return;
      }
      if (el.matches('[data-assist-rate-skip]')) { finishChat(); return; }
      if (el.matches('[data-assist-rate]')) {
        var rating = Number(el.dataset.assistRate);
        if (!(rating >= 1 && rating <= 5) || busy) return;
        busy = true;
        root.querySelectorAll('[data-assist-rate], [data-assist-rate-skip]').forEach(function (button) { button.disabled = true; });
        finishChat(rating).finally(function () {
          busy = false;
          root.querySelectorAll('[data-assist-rate], [data-assist-rate-skip]').forEach(function (button) { button.disabled = false; });
        });
        return;
      }
      if (el.matches('[data-assist-add-guest]')) {
        if (guestList.children.length >= 5) return;
        var row = document.createElement('div');
        row.className = 'iw-assist-guest-row';
        var input = document.createElement('input');
        input.type = 'email';
        input.name = 'guest_email[]';
        input.placeholder = 'Guest email address';
        input.required = true;
        input.maxLength = 254;
        input.setAttribute('aria-label', 'Additional guest email address');
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.setAttribute('aria-label', 'Remove guest');
        remove.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
        remove.addEventListener('click', function () { row.remove(); });
        row.append(input, remove);
        guestList.appendChild(row);
        input.focus();
      }
    });
    backdrop.addEventListener('click', closeBook);
    // Existing service links previously targeted a general-purpose modal.
    document.addEventListener('click', function (event) {
      var booking = event.target.closest('[data-assist-book-open]');
      if (booking && !root.contains(booking)) {
        event.preventDefault();
        var mobileMenu = document.getElementById('publicNav');
        if (mobileMenu && mobileMenu.classList.contains('show') && window.bootstrap) {
          window.bootstrap.Collapse.getOrCreateInstance(mobileMenu).hide();
        }
        openBook(booking);
        return;
      }
      var trigger = event.target.closest('[data-iw-help-open]');
      if (!trigger) return;
      event.preventDefault();
      openChat(trigger);
      var requested = trigger.getAttribute('data-iw-service');
      if (requested && session.status === 'active' && !messageInput.value) messageInput.value = 'I would like to ask about ' + requested + '.';
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        if (!book.hidden) { event.preventDefault(); closeBook(); return; }
        if (!endSheet.hidden) { event.preventDefault(); endSheet.hidden = true; return; }
        if (!chat.hidden) { event.preventDefault(); closeChat(); }
      }
      if (event.key === 'Tab' && !book.hidden) {
        var focusables = Array.from(book.querySelectorAll('button:not([disabled]),input:not([disabled]),textarea:not([disabled]),a[href]')).filter(function (node) { return node.offsetParent !== null; });
        if (!focusables.length) return;
        var first = focusables[0];
        var last = focusables[focusables.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      }
    });
    startForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (!startForm.reportValidity() || busy) return;
      var name = startForm.elements.namedItem('full_name').value.trim();
      var email = startForm.elements.namedItem('email').value.trim();
      if (!name || !email) return;
      busy=true;
      var submit=startForm.querySelector('[type=submit]');
      submit.disabled=true;
      hideError(startError);
      try {
        var live=await liveApi('start',{name:name,email:email});
        session = {name:name,email:email,id:String(live.id),token:String(live.token),
          items:[],lastMessageId:0,status:'active'};
        persist();
        showChatStage();
        updatePresence(live);
        startLivePoller();
        messageInput.focus();
      } catch (error) {
        showError(startError,error.message || 'Unable to start chat.');
      } finally { busy=false;submit.disabled=false; }
    });
    messageForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      var text = messageInput.value.trim();
      if (!text || !session.token || busy) return;
      busy = true;
      hideError(errorBox);
      var button = messageForm.querySelector('button[type=submit]');
      button.disabled = true;
      try {
        var result=await liveApi('send',{message:text,after_id:session.lastMessageId || 0});
        ingestMessages(result.messages);
        updatePresence(result);
        messageInput.value='';
        messageInput.focus();
      } catch (error) {
        showError(errorBox, error.message || 'Could not send message.');
      } finally { busy=false;button.disabled=false; }
    });
    messageInput.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        if (typeof messageForm.requestSubmit === 'function') messageForm.requestSubmit();
        else messageForm.querySelector('button[type=submit]').click();
      }
    });
    bookForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (!bookForm.reportValidity() || busy) return;
      var name = bookForm.elements.namedItem('full_name').value.trim();
      var email = bookForm.elements.namedItem('email').value.trim();
      var country = bookForm.elements.namedItem('country').value.trim();
      var date = bookForm.elements.namedItem('date').value;
      var time = bookForm.elements.namedItem('time').value;
      if (!date || !requestDays.some(function (day) { return day.iso === date; })) {
        showError(bookError, 'Select a suggested future weekday.'); return;
      }
      if (!time || !requestTimes.includes(time)) {
        showError(bookError, 'Select a suggested start time.'); return;
      }
      var guests = Array.from(guestList.querySelectorAll('input[type=email]')).map(function (input) { return input.value.trim().toLowerCase(); });
      if ((new Set(guests)).size !== guests.length || guests.includes(email.toLowerCase())) { showError(bookError, 'Each guest needs a different email address.'); return; }
      var stage = bookForm.querySelector('input[name=stage]:checked').value;
      var notes = bookForm.elements.namedItem('notes').value.trim();
      var body = [
        'CONSULTATION REQUEST — not a confirmed booking',
        'Stage: ' + stage,
        'Country/location: ' + country,
        'Preferred date (request, unconfirmed): ' + date,
        'Preferred time (Lagos, Africa/Lagos UTC+1): ' + time,
        'Visitor local timezone: ' + tz,
        'Slot status: requested, not live availability or confirmed appointment',
        'Additional guests: ' + (guests.join(', ') || 'None'),
        'Discussion: ' + (notes || 'Not specified'),
        'Origin: public website consultation panel'
      ].join('\n');
      busy = true;
      hideError(bookError);
      var button = bookForm.querySelector('button[type=submit]');
      button.disabled = true;
      try {
        await postLead(name, email, body);
        bookForm.hidden = true;
        bookSuccess.hidden = false;
      } catch (error) {
        showError(bookError, error && error.message ? error.message : 'Unable to send the consultation request.');
      } finally {
        busy = false;
        button.disabled = false;
      }
    });

    showChatStage();
    if (session.status === 'active') startLivePoller();
    document.addEventListener('visibilitychange',function () {
      if (document.visibilityState === 'visible') pollLive();
    });
    // Keep the invitation session-aware but resilient to background tabs.
    // The original one-shot timer was lost if the page was hidden at expiry.
    var nudgeTimer = null;
    var firstSeen = Number(getStore('first-seen')) || Date.now();
    function offerNudgeWhenReady() {
      if (nudgeTimer !== null) { window.clearTimeout(nudgeTimer); nudgeTimer = null; }
      if (getStore('nudge-dismissed') === 'yes') return;
      var remaining = Math.max(0, 120000 - (Date.now() - firstSeen));
      if (remaining > 0) {
        nudgeTimer = window.setTimeout(offerNudgeWhenReady, remaining);
        return;
      }
      if (document.visibilityState === 'hidden') return;
      if (!chat.hidden || !book.hidden) return;
      nudge.hidden = false;
    }
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'visible') offerNudgeWhenReady();
    });
    offerNudgeWhenReady();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
