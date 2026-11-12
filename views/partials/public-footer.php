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
            <?php if ($footerLogo !== ''): ?>
              <img src="<?= e($footerLogo) ?>" alt="<?= e($footerCompany) ?>" class="public-footer-logo" onerror="this.style.display='none'">
            <?php else: ?>
              <span class="public-footer-brand-text"><?= e($footerCompany) ?></span>
            <?php endif; ?>
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
</body>
</html>
