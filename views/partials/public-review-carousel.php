<?php
/**
 * Shared social proof for Home and About.
 * Only completed-job reviews with a customer's explicit publication consent
 * are eligible; no authored/testimonial fixtures are displayed in production.
 */
$iwPublishedReviews = function_exists('list_public_job_reviews') ? list_public_job_reviews(4) : [];
$iwGoogleMaps = 'https://www.google.com/maps/search/?api=1&query=iWorks%20Global%20Technologies%20Ado-Ekiti&query_place_id=ChIJqXoe-eP6RxARiguk7ysp-8g';
?>
<section class="iw-reviews" aria-labelledby="iwReviewsTitle">
  <div class="container public-container">
    <div class="iw-reviews-intro">
      <a class="iw-reviews-google" href="<?= e($iwGoogleMaps) ?>" target="_blank" rel="noopener noreferrer" aria-label="See the verified iWorks Global Google business listing">
        <span class="iw-reviews-google-score">4.8 <span>/ 5</span></span>
        <span class="iw-reviews-google-caption"><i class="bi bi-star-fill" aria-hidden="true"></i> On Google <i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
      </a>
      <h2 id="iwReviewsTitle">Customer reviews</h2>
      <p>Feedback from customers after their orders are completed.</p>
    </div>
    <?php if ($iwPublishedReviews !== []): ?>
      <div class="iw-review-carousel" data-iw-reviews>
        <div class="iw-review-carousel-top">
          <span><?= count($iwPublishedReviews) ?> recent verified <?= count($iwPublishedReviews) === 1 ? 'review' : 'reviews' ?></span>
          <?php if (count($iwPublishedReviews) > 1): ?>
            <div class="iw-review-arrows">
              <button type="button" data-iw-review-prev aria-label="Previous customer review"><i class="bi bi-arrow-left" aria-hidden="true"></i></button>
              <button type="button" data-iw-review-next aria-label="Next customer review"><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            </div>
          <?php endif; ?>
        </div>
        <div class="iw-review-viewport" data-iw-review-viewport tabindex="0" role="region" aria-roledescription="carousel" aria-label="Customer testimonials">
          <?php foreach ($iwPublishedReviews as $review): ?>
            <?php $rating = max(1, min(10, (int) ($review['rating'] ?? 0))); ?>
            <figure class="iw-review-slide">
              <div class="iw-review-stars" aria-label="Rating <?= $rating ?> out of 10"><i class="bi bi-star-fill" aria-hidden="true"></i> <?= $rating ?>/10</div>
              <blockquote><?= e((string) ($review['feedback'] ?? '')) ?></blockquote>
              <figcaption>
                <strong><?= e((string) ($review['display_name'] ?? 'Verified customer')) ?></strong>
                <span><?= e((string) ($review['service_name'] ?? 'Completed service')) ?> · Verified order</span>
              </figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <p class="iw-reviews-empty">Reviews from completed portal orders will appear here when customers choose to share them.</p>
    <?php endif; ?>
  </div>
</section>