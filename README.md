# BCM Developer Test

Code, short answers, and check evidence for the ten assessment questions.

## Requirements

- Docker Desktop with Docker Compose
- A modern browser
- Internet access for the initial Docker image downloads

PHP runs inside the WordPress container; a local PHP installation is optional.

## Project contents

| Path           | Purpose                                      |
| -------------- | -------------------------------------------- |
| `compose.yaml` | Local WordPress and MariaDB environment      |
| `theme/`       | Question 1 WordPress theme                   |
| `q4/`          | Responsive HTML and CSS layout               |
| `q5/`          | Corrected HTML and local JPEG                |
| `q6/`          | Paragraph-copying and visibility JavaScript  |
| `answers.md`   | Written answers, assumptions, and pseudocode |
| `evidence/`    | Screenshots and check results                |

Questions 2, 3, 7, 8, and 10 are written responses.
Question 9 uses pseudocode and describes proposed mock checks;
it is not an executable integration.

## Start WordPress

From the repository root:

```bash
docker compose up -d
```

Open:

http://localhost:8085/

On a fresh installation, complete the WordPress setup using local
test credentials. Then activate **BCM Test** under Appearance → Themes.

Alternatively, after completing installation:

```bash
docker compose exec -T --user www-data wordpress php -r '
require "/var/www/html/wp-load.php";
switch_theme("bcm-test");
echo "Active theme: " . get_stylesheet() . PHP_EOL;
'
```

Expected theme: `bcm-test`.

The database and WordPress installation persist in Docker volumes.
Local credentials in the Compose file are for development only.

## Create sample posts

Run the following after WordPress installation. It creates 12 published
posts and skips existing posts with the same slugs.

```bash
docker compose exec -T --user www-data wordpress php <<'PHP'
<?php
require '/var/www/html/wp-load.php';

for ($i = 1; $i <= 12; $i++) {
    $number = sprintf('%02d', $i);
    $slug = "bcm-test-$number";

    if (get_page_by_path($slug, OBJECT, 'post')) {
        echo "Skipped: BCM Test $number\n";
        continue;
    }

    $content = $i === 12
        ? "<p>Before the first advertisement.</p>
           <p>[ad]</p>
           <p>Between the first and second advertisements.</p>
           <p>[ad]</p>
           <p>Between the second and third advertisements.</p>
           <p>[ad]</p>
           <p>After the final advertisement.</p>"
        : "<p>This is BCM pagination test post $number.</p>
           <p>Sample content for checking search and pagination.</p>";

    $id = wp_insert_post([
        'post_title'   => "BCM Test $number",
        'post_name'    => $slug,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'post',
    ], true);

    echo is_wp_error($id)
        ? "Failed: {$id->get_error_message()}\n"
        : "Created: BCM Test $number (ID $id)\n";
}
PHP
```

## Question 1: pagination, search, and ad markers

Check PHP syntax:

```bash
docker compose exec -T wordpress \
  php -l /var/www/html/wp-content/themes/bcm-test/pagination.php
```

Run these browser checks:

| URL                                      | Expected result                                             |
| ---------------------------------------- | ----------------------------------------------------------- |
| `http://localhost:8085/`                 | First page, at most 10 posts, no missing-parameter warnings |
| `http://localhost:8085/?page=2`          | Second page of posts                                        |
| `http://localhost:8085/?s=BCM&page=2`    | Two remaining BCM test posts                                |
| `http://localhost:8085/?s=zzznomatch123` | No-results heading and empty-state message                  |
| `http://localhost:8085/?s=BCM+Test+12`   | Matching test post with three ad placeholders               |

If the default “Hello world!” post remains published, there are
13 posts overall: 10 on page 1 and 3 on page 2.

On the ad test page, run in the browser console:

```javascript
document.querySelectorAll(".entry-content .ad").length;
// Expected: 3

document.querySelector(".entry-content").textContent.includes("[ad]");
// Expected: false
```

Ad markers are case-sensitive standalone content blocks, outside
attributes, scripts, links, and code samples. Ad numbering restarts
within each article. The advertising script itself is outside scope.

## Open the HTML exercises

Serve the repository with PHP in Docker:

```bash
docker run --rm -p 8086:8000 \
  -v "$PWD:/app:ro" -w /app \
  php:8.3-cli php -S 0.0.0.0:8000
```

Keep that terminal running, then open:

- Question 4: http://localhost:8086/q4/
- Question 5: http://localhost:8086/q5/
- Question 6: http://localhost:8086/q6/

Press Ctrl+C to stop this server.

Alternatively, open each HTML file directly in a browser.

## Question 4: responsive layout

Use the browser's responsive device toolbar.

| Width  | Expected layout                          |
| ------ | ---------------------------------------- |
| 1280px | Three columns: `A A B`, `C D E`, `F G H` |
| 375px  | One column: A, D, B, C, G                |

The breakpoint is 768px. E, F, and H are hidden below it.

Temporarily replace A's heading with a long sentence and an unbroken
word. Check both widths for wrapping and horizontal overflow, then
restore the heading.

Source-order and accessibility tradeoffs are documented in `answers.md`.

## Question 5: HTML validation

Confirm that `q5/test.jpeg` loads in the browser.

Upload `q5/index.html` to:

https://validator.w3.org/nu/#file

The submitted validation screenshot reports no errors or warnings.
Informational messages about trailing slashes on void elements are
not validation failures.

The sample image is treated as decorative with `alt=""`.

## Question 6: JavaScript behaviour

Open Question 6 and refresh to reset it. The second button must be
created by JavaScript.

Define this helper in the browser console:

```javascript
function check() {
  console.table({
    paragraphs: document.querySelectorAll("#paragraphs > p").length,
    HRs: document.querySelectorAll("#paragraphs > hr").length,
    hidden: document.querySelectorAll("#paragraphs > p[hidden]").length,
  });
}
check();
```

Perform the following sequence without refreshing. Run `check()`
after each action.

| Action                | Paragraphs | HRs | Hidden |
| --------------------- | ---------: | --: | -----: |
| Initial load          |          3 |   1 |      0 |
| Hide image paragraphs |          3 |   1 |      1 |
| Add once              |          6 |   2 |      2 |
| Add again             |          9 |   4 |      3 |
| Show image paragraphs |          9 |   4 |      0 |
| Add again             |         12 |   5 |      0 |
| Hide again            |         12 |   5 |      4 |

Also inspect the Elements panel: an HR must follow every second
paragraph only when another paragraph follows, counting hidden ones.
There must be no trailing HR.

Each addition contains one copy of each original paragraph.
The order is randomised; occasionally matching the original order
is a valid outcome.

## Evidence and limitations

Screenshots are under `evidence/`, grouped by question.

- Q1: pagination, empty results, and ad-placeholder checks
- Q4: desktop/mobile layouts and long-heading checks
- Q5: HTML validation result
- Q6: repeated additions and visibility-toggle checks

The written troubleshooting and security scenarios are reasoned
responses, not claims of production testing. Question 9's mock checks
are proposed checks, not executed automated tests.

## Stop WordPress

```bash
docker compose down
```

This stops the services while preserving their data volumes.

## AI assistance

I used ChatGPT to help identify PHP and HTML errors, draft code for
pagination, responsive layout, and paragraph interactions, structure
the written answers, and troubleshoot the local Docker setup.

I reviewed and adapted the suggestions, checked PHP syntax, tested
pagination/search and ad placeholders in local WordPress, validated
the corrected HTML with the W3C Nu HTML Checker, and manually checked
responsive layouts and JavaScript paragraph/separator/visibility counts.
Screenshots are included in `evidence/`.

The troubleshooting, API-reliability, and security answers describe
proposed approaches; they were not verified against production systems.
# big-cabal-assessment
# big-cabal-assessment
