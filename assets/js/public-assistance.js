/* iWorks public assistance. Uses existing website_lead, not a fake live operator. */
(function () {
  'use strict';
  function init() {
    var root = document.getElementById('iwAssist');
    if (!root) return;
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
    var csrf = root.dataset.csrf;
    var storagePrefix = 'iworks-assistance-v1:';
    var session = { name: '', email: '', id: '', items: [], status: 'new' };
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
    var dateField = bookForm.elements.namedItem('date');
    if (dateField) dateField.min = localDateISO(new Date());

    try {
      var saved = JSON.parse(getStore('chat') || 'null');
      if (saved && saved.status === 'active' && typeof saved.name === 'string' && typeof saved.email === 'string' && Array.isArray(saved.items)) {
        session = {name: saved.name.slice(0, 150), email: saved.email.slice(0, 254), id: String(saved.id || '').slice(0, 50), items: saved.items.slice(-30), status: 'active'};
      }
    } catch (ignore) {}
    if (!getStore('first-seen')) setStore('first-seen', String(Date.now()));

    function renderMessage(item) {
      var bubble = document.createElement('div');
      bubble.className = 'iw-assist-bubble ' + (item.kind === 'user' ? 'is-user' : 'is-system');
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
    function showChatStage() {
      var active = session.status === 'active';
      var done = session.status === 'ended';
      identity.hidden = active || done;
      conversation.hidden = !active;
      ended.hidden = !done;
      compose.hidden = !active;
      endTrigger.hidden = !active;
      if (active) {
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
      launcher.hidden = true;
      launcher.setAttribute('aria-expanded', 'true');
      showChatStage();
      window.requestAnimationFrame(function () {
        if (session.status === 'active') messageInput.focus();
        else if (session.status === 'ended') root.querySelector('[data-assist-restart]').focus();
        else startForm.elements.namedItem('full_name').focus();
      });
    }
    function closeChat() {
      endSheet.hidden = true;
      chat.hidden = true;
      launcher.hidden = false;
      launcher.setAttribute('aria-expanded', 'false');
      if (focusReturn && typeof focusReturn.focus === 'function') focusReturn.focus();
    }
    function restartChat() {
      session = {name: '', email: '', id: '', items: [], status: 'new'};
      clearStore('chat');
      startForm.reset();
      showChatStage();
      startForm.elements.namedItem('full_name').focus();
    }
    function finishChat() {
      session.status = 'ended';
      session.items = [];
      clearStore('chat');
      endSheet.hidden = true;
      showChatStage();
    }
    function closeBook() {
      book.hidden = true;
      backdrop.hidden = true;
      document.body.style.overflow = oldOverflow;
      if (focusReturn && typeof focusReturn.focus === 'function') focusReturn.focus();
    }
    function openBook(trigger) {
      if (!chat.hidden) closeChat();
      focusReturn = trigger || document.activeElement;
      hideNudge(true);
      book.hidden = false;
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
      var response = await fetch(endpoint, {
        method: 'POST', credentials: 'same-origin',
        headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        body: data
      });
      var result;
      try { result = await response.json(); } catch (ignore) { throw new Error('Unable to reach support. Please try again.'); }
      if (!response.ok || !result || !result.ok) throw new Error((result && result.message) || 'Unable to send. Please try again.');
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
        postLead(session.name, session.email, 'Support chat experience rating: ' + rating + '/5. Reference: ' + session.id)
          .catch(function () { /* Rating feedback is optional; ending chat must still work. */ })
          .finally(function () {
            busy = false;
            root.querySelectorAll('[data-assist-rate], [data-assist-rate-skip]').forEach(function (button) { button.disabled = false; });
            finishChat();
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
    startForm.addEventListener('submit', function (event) {
      event.preventDefault();
      if (!startForm.reportValidity()) return;
      var name = startForm.elements.namedItem('full_name').value.trim();
      var email = startForm.elements.namedItem('email').value.trim();
      if (!name || !email) return;
      session = {
        name: name,
        email: email,
        id: (window.crypto && typeof window.crypto.randomUUID === 'function') ? window.crypto.randomUUID() : String(Date.now()) + '-' + String(Math.random()).slice(2, 10),
        items: [], status: 'active'
      };
      persist();
      showChatStage();
      messageInput.focus();
    });
    messageForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      var text = messageInput.value.trim();
      if (!text || !session.email || busy) return;
      busy = true;
      hideError(errorBox);
      var button = messageForm.querySelector('button[type=submit]');
      button.disabled = true;
      try {
        await postLead(session.name, session.email, 'Support conversation ' + session.id + '\n\n' + text);
        addMessage('user', text);
        if (session.items.length === 1) addMessage('system', 'Your message has been delivered to our enquiry team. A team member can reply to you by email at ' + session.email + '.');
        messageInput.value = '';
        messageInput.focus();
      } catch (error) {
        showError(errorBox, error && error.message ? error.message : 'Could not send message.');
      } finally {
        busy = false;
        button.disabled = false;
      }
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
      if (date < localDateISO(new Date())) { showError(bookError, 'Choose today or a future date.'); return; }
      var guests = Array.from(guestList.querySelectorAll('input[type=email]')).map(function (input) { return input.value.trim().toLowerCase(); });
      if ((new Set(guests)).size !== guests.length || guests.includes(email.toLowerCase())) { showError(bookError, 'Each guest needs a different email address.'); return; }
      var stage = bookForm.querySelector('input[name=stage]:checked').value;
      var notes = bookForm.elements.namedItem('notes').value.trim();
      var body = [
        'CONSULTATION REQUEST — not a confirmed booking',
        'Stage: ' + stage,
        'Country/location: ' + country,
        'Preferred date: ' + date,
        'Preferred time: ' + time,
        'Timezone: ' + tz,
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
    if (getStore('nudge-dismissed') !== 'yes') {
      var elapsed = Math.max(0, Date.now() - Number(getStore('first-seen') || Date.now()));
      window.setTimeout(function () {
        if (getStore('nudge-dismissed') === 'yes' || !chat.hidden || !book.hidden || document.visibilityState === 'hidden') return;
        nudge.hidden = false;
      }, Math.max(0, 120000 - elapsed));
    }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
