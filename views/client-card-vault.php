<div class="iw-client-heading iw-client-heading-row">
  <div>
    <span class="iw-client-eyebrow">BILLING · SAVED CARDS</span>
    <h1>Your saved cards</h1>
    <p>Add or remove cards securely for faster future checkout.</p>
  </div>
  <a href="<?= e(url('invoices')) ?>" class="iw-client-button iw-client-button-soft"><i class="bi bi-arrow-left"></i> Back to billing</a>
</div>
<div class="iw-vault-layout">
  <section class="iw-tm-panel">
    <div class="iw-tm-panel-head">
      <span class="iw-tm-section-number"><i class="bi bi-credit-card"></i></span>
      <div><h2>Payment methods</h2>
      <p>Only masked card details are displayed. Stripe securely stores the actual card information.</p></div>
    </div>
    <?php if (!$vaultEnabled): ?>
      <div class="iw-client-empty">
        <i class="bi bi-shield-lock"></i>
        <div><strong>Card vault currently unavailable</strong>
        <p>Stripe must be enabled by iWorks administration before customers can save cards. Invoices can still use any currently available payment methods.</p></div>
      </div>
    <?php elseif ($vaultNotice !== ''): ?>
      <div class="alert alert-warning" role="alert"><?= e($vaultNotice) ?></div>
    <?php elseif (!$savedCards): ?>
      <div class="iw-client-empty">
        <i class="bi bi-credit-card"></i>
        <div><strong>No saved cards yet</strong>
        <p>Choose Add a card below to securely save one through Stripe without making a purchase.</p></div>
      </div>
    <?php else: ?>
      <div class="iw-vault-cards">
        <?php foreach ($savedCards as $card): ?>
          <div class="iw-vault-card">
            <span class="iw-vault-card-symbol"><i class="bi bi-credit-card-2-front"></i></span>
            <div class="iw-vault-card-info">
              <strong><?= e(ucfirst((string) ($card['brand'] ?? 'Card'))) ?> ···· <?= e((string) $card['last4']) ?></strong>
              <small>Expires <?= e(sprintf('%02d', (int) $card['exp_month'])) ?>/<?= (int) $card['exp_year'] ?></small>
            </div>
            <form method="post" action="<?= e(url('card_vault')) ?>"
              onsubmit="return confirm('Remove this card from your iWorks saved payment methods?');">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="form_action" value="remove_card">
              <input type="hidden" name="payment_method_id" value="<?= e((string) $card['id']) ?>">
              <button type="submit" class="iw-vault-remove" aria-label="Remove <?= e((string) $card['brand']) ?> card ending <?= e((string) $card['last4']) ?>">
                <i class="bi bi-trash3"></i> Remove
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($vaultEnabled): ?>
      <div class="iw-vault-add">
        <h3>Add a card</h3>
        <p>A secure Stripe page will collect your card details. iWorks will only show its brand, last four digits and expiry date.</p>
        <form method="post" action="<?= e(url('card_vault')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="form_action" value="save_card">
          <label class="iw-vault-consent">
            <input type="checkbox" name="save_card_consent" value="1" required>
            <span>I agree to save this card with Stripe for future iWorks purchases. I understand that saving a card does not charge it, and I can remove it here. Every purchase still requires my confirmation.</span>
          </label>
          <button class="iw-client-button" type="submit"><i class="bi bi-plus-lg"></i> Add a card securely</button>
        </form>
      </div>
    <?php endif; ?>
  </section>
  <aside class="iw-vault-sidebar">
    <div class="iw-vault-security">
      <i class="bi bi-shield-check"></i>
      <h3>Your card security</h3>
      <p>iWorks does not see or store your full card number or security code. Stripe handles card setup and tokenization.</p>
      <p>Saved cards can appear during future <strong>USD Stripe checkout</strong>. NGN invoices use the payment providers configured for naira; these cards are not automatically shared with Paystack or Flutterwave.</p>
      <a href="<?= e(url('invoices')) ?>">View all invoices <i class="bi bi-arrow-right"></i></a>
    </div>
  </aside>
</div>
