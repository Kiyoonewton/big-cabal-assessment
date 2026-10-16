<?php
// Accept a positive integer; otherwise use page 1.
$raw_page = $_GET['page'] ?? '1';

$page = is_string($raw_page)
    ? filter_var(
        $raw_page,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    )
    : false;

$page = $page === false ? 1 : $page;

// WordPress adds slashes to request values.
$raw_search = $_GET['s'] ?? '';
$search = is_string($raw_search)
    ? sanitize_text_field(wp_unslash($raw_search))
    : '';

$q = new WP_Query([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 10,
    'paged'         => $page,
    's'             => $search,
]);

$pages = (int) $q->max_num_pages;
?>

<h1>
    <?php if ($pages > 0) : ?>
        Page <?= esc_html((string) $page) ?>
        of <?= esc_html((string) $pages) ?>
    <?php else : ?>
        No results
    <?php endif; ?>

    <?php if ($search !== '') : ?>
        : Search for <?= esc_html($search) ?>
    <?php endif; ?>
</h1>

<?php if ($q->have_posts()) : ?>
    <?php while ($q->have_posts()) : ?>
        <?php
        $q->the_post();

        // Apply normal WordPress content formatting before inserting divs.
        $content = apply_filters('the_content', get_the_content());
        $ad_number = 0;

        // Remove a paragraph wrapper around a standalone marker.
        $content = preg_replace(
            '#<p>\s*\[ad\]\s*</p>#',
            '[ad]',
            $content
        );

        $content = preg_replace_callback(
            '#\[ad\]#',
            static function () use (&$ad_number) {
                $ad_number++;

                return '<div class="ad ad-' . $ad_number . '"></div>';
            },
            $content
        );
        ?>

        <article>
            <h2><?= esc_html(get_the_title()) ?></h2>

            <div class="entry-content">
                <?php
                // Intentional HTML: normal WordPress content plus ad divs.
                echo $content;
                ?>
            </div>
        </article>
    <?php endwhile; ?>
<?php else : ?>
    <p>No posts found for this page.</p>
<?php endif; ?>

<?php wp_reset_postdata(); ?>