<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$page = $_GET['page'] ?? null;
$requestedPrettyPublicPage = false;

if ($page === null) {
    $rawPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    $scriptDir = rtrim($scriptDir, '/');
    if ($scriptDir !== '' && $scriptDir !== '.' && str_starts_with($rawPath, $scriptDir)) {
        $rawPath = (string) substr($rawPath, strlen($scriptDir));
    }
    $rawPath = trim($rawPath, '/');
    if ($rawPath !== '') {
        if ($rawPath === 'blog' || $rawPath === 'insights') {
            $page = 'blog';
            $requestedPrettyPublicPage = true;
        } elseif (preg_match('~^(?:blog|insights)/([^/]+)$~', $rawPath, $matches)) {
            $page = 'blog_post';
            $_GET['slug'] = urldecode((string) ($matches[1] ?? ''));
            $requestedPrettyPublicPage = true;
        } elseif (in_array($rawPath, ['packages', 'start-now', 'startnow'], true)) {
            $page = 'services';
            $requestedPrettyPublicPage = true;
        } elseif ($rawPath === 'services') {
            $page = 'services';
            $requestedPrettyPublicPage = true;
        } elseif (preg_match('~^services/([^/]+)$~', $rawPath, $matches)) {
            $page = 'service';
            $_GET['slug'] = urldecode((string) ($matches[1] ?? ''));
            $requestedPrettyPublicPage = true;
        } elseif (in_array($rawPath, ['home', 'management', 'about', 'case-studies', 'pricing', 'track', 'faq', 'contact', 'terms', 'privacy', 'login'], true)) {
            $page = $rawPath;
            $requestedPrettyPublicPage = true;
        }
    }
}

if (!is_configured() && $page !== 'setup') {
    redirect('setup.php');
}

$title = app_name();

// Crawlers receive only published public URLs; no account, job or payment data.
// Search discovery must be served on the marketing domain, not duplicated
// on the customer application's hostname.
if (in_array($page, ['seo_sitemap', 'seo_robots'], true)
    && should_enforce_split_domain()
    && request_host() !== configured_host(public_base_url())) {
    header('Location: ' . public_absolute_url($page === 'seo_sitemap' ? 'sitemap.xml' : 'robots.txt'), true, 301);
    exit;
}
if ($page === 'seo_sitemap') {
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: public, max-age=1800');
    echo iworks_seo_public_sitemap();
    exit;
}
if ($page === 'seo_robots') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=1800');
    echo iworks_seo_robots_text();
    exit;
}

if (!function_exists('safe_internal_return_url')) {
    function safe_internal_return_url(string $candidate, string $fallback): string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return $fallback;
        }

        if (str_contains($candidate, "\n") || str_contains($candidate, "\r")) {
            return $fallback;
        }

        if (preg_match('~^(?:[a-z][a-z0-9+.-]*:)?//~i', $candidate)) {
            return $fallback;
        }

        if (str_starts_with($candidate, '/')) {
            return $candidate;
        }

        if (str_starts_with($candidate, '?')) {
            return 'index.php' . $candidate;
        }

        if (str_starts_with($candidate, 'index.php')) {
            return $candidate;
        }

        return $fallback;
    }
}

try {
    if ($page === null) {
        // www.iworksgt.com serves the public index even if a session cookie
        // is present. portal.iworksgt.com opens the application itself.
        if (should_enforce_split_domain() && request_host() === configured_host(public_base_url())) {
            $page = 'home';
        } elseif (should_enforce_split_domain() && request_host() === configured_host(portal_base_url())) {
            $page = is_logged_in() ? 'dashboard' : 'login';
        } else {
            $page = is_logged_in() ? 'dashboard' : 'home';
        }
    }

    $publicPageSlugs = ['home', 'management', 'about', 'case-studies', 'services', 'pricing', 'track', 'faq', 'contact', 'terms', 'privacy', 'service', 'blog', 'blog_post'];

    if (should_enforce_split_domain()) {
        $requestHost = request_host();
        $publicHost = configured_host(public_base_url());
        $portalHost = configured_host(portal_base_url());

        if (in_array($page, $publicPageSlugs, true) && $publicHost !== '' && $requestHost !== $publicHost) {
            if ($page === 'service') {
                $target = public_url('service', ['slug' => (string) ($_GET['slug'] ?? '')] + current_query_params_without_page(['slug']));
            } elseif ($page === 'blog_post') {
                $target = public_url('blog_post', ['slug' => (string) ($_GET['slug'] ?? '')] + current_query_params_without_page(['slug']));
            } else {
                $target = public_url($page, current_query_params_without_page());
            }

            header('Location: ' . $target, true, 301);
            exit;
        }

        if (!in_array($page, $publicPageSlugs, true) && $portalHost !== '' && $requestHost !== $portalHost) {
            redirect(portal_url($page, current_query_params_without_page()));
        }
    }

    // Keep portal accounts, invoices, OAuth callbacks and dashboard URLs
    // out of public search results. Published marketing URLs live on apex.
    if (should_enforce_split_domain()
        && request_host() === configured_host(portal_base_url())
        && !in_array($page, $publicPageSlugs, true)) {
        header('X-Robots-Tag: noindex, nofollow');
    }

    if (is_logged_in()) {
        $mustChangeUser = current_user();
        if (
            $mustChangeUser
            && (int) ($mustChangeUser['must_change_password'] ?? 0) === 1
            && !in_array($page, ['profile', 'realtime', 'set_pay_currency'], true)
        ) {
            flash('error', 'You must change your temporary password before continuing.');
            redirect(url('profile', ['force_password' => '1']));
        }
    }

    if (in_array($page, $publicPageSlugs, true)
        && (!is_logged_in() || $requestedPrettyPublicPage
            || (should_enforce_split_domain() && request_host() === configured_host(public_base_url())))) {
        $publicSlug = $page === 'service' ? trim((string) ($_GET['slug'] ?? '')) : $page;
        if ($page === 'service') {
            $service = get_service_by_slug($publicSlug);
            if (!$service) {
                http_response_code(404);
                $title = 'Not Found - ' . app_name();
                $pageSeo = [];
                $publicHeaderConfig = get_public_header_config();
                $publicServices = list_public_services_catalog();
                $publicServiceCategories = list_public_service_categories();
                $publicFooterData = get_public_footer_data('home');
                require __DIR__ . '/views/partials/public-header.php';
                require __DIR__ . '/views/public-404.php';
                require __DIR__ . '/views/partials/public-footer.php';
                return;
            }
            $title = ($service['name'] ?? 'Service') . ' - ' . app_name();
            $pageSeo = [
                'metaTitle' => ($service['name'] ?? 'Service') . ' - ' . brand_name(),
                'metaDescription' => trim((string) strip_tags((string) ($service['description'] ?? ''))),
                'ogImage' => '',
            ];
            $publicHeaderConfig = get_public_header_config();
            $publicServices = list_public_services_catalog();
            $publicServiceCategories = list_public_service_categories();
            $publicFooterData = get_public_footer_data('services');
            require __DIR__ . '/views/partials/public-header.php';
            require __DIR__ . '/views/public-service-detail.php';
            require __DIR__ . '/views/partials/public-footer.php';
            return;
        }

        if ($page === 'blog_post') {
            $blogSlug = trim((string) ($_GET['slug'] ?? ''));
            $blogPost = get_blog_post_public_by_slug($blogSlug);
            if (!$blogPost) {
                http_response_code(404);
                $title = 'Not Found - ' . app_name();
                $pageSeo = [];
                $publicHeaderConfig = get_public_header_config();
                $publicServices = list_public_services_catalog();
                $publicServiceCategories = list_public_service_categories();
                $publicFooterData = get_public_footer_data('home');
                require __DIR__ . '/views/partials/public-header.php';
                require __DIR__ . '/views/public-404.php';
                require __DIR__ . '/views/partials/public-footer.php';
                return;
            }

            $title = ($blogPost['title'] ?? 'Insight') . ' - ' . app_name();
            $pageSeo = [
                'metaTitle' => ($blogPost['title'] ?? 'Insight') . ' - ' . brand_name(),
                'metaDescription' => trim((string) ($blogPost['excerpt'] ?? '')),
                'ogImage' => trim((string) ($blogPost['featured_image']['url'] ?? '')),
            ];
            $publicHeaderConfig = get_public_header_config();
            $publicServices = list_public_services_catalog();
            $publicServiceCategories = list_public_service_categories();
            $publicPageSlug = 'blog_post';
            $publicFooterData = get_public_footer_data('blog');
            require __DIR__ . '/views/partials/public-header.php';
            require __DIR__ . '/views/public-blog-detail.php';
            require __DIR__ . '/views/partials/public-footer.php';
            return;
        }

        $pageData = get_page_published_by_slug($publicSlug);
        if (!$pageData) {
            http_response_code(404);
            $title = 'Not Found - ' . app_name();
            $pageSeo = [];
            $publicFooterData = get_public_footer_data('home');
            require __DIR__ . '/views/partials/public-header.php';
            require __DIR__ . '/views/public-404.php';
            require __DIR__ . '/views/partials/public-footer.php';
            return;
        }

        $publicServices = list_public_services_catalog();
        $publicServiceCategories = list_public_service_categories();
        $publicHeaderConfig = get_public_header_config();
        $title = ((string) ($pageData['title'] ?? 'Page')) . ' - ' . app_name();
        $pageSeo = is_array($pageData['seo'] ?? null) ? $pageData['seo'] : [];
        $publicPageSlug = $publicSlug;
        $publicFooterData = extract_footer_from_layout(is_array($pageData['layout'] ?? null) ? $pageData['layout'] : [])
            ?? get_public_footer_data('home');

        if (method_is('POST') && (string) ($_POST['form_action'] ?? '') === 'website_lead') {
            require_csrf();
            $isAsyncLead = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
            try {
                save_website_lead([
                    'full_name' => (string) ($_POST['full_name'] ?? ''),
                    'email' => (string) ($_POST['email'] ?? ''),
                    'phone' => (string) ($_POST['phone'] ?? ''),
                    'company_name' => (string) ($_POST['company_name'] ?? ''),
                    'message' => (string) ($_POST['message'] ?? ''),
                    'page_slug' => $publicSlug,
                ]);
                $message = 'Your enquiry has been sent. Our team will respond as soon as possible.';
                if ($isAsyncLead) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => true, 'message' => $message]);
                    exit;
                }
                flash('success', $message);
            } catch (Throwable $e) {
                if ($isAsyncLead) {
                    http_response_code(422);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => false, 'message' => 'We could not send your message. Check the details and try again.']);
                    exit;
                }
                flash('error', $e->getMessage());
            }
            redirect(public_url($publicSlug));
        }

        if (in_array($publicSlug, ['track', 'home'], true)
            && method_is('POST')
            && ($publicSlug === 'track' || (string) ($_POST['form_action'] ?? '') === 'public_track')) {
            require_csrf();
            $jobCode = trim((string) ($_POST['job_code'] ?? ''));
            $identifier = trim((string) ($_POST['identifier'] ?? ''));
            $publicTrackResult = null;
            if ($jobCode !== '' && $identifier !== '') {
                $publicTrackResult = find_public_job_track($jobCode, $identifier);
                if (!$publicTrackResult) {
                    flash('error', 'No job found with that ID and contact.');
                }
            } else {
                flash('error', 'Enter your Job ID and email or phone.');
            }
        }

        require __DIR__ . '/views/partials/public-header.php';
        require __DIR__ . '/views/public-page.php';
        require __DIR__ . '/views/partials/public-footer.php';
        return;
    }

    // The selected public service must survive the portal's authentication
    // lifecycle, including redirecting through Google's separate origin.
    if (in_array($page, ['login', 'register', 'google_start'], true) && !method_is('POST')) {
        iworks_order_intent_capture($_GET);
    }

    switch ($page) {
        case 'login':
            if (is_logged_in()) {
                redirect(iworks_post_login_destination(current_user()));
            }

            if (!method_is('POST')) {
                clear_pending_otp();
                unset($_SESSION['pending_otp_remember']);
            }

            if (method_is('POST')) {
                require_csrf();
                $email = trim((string) ($_POST['email'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $remember30Days = !empty($_POST['remember_30days']);

                $user = authenticate_with_password($email, $password);
                if (!$user) {
                    flash('error', 'Invalid login details.');
                } else {
                    if (!is_email_verified($user)) {
                        if (!email_enabled()) {
                            login_user((int) $user['id']);
                            mark_email_verified((int) $user['id']);
                            flash('success', 'Welcome back. Email OTP is temporarily disabled until mail settings are enabled.');
                            redirect(iworks_post_login_destination(current_user()));
                        }

                        start_otp_challenge((int) $user['id'], 'verify_email');
                        $_SESSION['pending_otp_remember'] = $remember30Days ? '1' : '0';
                        flash('success', 'Enter the 6-digit OTP sent to your email to verify your account.');
                        redirect(url('otp'));
                    }

                    if (has_valid_trusted_device((int) $user['id'])) {
                        login_user((int) $user['id']);
                        flash('success', 'Welcome back.');
                        redirect(iworks_post_login_destination(current_user()));
                    }

                    if (!email_enabled()) {
                        login_user((int) $user['id']);
                        flash('success', 'Welcome back. Email OTP is temporarily disabled until mail settings are enabled.');
                        redirect(iworks_post_login_destination(current_user()));
                    }

                    start_otp_challenge((int) $user['id'], 'login');
                    $_SESSION['pending_otp_remember'] = $remember30Days ? '1' : '0';
                    flash('success', 'Enter the 6-digit OTP sent to your email.');
                    redirect(url('otp'));
                }
            }

            $title = 'Login - ' . app_name();
            $embeddedAuth = !method_is('POST') && (string) ($_GET['embed'] ?? '') === '1';
            require __DIR__ . ($embeddedAuth ? '/views/partials/auth-embed-header.php' : '/views/partials/header.php');
            require __DIR__ . '/views/login.php';
            require __DIR__ . ($embeddedAuth ? '/views/partials/auth-embed-footer.php' : '/views/partials/footer.php');
            break;

        case 'frontend':
            require_roles(['admin']);
            $slug = trim((string) ($_GET['slug'] ?? 'home'));

            if (method_is('POST')) {
                require_csrf();
                try {
                    $action = (string) ($_POST['form_action'] ?? '');
                    $userId = (int) current_user()['id'];

                    if ($action === 'save_page_draft') {
                        $slug = trim((string) ($_POST['slug'] ?? 'home'));
                        $title = trim((string) ($_POST['title'] ?? ''));
                        if ($title === '') {
                            throw new RuntimeException('Title is required.');
                        }

                        $layout = is_array($_POST['blocks'] ?? null) ? $_POST['blocks'] : [];
                        $layout = array_values(array_map(static function ($block) {
                            $type = (string) ($block['type'] ?? '');
                            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
                            return ['type' => $type, 'data' => $data];
                        }, $layout));

                        $seo = [
                            'metaTitle' => trim((string) ($_POST['seo_meta_title'] ?? '')),
                            'metaDescription' => trim((string) ($_POST['seo_meta_description'] ?? '')),
                            'ogImage' => trim((string) ($_POST['seo_og_image'] ?? '')),
                        ];

                        save_page_draft($slug, $title, $layout, $seo, $userId);
                        flash('success', 'Draft saved.');
                    }

                    if ($action === 'publish_page') {
                        $slug = trim((string) ($_POST['slug'] ?? 'home'));
                        $label = trim((string) ($_POST['revision_label'] ?? ''));
                        publish_page($slug, $userId, $label);
                        flash('success', 'Page published.');
                    }

                    if ($action === 'rollback_page') {
                        $slug = trim((string) ($_POST['slug'] ?? 'home'));
                        $revisionId = (int) ($_POST['revision_id'] ?? 0);
                        rollback_page_revision($slug, $revisionId, $userId);
                        flash('success', 'Page rolled back and published.');
                    }

                    if ($action === 'upload_media') {
                        $files = normalize_uploaded_files($_FILES['media_files'] ?? null);
                        if (!$files) {
                            $single = $_FILES['media_file'] ?? null;
                            if (is_array($single)) {
                                $files = [$single];
                            }
                        }
                        if (!$files) {
                            throw new RuntimeException('Select a media file.');
                        }
                        $uploaded = 0;
                        foreach ($files as $file) {
                            store_media_asset($userId, $file);
                            $uploaded++;
                        }
                        flash('success', $uploaded > 1 ? 'Media files uploaded.' : 'Media uploaded.');
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }

                redirect(url('frontend', ['slug' => $slug]));
            }

            $pages = list_pages_admin();
            $pageData = get_page_admin_by_slug($slug);
            if (!$pageData) {
            $slug = 'home';
            $pageData = get_page_admin_by_slug($slug);
        }
        $mediaAssets = list_media_assets(200);
        $pageRevisions = $pageData ? list_page_revisions((string) $pageData['id'], 10) : [];
        $websiteLeads = list_website_leads(20);
        $title = 'Frontend Editor - ' . app_name();
        require __DIR__ . '/views/partials/header.php';
        require __DIR__ . '/views/frontend-editor.php';
        require __DIR__ . '/views/partials/footer.php';
        break;

        case 'frontend_preview':
            require_roles(['admin']);
            $slug = trim((string) ($_GET['slug'] ?? 'home'));
            $pageData = get_page_admin_by_slug($slug);
            if (!$pageData) {
                http_response_code(404);
                $title = 'Not Found - ' . app_name();
                $pageSeo = [];
                $publicFooterData = get_public_footer_data('home');
                require __DIR__ . '/views/partials/public-header.php';
                require __DIR__ . '/views/public-404.php';
                require __DIR__ . '/views/partials/public-footer.php';
                break;
            }
            $publicServices = list_public_services_catalog();
            $publicServiceCategories = list_public_service_categories();
            $publicHeaderConfig = get_public_header_config();
            $title = ((string) ($pageData['title'] ?? 'Page')) . ' - ' . app_name();
            $pageSeo = is_array($pageData['seo'] ?? null) ? $pageData['seo'] : [];
            $pageData['layout'] = normalize_page_layout(page_decode_json((string) ($pageData['layout_json'] ?? '')));
            $publicPageSlug = $slug;
            $publicFooterData = extract_footer_from_layout(is_array($pageData['layout'] ?? null) ? $pageData['layout'] : [])
                ?? get_public_footer_data('home');
            require __DIR__ . '/views/partials/public-header.php';
            require __DIR__ . '/views/public-page.php';
            require __DIR__ . '/views/partials/public-footer.php';
            break;

        case 'media':
            require_roles(['admin', 'agent']);

            if (method_is('POST')) {
                require_csrf();

                $redirectTo = safe_internal_return_url((string) ($_POST['return_to'] ?? ''), url('media'));

                try {
                    $action = (string) ($_POST['form_action'] ?? '');
                    if ($action === 'upload_media') {
                        $files = normalize_uploaded_files($_FILES['media_files'] ?? null);
                        if (!$files) {
                            $single = $_FILES['media_file'] ?? null;
                            if (is_array($single)) {
                                $files = [$single];
                            }
                        }
                        if (!$files) {
                            throw new RuntimeException('Select a media file.');
                        }
                        $uploaded = 0;
                        foreach ($files as $file) {
                            store_media_asset((int) current_user()['id'], $file);
                            $uploaded++;
                        }
                        flash('success', $uploaded > 1 ? 'Media files uploaded.' : 'Media uploaded.');
                    }

                    if ($action === 'delete_media') {
                        if (!has_role(['admin'])) {
                            throw new RuntimeException('Only admins can delete media files.');
                        }
                        delete_media_asset((int) ($_POST['media_id'] ?? 0));
                        flash('success', 'Media file deleted.');
                    }

                    if ($action === 'rename_media') {
                        if (!has_role(['admin'])) {
                            throw new RuntimeException('Only admins can rename media files.');
                        }
                        update_media_label((int) ($_POST['media_id'] ?? 0), (string) ($_POST['media_label'] ?? ''));
                        flash('success', 'Media name updated.');
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }

                redirect($redirectTo);
            }

            $mediaAssets = list_media_assets(500);
            $title = 'Media Library - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/media-library.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'blog':
            require_roles(['admin', 'agent']);
            $user = current_user();
            $isAdminBlog = has_role(['admin']);
            $blogAction = strtolower(trim((string) ($_GET['action'] ?? '')));
            $editBlogPost = null;

            if (method_is('POST')) {
                require_csrf();

                try {
                    $action = (string) ($_POST['form_action'] ?? '');

                    if ($action === 'save_blog_post') {
                        $savedId = save_blog_post(
                            $_POST,
                            (int) $user['id'],
                            $isAdminBlog,
                            is_array($_FILES['featured_image_upload'] ?? null) ? $_FILES['featured_image_upload'] : null
                        );
                        flash('success', 'Insight saved.');
                        redirect(url('blog', ['action' => 'edit', 'id' => $savedId]));
                    }

                    if ($action === 'delete_blog_post') {
                        delete_blog_post((int) ($_POST['post_id'] ?? 0), (int) $user['id'], $isAdminBlog);
                        flash('success', 'Insight deleted.');
                        redirect(url('blog'));
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                    $redirectParams = [];
                    if (!empty($_POST['post_id'])) {
                        $redirectParams = [
                            'action' => 'edit',
                            'id' => (int) $_POST['post_id'],
                        ];
                    }
                    redirect(url('blog', $redirectParams));
                }
            }

            if ($blogAction === 'edit' && !empty($_GET['id'])) {
                $editBlogPost = get_blog_post_manage((int) $_GET['id'], (int) $user['id'], $isAdminBlog);
                if (!$editBlogPost) {
                    flash('error', 'Insight not found or access denied.');
                    redirect(url('blog'));
                }
            }

            $blogPosts = list_blog_posts_manage((int) $user['id'], $isAdminBlog);
            $mediaAssets = list_media_assets(200);
            $title = 'Insights - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/blog.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'google_start':
            if (is_logged_in()) {
                redirect(iworks_post_login_destination(current_user()));
            }

            try {
                redirect(google_oauth_authorization_url());
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                redirect(url('login'));
            }
            break;

        case 'google_callback':
            if (is_logged_in()) {
                redirect(iworks_post_login_destination(current_user()));
            }

            $oauthError = trim((string) ($_GET['error'] ?? ''));
            if ($oauthError !== '') {
                flash('error', 'Google sign-in failed: ' . str_replace('_', ' ', $oauthError) . '.');
                redirect(url('login'));
            }

            $state = trim((string) ($_GET['state'] ?? ''));
            $code = trim((string) ($_GET['code'] ?? ''));

            try {
                if (!consume_google_oauth_state($state)) {
                    throw new RuntimeException('Invalid Google sign-in state. Please try again.');
                }

                $user = authenticate_with_google_code($code);
                login_user((int) $user['id']);
                remember_trusted_device((int) $user['id']);

                flash('success', 'Signed in with Google successfully.');
                redirect(iworks_post_login_destination(current_user()));
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                redirect(url('login'));
            }
            break;

        case 'forgot_password':
            if (is_logged_in()) {
                redirect(url('dashboard'));
            }

            if (method_is('POST')) {
                require_csrf();
                $email = trim((string) ($_POST['email'] ?? ''));
                if ($email !== '') {
                    request_password_reset_by_email($email);
                }
                flash('success', 'If that email exists, a password reset link has been sent.');
                redirect(url('forgot_password'));
            }

            $title = 'Forgot Password - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/forgot-password.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'set_pay_currency':
            require_login();
            if (!method_is('POST')) {
                redirect(url('dashboard'));
            }

            require_csrf();
            $currency = normalize_payment_currency((string) ($_POST['pay_currency'] ?? ''));
            set_active_pay_currency($currency);

            $redirectTo = trim((string) ($_POST['redirect_to'] ?? ''));
            if ($redirectTo === '' || str_contains($redirectTo, 'setup.php')) {
                $redirectTo = url('dashboard');
            }

            if (!preg_match('~^(index\.php|invoice\.php)~', $redirectTo)) {
                $redirectTo = url('dashboard');
            }

            if (strtolower(trim((string) ($_POST['ajax'] ?? ''))) === '1') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'pay_currency' => $currency,
                ], JSON_UNESCAPED_SLASHES);
                break;
            }

            redirect($redirectTo);
            break;

        case 'reset_password':
            if (is_logged_in()) {
                redirect(url('dashboard'));
            }

            $resetToken = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
            if ($resetToken === '') {
                throw new RuntimeException('Invalid password reset request.');
            }

            if (method_is('POST')) {
                require_csrf();

                try {
                    reset_password_with_token(
                        $resetToken,
                        (string) ($_POST['new_password'] ?? ''),
                        (string) ($_POST['confirm_password'] ?? '')
                    );

                    flash('success', 'Password reset successful. You can now login.');
                    redirect(url('login'));
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                    redirect(url('reset_password', ['token' => $resetToken]));
                }
            }

            $title = 'Reset Password - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/reset-password.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'register':
            if (is_logged_in()) {
                redirect(iworks_post_login_destination(current_user()));
            }

            if (method_is('POST')) {
                require_csrf();
                $fullName = trim($_POST['full_name'] ?? '');
                $firstName = trim((string) ($_POST['first_name'] ?? ''));
                $lastName = trim((string) ($_POST['last_name'] ?? ''));
                $email = trim($_POST['email'] ?? '');
                $phone = trim((string) ($_POST['phone'] ?? ''));
                $country = trim((string) ($_POST['country'] ?? ''));
                $stateRegion = trim((string) ($_POST['state_region'] ?? ''));
                $city = trim((string) ($_POST['city'] ?? ''));
                $addressLine = trim((string) ($_POST['address_line'] ?? ''));
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if ($fullName === '' && ($firstName === '' || $lastName === '')) {
                    flash('error', 'Provide first name and last name.');
                } elseif ($email === '' || strlen($password) < 6) {
                    flash('error', 'Fill all required fields. Password must be at least 6 characters.');
                } elseif ($password !== $confirmPassword) {
                    flash('error', 'Password and confirm password do not match.');
                } elseif ($phone === '' || $country === '' || $stateRegion === '' || $city === '' || $addressLine === '') {
                    flash('error', 'Phone, country, state/region, city, and address are required.');
                } elseif (find_user_by_email($email)) {
                    flash('error', 'This email already exists.');
                } else {
                    $gender = trim((string) ($_POST['gender'] ?? ''));
                    if ($gender !== '' && !in_array($gender, ['male', 'female', 'other', 'prefer_not_say'], true)) {
                        $gender = '';
                    }

                    $dob = trim((string) ($_POST['date_of_birth'] ?? ''));
                    if ($dob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                        flash('error', 'Date of birth must be in YYYY-MM-DD format.');
                        $title = 'Register - ' . app_name();
                        require __DIR__ . '/views/partials/header.php';
                        require __DIR__ . '/views/register.php';
                        require __DIR__ . '/views/partials/footer.php';
                        break;
                    }

                    $userId = create_user([
                        'full_name' => $fullName !== '' ? $fullName : trim($firstName . ' ' . $lastName),
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => $phone,
                        'gender' => $gender,
                        'date_of_birth' => $dob,
                        'country' => $country,
                        'state_region' => $stateRegion,
                        'city' => $city,
                        'address_line' => $addressLine,
                        'occupation' => trim((string) ($_POST['occupation'] ?? '')),
                        'password' => $password,
                        'role' => 'client',
                    ]);

                    if (!email_enabled()) {
                        mark_email_verified($userId);
                        login_user($userId);
                        flash('success', 'Account created successfully. Email OTP will activate after mail setup is enabled.');
                        redirect(iworks_post_login_destination(current_user()));
                    }

                    $_SESSION['pending_otp_remember'] = !empty($_POST['remember_30days']) ? '1' : '0';
                    start_otp_challenge($userId, 'verify_email');
                    flash('success', 'Account created. Enter the 6-digit OTP sent to your email to continue.');
                    redirect(url('otp'));
                }
            }

            $title = 'Register - ' . app_name();
            $embeddedAuth = !method_is('POST') && (string) ($_GET['embed'] ?? '') === '1';
            require __DIR__ . ($embeddedAuth ? '/views/partials/auth-embed-header.php' : '/views/partials/header.php');
            require __DIR__ . '/views/register.php';
            require __DIR__ . ($embeddedAuth ? '/views/partials/auth-embed-footer.php' : '/views/partials/footer.php');
            break;

        case 'otp':
            if (is_logged_in()) {
                redirect(iworks_post_login_destination(current_user()));
            }

            $pendingOtp = pending_otp_context();
            if (!$pendingOtp) {
                flash('error', 'OTP session not found. Please login again.');
                redirect(url('login'));
            }

            $otpUser = find_user_by_id((int) $pendingOtp['user_id']);
            if (!$otpUser) {
                clear_pending_otp();
                unset($_SESSION['pending_otp_remember']);
                flash('error', 'Your OTP session has expired. Please login again.');
                redirect(url('login'));
            }

            if (method_is('POST')) {
                require_csrf();
                $action = $_POST['form_action'] ?? '';

                try {
                    if ($action === 'resend_otp') {
                        if (!email_enabled()) {
                            throw new RuntimeException('Email delivery is currently disabled. Enable mail in admin settings.');
                        }

                        $cooldownRemaining = otp_resend_cooldown_remaining((int) $otpUser['id'], (string) $pendingOtp['purpose']);
                        if ($cooldownRemaining > 0) {
                            throw new RuntimeException('Please wait ' . $cooldownRemaining . ' second(s) before requesting another OTP.');
                        }

                        start_otp_challenge((int) $otpUser['id'], (string) $pendingOtp['purpose']);
                        flash('success', 'A new OTP code was queued for delivery.');
                        redirect(url('otp'));
                    }

                    if ($action === 'fallback_otp') {
                        if (!email_enabled()) {
                            throw new RuntimeException('Email delivery is currently disabled. Enable mail in admin settings.');
                        }

                        if (!otp_delivery_fallback_allowed((int) $otpUser['id'], (string) $pendingOtp['purpose'])) {
                            throw new RuntimeException('Fallback is only available when queue delivery is delayed.');
                        }

                        if (!send_latest_otp_via_fallback((int) $otpUser['id'], (string) $pendingOtp['purpose'])) {
                            throw new RuntimeException('Fallback delivery failed. Please try resend again.');
                        }

                        flash('success', 'OTP sent via fallback delivery.');
                        redirect(url('otp'));
                    }

                    if ($action === 'verify_otp') {
                        $otpCode = preg_replace('/\D+/', '', (string) ($_POST['otp_code'] ?? ''));
                        if (!is_string($otpCode) || strlen($otpCode) !== 6) {
                            throw new RuntimeException('Enter a valid 6-digit OTP code.');
                        }

                        if (!verify_otp_code((int) $otpUser['id'], (string) $pendingOtp['purpose'], $otpCode)) {
                            throw new RuntimeException('Invalid or expired OTP code.');
                        }

                        if ((string) $pendingOtp['purpose'] === 'verify_email' && !is_email_verified($otpUser)) {
                            mark_email_verified((int) $otpUser['id']);
                        }

                        $rememberFlag = !empty($_POST['remember_30days']) || (($_SESSION['pending_otp_remember'] ?? '0') === '1');

                        login_user((int) $otpUser['id']);

                        if ($rememberFlag) {
                            remember_trusted_device((int) $otpUser['id']);
                        }

                        clear_pending_otp();
                        unset($_SESSION['pending_otp_remember']);

                        flash('success', 'Authentication successful.');
                        redirect(iworks_post_login_destination(current_user()));
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                    redirect(url('otp'));
                }
            }

            $emailParts = explode('@', (string) ($otpUser['email'] ?? ''));
            $emailName = $emailParts[0] ?? '';
            $emailDomain = $emailParts[1] ?? '';
            $maskedEmail = strlen($emailName) > 2
                ? substr($emailName, 0, 2) . str_repeat('*', max(strlen($emailName) - 2, 2)) . '@' . $emailDomain
                : str_repeat('*', max(strlen($emailName), 2)) . '@' . $emailDomain;
            $otpPurpose = (string) $pendingOtp['purpose'];
            $otpResendCooldownRemaining = otp_resend_cooldown_remaining((int) $otpUser['id'], $otpPurpose);
            $otpQueueDispatch = latest_otp_dispatch((int) $otpUser['id'], $otpPurpose);
            $otpFallbackAllowed = otp_delivery_fallback_allowed((int) $otpUser['id'], $otpPurpose);
            $title = 'OTP Verification - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/otp.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'payment_callback':
            $provider = strtolower(trim((string) ($_GET['provider'] ?? '')));
            $invoiceId = (int) ($_GET['invoice_id'] ?? 0);

            if ($provider === '' || $invoiceId <= 0) {
                throw new RuntimeException('Invalid payment callback request.');
            }

            $result = handle_gateway_callback($provider, $invoiceId, $_GET);
            if (!empty($result['redirect_url'])) {
                if (!empty($result['success'])) {
                    flash('success', (string) ($result['message'] ?? 'Payment processed successfully.'));
                } else {
                    flash('error', (string) ($result['message'] ?? 'Payment verification failed.'));
                }
                redirect((string) $result['redirect_url']);
            }

            $title = (!empty($result['success']) ? 'Payment Successful' : 'Payment Failed') . ' - ' . app_name();
            $paymentResult = $result;
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/payment-callback.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'payment_webhook':
            $provider = strtolower(trim((string) ($_GET['provider'] ?? '')));
            if ($provider === '') {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid payment webhook request.',
                ], JSON_UNESCAPED_SLASHES);
                break;
            }

            $rawPayload = file_get_contents('php://input');
            if (!is_string($rawPayload)) {
                $rawPayload = '';
            }

            $headers = [];
            if (function_exists('getallheaders')) {
                $all = getallheaders();
                if (is_array($all)) {
                    $headers = $all;
                }
            } else {
                foreach ($_SERVER as $key => $value) {
                    if (!str_starts_with((string) $key, 'HTTP_')) {
                        continue;
                    }

                    $name = str_replace('_', '-', strtolower(substr((string) $key, 5)));
                    $headers[$name] = (string) $value;
                }
            }

            try {
                $result = handle_gateway_webhook($provider, $rawPayload, $headers);
                http_response_code((int) ($result['status_code'] ?? 200));
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => !empty($result['success']),
                    'message' => (string) ($result['message'] ?? 'ok'),
                ], JSON_UNESCAPED_SLASHES);
            } catch (Throwable $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], JSON_UNESCAPED_SLASHES);
            }
            break;

        case 'realtime':
            require_login();
            header('Content-Type: application/json');
            $scope = strtolower(trim((string) ($_GET['scope'] ?? 'notifications')));
            $user = current_user();

            if ($scope === 'chat_typing') {
                if (!has_role(['admin', 'agent'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Chat access denied.'], JSON_UNESCAPED_SLASHES);
                    break;
                }

                $threadId = (int) ($_GET['thread_id'] ?? 0);
                if ($threadId <= 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid thread ID.'], JSON_UNESCAPED_SLASHES);
                    break;
                }

                $thread = get_chat_thread($threadId);
                if (!$thread || !can_access_chat_thread($user, $thread)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Chat thread not accessible.'], JSON_UNESCAPED_SLASHES);
                    break;
                }

                $typing = (int) ($_GET['typing'] ?? 0) === 1;
                if ($typing) {
                    touch_chat_typing($threadId, (int) $user['id']);
                }

                echo json_encode([
                    'success' => true,
                    'scope' => 'chat_typing',
                    'is_typing' => chat_other_user_typing($threadId, (int) $user['id'], 6),
                ], JSON_UNESCAPED_SLASHES);
                break;
            }

            if ($scope === 'chat') {
                if (!has_role(['admin', 'agent'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Chat access denied.'], JSON_UNESCAPED_SLASHES);
                    break;
                }

                $threadId = (int) ($_GET['thread_id'] ?? 0);
                $afterId = (int) ($_GET['after_id'] ?? 0);
                if ($threadId <= 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid thread ID.'], JSON_UNESCAPED_SLASHES);
                    break;
                }

                $thread = get_chat_thread($threadId);
                if (!$thread || !can_access_chat_thread($user, $thread)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Chat thread not accessible.'], JSON_UNESCAPED_SLASHES);
                    break;
                }

                $messages = list_chat_messages_after($threadId, $afterId);
                $payload = [];
                foreach ($messages as $message) {
                    $payload[] = [
                        'id' => (int) $message['id'],
                        'sender_id' => (int) $message['sender_id'],
                        'sender_name' => (string) ($message['sender_name'] ?? ''),
                        'created_at' => (string) ($message['created_at'] ?? ''),
                        'message' => (string) ($message['message'] ?? ''),
                        'attachment_url' => !empty($message['attachment_path'])
                            ? 'download.php?chat_message_id=' . (int) $message['id']
                            : '',
                    ];
                }

                echo json_encode([
                    'success' => true,
                    'scope' => 'chat',
                    'current_user_id' => (int) $user['id'],
                    'messages' => $payload,
                ], JSON_UNESCAPED_SLASHES);
                break;
            }

            if ($scope === 'rates') {
                $amount = (float) ($_GET['amount'] ?? 0);
                $currency = normalize_payment_currency((string) ($_GET['currency'] ?? active_pay_currency()));
                $force = (int) ($_GET['force'] ?? 0) === 1;
                if ($amount < 0) {
                    $amount = 0;
                }

                $quote = get_crypto_checkout_quote($amount, $currency, $force);
                echo json_encode([
                    'success' => true,
                    'scope' => 'rates',
                    'pay_currency' => $currency,
                    'payable_amount' => (float) ($quote['payable_amount'] ?? 0),
                    'fx_rate_used' => (float) ($quote['fx_rate_used'] ?? fx_ngn_per_usd()),
                    'fx_last_updated_at' => (string) ($quote['fx_last_updated_at'] ?? ''),
                    'usd_amount' => (float) ($quote['usd_amount'] ?? 0),
                    'assets' => is_array($quote['assets'] ?? null) ? $quote['assets'] : [],
                    'rates_source' => (string) ($quote['rates_source'] ?? 'fallback'),
                    'rates_last_updated_at' => (string) ($quote['rates_last_updated_at'] ?? ''),
                    'rates_warning' => (string) ($quote['rates_warning'] ?? ''),
                ], JSON_UNESCAPED_SLASHES);
                break;
            }

            $state = notification_realtime_state((int) $user['id']);
            $notifications = list_notifications_for_user((int) $user['id'], 20);
            $notificationPayload = [];
            foreach ($notifications as $notification) {
                $title = trim((string) ($notification['title'] ?? ''));
                $message = trim((string) ($notification['message'] ?? ''));

                if ($title === '') {
                    $title = 'Notification';
                }

                if (function_exists('mb_substr')) {
                    $message = mb_substr($message, 0, 160);
                } else {
                    $message = substr($message, 0, 160);
                }

                $notificationPayload[] = [
                    'id' => (int) $notification['id'],
                    'title' => $title,
                    'message' => $message,
                    'created_at' => (string) ($notification['created_at'] ?? ''),
                    'is_read' => (int) ($notification['is_read'] ?? 0),
                    'target_url' => (string) ($notification['target_url'] ?? ''),
                    'open_url' => url('notifications', ['open' => (int) $notification['id']]),
                ];
            }

            echo json_encode([
                'success' => true,
                'scope' => 'notifications',
                'unread_count' => (int) $state['unread_count'],
                'latest_id' => (int) $state['latest_id'],
                'notifications' => $notificationPayload,
            ], JSON_UNESCAPED_SLASHES);
            break;

        case 'applications':
            require_roles(['client']);
            if (method_is('POST')) {
                require_csrf();
                try {
                    if ((string) ($_POST['form_action'] ?? '') !== 'delete_unpaid_order') {
                        throw new RuntimeException('Unknown request.');
                    }
                    $clientId=(int) current_user()['id'];
                    $jobId=max(0,(int) ($_POST['job_id'] ?? 0));
                    $target=$jobId>0?get_job($jobId):null;
                    if (!$target || (int) ($target['client_id'] ?? 0)!==$clientId
                        || (string) ($target['status'] ?? '')!=='pending') {
                        throw new RuntimeException('Only your own unpaid, unprocessed requests can be deleted.');
                    }
                    // Linked wizard submissions use their own immutable invoice histories.
                    if (iworks_client_trademark_for_job($jobId,$clientId)
                        || cac_find_for_job($jobId,$clientId)) {
                        throw new RuntimeException('This submitted application cannot be deleted here. Contact support to withdraw it.');
                    }
                    delete_unpaid_job_for_client($jobId,$clientId);
                    flash('success','Unpaid service request deleted.');
                } catch (Throwable $e) {
                    flash('error',$e->getMessage());
                }
                redirect(url('applications'));
            }
            trademark_ensure_schema();
            $client = current_user();
            $trademarkApplications = trademark_list((int) $client['id']);
            $cacApplications = cac_list((int) $client['id']);
            $existingOrders = list_accessible_jobs($client, ['page'=>1, 'per_page'=>100, 'sort'=>'newest']);
            $clientServiceItems = iworks_client_service_items($trademarkApplications, $existingOrders['items'] ?? [], $cacApplications);
            $activeClientStatus = strtolower(trim((string) ($_GET['status'] ?? 'all')));
            if (!in_array($activeClientStatus, ['all','draft','payment','progress','completed','cancelled'], true)) { $activeClientStatus='all'; }
            $title = 'My Services - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/client-applications.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'card_vault':
            require_roles(['client']);
            $vaultUser = current_user();
            $vaultUserId = (int) $vaultUser['id'];
            if (method_is('POST')) {
                require_csrf();
                try {
                    $vaultAction = (string) ($_POST['form_action'] ?? '');
                    if ($vaultAction === 'save_card') {
                        $vaultCheckoutUrl = stripe_vault_start_setup(
                            $vaultUser, (string) ($_POST['save_card_consent'] ?? '') === '1',
                            (string) ($_POST['return_to'] ?? '') === 'invoices'
                        );
                        redirect($vaultCheckoutUrl);
                    }
                    if ($vaultAction === 'remove_card') {
                        stripe_vault_remove_card(
                            $vaultUserId, trim((string) ($_POST['payment_method_id'] ?? ''))
                        );
                        flash('success', 'Saved card removed from Stripe.');
                        redirect((string) ($_POST['return_to'] ?? '')==='invoices'
                            ? url('invoices').'#payment-methods' : url('card_vault'));
                    }
                    throw new RuntimeException('Unknown saved-card action.');
                } catch (Throwable $e) {
                    flash('error', iworks_client_safe_payment_error($e->getMessage()));
                    redirect((string) ($_POST['return_to'] ?? '')==='invoices'
                        ? url('invoices').'#payment-methods' : url('card_vault'));
                }
            }
            $setupSessionId = trim((string) ($_GET['session_id'] ?? ''));
            if ($setupSessionId !== '') {
                try {
                    stripe_vault_confirm_setup($vaultUserId, $setupSessionId);
                    flash('success', 'Card saved with Stripe. It is ready for future Stripe checkout.');
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }
                // No Stripe session IDs are retained in browser navigation.
                redirect((string) ($_GET['billing'] ?? '')==='1'
                    ? url('invoices').'#payment-methods' : url('card_vault'));
            }
            if ((string) ($_GET['billing'] ?? '')==='1') {
                redirect(url('invoices').'#payment-methods');
            }
            $vaultEnabled = stripe_vault_available();
            $savedCards = [];
            $vaultNotice = '';
            if ($vaultEnabled) {
                try {
                    $savedCards = stripe_vault_list_cards($vaultUserId);
                } catch (Throwable $e) {
                    $vaultNotice = 'Saved cards could not be loaded right now. Please try again later.';
                }
            }
            $title = 'Saved Cards - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/client-card-vault.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'cac_file':
            require_login();
            cac_ensure_schema();
            $fileId = max(0,(int) ($_GET['id'] ?? 0));
            $file = db_one('SELECT f.*, a.job_id,a.client_id AS owner_id
                FROM cac_application_files f
                JOIN cac_applications a ON a.id=f.application_id
                WHERE f.id=:id LIMIT 1',['id'=>$fileId]);
            if (!$file) { http_response_code(404); exit('File not found.'); }
            $viewer=current_user();
            $owner=(int) ($viewer['id'] ?? 0)===(int) $file['owner_id'];
            $isStaff=in_array((string) ($viewer['role'] ?? ''),['admin','finance','agent'],true);
            $job=$isStaff && (int) ($file['job_id'] ?? 0)>0 ? get_job((int) $file['job_id']) : null;
            if (!$owner && (!$isStaff || !$job || !can_access_job($viewer,$job))) {
                http_response_code(403);exit('Access denied.');
            }
            $name=basename((string) ($file['storage_name'] ?? ''));
            if (!preg_match('/^[a-f0-9]{36}\.(?:jpg|png|webp|pdf)$/D',$name)) {
                http_response_code(404);exit('File not found.');
            }
            $full=cac_private_dir().'/'.$name;
            if (!is_file($full)) { http_response_code(404);exit('File not found.'); }
            $type=(string) $file['mime'];
            if (!in_array($type,['image/jpeg','image/png','image/webp','application/pdf'],true)) {
                http_response_code(404);exit('Unsupported document.');
            }
            header('Content-Type: '.$type);
            header('Content-Length: '.filesize($full));
            header('Content-Disposition: attachment; filename="cac-document-'.(int) $file['id'].'.'.pathinfo($name,PATHINFO_EXTENSION).'"');
            header('Cache-Control: private, no-store');
            header('X-Content-Type-Options: nosniff');
            header("Content-Security-Policy: default-src 'none'; sandbox");
            readfile($full);
            exit;

        case 'cac':
            require_roles(['client']);
            cac_ensure_schema();
            $cacUser=current_user();
            $cacClient=(int) $cacUser['id'];
            $cacId=max(0,(int) ($_GET['id'] ?? $_POST['id'] ?? 0));
            $cacAsync=method_is('POST') && (
                (string) ($_POST['ajax'] ?? '')==='1'
                || str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')),'application/json')
            );
            if (method_is('POST')) {
                require_csrf();
                try {
                    $action=(string) ($_POST['form_action'] ?? '');
                    if ($action==='delete_draft') {
                        cac_delete_draft($cacId,$cacClient);
                        flash('success','Unsubmitted CAC draft deleted.');
                        redirect(url('applications'));
                    }
                    if ($action==='upload_file') {
                        $uploaded=cac_upload_file($cacClient,$cacId,
                            (string) ($_POST['field_key'] ?? ''),$_FILES['document'] ?? null);
                        header('Content-Type: application/json; charset=utf-8');
                        header('Cache-Control: no-store');
                        echo json_encode(['success'=>true]+$uploaded,JSON_UNESCAPED_SLASHES);
                        exit;
                    }
                    if (in_array($action,['save_draft','review_draft'],true)) {
                        $posted=cac_form_values($_POST);
                        $cacId=cac_save_draft($cacClient,$cacId,$posted);
                        if ($cacAsync) {
                            header('Content-Type: application/json; charset=utf-8');
                            header('Cache-Control: no-store');
                            echo json_encode(['success'=>true,'id'=>$cacId],JSON_UNESCAPED_SLASHES);
                            exit;
                        }
                        if ($action==='review_draft') {
                            $saved=cac_find($cacId,$cacClient);
                            $files=cac_list_files($cacId,$cacClient);
                            cac_validate($saved['data'],array_column($files,'field_key'));
                            redirect(url('cac',['id'=>$cacId,'step'=>'review']));
                        }
                        flash('success','Application draft saved.');
                        redirect(url('cac',['id'=>$cacId]));
                    }
                    if ($action==='submit_for_payment') {
                        $invoiceId=cac_checkout($cacClient,$cacId);
                        iworks_order_intent_clear();
                        flash('success','Application saved and invoice created. Complete payment to start processing.');
                        redirect(url('cac',['id'=>$cacId,'step'=>'payment']));
                    }
                    throw new RuntimeException('Unknown CAC application action.');
                } catch (Throwable $e) {
                    if ($cacAsync) {
                        http_response_code(422);
                        header('Content-Type: application/json; charset=utf-8');
                        header('Cache-Control: no-store');
                        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
                        exit;
                    }
                    flash('error',$e->getMessage());
                    redirect(url('cac',$cacId>0?['id'=>$cacId]:['kind'=>cac_kind((string) ($_POST['kind'] ?? 'business_name'))]));
                }
            }
            $cacApplication=$cacId>0?cac_find($cacId,$cacClient):null;
            if ($cacId>0 && !$cacApplication) { http_response_code(404);exit('Application not found.'); }
            $cacKind=$cacApplication ? (string) $cacApplication['kind']
                : cac_kind((string) ($_GET['kind'] ?? 'business_name'));
            $cacData=$cacApplication['data'] ?? ['kind'=>$cacKind,'currency'=>'NGN','people'=>[]];
            $cacFiles=$cacApplication ? cac_list_files($cacId,$cacClient) : [];
            $cacStage=(string) ($_GET['step'] ?? 'details');
            if (!in_array($cacStage,['details','review','payment'],true)) { $cacStage='details'; }
            if ($cacApplication && $cacApplication['status']!=='draft') {
                $cacStage='payment';
            } else if ($cacStage==='payment') { $cacStage='review'; }
            if ($cacStage==='review') {
                try { cac_validate($cacData,array_column($cacFiles,'field_key')); }
                catch (Throwable $e) {
                    flash('error',$e->getMessage());
                    redirect(url('cac',$cacId>0?['id'=>$cacId]:['kind'=>$cacKind]));
                }
            }
            $title=($cacKind==='company'?'Company incorporation':'Business name registration').' - '.app_name();
            require __DIR__.'/views/partials/header.php';
            require __DIR__.'/views/client-cac.php';
            require __DIR__.'/views/partials/footer.php';
            break;

        case 'trademark_file':
            require_roles(['client']);
            trademark_ensure_schema();
            $application = trademark_find((int) ($_GET['id'] ?? 0), (int) current_user()['id']);
            $name = (string) ($application['logo_filename'] ?? '');
            if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $name)) {
                http_response_code(404);
                exit('File not found.');
            }
            $filePath = trademark_private_logo_dir() . '/' . $name;
            if (!is_file($filePath)) {
                http_response_code(404);
                exit('File not found.');
            }
            $extension = (string) pathinfo($name, PATHINFO_EXTENSION);
            $mimeType = ['jpg'=>'image/jpeg', 'png'=>'image/png', 'webp'=>'image/webp'][$extension];
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: private, no-store');
            header('Content-Security-Policy: default-src \'none\'; sandbox');
            header('X-Content-Type-Options: nosniff');
            readfile($filePath);
            exit;

        case 'trademark':
            require_roles(['client']);
            trademark_ensure_schema();
            $clientId = (int) current_user()['id'];
            $appId = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
            if (method_is('POST')) {
                require_csrf();
                try {
                    $action = (string) ($_POST['form_action'] ?? '');
                    if ($action === 'delete_draft') {
                        trademark_delete_draft($appId, $clientId);
                        flash('success', 'Draft application deleted.');
                        redirect(url('applications'));
                    }
                    if (in_array($action, ['save_draft', 'review_draft'], true)) {
                        $data = trademark_form_values($_POST);
                        $savedId = trademark_save_draft($clientId, $appId, $data, $_FILES['mark_logo'] ?? null);
                        $appId = $savedId; // Preserve the draft link if review validation fails.
                        if ($action === 'review_draft') {
                            $saved = trademark_find($savedId, $clientId);
                            trademark_validate_complete($saved['data'], !empty($saved['logo_filename']));
                            redirect(url('trademark', ['id'=>$savedId, 'step'=>'review']));
                        }
                        flash('success', 'Your application draft has been saved.');
                        redirect(url('trademark', ['id'=>$savedId]));
                    }
                    if ($action === 'submit_for_payment') {
                        if ($appId <= 0) { throw new RuntimeException('Save the application before proceeding.'); }
                        $invoiceId = trademark_checkout($appId, $clientId);
                        iworks_order_intent_clear();
                        flash('success', 'Review your payment options to finalise your trademark application.');
                        redirect(url('trademark', ['id'=>$appId, 'step'=>'payment']));
                    }
                    throw new RuntimeException('Unknown application action.');
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                    redirect(url('trademark', $appId > 0 ? ['id'=>$appId] : []));
                }
            }
            $trademarkApplication = $appId > 0 ? trademark_find($appId, $clientId) : null;
            if ($appId > 0 && !$trademarkApplication) {
                http_response_code(404);
                exit('Application not found.');
            }
            $trademarkData = $trademarkApplication['data'] ?? [];
            $trademarkStage = (string) ($_GET['step'] ?? 'details');
            if (!in_array($trademarkStage, ['details','review','payment'], true)) { $trademarkStage = 'details'; }
            if ($trademarkApplication && $trademarkApplication['status'] !== 'draft') {
                $trademarkStage = 'payment';
            } elseif ($trademarkStage === 'payment') {
                $trademarkStage = 'review';
            }
            if ($trademarkStage === 'review') {
                try {
                    trademark_validate_complete($trademarkData, !empty($trademarkApplication['logo_filename']));
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                    redirect(url('trademark', ['id'=>$appId]));
                }
            }
            $trademarkUnit = ($trademarkApplication && $trademarkApplication['status'] !== 'draft')
                ? (float) ($trademarkApplication['unit_price_usd'] ?? trademark_price_usd())
                : trademark_price_usd();
            $trademarkFxRate = ($trademarkApplication && $trademarkApplication['status'] !== 'draft')
                ? (float) ($trademarkApplication['fx_rate_used'] ?? fx_ngn_per_usd())
                : (float) fx_ngn_per_usd();
            $trademarkQuote = trademark_quote(
                $trademarkData['classes'] ?? [],
                $trademarkData['currency'] ?? 'USD',
                $trademarkUnit,
                $trademarkFxRate
            );
            $trademarkMethods = $trademarkStage === 'payment'
                ? list_available_payment_methods_for_currency((string) ($trademarkApplication['pay_currency'] ?? 'USD'))
                : [];
            $title = 'Trademark Application - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/client-trademark.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'dashboard':
            require_login();
            $user = current_user();

            if (method_is('POST')) {
                require_csrf();

                if (($_POST['form_action'] ?? '') === 'acknowledge_announcement') {
                    acknowledge_announcement((int) ($_POST['announcement_id'] ?? 0), (int) $user['id']);
                }

                redirect(url('dashboard'));
            }

            if (($user['role'] ?? '') === 'client') {
                trademark_ensure_schema();
                $trademarkApplications = trademark_list((int) $user['id']);
                $cacApplications = cac_list((int) $user['id']);
                $clientCatalogue = iworks_client_catalogue(list_services(true));
                $recentOrders = list_accessible_jobs($user, ['page'=>1, 'per_page'=>25, 'sort'=>'newest']);
                $clientRecent = array_slice(iworks_client_service_items($trademarkApplications, $recentOrders['items'] ?? [], $cacApplications), 0, 4);
                $title = 'New Application - ' . app_name();
                require __DIR__ . '/views/partials/header.php';
                require __DIR__ . '/views/client-dashboard.php';
                require __DIR__ . '/views/partials/footer.php';
                break;
            }

            $stats = counts_for_dashboard($user);
            $banners = $user['role'] === 'client' ? list_active_banners() : [];
            $agentSummary = $user['role'] === 'agent' ? agent_withdrawal_summary((int) $user['id']) : null;
            $blockingAnnouncement = get_blocking_announcement_for_user((int) $user['id']);
            $title = 'Dashboard - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/dashboard.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'services':
            require_roles(['admin', 'finance']);
            $serviceFormMode = in_array($_GET['action'] ?? '', ['new', 'edit'], true);
            $editService = null;
            $serviceIntakeAttachments = [];
            $intakeForms = list_intake_forms('all', '');

            if (method_is('POST')) {
                require_csrf();

                if (!has_role(['admin'])) {
                    flash('error', 'Only admin can modify services.');
                    redirect(url('services'));
                }

                try {
                    $serviceAction = (string) ($_POST['form_action'] ?? '');

                    if ($serviceAction === 'save_service') {
                        $serviceId = !empty($_POST['service_id']) ? (int) $_POST['service_id'] : null;
                        save_service($_POST, $serviceId);
                        flash('success', 'Service saved.');
                    }

                    if ($serviceAction === 'delete_service') {
                        delete_service((int) ($_POST['service_id'] ?? 0));
                        flash('success', 'Service deleted.');
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }

                redirect(url('services'));
            }

            if (($_GET['action'] ?? '') === 'edit' && isset($_GET['id'])) {
                $editService = get_service((int) $_GET['id']);
                if ($editService) {
                    $serviceIntakeAttachments = list_service_intake_forms((int) $editService['id']);
                }
            }

            $services = list_services(false);
            $mediaAssets = list_media_assets(300);
            $title = 'Services - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/services.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'forms':
            require_roles(['admin']);
            $formAction = strtolower(trim((string) ($_GET['action'] ?? '')));
            $formStatusFilter = strtolower(trim((string) ($_GET['status'] ?? 'all')));
            $formSearch = trim((string) ($_GET['q'] ?? ''));
            $editIntakeForm = null;
            $servicesForAttachment = list_services(false);

            if (method_is('POST')) {
                require_csrf();

                try {
                    $action = (string) ($_POST['form_action'] ?? '');

                    if ($action === 'save_intake_form') {
                        $formId = !empty($_POST['intake_form_id']) ? (int) $_POST['intake_form_id'] : null;
                        $savedId = save_intake_form($_POST, (int) current_user()['id'], $formId);
                        flash('success', 'Intake form saved successfully.');
                        redirect(url('forms', ['action' => 'edit', 'id' => $savedId]));
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                    if (!empty($_POST['intake_form_id'])) {
                        redirect(url('forms', ['action' => 'edit', 'id' => (int) $_POST['intake_form_id']]));
                    }
                    redirect(url('forms'));
                }

                redirect(url('forms'));
            }

            if ($formAction === 'edit' && !empty($_GET['id'])) {
                $editIntakeForm = get_intake_form((int) $_GET['id']);
            }

            $intakeFormsList = list_intake_forms($formStatusFilter, $formSearch);
            $title = 'Intake Forms - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/forms.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'jobs':
            require_login();
            if ((current_user()['role'] ?? '') === 'client'
                && !in_array((string) ($_GET['action'] ?? ''), ['new'], true)
                && !method_is('POST')) {
                redirect(url('applications'));
            }
            maybe_auto_close_delivered_jobs();
            $showCreateForm = ($_GET['action'] ?? '') === 'new' && has_role(['admin', 'client']);
            $serviceOptions = list_services(true);
            // Product-specific trademark pricing requires class selection.
            // The legacy cart must not bypass its dedicated $/class flow.
            if (has_role(['client'])) {
                $serviceOptions = array_values(array_filter($serviceOptions,
                    static fn(array $service): bool => (string) ($service['slug'] ?? '') !== 'trademark'));
            }
            $preselectedOrder = null;
            if ($showCreateForm && has_role(['client'])) {
                $requestedService = (string) ($_GET['service'] ?? iworks_order_intent_peek());
                $preselectedOrder = iworks_order_catalog_selection($requestedService, $serviceOptions);
            }
            $clientOptions = has_role(['admin']) ? list_users_by_role('client') : [];
            $agentOptions = has_role(['admin']) ? list_users_by_role('agent') : [];
            $jobPage = max(1, (int) ($_GET['p'] ?? 1));
            $jobPerPage = (int) ($_GET['per_page'] ?? 10);
            if (!in_array($jobPerPage, [10, 25, 50, 100], true)) {
                $jobPerPage = 10;
            }
            $jobFilters = [
                'status' => strtolower(trim((string) ($_GET['status'] ?? ''))),
                'payment_status' => strtolower(trim((string) ($_GET['payment_status'] ?? ''))),
                'sort' => strtolower(trim((string) ($_GET['sort'] ?? 'newest'))),
                'page' => $jobPage,
                'per_page' => $jobPerPage,
            ];

            if (method_is('POST')) {
                require_csrf();

                try {
                    $action = (string) ($_POST['form_action'] ?? '');

                    if ($action === 'delete_unpaid_job') {
                        if (!has_role(['client'])) {
                            throw new RuntimeException('Only clients can delete unpaid jobs.');
                        }

                        delete_unpaid_job_for_client(
                            (int) ($_POST['job_id'] ?? 0),
                            (int) current_user()['id']
                        );
                        flash('success', 'Unpaid job deleted.');
                        redirect(url('jobs'));
                    }

                    if ($action === 'delete_job_admin') {
                        if (!has_role(['admin'])) {
                            throw new RuntimeException('Only admins can delete jobs.');
                        }

                        delete_job_for_admin((int) ($_POST['job_id'] ?? 0));
                        flash('success', 'Job deleted.');
                        redirect(url('jobs'));
                    }

                    if ($action === 'bulk_manage_jobs') {
                        if (!has_role(['admin'])) {
                            throw new RuntimeException('Only admins can perform bulk job actions.');
                        }

                        $result = bulk_manage_jobs_as_admin(
                            is_array($_POST['job_ids'] ?? null) ? $_POST['job_ids'] : [],
                            (string) ($_POST['bulk_action'] ?? ''),
                            (int) current_user()['id']
                        );

                        $message = (int) $result['updated'] . ' job(s) updated.';
                        if ((int) $result['failed'] > 0) {
                            $message .= ' ' . (int) $result['failed'] . ' failed.';
                            $errors = is_array($result['errors'] ?? null) ? $result['errors'] : [];
                            if ($errors !== []) {
                                $message .= ' ' . implode(' | ', $errors);
                            }
                        }
                        flash((int) $result['failed'] > 0 ? 'error' : 'success', $message);
                        redirect(url('jobs'));
                    }

                    if ($action === 'create_job') {
                        if (!has_role(['admin', 'client'])) {
                            throw new RuntimeException('You cannot create jobs.');
                        }

                        $payload = $_POST;
                        $payload['created_by'] = (int) current_user()['id'];

                        if (has_role(['client'])) {
                            $requestedIds = is_array($_POST['service_ids'] ?? null) ? $_POST['service_ids'] : [];
                            $requestedIds[] = $_POST['service_id'] ?? 0;
                            foreach ($requestedIds as $requestedId) {
                                if ((int) $requestedId <= 0) { continue; }
                                $requestedService = get_service((int) $requestedId);
                                if (($requestedService['slug'] ?? '') === 'trademark') {
                                    throw new RuntimeException('Use the dedicated trademark application to select classes and calculate the correct fee.');
                                }
                            }
                            $payload['client_id'] = (int) current_user()['id'];
                            $payload['payment_status'] = 'unpaid';
                            unset($payload['agent_id'], $payload['amount'], $payload['commission_percent'], $payload['tax_percent'], $payload['currency']);
                            if (!empty($payload['pay_currency'])) {
                                set_active_pay_currency((string) $payload['pay_currency']);
                            }
                        }

                        if (has_role(['client'])) {
                            $checkoutPayMethod = normalize_pay_method((string) ($_POST['checkout_pay_method'] ?? 'card'));
                            $checkoutCryptoAsset = normalize_crypto_asset((string) ($_POST['crypto_asset'] ?? 'USDT'));
                            $checkoutCryptoNetwork = normalize_crypto_network($checkoutCryptoAsset, (string) ($_POST['crypto_network'] ?? ''));
                            $selectedServiceIds = $_POST['service_ids'] ?? [];
                            $hasMultiServices = is_array($selectedServiceIds) && count(array_filter($selectedServiceIds, static fn($v): bool => (int) $v > 0)) > 1;
                            if ($hasMultiServices) {
                                $bundle = create_client_order_bundle($payload);
                                $invoice = get_invoice((int) ($bundle['invoice_id'] ?? 0));
                                notify_roles(['admin', 'finance'], 'New service bundle order', 'A new multi-service order was created.', 'invoice.php?id=' . (int) ($bundle['invoice_id'] ?? 0));
                            } else {
                                if (is_array($selectedServiceIds) && !empty($selectedServiceIds[0])) {
                                    $payload['service_id'] = (int) $selectedServiceIds[0];
                                }
                                $jobId = create_job($payload);
                                $job = get_job($jobId);
                                if ($job) {
                                    notify_roles(['admin', 'finance'], 'New service order', 'A new order was created: ' . $job['job_code'] . '.', url('job', ['id' => $jobId]));
                                }
                                $invoice = get_invoice_by_job_id($jobId);
                            }

                            // Only clear a pending order once checkout creates
                            // the invoice, not when a customer signs in.
                            iworks_order_intent_clear();
                            flash('success', 'Order created. Complete payment to activate service processing.');

                            if ($invoice) {
                                $redirectInvoice = 'invoice.php?id=' . (int) $invoice['id'];
                                if ($checkoutPayMethod === 'crypto') {
                                    $redirectInvoice .= '&pay_mode=crypto&crypto_asset=' . rawurlencode($checkoutCryptoAsset);
                                    if ($checkoutCryptoNetwork !== null) {
                                        $redirectInvoice .= '&crypto_network=' . rawurlencode($checkoutCryptoNetwork);
                                    }
                                }
                                redirect($redirectInvoice);
                            }
                            redirect(url('jobs'));
                        }

                        $jobId = create_job($payload);
                        $job = get_job($jobId);
                        if ($job) {
                            notify_roles(['admin', 'finance'], 'New service order', 'A new order was created: ' . $job['job_code'] . '.', url('job', ['id' => $jobId]));
                        }
                        flash('success', 'Job created successfully.');
                        redirect(url('job', ['id' => $jobId]));
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                    if (($action ?? '') === 'create_job') {
                        $slug = iworks_order_intent_peek();
                        redirect(url('jobs', ['action' => 'new'] + ($slug !== '' ? ['service' => $slug] : [])));
                    }
                    redirect(url('jobs'));
                }
            }

            $jobsResult = list_accessible_jobs(current_user(), $jobFilters);
            $jobs = $jobsResult['items'] ?? [];
            $jobPagination = $jobsResult['pagination'] ?? [
                'page' => $jobPage,
                'per_page' => $jobPerPage,
                'total' => is_array($jobs) ? count($jobs) : 0,
                'total_pages' => 1,
                'from' => 0,
                'to' => 0,
            ];
            $title = 'Jobs - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/jobs.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'job':
            require_login();
            maybe_auto_close_delivered_jobs();
            $jobId = (int) ($_GET['id'] ?? 0);
            $job = get_job($jobId);

            if (!$job) {
                http_response_code(404);
                exit('Job not found.');
            }

            $user = current_user();
            if (!can_access_job($user, $job)) {
                http_response_code(403);
                exit('Not allowed to access this job.');
            }

            if (method_is('POST')) {
                require_csrf();
                $action = $_POST['form_action'] ?? '';
                $isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
                    || (string) ($_POST['ajax'] ?? '') === '1';
                $ajaxResponse = null;

                try {
                    if ($action === 'add_comment') {
                        $comment = trim($_POST['comment'] ?? '');
                        if ($comment !== '') {
                            add_comment($jobId, (int) $user['id'], $comment);
                            flash('success', 'Comment added.');
                            if ($isAjaxRequest) {
                                $ajaxResponse = [
                                    'success' => true,
                                    'message' => 'Comment added.',
                                ];
                            }
                        }
                    }

                    if ($action === 'upload_file') {
                        $uploadedFiles = normalize_job_uploaded_files_payload($_FILES['attachment'] ?? null);
                        $uploadedCount = store_uploaded_files($jobId, (int) $user['id'], $uploadedFiles);
                        $uploadMessage = $uploadedCount === 1 ? '1 file uploaded.' : ($uploadedCount . ' files uploaded.');
                        flash('success', $uploadMessage);
                        if ($isAjaxRequest) {
                            $ajaxResponse = [
                                'success' => true,
                                'message' => $uploadMessage,
                                'uploaded_count' => $uploadedCount,
                            ];
                        }
                    }

                    if ($action === 'delete_job_file') {
                        delete_job_file($jobId, (int) ($_POST['file_id'] ?? 0), (int) $user['id']);
                        flash('success', 'File removed.');
                        if ($isAjaxRequest) {
                            $ajaxResponse = [
                                'success' => true,
                                'message' => 'File removed.',
                            ];
                        }
                    }

                    if ($action === 'claim_job' && $user['role'] === 'agent') {
                        if ($job['agent_id'] === null && empty($job['managed_by_admin_id'])) {
                            assign_job_agent($jobId, (int) $user['id'], (int) $user['id']);
                            flash('success', 'Job claimed.');
                        } else {
                            flash('error', 'This job is no longer available in the job pool.');
                        }
                    }

                    if ($action === 'assign_agent' && has_role(['admin'])) {
                        $agentId = (int) ($_POST['agent_id'] ?? 0);
                        if ($agentId > 0) {
                            assign_job_agent($jobId, $agentId, (int) $user['id']);
                            flash('success', 'Agent assignment updated.');
                        }
                    }

                    if ($action === 'assign_admin_owner' && has_role(['admin'])) {
                        assign_job_to_admin($jobId, (int) $user['id']);
                        flash('success', 'Job is now assigned to admin.');
                    }

                    if ($action === 'update_status') {
                        $newStatus = $_POST['status'] ?? 'pending';
                        $canUpdate = has_role(['admin']) || ($user['role'] === 'agent' && (int) $job['agent_id'] === (int) $user['id']);
                        if ($canUpdate) {
                            update_job_status($jobId, $newStatus, (int) $user['id'], (string) ($user['role'] ?? ''));
                            flash('success', 'Job status updated.');
                        } else {
                            flash('error', 'You cannot update this job status.');
                        }
                    }

                    if ($action === 'submit_order_review') {
                        submit_job_review(
                            $jobId,
                            $user,
                            (int) ($_POST['rating'] ?? 0),
                            (string) ($_POST['feedback'] ?? ''),
                            (string) ($_POST['publish_consent'] ?? '') === '1'
                        );
                        flash('success', 'Thank you. Your order review has been saved.');
                    }

                    if ($action === 'accept_delivery' && $user['role'] === 'client') {
                        client_accept_job_delivery($jobId, (int) $user['id']);
                        flash('success', 'Delivery accepted and job closed.');
                    }

                    if ($action === 'request_revision' && $user['role'] === 'client') {
                        $revisionNote = trim((string) ($_POST['revision_note'] ?? ''));
                        client_request_job_revision($jobId, (int) $user['id'], $revisionNote);
                        flash('success', 'Revision requested. The assigned team has been notified.');
                    }

                    if ($action === 'reopen_job' && has_role(['admin'])) {
                        $reopenNote = trim((string) ($_POST['reopen_note'] ?? ''));
                        admin_reopen_job($jobId, (int) $user['id'], $reopenNote);
                        flash('success', 'Job reopened successfully.');
                    }

                    if ($action === 'update_finance' && has_role(['admin', 'finance'])) {
                        $financePayload = $_POST;
                        $financePayload['changed_by'] = (int) $user['id'];
                        update_job_finance($jobId, $financePayload);
                        flash('success', 'Payment and commission updated.');
                    }
                } catch (Throwable $e) {
                    if ($isAjaxRequest) {
                        $ajaxResponse = [
                            'success' => false,
                            'message' => $e->getMessage(),
                        ];
                    } else {
                        flash('error', $e->getMessage());
                    }
                }

                if ($isAjaxRequest) {
                    header('Content-Type: application/json');
                    echo json_encode($ajaxResponse ?? ['success' => true], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    break;
                }

                redirect(url('job', ['id' => $jobId]));
            }

            $job = get_job($jobId);
            $clientOrderReview = ((string) ($user['role'] ?? '') === 'client' && (int) ($job['client_id'] ?? 0) === (int) $user['id'])
                ? job_review_for_order($jobId)
                : null;
            $comments = list_comments($jobId);
            $files = list_job_files($jobId);
            $logs = list_status_logs($jobId);
            $agentOptions = has_role(['admin']) ? list_users_by_role('agent') : [];
            $invoice = get_invoice_by_job_id($jobId);
            $intakeSubmissions = list_job_intake_submissions_for_user($jobId, $user);
            $intakeSummary = intake_job_completion_summary($jobId);

            $title = $job['job_code'] . ' - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            if (($user['role'] ?? '') === 'client') {
                $wizardTrademark = iworks_client_trademark_for_job($jobId, (int) $user['id']);
                require __DIR__ . '/views/client-job.php';
            } else {
                require __DIR__ . '/views/job.php';
            }
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'intake_submission':
            require_login();
            $user = current_user();
            $submissionId = (int) ($_GET['id'] ?? 0);
            $intakeSubmission = get_intake_submission($submissionId);

            if (!$intakeSubmission) {
                http_response_code(404);
                exit('Intake submission not found.');
            }

            if (!can_access_intake_submission($user, $intakeSubmission)) {
                http_response_code(403);
                exit('You do not have access to this intake submission.');
            }

            if (method_is('POST')) {
                require_csrf();
                $action = (string) ($_POST['form_action'] ?? '');

                try {
                    if (in_array($action, ['autosave_intake', 'submit_intake'], true)) {
                        if ((string) ($user['role'] ?? '') !== 'client' || (int) $intakeSubmission['client_user_id'] !== (int) $user['id']) {
                            throw new RuntimeException('Only client owner can submit this intake form.');
                        }
                        if (!intake_client_can_edit($intakeSubmission)) {
                            throw new RuntimeException('This intake form is locked.');
                        }

                        $answers = $_POST['answers'] ?? [];
                        if (!is_array($answers)) {
                            $answers = [];
                        }

                        $normalizedFiles = isset($_FILES['files']) && is_array($_FILES['files'])
                            ? normalize_intake_uploaded_files_payload($_FILES['files'])
                            : [];
                        $removeFiles = normalize_intake_remove_file_indexes(
                            is_array($_POST['remove_files'] ?? null) ? $_POST['remove_files'] : []
                        );

                        $isSubmit = $action === 'submit_intake';
                        save_intake_submission_draft($submissionId, (int) $user['id'], $answers, $normalizedFiles, $isSubmit, $removeFiles);

                        if ((string) ($_POST['ajax'] ?? '0') === '1') {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'status' => $isSubmit ? 'submitted' : 'draft'], JSON_UNESCAPED_SLASHES);
                            break;
                        }

                        flash('success', $isSubmit ? 'Intake form submitted successfully.' : 'Intake draft saved.');
                    }

                    if ($action === 'request_update') {
                        if (!in_array((string) ($user['role'] ?? ''), ['admin', 'finance', 'agent'], true)) {
                            throw new RuntimeException('Only admin/agent can request form updates.');
                        }

                        request_intake_submission_update($submissionId, (int) $user['id'], (string) ($_POST['update_note'] ?? ''));
                        flash('success', 'Update request sent to client.');
                    }
                } catch (Throwable $e) {
                    if ((string) ($_POST['ajax'] ?? '0') === '1') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_SLASHES);
                        break;
                    }
                    flash('error', $e->getMessage());
                }

                redirect(url('intake_submission', ['id' => $submissionId]));
            }

            $intakeSubmission = get_intake_submission($submissionId);
            $intakeEvents = list_intake_submission_events($submissionId);
            $title = 'Intake Submission - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/intake-submission.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'invoices':
            require_roles(['client', 'admin', 'finance']);
            $user = current_user();

            if (method_is('POST')) {
                require_csrf();

                try {
                    $action = (string) ($_POST['form_action'] ?? '');
                    $invoiceId = (int) ($_POST['invoice_id'] ?? 0);

                    if (!has_role(['client'])) {
                        throw new RuntimeException('Only clients can edit or delete invoice drafts.');
                    }

                    if ($action === 'edit_unpaid_invoice') {
                        update_unpaid_invoice_for_client(
                            $invoiceId,
                            (int) $user['id'],
                            (string) ($_POST['job_title'] ?? ''),
                            (string) ($_POST['job_description'] ?? '')
                        );
                        flash('success', 'Invoice order details updated.');
                    }

                    if ($action === 'delete_unpaid_invoice') {
                        delete_unpaid_invoice_for_client($invoiceId, (int) $user['id']);
                        flash('success', 'Unpaid invoice deleted.');
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }

                redirect(url('invoices'));
            }

            $invoiceFilters = [
                'status' => strtolower(trim((string) ($_GET['status'] ?? ''))),
                'payment_status' => strtolower(trim((string) ($_GET['payment_status'] ?? ''))),
                'sort' => strtolower(trim((string) ($_GET['sort'] ?? 'newest'))),
                'page' => max(1, (int) ($_GET['p'] ?? 1)),
                'per_page' => in_array((int) ($_GET['per_page'] ?? 10), [10, 25, 50, 100], true)
                    ? (int) ($_GET['per_page'] ?? 10)
                    : 10,
            ];
            $invoiceResult = list_invoices_for_user($user, $invoiceFilters);
            $invoices = $invoiceResult['items'] ?? [];
            $vaultEnabled = false; $savedCards = []; $vaultNotice = '';
            if ((string) ($user['role'] ?? '')==='client') {
                $vaultEnabled = stripe_vault_available();
                if ($vaultEnabled) {
                    try { $savedCards = stripe_vault_list_cards((int) $user['id']); }
                    catch (Throwable $e) { $vaultNotice = 'Saved payment methods could not be loaded at the moment.'; }
                }
            }
            $invoicePagination = $invoiceResult['pagination'] ?? [
                'page' => (int) $invoiceFilters['page'],
                'per_page' => (int) $invoiceFilters['per_page'],
                'total' => is_array($invoices) ? count($invoices) : 0,
                'total_pages' => 1,
                'from' => 0,
                'to' => 0,
            ];
            $title = 'Invoices - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            if (($user['role'] ?? '') === 'client') {
                require __DIR__ . '/views/client-billing.php';
            } else {
                require __DIR__ . '/views/invoices.php';
            }
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'agents':
            require_roles(['admin', 'finance']);
            $showCreateForm = ($_GET['action'] ?? '') === 'new';

            if (method_is('POST')) {
                require_csrf();
                try {
                    if (($_POST['form_action'] ?? '') === 'create_agent') {
                        if (!has_role(['admin'])) {
                            throw new RuntimeException('Only admin can create agents.');
                        }

                        $email = trim($_POST['email'] ?? '');
                        if ($email === '' || find_user_by_email($email)) {
                            throw new RuntimeException('Agent email is invalid or already exists.');
                        }

                        create_user([
                            'full_name' => $_POST['full_name'] ?? '',
                            'email' => $email,
                            'phone' => $_POST['phone'] ?? '',
                            'password' => $_POST['password'] ?? '',
                            'role' => 'agent',
                            'bank_name' => $_POST['bank_name'] ?? null,
                            'bank_account_name' => $_POST['bank_account_name'] ?? null,
                            'bank_account_number' => $_POST['bank_account_number'] ?? null,
                            'bank_swift' => $_POST['bank_swift'] ?? null,
                            'bank_currency' => $_POST['bank_currency'] ?? default_currency(),
                        ]);

                        flash('success', 'Agent user created.');
                        redirect(url('agents'));
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                    redirect(url('agents'));
                }
            }

            $agents = list_users_by_role('agent');
            $title = 'Agents - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/agents.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'users':
            require_roles(['admin']);
            $roleFilter = trim((string) ($_GET['role'] ?? 'all'));
            if (!in_array($roleFilter, ['all', 'client', 'agent'], true)) {
                $roleFilter = 'all';
            }

            if (method_is('POST')) {
                require_csrf();

                try {
                    $action = (string) ($_POST['form_action'] ?? '');
                    $userId = (int) ($_POST['user_id'] ?? 0);

                    if ($action === 'create_user') {
                        $newUserId = admin_create_manageable_user($_POST, (int) current_user()['id']);
                        $createdRole = ucfirst(strtolower(trim((string) ($_POST['role'] ?? 'client'))));
                        $message = $createdRole . ' account created. Temporary password is saved.';
                        if (!empty($_POST['force_first_login_update'])) {
                            $message .= ' User must change password at first login.';
                        }
                        flash('success', $message);
                    }

                    if ($action === 'save_user') {
                        update_user_profile_admin($userId, $_POST);
                        flash('success', 'User profile updated.');
                    }

                    if ($action === 'set_user_state') {
                        set_user_account_state_admin($userId, (string) ($_POST['account_state'] ?? 'active'));
                        flash('success', 'User account state updated.');
                    }

                    if ($action === 'delete_user') {
                        soft_delete_user_admin($userId);
                        flash('success', 'User account deleted.');
                    }

                    if ($action === 'send_reset_link') {
                        send_password_reset_link_to_user($userId);
                        flash('success', 'Password reset link sent.');
                    }

                    if ($action === 'send_reset_links_all') {
                        $sent = send_password_reset_links_to_all_active_users();
                        flash('success', 'Password reset links sent to ' . $sent . ' active user(s).');
                    }

                    if ($action === 'open_agent_chat') {
                        $target = get_manageable_user($userId);
                        if (!$target || (string) ($target['role'] ?? '') !== 'agent') {
                            throw new RuntimeException('Agent account not found for chat.');
                        }

                        $threadId = ensure_chat_thread((int) current_user()['id'], $userId);
                        redirect(url('messages', ['thread_id' => $threadId]));
                    }

                    if ($action === 'add_custom_service_order') {
                        $jobId = admin_create_custom_service_order_for_user((int) current_user()['id'], $userId, $_POST);
                        $job = get_job($jobId);
                        $invoice = get_invoice_by_job_id($jobId);
                        $successMessage = 'Custom service order added to user account.';
                        if ($invoice) {
                            $successMessage .= ' Invoice ' . (string) ($invoice['invoice_number'] ?? '') . ' created.';
                        } elseif ($job) {
                            $successMessage .= ' Job ' . (string) ($job['job_code'] ?? '') . ' created.';
                        }
                        flash('success', trim($successMessage));
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }

                redirect(url('users', ['role' => $roleFilter]));
            }

            $users = list_manageable_users($roleFilter);
            $serviceOptions = list_services(true);
            $title = 'Users - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/users.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'messages':
            require_roles(['admin', 'agent']);
            $user = current_user();
            $threads = list_chat_threads_for_user($user);
            $activeThread = null;
            $chatMessages = [];

            if (method_is('POST')) {
                require_csrf();
                try {
                    $action = (string) ($_POST['form_action'] ?? '');

                    if ($action === 'start_chat' && $user['role'] === 'admin') {
                        $agentId = (int) ($_POST['agent_id'] ?? 0);
                        if ($agentId <= 0) {
                            throw new RuntimeException('Select an agent to start chat.');
                        }

                        $threadId = ensure_chat_thread((int) $user['id'], $agentId);
                        redirect(url('messages', ['thread_id' => $threadId]));
                    }

                    if ($action === 'send_message') {
                        $threadId = (int) ($_POST['thread_id'] ?? 0);
                        $thread = get_chat_thread($threadId);
                        if (!$thread || !can_access_chat_thread($user, $thread)) {
                            throw new RuntimeException('Chat access denied.');
                        }

                        send_chat_message(
                            $threadId,
                            (int) $user['id'],
                            trim((string) ($_POST['message'] ?? '')),
                            $_FILES['attachment'] ?? null
                        );
                        redirect(url('messages', ['thread_id' => $threadId]));
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                    redirect(url('messages', ['thread_id' => (int) ($_POST['thread_id'] ?? 0)]));
                }
            }

            $requestedThreadId = (int) ($_GET['thread_id'] ?? 0);
            if ($requestedThreadId > 0) {
                $thread = get_chat_thread($requestedThreadId);
                if ($thread && can_access_chat_thread($user, $thread)) {
                    $activeThread = $thread;
                    $chatMessages = list_chat_messages((int) $thread['id']);
                }
            }

            $adminAgentOptions = $user['role'] === 'admin' ? list_users_by_role('agent') : [];
            $title = 'Messages - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/messages.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'payments':
            require_roles(['admin', 'finance']);
            $paymentPage = max(1, (int) ($_GET['p'] ?? 1));
            $paymentPerPage = in_array((int) ($_GET['per_page'] ?? 10), [10, 25, 50, 100], true)
                ? (int) ($_GET['per_page'] ?? 10)
                : 10;
            $paymentMethodId = (int) ($_GET['method_id'] ?? 0);
            if ($paymentMethodId < 1) {
                $paymentMethodId = 0;
            }
            $paymentFilters = [
                'status' => strtolower(trim((string) ($_GET['status'] ?? ''))),
                'method_id' => $paymentMethodId,
                'sort' => strtolower(trim((string) ($_GET['sort'] ?? 'newest'))),
                'page' => $paymentPage,
                'per_page' => $paymentPerPage,
            ];
            $paymentRouteParams = [
                'status' => $paymentFilters['status'],
                'method_id' => $paymentFilters['method_id'] > 0 ? $paymentFilters['method_id'] : '',
                'sort' => $paymentFilters['sort'],
                'p' => $paymentPage,
                'per_page' => $paymentPerPage,
            ];

            if (method_is('POST')) {
                require_csrf();

                try {
                    if (($_POST['form_action'] ?? '') === 'review_payment') {
                        review_payment(
                            (int) ($_POST['payment_id'] ?? 0),
                            (string) ($_POST['status'] ?? 'rejected'),
                            (int) current_user()['id'],
                            trim((string) ($_POST['review_note'] ?? ''))
                        );

                        flash('success', 'Payment review updated.');
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }

                redirect(url('payments', $paymentRouteParams));
            }

            $paymentsResult = list_payments_admin($paymentFilters);
            $payments = $paymentsResult['items'] ?? [];
            $paymentPagination = $paymentsResult['pagination'] ?? [
                'page' => $paymentPage,
                'per_page' => $paymentPerPage,
                'total' => is_array($payments) ? count($payments) : 0,
                'total_pages' => 1,
                'from' => 0,
                'to' => 0,
            ];
            $paymentMethodOptions = list_payment_methods(false);
            $title = 'Payments - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/payments.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'payouts':
            require_roles(['admin', 'finance']);
            $payoutPage = max(1, (int) ($_GET['p'] ?? 1));
            $payoutPerPage = in_array((int) ($_GET['per_page'] ?? 10), [10, 25, 50, 100], true)
                ? (int) ($_GET['per_page'] ?? 10)
                : 10;
            $payoutFilters = [
                'status' => strtolower(trim((string) ($_GET['status'] ?? ''))),
                'sort' => strtolower(trim((string) ($_GET['sort'] ?? 'newest'))),
                'page' => $payoutPage,
                'per_page' => $payoutPerPage,
            ];
            $payoutRouteParams = [
                'status' => $payoutFilters['status'],
                'sort' => $payoutFilters['sort'],
                'p' => $payoutPage,
                'per_page' => $payoutPerPage,
            ];

            if (method_is('POST')) {
                require_csrf();
                try {
                    $action = (string) ($_POST['form_action'] ?? '');
                    if ($action === 'update_payout_status') {
                        update_payout_status((int) $_POST['payout_id'], $_POST['status'] ?? 'accrued', (int) current_user()['id']);
                        flash('success', 'Payout status updated.');
                    }
                    if ($action === 'bulk_update_payout_status') {
                        $updated = bulk_update_payout_status(
                            is_array($_POST['payout_ids'] ?? null) ? $_POST['payout_ids'] : [],
                            (string) ($_POST['bulk_status'] ?? 'accrued'),
                            (int) current_user()['id']
                        );
                        flash('success', $updated . ' payout request(s) updated.');
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }

                redirect(url('payouts', $payoutRouteParams));
            }

            $payoutsResult = list_payouts_admin($payoutFilters);
            $payouts = $payoutsResult['items'] ?? [];
            $payoutPagination = $payoutsResult['pagination'] ?? [
                'page' => $payoutPage,
                'per_page' => $payoutPerPage,
                'total' => is_array($payouts) ? count($payouts) : 0,
                'total_pages' => 1,
                'from' => 0,
                'to' => 0,
            ];
            $title = 'Payouts - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/payouts.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'withdrawals':
            require_roles(['agent', 'admin', 'finance']);
            $user = current_user();

            if (method_is('POST')) {
                require_csrf();

                try {
                    $action = $_POST['form_action'] ?? '';

                    if ($action === 'request_withdrawal' && $user['role'] === 'agent') {
                        $amount = (float) ($_POST['amount'] ?? 0);
                        create_withdrawal_request((int) $user['id'], $amount, $_POST['note'] ?? '');
                        flash('success', 'Withdrawal request submitted.');
                    }

                    if ($action === 'update_withdrawal_status' && has_role(['admin', 'finance'])) {
                        update_withdrawal_request_status(
                            (int) ($_POST['request_id'] ?? 0),
                            $_POST['status'] ?? 'pending',
                            (int) $user['id'],
                            $_POST['admin_note'] ?? ''
                        );
                        flash('success', 'Withdrawal request updated.');
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }

                redirect(url('withdrawals'));
            }

            $withdrawals = list_withdrawal_requests_for_user($user);
            $withdrawalSummary = $user['role'] === 'agent' ? agent_withdrawal_summary((int) $user['id']) : null;

            $title = 'Withdrawals - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/withdrawals.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'announcement':
            require_login();
            $user = current_user();
            $announcementId = (int) ($_GET['id'] ?? 0);
            $announcement = get_announcement($announcementId);
            if (!$announcement || !can_access_announcement($user, $announcement)) {
                http_response_code(404);
                exit('Announcement not found.');
            }

            if (method_is('POST')) {
                require_csrf();
                if (($_POST['form_action'] ?? '') === 'acknowledge_announcement') {
                    acknowledge_announcement($announcementId, (int) $user['id']);
                    flash('success', 'Announcement marked as read.');
                    redirect(url('announcement', ['id' => $announcementId]));
                }
            }

            $title = 'Announcement - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/announcement.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'notifications':
            require_login();
            $user = current_user();

            $openNotificationId = (int) ($_GET['open'] ?? 0);
            if ($openNotificationId > 0) {
                $notification = get_notification_for_user($openNotificationId, (int) $user['id']);
                if ($notification) {
                    mark_notification_read($openNotificationId, (int) $user['id']);
                    $target = trim((string) ($notification['target_url'] ?? ''));
                    if ($target !== '') {
                        redirect($target);
                    }
                }

                redirect(url('notifications'));
            }

            if (method_is('POST')) {
                require_csrf();
                $action = $_POST['form_action'] ?? '';
                $isAjax = (string) ($_POST['ajax'] ?? '0') === '1';

                if ($action === 'read_one') {
                    mark_notification_read((int) ($_POST['notification_id'] ?? 0), (int) $user['id']);
                }

                if ($action === 'read_all') {
                    mark_all_notifications_read((int) $user['id']);
                }

                if ($action === 'clear_one') {
                    clear_notification((int) ($_POST['notification_id'] ?? 0), (int) $user['id']);
                }

                if ($action === 'clear_all') {
                    clear_all_notifications((int) $user['id']);
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'unread_count' => unread_notification_count((int) $user['id']),
                    ], JSON_UNESCAPED_SLASHES);
                    break;
                }

                redirect(url('notifications'));
            }

            $notifications = list_notifications_for_user((int) $user['id'], 100);

            $title = 'Notifications - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/notifications.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'support':
            require_roles(['client', 'admin', 'finance']);
            $user = current_user();
            $ticketFilters = [
                'status' => strtolower(trim((string) ($_GET['status'] ?? ''))),
                'priority' => strtolower(trim((string) ($_GET['priority'] ?? ''))),
                'sort' => strtolower(trim((string) ($_GET['sort'] ?? 'updated'))),
            ];

            if (method_is('POST')) {
                require_csrf();

                try {
                    $action = (string) ($_POST['form_action'] ?? '');

                    if ($action === 'create_ticket' && $user['role'] === 'client') {
                        $linkedJobIds=support_client_job_ids(
                            (int) $user['id'],$_POST['job_ids'] ?? []
                        );
                        $ticketId = create_support_ticket(
                            (int) $user['id'],
                            (string) ($_POST['subject'] ?? ''),
                            (string) ($_POST['message'] ?? ''),
                            (string) ($_POST['priority'] ?? 'normal'),
                            (string) ($_POST['category'] ?? 'general'),
                            $_FILES['attachment'] ?? null
                        );
                        support_link_ticket_jobs($ticketId,(int) $user['id'],$linkedJobIds);
                        flash('success', 'Support ticket created successfully.');
                        redirect(url('support', ['ticket_id' => $ticketId]));
                    }

                    if ($action === 'reply_ticket') {
                        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
                        $ticket = get_support_ticket($ticketId);
                        if (!$ticket || !can_access_support_ticket($user, $ticket)) {
                            throw new RuntimeException('Support ticket access denied.');
                        }

                        add_support_ticket_message(
                            $ticketId,
                            (int) $user['id'],
                            (string) ($_POST['message'] ?? ''),
                            $_FILES['attachment'] ?? null
                        );
                        flash('success', 'Support reply sent.');
                        redirect(url('support', ['ticket_id' => $ticketId]));
                    }

                    if ($action === 'update_ticket_status' && has_role(['admin', 'finance'])) {
                        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
                        update_support_ticket_status($ticketId, (string) ($_POST['status'] ?? 'open'), (int) $user['id']);
                        flash('success', 'Ticket status updated.');
                        redirect(url('support', ['ticket_id' => $ticketId]));
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                    redirect(url('support', ['ticket_id' => (int) ($_POST['ticket_id'] ?? 0)]));
                }
            }

            $tickets = list_support_tickets_for_user($user, $ticketFilters);
            $supportOrderOptions=($user['role'] ?? '')==='client'
                ? (list_accessible_jobs($user,['page'=>1,'per_page'=>100,'sort'=>'newest'])['items'] ?? [])
                : [];
            $ticketLinkedOrders=[];
            $activeTicket = null;
            $ticketMessages = [];
            $requestedTicketId = (int) ($_GET['ticket_id'] ?? 0);
            if ($requestedTicketId > 0) {
                $ticket = get_support_ticket($requestedTicketId);
                if ($ticket && can_access_support_ticket($user, $ticket)) {
                    $activeTicket = $ticket;
                    $ticketMessages = list_support_ticket_messages($requestedTicketId);
                    $ticketLinkedOrders=support_linked_jobs($requestedTicketId,$user);
                }
            }

            $title = 'Support - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/support.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'community':
            require_roles(['agent', 'admin']);
            $user = current_user();

            if (!can_access_community($user)) {
                http_response_code(403);
                exit('You do not have permission for this section.');
            }

            if (method_is('POST')) {
                require_csrf();

                try {
                    $action = (string) ($_POST['form_action'] ?? '');

                    if ($action === 'create_post') {
                        create_community_post(
                            (int) $user['id'],
                            (string) ($_POST['title'] ?? ''),
                            (string) ($_POST['body'] ?? '')
                        );
                        flash('success', 'Community post published.');
                    }

                    if ($action === 'toggle_like') {
                        toggle_community_post_like((int) ($_POST['post_id'] ?? 0), (int) $user['id']);
                    }

                    if ($action === 'add_comment') {
                        add_community_post_comment(
                            (int) ($_POST['post_id'] ?? 0),
                            (int) $user['id'],
                            (string) ($_POST['comment'] ?? '')
                        );
                    }

                    if ($action === 'set_post_visibility' && has_role(['admin'])) {
                        set_community_post_visibility(
                            (int) ($_POST['post_id'] ?? 0),
                            !empty($_POST['is_active']),
                            (int) $user['id']
                        );
                        flash('success', 'Post visibility updated.');
                    }

                    if ($action === 'delete_post' && has_role(['admin'])) {
                        delete_community_post((int) ($_POST['post_id'] ?? 0), (int) $user['id']);
                        flash('success', 'Community post deleted.');
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }

                redirect(url('community'));
            }

            $communityPosts = list_community_posts($user);
            $communityPostComments = list_community_comments_for_posts(array_map(static fn(array $row): int => (int) $row['id'], $communityPosts));
            $title = 'Community - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/community.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'settings':
            require_roles(['admin']);

            if (method_is('POST')) {
                require_csrf();

                try {
                    $action = $_POST['form_action'] ?? '';

                    if ($action === 'save_brand') {
                        $primary = trim($_POST['brand_primary'] ?? '#ff6600');
                        $primaryDark = trim($_POST['brand_primary_dark'] ?? '#111111');
                        $accent = trim($_POST['brand_accent'] ?? '#6b7280');
                        $brandDisplayName = trim((string) ($_POST['brand_name'] ?? brand_name()));
                        $portalTitle = trim((string) ($_POST['app_name'] ?? app_name()));
                        $companyLegalName = trim((string) ($_POST['company_legal_name'] ?? company_legal_name()));
                        $businessSummary = trim((string) ($_POST['business_support_summary'] ?? business_support_summary()));

                        if (!is_valid_color_hex($primary) || !is_valid_color_hex($primaryDark) || !is_valid_color_hex($accent)) {
                            throw new RuntimeException('Brand colors must be full hex values like #ff6600.');
                        }
                        if ($brandDisplayName === '') {
                            throw new RuntimeException('Brand name is required.');
                        }
                        if ($portalTitle === '') {
                            throw new RuntimeException('Website title is required.');
                        }
                        if ($companyLegalName === '') {
                            throw new RuntimeException('Company legal name is required.');
                        }
                        if ($businessSummary === '') {
                            throw new RuntimeException('Business support summary is required.');
                        }

                        $currency = strtoupper(trim($_POST['default_currency'] ?? default_currency()));
                        if (!in_array($currency, currency_options(), true)) {
                            throw new RuntimeException('Invalid default currency selected.');
                        }

                        $fxRate = (float) ($_POST['fx_ngn_per_usd'] ?? fx_ngn_per_usd());
                        if ($fxRate <= 0) {
                            throw new RuntimeException('NGN per USD conversion rate must be greater than zero.');
                        }

                        set_setting('brand_primary', $primary);
                        set_setting('brand_primary_dark', $primaryDark);
                        set_setting('brand_accent', $accent);
                        set_setting('brand_name', $brandDisplayName);
                        set_setting('app_name', $portalTitle);
                        set_setting('company_legal_name', $companyLegalName);
                        set_setting('business_support_summary', $businessSummary);
                        set_setting('brand_footer_text', trim($_POST['brand_footer_text'] ?? footer_text()));
                        set_setting('default_currency', $currency);
                        set_setting('fx_ngn_per_usd', (string) $fxRate);

                        flash('success', 'Brand settings updated.');
                    }

                    if ($action === 'upload_logo') {
                        if (!empty($_FILES['logo_file'])) {
                            $logoPath = save_uploaded_image($_FILES['logo_file'], 'branding');
                            set_setting('logo_path', $logoPath);
                            flash('success', 'Logo updated successfully.');
                        }
                    }

                    if ($action === 'upload_favicon') {
                        if (!empty($_FILES['favicon_file'])) {
                            $faviconPath = save_uploaded_image($_FILES['favicon_file'], 'branding');
                            set_setting('favicon_path', $faviconPath);
                            flash('success', 'Favicon updated successfully.');
                        }
                    }

                    if ($action === 'upload_partner_logos') {
                        $uploaded = add_website_partner_logos((int) current_user()['id'], $_FILES['partner_logo_files'] ?? null);
                        flash('success', $uploaded === 1 ? 'Partner logo uploaded.' : $uploaded . ' partner logos uploaded.');
                    }

                    if ($action === 'remove_partner_logo') {
                        remove_website_partner_logo((int) ($_POST['media_id'] ?? 0));
                        flash('success', 'Partner logo removed from the website strip.');
                    }

                    if ($action === 'save_rules') {
                        $minimum = max((float) ($_POST['withdrawal_minimum'] ?? 20000), 1);
                        $cooldown = max((int) ($_POST['withdrawal_cooldown_days'] ?? 14), 1);
                        $cryptoRefresh = max(5, min(30, (int) ($_POST['crypto_rates_refresh_minutes'] ?? 10)));

                        $trademarkPrice = filter_var($_POST['trademark_price_usd_per_class'] ?? null, FILTER_VALIDATE_FLOAT);
                        if ($trademarkPrice === false || $trademarkPrice < 1 || $trademarkPrice > 100000) {
                            throw new RuntimeException('Trademark price per class must be between USD 1 and USD 100,000.');
                        }
                        set_setting('withdrawal_minimum', (string) $minimum);
                        set_setting('withdrawal_cooldown_days', (string) $cooldown);
                        set_setting('crypto_rates_refresh_minutes', (string) $cryptoRefresh);
                        set_setting('trademark_price_usd_per_class', number_format((float) $trademarkPrice, 2, '.', ''));

                        flash('success', 'Business rules updated.');
                    }

                    if ($action === 'save_cac_pricing') {
                        $priceFields = [
                            'cac_business_base_ngn'=>26000,
                            'cac_business_extra_partner_ngn'=>15000,
                            'cac_company_base_ngn'=>0,
                            'cac_company_extra_director_ngn'=>0,
                            'cac_company_extra_shareholder_ngn'=>0,
                        ];
                        $validated=[];
                        foreach ($priceFields as $key=>$fallback) {
                            $value=filter_var($_POST[$key] ?? $fallback,FILTER_VALIDATE_FLOAT);
                            if ($value===false || !is_finite((float) $value) || $value<0 || $value>100000000) {
                                throw new RuntimeException('Invalid CAC fee for '.$key.'.');
                            }
                            if (in_array($key,['cac_business_base_ngn','cac_business_extra_partner_ngn'],true) && $value<1) {
                                throw new RuntimeException('Business Name base and partner fees must be greater than zero.');
                            }
                            $validated[$key]=number_format((float) $value,2,'.','');
                        }
                        foreach ($validated as $key=>$value) { set_setting($key,$value); }
                        flash('success','CAC registration pricing saved. New quotes use these amounts.');
                    }

                    if ($action === 'save_payment_method') {
                        save_payment_method($_POST);
                        flash('success', 'Payment method updated.');
                    }

                    if ($action === 'save_mail') {
                        set_setting('mail_enabled', !empty($_POST['mail_enabled']) ? '1' : '0');
                        set_setting('mail_from_name', trim((string) ($_POST['mail_from_name'] ?? app_name())));
                        set_setting('mail_from_email', trim((string) ($_POST['mail_from_email'] ?? 'no-reply@iworksgt.com')));
                        set_setting('email_notifications_enabled', !empty($_POST['email_notifications_enabled']) ? '1' : '0');

                        $otpExpiry = max((int) ($_POST['otp_expiry_minutes'] ?? 10), 1);
                        set_setting('otp_expiry_minutes', (string) $otpExpiry);

                        flash('success', 'Email and OTP settings saved.');
                    }

                    if ($action === 'save_google_auth') {
                        $enabled = !empty($_POST['google_auth_enabled']) ? '1' : '0';
                        $clientId = trim((string) ($_POST['google_client_id'] ?? ''));
                        // A blank admin field means keep the previously saved secret.
                        $clientSecret = trim((string) ($_POST['google_client_secret'] ?? ''));
                        if ($clientSecret === '') {
                            $clientSecret = google_client_secret();
                        }
                        $redirectOverride = trim((string) ($_POST['google_redirect_uri'] ?? ''));

                        if ($enabled === '1' && ($clientId === '' || $clientSecret === '')) {
                            throw new RuntimeException('Google Client ID and Client Secret are required when Google login is enabled.');
                        }

                        if ($redirectOverride !== '' && !preg_match('~^https?://~i', $redirectOverride)) {
                            throw new RuntimeException('Google redirect URL must start with http:// or https://');
                        }
                        if ($enabled === '1') {
                            $redirectCandidate = $redirectOverride !== ''
                                ? $redirectOverride
                                : portal_absolute_url(url('google_callback'));
                            $issues = google_oauth_redirect_problems($redirectCandidate, portal_base_url());
                            if ($issues !== []) {
                                throw new RuntimeException('Google callback configuration: ' . implode(' ', $issues));
                            }
                        }

                        set_setting('google_auth_enabled', $enabled);
                        set_setting('google_client_id', $clientId);
                        set_setting('google_client_secret', $clientSecret);
                        set_setting('google_redirect_uri', $redirectOverride);

                        flash('success', 'Google authentication settings saved.');
                    }

                    if ($action === 'save_ai') {
                        $aiEnabled = !empty($_POST['ai_enabled']) ? '1' : '0';
                        $aiProvider = strtolower(trim((string) ($_POST['ai_provider'] ?? 'openai')));
                        $aiModel = trim((string) ($_POST['ai_model'] ?? 'gpt-4o-mini'));
                        $aiApiKey = trim((string) ($_POST['ai_api_key'] ?? ''));

                        if ($aiProvider === '') {
                            $aiProvider = 'openai';
                        }
                        if ($aiModel === '') {
                            $aiModel = 'gpt-4o-mini';
                        }

                        if ($aiEnabled === '1' && $aiApiKey === '') {
                            throw new RuntimeException('AI API key is required when AI generation is enabled.');
                        }

                        set_setting('ai_enabled', $aiEnabled);
                        set_setting('ai_provider', $aiProvider);
                        set_setting('ai_model', $aiModel);
                        set_setting('ai_api_key', $aiApiKey);

                        flash('success', 'AI form generation settings saved.');
                    }

                    if ($action === 'save_seo_social') {
                        set_setting('seo_description', trim((string) ($_POST['seo_description'] ?? seo_description())));
                        set_setting('seo_tags', trim((string) ($_POST['seo_tags'] ?? seo_tags())));
                        set_setting('social_facebook', trim((string) ($_POST['social_facebook'] ?? '')));
                        set_setting('social_instagram', trim((string) ($_POST['social_instagram'] ?? '')));
                        set_setting('social_x', trim((string) ($_POST['social_x'] ?? '')));
                        set_setting('social_linkedin', trim((string) ($_POST['social_linkedin'] ?? '')));
                        set_setting('social_tiktok', trim((string) ($_POST['social_tiktok'] ?? '')));
                        set_setting('social_youtube', trim((string) ($_POST['social_youtube'] ?? '')));

                        flash('success', 'SEO and social links updated.');
                    }

                    if ($action === 'save_banner') {
                        save_banner($_POST, $_FILES['banner_image_desktop'] ?? null, $_FILES['banner_image_mobile'] ?? null);
                        flash('success', 'Banner saved.');
                    }

                    if ($action === 'delete_banner') {
                        delete_banner((int) ($_POST['banner_id'] ?? 0));
                        flash('success', 'Banner deleted.');
                    }

                    if ($action === 'save_announcement') {
                        save_announcement($_POST, (int) current_user()['id'], $_FILES['announcement_attachment'] ?? null);
                        flash('success', 'Announcement saved.');
                    }

                    if ($action === 'delete_announcement') {
                        delete_announcement((int) ($_POST['announcement_id'] ?? 0));
                        flash('success', 'Announcement deleted.');
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }

                redirect(url('settings'));
            }

            $paymentMethods = list_payment_methods(false);
            $banners = list_banners(false);
            $announcements = list_announcements(false);
            $editBanner = null;
            $editAnnouncement = null;

            if (($_GET['action'] ?? '') === 'edit-banner' && !empty($_GET['id'])) {
                $editBanner = db_one('SELECT * FROM banners WHERE id = :id LIMIT 1', ['id' => (int) $_GET['id']]);
            }

            if (($_GET['action'] ?? '') === 'edit-announcement' && !empty($_GET['id'])) {
                $editAnnouncement = get_announcement((int) $_GET['id']);
            }

            $title = 'Settings - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/settings.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'exports':
            require_roles(['admin', 'finance']);
            $title = 'Export Data - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            require __DIR__ . '/views/exports.php';
            require __DIR__ . '/views/partials/footer.php';
            break;

        case 'profile':
            require_login();

            if (method_is('POST')) {
                require_csrf();
                $action = $_POST['form_action'] ?? '';
                $user = current_user();
                try {
                    if ($action === 'update_profile') {
                        $fullName = trim((string) ($_POST['full_name'] ?? ''));
                        $firstName = trim((string) ($_POST['first_name'] ?? ''));
                        $lastName = trim((string) ($_POST['last_name'] ?? ''));
                        if ($fullName === '' && ($firstName === '' || $lastName === '')) {
                            throw new RuntimeException('Provide full name or both first and last name.');
                        }

                        db_execute(
                            'UPDATE users
                             SET full_name = :full_name,
                                 first_name = :first_name,
                                 last_name = :last_name,
                                 phone = :phone,
                                 gender = :gender,
                                 date_of_birth = :date_of_birth,
                                 country = :country,
                                 state_region = :state_region,
                                 city = :city,
                                 address_line = :address_line,
                                 occupation = :occupation
                             WHERE id = :id',
                            [
                                'full_name' => $fullName !== '' ? $fullName : trim($firstName . ' ' . $lastName),
                                'first_name' => $firstName !== '' ? $firstName : null,
                                'last_name' => $lastName !== '' ? $lastName : null,
                                'phone' => trim($_POST['phone'] ?? ''),
                                'gender' => trim((string) ($_POST['gender'] ?? '')) ?: null,
                                'date_of_birth' => trim((string) ($_POST['date_of_birth'] ?? '')) ?: null,
                                'country' => trim((string) ($_POST['country'] ?? '')) ?: null,
                                'state_region' => trim((string) ($_POST['state_region'] ?? '')) ?: null,
                                'city' => trim((string) ($_POST['city'] ?? '')) ?: null,
                                'address_line' => trim((string) ($_POST['address_line'] ?? '')) ?: null,
                                'occupation' => trim((string) ($_POST['occupation'] ?? '')) ?: null,
                                'id' => $user['id'],
                            ]
                        );
                        flash('success', 'Profile updated.');
                    }

                    if ($action === 'update_bank' && $user['role'] !== 'client') {
                        db_execute(
                            'UPDATE users SET bank_name = :bank_name, bank_account_name = :bank_account_name,
                             bank_account_number = :bank_account_number, bank_swift = :bank_swift, bank_currency = :bank_currency
                             WHERE id = :id',
                            [
                                'bank_name' => trim($_POST['bank_name'] ?? ''),
                                'bank_account_name' => trim($_POST['bank_account_name'] ?? ''),
                                'bank_account_number' => trim($_POST['bank_account_number'] ?? ''),
                                'bank_swift' => trim($_POST['bank_swift'] ?? ''),
                                'bank_currency' => trim($_POST['bank_currency'] ?? default_currency()),
                                'id' => $user['id'],
                            ]
                        );
                        flash('success', 'Bank details saved.');
                    }

                    if ($action === 'update_password') {
                        change_user_password(
                            (int) $user['id'],
                            (string) ($_POST['current_password'] ?? ''),
                            (string) ($_POST['new_password'] ?? ''),
                            (string) ($_POST['confirm_password'] ?? '')
                        );
                        flash('success', 'Password changed successfully.');
                    }

                    if ($action === 'update_pay_currency') {
                        $currency = normalize_payment_currency((string) ($_POST['pay_currency'] ?? ''));
                        set_active_pay_currency($currency);
                        flash('success', 'Preferred pay currency updated.');
                    }
                } catch (Throwable $e) {
                    flash('error', $e->getMessage());
                }

                redirect(url('profile'));
            }

            $title = 'My Profile - ' . app_name();
            require __DIR__ . '/views/partials/header.php';
            if ((current_user()['role'] ?? '') === 'client') {
                require __DIR__ . '/views/client-profile.php';
            } else {
                require __DIR__ . '/views/profile.php';
            }
            require __DIR__ . '/views/partials/footer.php';
            break;

        default:
            http_response_code(404);
            echo 'Page not found.';
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    $appTitle = app_name();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?= e('Error - ' . $appTitle) ?></title>
      <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; }
        .wrap { max-width: 760px; margin: 40px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 24px rgba(15,23,42,0.08); }
        .card-body { padding: 20px; }
        .err { color: #991b1b; }
        .muted { color: #64748b; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }
      </style>
    </head>
    <body>
      <div class="wrap">
        <div class="card">
          <div class="card-body">
            <h1 style="margin-top:0;">System error</h1>
            <p class="err"><strong><?= e($e->getMessage()) ?></strong></p>
            <p class="muted">Check <code>app/config.php</code>, database credentials, and rerun <code>setup.php</code> if required.</p>
          </div>
        </div>
      </div>
    </body>
    </html>
    <?php
}
