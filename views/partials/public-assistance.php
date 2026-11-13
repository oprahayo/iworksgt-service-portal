<?php
/**
 * Public assistance widget. Uses the existing CSRF-protected website_lead
 * handler; it is an asynchronous enquiry channel, not a simulated live agent.
 */
$iwAssistEndpoint = public_url('home');
?>
<div class="iw-assist" id="iwAssist" data-endpoint="<?= e($iwAssistEndpoint) ?>" data-live-endpoint="<?= e(public_url('home',['page'=>'live_chat'])) ?>" data-csrf="<?= e(csrf_token()) ?>">
  <div class="iw-assist-nudge" id="iwAssistNudge" hidden>
    <button type="button" class="iw-assist-nudge-dismiss" data-assist-nudge-dismiss aria-label="Dismiss consultation suggestion"><i class="bi bi-x" aria-hidden="true"></i></button>
    <strong>Have a question about our services?</strong>
    <p>We can help you find the right solution.</p>
    <div class="iw-assist-nudge-actions">
      <button type="button" data-assist-book-open>Request a call <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
      <button type="button" data-assist-chat-open>Send a message</button>
    </div>
  </div>
  <button type="button" class="iw-assist-launcher" data-assist-chat-open aria-label="Open iWorks support" aria-expanded="false" aria-controls="iwAssistChat">
    <i class="bi bi-chat-dots-fill" aria-hidden="true"></i>
  </button>

  <section class="iw-assist-chat" id="iwAssistChat" role="dialog" aria-labelledby="iwAssistChatTitle" aria-modal="false" hidden>
    <header class="iw-assist-chat-head">
      <div><h2 id="iwAssistChatTitle">iWorks Support</h2><p><span class="iw-assist-indicator" aria-hidden="true"></span><span id="iwAssistLiveStatus">Checking support availability…</span></p></div>
      <div class="iw-assist-head-actions">
        <button type="button" class="iw-assist-end-trigger" data-assist-end-open hidden>End chat</button>
        <button type="button" data-assist-chat-minimize title="Minimize" aria-label="Minimize support chat"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
        <button type="button" data-assist-chat-close title="Close" aria-label="Close support chat"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
      </div>
    </header>
    <div class="iw-assist-chat-main">
      <div class="iw-assist-identity" id="iwAssistIdentity">
        <div class="iw-assist-hello" aria-hidden="true"><i class="bi bi-chat-heart" aria-hidden="true"></i></div>
        <h3>Welcome to iWorks</h3>
        <p>Tell us who you are to start a conversation.</p>
        <form id="iwAssistStartForm">
          <label for="iwAssistName">Full name</label>
          <input id="iwAssistName" name="full_name" autocomplete="name" maxlength="150" placeholder="Your name" required>
          <label for="iwAssistEmail">Email address</label>
          <input id="iwAssistEmail" name="email" type="email" autocomplete="email" maxlength="254" placeholder="you@example.com" required>
          <p class="iw-assist-chat-privacy">To handle your enquiry, we record the conversation, its time and a masked network address. Approximate country may be shown to staff when available. No precise GPS is collected.</p>
          <button type="submit" class="iw-assist-primary">Start conversation <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
          <p class="iw-assist-error" id="iwAssistStartError" role="alert" hidden></p>
        </form>
      </div>
      <div class="iw-assist-conversation" id="iwAssistConversation" hidden>
        <p class="iw-assist-chat-intro">How can we help you today? Our team can reply here when online. If support is offline, you can still leave your message.</p>
        <div class="iw-assist-messages" id="iwAssistMessages" role="log" aria-live="polite" aria-label="Support conversation"></div>
      </div>
      <div class="iw-assist-ended" id="iwAssistEnded" hidden>
        <i class="bi bi-check-circle" aria-hidden="true"></i>
        <h3>Conversation ended</h3>
        <p>Thank you for contacting iWorks. You can start another conversation whenever you need us.</p>
        <button type="button" class="iw-assist-primary" data-assist-restart>Start a new conversation</button>
      </div>
      <div class="iw-assist-end-sheet" id="iwAssistEndSheet" hidden>
        <div class="iw-assist-sheet-confirm" data-assist-end-confirm>
          <strong>End this conversation?</strong>
          <p>Any messages already sent will still reach our team.</p>
          <div><button type="button" class="iw-assist-danger" data-assist-end-accept>End chat</button><button type="button" data-assist-end-cancel>Cancel</button></div>
        </div>
        <div class="iw-assist-sheet-rate" data-assist-end-rate hidden>
          <strong>How was your experience?</strong>
          <p>Rate this conversation, or skip.</p>
          <div class="iw-assist-ratings" role="group" aria-label="Rate the conversation from 1 to 5">
            <?php foreach ([1, 2, 3, 4, 5] as $score): ?><button type="button" data-assist-rate="<?= $score ?>" aria-label="Rate <?= $score ?> out of 5"><?= $score ?></button><?php endforeach; ?>
          </div>
          <button type="button" class="iw-assist-skip" data-assist-rate-skip>Skip</button>
        </div>
      </div>
    </div>
    <div class="iw-assist-chat-compose" id="iwAssistCompose" hidden>
      <form id="iwAssistMessageForm">
        <label for="iwAssistMessage" class="visually-hidden">Your message</label>
        <textarea id="iwAssistMessage" maxlength="2000" rows="1" placeholder="Type your message..." required></textarea>
        <button type="submit" aria-label="Send your message"><i class="bi bi-send-fill" aria-hidden="true"></i></button>
      </form>
      <p id="iwAssistMessageError" class="iw-assist-error" role="alert" hidden></p>
    </div>
    <footer class="iw-assist-chat-foot"><a href="<?= e(public_url('privacy')) ?>">Privacy</a></footer>
  </section>

  <div class="iw-assist-book-backdrop" id="iwAssistBookBackdrop" hidden></div>
  <aside class="iw-assist-book" id="iwAssistBook" role="dialog" aria-modal="true" aria-labelledby="iwAssistBookTitle" hidden>
    <header class="iw-assist-book-head">
      <div><h2 id="iwAssistBookTitle">Speak with the iWorks team</h2><p>Request a 15-minute consultation</p></div>
      <button type="button" data-assist-book-close aria-label="Close consultation panel"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </header>
    <div class="iw-assist-book-content" id="iwAssistBookContent">
      <div class="iw-assist-book-intro"><i class="bi bi-calendar2-week" aria-hidden="true"></i><h3>Choose a consultation time</h3><p>Pick a weekday and preferred 15-minute consultation start time. We will confirm it by email.</p></div>
      <form id="iwAssistBookForm">
        <fieldset><legend>What can we help with?</legend>
          <label class="iw-assist-radio"><input type="radio" name="stage" value="Exploring options" checked> I'm exploring my options</label>
          <label class="iw-assist-radio"><input type="radio" name="stage" value="Ready to start"> I'm ready to start a project</label>
          <label class="iw-assist-radio"><input type="radio" name="stage" value="Existing customer"> I'm an existing customer</label>
        </fieldset>
        <div class="iw-assist-book-grid">
          <label>Full name <span aria-hidden="true">*</span><input name="full_name" autocomplete="name" maxlength="150" required></label>
          <label>Email address <span aria-hidden="true">*</span><input name="email" type="email" autocomplete="email" maxlength="254" required></label>
        </div>
        <label>Location or country <span aria-hidden="true">*</span><input name="country" autocomplete="country-name" maxlength="100" required placeholder="e.g. Nigeria"></label>
        <label>What would you like to discuss? <textarea name="notes" rows="3" maxlength="1000" placeholder="Briefly describe your question or project"></textarea></label>
        <fieldset class="iw-assist-slot-fieldset" id="iwAssistDatePicker">
          <legend>Preferred date <span aria-hidden="true">*</span></legend>
          <div class="iw-assist-slot-navigation">
            <button type="button" data-assist-dates-prev aria-label="Previous dates" disabled><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
            <strong id="iwAssistDateRange" aria-live="polite">Choose a day</strong>
            <button type="button" data-assist-dates-next aria-label="More dates"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
          </div>
          <div class="iw-assist-date-options" id="iwAssistDateOptions" role="group" aria-label="Select a preferred consultation date"></div>
          <input type="hidden" name="date" id="iwAssistSelectedDate">
        </fieldset>
        <fieldset class="iw-assist-slot-fieldset" id="iwAssistTimePicker">
          <legend>Preferred time <span aria-hidden="true">*</span></legend>
          <div class="iw-assist-time-options" id="iwAssistTimeOptions" role="group" aria-label="Select a preferred consultation time"></div>
          <input type="hidden" name="time" id="iwAssistSelectedTime">
        </fieldset>
        <p class="iw-assist-tz"><i class="bi bi-globe" aria-hidden="true"></i> Requested times in <strong>West Africa Time (Lagos, UTC+1)</strong>. Your local timezone: <strong id="iwAssistTimezone">Local time</strong></p>
        <p class="iw-assist-slot-note"><i class="bi bi-info-circle" aria-hidden="true"></i> Choose one of the suggested request times. Dates and times are not live availability; our team confirms the meeting before it is booked.</p>
        <fieldset class="iw-assist-guests">
          <legend>Additional guests <small>(optional)</small></legend>
          <div id="iwAssistGuestList"></div>
          <button type="button" class="iw-assist-add-guest" data-assist-add-guest><i class="bi bi-plus-circle" aria-hidden="true"></i> Add guest email</button>
        </fieldset>
        <button type="submit" class="iw-assist-primary">Send consultation request <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        <p class="iw-assist-book-disclaimer">This is a request, not a confirmed appointment. We will email you before adding a meeting to any calendar.</p>
        <p id="iwAssistBookError" class="iw-assist-error" role="alert" hidden></p>
      </form>
      <div class="iw-assist-book-success" id="iwAssistBookSuccess" hidden>
        <i class="bi bi-envelope-check" aria-hidden="true"></i>
        <h3>Request received</h3><p>Your preferred time and guest details have been sent. No meeting has been booked yet; we'll email you to confirm.</p>
        <button type="button" data-assist-book-close>Close</button>
      </div>
    </div>
    <footer class="iw-assist-book-foot"><i class="bi bi-clock" aria-hidden="true"></i><span>15-minute consultation<br><small>Subject to availability and confirmation</small></span></footer>
  </aside>
</div>
