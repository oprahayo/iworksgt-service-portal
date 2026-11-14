<?php
$footerData = is_array($publicFooterData ?? null) ? $publicFooterData : default_footer_block_data();
$footerCompany = trim((string) ($footerData['companyName'] ?? brand_name())) ?: brand_name();
$footerSummary = trim((string) ($footerData['summary'] ?? business_support_summary()));
$footerAddress = trim((string) ($footerData['address'] ?? ''));
if (strcasecmp($footerAddress, 'Lagos, Nigeria') === 0) {
    // Replace the obsolete default city; real custom addresses stay untouched.
    $footerAddress = 'Awolowo Civic Centre, Fajuyi, Ado-Ekiti, Ekiti State, Nigeria';
}
$footerPhone = trim((string) ($footerData['phone'] ?? ''));
if (preg_match('/^\\+?234\\s*806\\s*000\\s*0000$/', $footerPhone)) {
    $footerPhone = ''; // Never publish a placeholder number as an active support line.
}
$footerEmail = trim((string) ($footerData['email'] ?? ''));
$footerColumns = is_array($footerData['columns'] ?? null) ? $footerData['columns'] : [];
$footerLogo = logo_path();

if ($footerColumns === []) {
    $footerColumns = default_footer_block_data()['columns'] ?? [];
}

$displayColumns = array_values(array_filter($footerColumns, static fn($column): bool => is_array($column)));
if (count($displayColumns) < 3) {
    $fallbackColumns = default_footer_block_data()['columns'] ?? [];
    foreach ($fallbackColumns as $fallbackColumn) {
        if (!is_array($fallbackColumn)) {
            continue;
        }
        $displayColumns[] = $fallbackColumn;
        if (count($displayColumns) >= 3) {
            break;
        }
    }
}
$displayColumns = array_slice($displayColumns, 0, 3);

$fallbackFooterMenus = [
    'company' => [
        ['label' => 'About us', 'href' => public_url('about')],
        ['label' => 'Management', 'href' => public_url('management')],
        ['label' => 'Case Studies', 'href' => public_url('case-studies')],
        ['label' => 'Insights', 'href' => public_url('blog')],
        ['label' => 'FAQs', 'href' => public_url('faq')],
        ['label' => 'Contact', 'href' => public_url('contact')],
    ],
    'services' => [
        ['label' => 'All Services', 'href' => public_url('services')],
        ['label' => 'Business Registration', 'href' => public_url('services', ['category' => 'business-registration'])],
        ['label' => 'Trademark Services', 'href' => public_url('services', ['category' => 'trademark'])],
        ['label' => 'Digital Growth', 'href' => public_url('services', ['category' => 'growth'])],
    ],
    'legal' => [
        ['label' => 'Terms & Conditions', 'href' => public_url('terms')],
        ['label' => 'Privacy Policy', 'href' => public_url('privacy')],
        ['label' => 'Track Order', 'href' => public_url('track')],
        ['label' => 'Portal Login', 'href' => portal_url('login')],
    ],
];

$socialLinks = [
    'youtube' => setting('social_youtube', ''),
    'instagram' => setting('social_instagram', ''),
    'x' => setting('social_x', ''),
    'linkedin' => setting('social_linkedin', ''),
    'facebook' => setting('social_facebook', ''),
    'tiktok' => setting('social_tiktok', ''),
];

$socialIcons = [
    'youtube' => 'bi-youtube',
    'instagram' => 'bi-instagram',
    'x' => 'bi-twitter-x',
    'linkedin' => 'bi-linkedin',
    'facebook' => 'bi-facebook',
    'tiktok' => 'bi-tiktok',
];
?>
  <section class="iw-sitewide-cta" aria-labelledby="iw-sitewide-cta-title">
    <div class="container public-container iw-sitewide-cta-inner">
      <h2 id="iw-sitewide-cta-title">Ready to launch and grow your business?</h2>
      <p>From registration and compliance to websites and digital growth, bring your next step together with iWorks.</p>
      <div class="iw-sitewide-cta-actions">
        <a class="iw-sitewide-cta-primary" href="<?= e(public_url('services')) ?>">Get started <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        <?php if ($footerPhone !== ''): ?>
          <a class="iw-sitewide-cta-secondary" href="tel:<?= e(preg_replace('/[^\d+]/', '', $footerPhone)) ?>"><i class="bi bi-telephone" aria-hidden="true"></i> Call <?= e($footerPhone) ?></a>
        <?php else: ?>
          <a class="iw-sitewide-cta-secondary" href="<?= e(public_url('contact')) ?>"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Talk to our team</a>
        <?php endif; ?>
      </div>
      <ul class="iw-sitewide-cta-assurances">
        <li><i class="bi bi-shield-check" aria-hidden="true"></i> Secure online requests</li>
        <li><i class="bi bi-check-circle" aria-hidden="true"></i> Clear service details</li>
        <li><i class="bi bi-clipboard-check" aria-hidden="true"></i> Track your progress</li>
      </ul>
    </div>
  </section>
  <footer class="public-footer public-footer-light">
    <div class="container public-container">
      <div class="public-footer-shell">
        <div class="public-footer-top-grid">
          <?php foreach ($displayColumns as $column): ?>
            <?php
            $links = is_array($column['links'] ?? null) ? $column['links'] : [];
            $columnTitle = trim((string) ($column['title'] ?? 'Links'));
            $columnKey = strtolower($columnTitle);
            if ($links === []) {
                if (str_contains($columnKey, 'company')) {
                    $links = $fallbackFooterMenus['company'];
                } elseif (str_contains($columnKey, 'service')) {
                    $links = $fallbackFooterMenus['services'];
                } elseif (str_contains($columnKey, 'legal')) {
                    $links = $fallbackFooterMenus['legal'];
                }
            }
            ?>
            <div class="public-footer-col">
              <h4><?= e($columnTitle !== '' ? $columnTitle : 'Links') ?></h4>
              <ul class="list-unstyled">
                <?php foreach ($links as $link): ?>
                  <?php
                  $label = trim((string) ($link['label'] ?? ''));
            $href = normalize_public_href(trim((string) ($link['href'] ?? '#')), '#');
                  if ($label === '') {
                      continue;
                  }
                  ?>
                  <li><a href="<?= e($href) ?>"><?= e($label) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endforeach; ?>
          <div class="public-footer-col public-footer-contact-col">
            <h4>Contact</h4>
            <?php if ($footerEmail !== ''): ?><p><a href="mailto:<?= e($footerEmail) ?>"><?= e($footerEmail) ?></a></p><?php endif; ?>
            <?php if ($footerPhone !== ''): ?><p><a href="tel:<?= e($footerPhone) ?>"><?= e($footerPhone) ?></a></p><?php endif; ?>
            <?php if ($footerAddress !== ''): ?><p><?= e($footerAddress) ?></p><?php endif; ?>
            <?php if ($footerEmail === '' && $footerPhone === '' && $footerAddress === ''): ?>
              <p><a href="<?= e(public_url('contact')) ?>">Contact support</a></p>
            <?php endif; ?>
          </div>
        </div>

        <div class="public-footer-divider"></div>

        <div class="public-footer-brand-row">
          <a class="public-footer-brand-lockup" href="<?= e(public_url('home')) ?>">
            <picture class="iw-brand-picture">
              <source media="(max-width:575.98px)" srcset="<?= e(public_absolute_url('assets/branding/iworks-compact.svg')) ?>?v=20261113-logo1">
              <img src="<?= e(public_absolute_url('assets/branding/iworks-desktop.svg')) ?>?v=20261113-logo1" alt="<?= e($footerCompany) ?>" class="public-footer-logo iw-desktop-logo" width="195" height="18" loading="lazy" decoding="async">
            </picture>
          </a>
          <?php if ($footerSummary !== ''): ?><p class="public-footer-summary"><?= e($footerSummary) ?></p><?php endif; ?>
          <div class="public-footer-socials">
            <?php foreach ($socialLinks as $key => $value): ?>
              <?php if ($value === '') { continue; } ?>
              <a href="<?= e($value) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($key)) ?>">
                <i class="bi <?= e($socialIcons[$key] ?? 'bi-link-45deg') ?>"></i>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="public-footer-divider"></div>

        <div class="public-footer-bottom">
          <span>© <?= date('Y') ?> <?= e($footerCompany) ?>. All rights reserved.</span>
        </div>
      </div>
    </div>
  </footer>

  <?php if (!should_enforce_split_domain() || request_host() === configured_host(portal_base_url())): ?>
  <!-- Only render password/registration forms on the portal origin. -->
  <!-- Same-page authentication: existing portal forms, CSRF and OTP, no iframe. -->
  <dialog class="iw-public-dialog iw-auth-dialog" id="iwPublicAuthDialog" aria-labelledby="iwPublicAuthTitle">
    <div class="iw-public-dialog-head">
      <h2 id="iwPublicAuthTitle">Sign in to iWorks</h2>
      <button type="button" data-iw-dialog-close aria-label="Close account window"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
    <div class="iw-auth-dialog-tabs" role="group" aria-label="Account action">
      <button type="button" data-iw-auth-tab="login" aria-pressed="true">Sign in</button>
      <button type="button" data-iw-auth-tab="register" aria-pressed="false">Create account</button>
    </div>
    <?php $iwEmbeddedPublicAuth = true; ?>
    <div class="iw-auth-dialog-content" id="iwPublicAuthContent">
      <div class="iw-auth-pane" data-iw-auth-pane="login">
        <?php require __DIR__ . '/../login.php'; ?>
      </div>
      <div class="iw-auth-pane" data-iw-auth-pane="register" hidden>
        <?php require __DIR__ . '/../register.php'; ?>
      </div>
    </div>
    <?php unset($iwEmbeddedPublicAuth); ?>
    <p class="iw-auth-dialog-fallback">Having trouble? <a id="iwAuthDirectLink" href="<?= e(portal_url('login')) ?>" data-login-direct="<?= e(portal_url('login')) ?>" data-register-direct="<?= e(portal_url('register')) ?>">Open secure sign in</a>.</p>
  </dialog>

  <?php endif; ?>
  <?php require __DIR__ . '/public-assistance.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      function initPublicHeaderInteractions() {
        var navbar = document.querySelector('.public-navbar');
        if (navbar) {
          var syncScrollState = function () {
            navbar.classList.toggle('scrolled', window.scrollY > 10);
          };
          syncScrollState();
          window.addEventListener('scroll', syncScrollState, { passive: true });
        }

        var navCollapse = document.getElementById('publicNav');
        if (navCollapse) {
          navCollapse.addEventListener('show.bs.collapse', function () {
            document.body.classList.add('public-nav-open');
          });
          navCollapse.addEventListener('hidden.bs.collapse', function () {
            document.body.classList.remove('public-nav-open');
          });

          navCollapse.querySelectorAll('a, .btn').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
              if (window.innerWidth >= 1200 || !navCollapse.classList.contains('show') || !window.bootstrap) {
                return;
              }
              if (
                trigger.hasAttribute('data-public-services-toggle') ||
                (event.target && event.target.closest && event.target.closest('[data-public-services-menu]'))
              ) {
                return;
              }
              var href = (trigger.getAttribute('href') || '').trim();
              if (href === '' || href === '#') {
                return;
              }
              var collapse = window.bootstrap.Collapse.getOrCreateInstance(navCollapse);
              collapse.hide();
            });
          });
        }

        var dropdownWrap = document.querySelector('[data-public-services-dropdown]');
        if (!dropdownWrap) {
          return;
        }

        var toggle = dropdownWrap.querySelector('[data-public-services-toggle]');
        var dropdownMenu = dropdownWrap.querySelector('[data-public-services-menu]');
        if (!toggle || !dropdownMenu) {
          return;
        }
        var hideTimer = null;
        var isDesktop = function () {
          return window.innerWidth >= 1200;
        };

        var clearHide = function () {
          if (!hideTimer) return;
          window.clearTimeout(hideTimer);
          hideTimer = null;
        };

        var openMenu = function () {
          clearHide();
          dropdownWrap.classList.add('is-open');
          toggle.setAttribute('aria-expanded', 'true');
          dropdownMenu.hidden = false;
        };

        var closeMenu = function () {
          clearHide();
          dropdownWrap.classList.remove('is-open');
          toggle.setAttribute('aria-expanded', 'false');
          dropdownMenu.hidden = true;
        };

        var showDesktopDropdown = function () {
          if (!isDesktop()) return;
          openMenu();
        };

        var hideDesktopDropdown = function () {
          if (!isDesktop()) return;
          clearHide();
          hideTimer = window.setTimeout(closeMenu, 140);
        };

        closeMenu();

        toggle.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          if (dropdownWrap.classList.contains('is-open')) {
            closeMenu();
            return;
          }
          openMenu();
        });

        dropdownWrap.addEventListener('mouseenter', showDesktopDropdown);
        dropdownWrap.addEventListener('mouseleave', hideDesktopDropdown);

        dropdownMenu.querySelectorAll('a').forEach(function (link) {
          link.addEventListener('click', function () {
            closeMenu();
          });
        });

        document.addEventListener('click', function (event) {
          if (!dropdownWrap.contains(event.target)) {
            closeMenu();
          }
        });

        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape') {
            closeMenu();
          }
        });

        window.addEventListener('resize', function () {
          closeMenu();
        });

        if (navCollapse) {
          navCollapse.addEventListener('hidden.bs.collapse', function () {
            closeMenu();
          });
        }
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPublicHeaderInteractions);
      } else {
        initPublicHeaderInteractions();
      }
    })();
  </script>
  <script>
    (function () {
      if (!('IntersectionObserver' in window) || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) return;
      var targets = document.querySelectorAll('.iw-journey-card, .iw-about-bridge-inner, .iw-about-story-grid, .iw-expertise-grid > a, .launch-work-card');
      if (!targets.length) return;
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('iw-reveal-visible');
          observer.unobserve(entry.target);
        });
      }, { threshold: 0.07 });
      targets.forEach(function (element) {
        element.classList.add('iw-reveal-pending');
        observer.observe(element);
      });
    })();
  </script>
<script>
(function () {
  'use strict';
  var auth = document.getElementById('iwPublicAuthDialog');
  var direct = document.getElementById('iwAuthDirectLink');
  var content = document.getElementById('iwPublicAuthContent');
  var tabs = document.querySelectorAll('[data-iw-auth-tab]');
  var currentTrigger = null;
  function setAuthMode(mode) {
    if (!auth || !direct) return;
    mode = mode === 'register' ? 'register' : 'login';
    auth.querySelectorAll('[data-iw-auth-pane]').forEach(function (pane) {
      pane.hidden = pane.getAttribute('data-iw-auth-pane') !== mode;
    });
    direct.href = direct.getAttribute('data-' + mode + '-direct');
    direct.textContent = mode === 'login' ? 'Open secure sign in' : 'Open registration';
    tabs.forEach(function (tab) {
      tab.setAttribute('aria-pressed', tab.getAttribute('data-iw-auth-tab') === mode ? 'true' : 'false');
    });
    var heading = document.getElementById('iwPublicAuthTitle');
    if (heading) heading.textContent = mode === 'login' ? 'Sign in to iWorks' : 'Create an account';
    if (content) {
      content.classList.remove('is-flipping');
      void content.offsetWidth;
      content.classList.add('is-flipping');
    }
  }
  function show(dialog, trigger) {
    if (!dialog || typeof dialog.showModal !== 'function') return false;
    currentTrigger = trigger || null;
    if (!dialog.open) dialog.showModal();
    return true;
  }
  document.addEventListener('click', function (event) {
    var authTrigger = event.target.closest('[data-iw-auth]');
    if (authTrigger) {
      if (!auth || typeof auth.showModal !== 'function') return;
      // Public and portal can be configured on distinct origins. Do not use
      // the public-origin CSRF session to POST credentials across origins.
      try {
        if (new URL(authTrigger.href, window.location.href).origin !== window.location.origin) return;
      } catch (error) { return; }
      event.preventDefault();
      setAuthMode(authTrigger.getAttribute('data-iw-auth'));
      show(auth, authTrigger);
      return;
    }
    var authSwitch = event.target.closest('.iw-auth-dialog .iw-auth-switch a');
    if (authSwitch) {
      event.preventDefault();
      var current = authSwitch.closest('[data-iw-auth-pane]');
      setAuthMode(current && current.getAttribute('data-iw-auth-pane') === 'login' ? 'register' : 'login');
      return;
    }
    var tab = event.target.closest('[data-iw-auth-tab]');
    if (tab) { setAuthMode(tab.getAttribute('data-iw-auth-tab')); return; }
    var close = event.target.closest('[data-iw-dialog-close]');
    if (close) {
      var parent = close.closest('dialog');
      if (parent) parent.close();
    }
  });
  [auth].forEach(function (dialog) {
    if (!dialog) return;
    dialog.addEventListener('click', function (event) { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', function () {
      if (currentTrigger && typeof currentTrigger.focus === 'function') currentTrigger.focus();
    });
  });
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var targets = document.querySelectorAll('.iw-clean-service, .iw-clean-step, .iw-about-min-story, .iw-clean-support');
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('iw-polish-in');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.08 });
    targets.forEach(function (target) {
      target.classList.add('iw-polish-ready');
      observer.observe(target);
    });
  }
})();
</script>
<button type="button" class="iw-back-to-top" data-iw-to-top aria-label="Back to top" title="Back to top"><i class="bi bi-arrow-up"></i></button>
<script>
(function(){
  var links=document.querySelectorAll('[data-iw-auth-status]');
  if(links.length){
    fetch(<?= json_encode(portal_url('public_auth_status')) ?>,{credentials:'include',mode:'cors',cache:'no-store'})
      .then(function(r){return r.ok?r.json():null})
      .then(function(data){
        if(!data||!data.authenticated)return;
        links.forEach(function(link){
          link.href=<?= json_encode(portal_url('dashboard')) ?>;
          link.removeAttribute('data-iw-auth');
          link.title='Open your dashboard';
          link.setAttribute('aria-label','Open your iWorks dashboard');
          link.innerHTML='<i class="bi bi-person-circle" aria-hidden="true"></i><span>Dashboard</span>';
          link.classList.add('iw-public-account-active');
        });
        // The portal auth-status endpoint is the authority across the split
        // public/portal domains. Hide only menu onboarding actions for users
        // with an active session; keep the Dashboard link visible.
        document.querySelectorAll('[data-iw-guest-cta]').forEach(function(cta){
          cta.hidden=true;
        });
      }).catch(function(){});
  }
  var top=document.querySelector('[data-iw-to-top]');
  if(top){
    function refresh(){top.classList.toggle('iw-visible',window.scrollY>480);}
    window.addEventListener('scroll',refresh,{passive:true});
    refresh();
    top.addEventListener('click',function(){
      window.scrollTo({top:0,behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});
    });
  }
})();
</script>
<script defer src="<?= e(public_absolute_url('assets/js/public-ux.js')) ?>?v=20261112b"></script>
<?php
  // Serve the small widget controller from the PHP app when available.
  // This prevents a silent, unclickable launcher when cPanel's public
  // document root doesn't expose the portal's assets/js directory.
  $iwAssistJsPath = __DIR__ . '/../../assets/js/public-assistance.js';
  if (is_file($iwAssistJsPath) && is_readable($iwAssistJsPath)): ?>
  <script id="iw-assist-controller"><?= str_ireplace('</script', '<\\/script', (string) file_get_contents($iwAssistJsPath)) ?></script>
<?php else: ?>
  <script defer src="<?= e(public_absolute_url('assets/js/public-assistance.js')) ?>?v=20261114-compact2"></script>
<?php endif; ?>
</body>
</html>
