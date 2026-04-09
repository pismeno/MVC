<?php
namespace App;

class ViewBuilder
{
    private static array $TWO_PART_TAGS = ['POSTS', 'MENU'];

    private string $bladePath;
    private string $blade;
    private array $globalTags;

    private int $cursor = 0;

    public function __construct(string $bladePath)
    {
        $this->globalTags = [
            'PAGINATION' => get_the_posts_pagination(),
        ];

        $this->bladePath = $bladePath;
        $this->blade = $this->get_the_blade();
    }

    protected function get_the_blade() : string
    {
        return file_get_contents(THEME_RESOURCES_PATH . $this->bladePath);
    }
    public function build() : string
    {
        $bladeLength = strlen($this->blade);
        $view = '';

        while ($this->cursor < $bladeLength) {
            $view .= $this->next_key();
        }
        $view .= $this->rest_of_blade();

        $wp_head_html = self::ob_action('wp_head');
        $wp_footer_html = self::ob_action('wp_footer');

        $view = str_replace('{{ WP_HEAD }}', $wp_head_html, $view);
        $view = str_replace('{{ WP_FOOTER }}', $wp_footer_html, $view);

        return $view;
    }

    private function next_key(): string
    {
        $startCursor = $this->cursor;
        $nextBracket = strpos($this->blade, '{{', $startCursor);

        if ($nextBracket === false) {
            $this->cursor = strlen($this->blade);
            return substr($this->blade, $startCursor);
        }

        $before = substr($this->blade, $startCursor, $nextBracket - $startCursor);
        $endOfBrackets = strpos($this->blade, '}}', $nextBracket);

        if ($endOfBrackets !== false) {
            $keyStart = $nextBracket + 2;
            $length = $endOfBrackets - $keyStart;
            $key = trim(substr($this->blade, $keyStart, $length));

            $this->cursor = $endOfBrackets + 2;

            return $before . $this->replaced_key($key);
        }

        return $before;
    }

    private function replaced_key(string $key): string
    {
        if (array_key_exists($key, $this->globalTags)) {
            return $this->globalTags[$key];
        }

        $parts = explode(':', $key, 2);
        $space = trim($parts[0]);
        $tag = trim($parts[1] ?? '');
        if (in_array($space, self::$TWO_PART_TAGS)) {
            return $this->processed_two_part_tag($space, $tag);
        }

        return match ($space) {
            'CSS' => $this->enqueue_style($tag),
            'CSS_REMOTE' => $this->enqueue_style_full_uri($tag),
            'PATH' => $this->full_path($tag),
            'WP_HEAD' => '{{ WP_HEAD }}',
            'WP_FOOTER' => '{{ WP_FOOTER }}',
            default => '',
        };
    }

    private function processed_two_part_tag(string $space, string $tag): string
    {
        $parts = explode(':', $tag, 2);
        $param = '';
        $action = $tag;

        if (count($parts) === 2) {
            $param = trim($parts[0]);
            $action = trim($parts[1]);
        }

        if ($action !== 'START') return '';

        $startCursor = $this->cursor;

        if ($param) {
            $pattern = '/{{\s*' . preg_quote($space) . ':' . preg_quote($param) . ':END\s*}}/';
        } else {
            $pattern = '/{{\s*' . preg_quote($space) . ':END\s*}}/';
        }

        if (preg_match($pattern, $this->blade, $matches, PREG_OFFSET_CAPTURE, $startCursor)) {
            $endTagPos = $matches[0][1];
            $length = strlen($matches[0][0]);

            $inbetween = substr($this->blade, $startCursor, $endTagPos - $startCursor);
            $this->cursor = $endTagPos + $length;

            return match ($space) {
                'POSTS' => $this->processed_posts($inbetween),
                'MENU'  => $this->processed_menu($param, $inbetween),
                default => '',
            };
        }

        return '';
    }
    private function processed_posts(string $bladeSource): string
    {
        $all_posts = "";

        if (have_posts()) {
            while (have_posts()) {
                the_post();

                $all_posts .= $this->processed_post($bladeSource);
            }
        }

        return $all_posts;
    }

    private function processed_post(string $bladeSource): string
    {
        return preg_replace_callback('/{{\s*(.*?)\s*}}/', function($matches) {
            return $this->processed_post_key(trim($matches[1]));
        }, $bladeSource);
    }

    private function processed_post_key(string $key): string
    {
        $standard_value = match ($key) {
            'CLASS' => in_category('3') ? 'post-cat-three' : 'post',
            'TITLE' => get_the_title(),
            'PERMALINK' => get_permalink(),
            'THUMBNAIL' => get_the_post_thumbnail(),
            'CONTENT' => get_the_content(),
            'TIME' => get_the_time('F jS, Y'),
            'AUTHOR_POSTS_LINK' => get_the_author_posts_link(),
            'CATEGORIES' => get_the_category_list(', '),
            default => null,
        };

        if ($standard_value !== null) {
            return htmlspecialchars($standard_value);
        }

        $parts = explode(':', $key, 2);
        if (count($parts) === 2) {
            $space = trim($parts[0]);
            $tag = trim($parts[1]);

            if ($space === 'FIELD') {
                return (string) \get_post_meta(\get_the_ID(), $tag, true);
            }
        }

        return '';
    }

    private function processed_menu(string $param, string $bladeSource): string
    {
        $locations = get_nav_menu_locations();

        if (!isset($locations[$param])) {
            return '';
        }

        $menu = \wp_get_nav_menu_object($locations[$param]);
        if (!$menu) return '';

        $items = \wp_get_nav_menu_items($menu->term_id);
        if (!$items) return '';

        $all_items_html = '';

        foreach ($items as $item) {
            $all_items_html .= $this->processed_menu_item($bladeSource, $item);
        }

        return $all_items_html;
    }

    private function processed_menu_item(string $bladeSource, $item): string
    {
        return preg_replace_callback('/{{\s*(.*?)\s*}}/', function($matches) use ($item) {
            return $this->processed_menu_item_key(trim($matches[1]), $item);
        }, $bladeSource);
    }

    private function processed_menu_item_key(string $key, $item): string
    {
        $value = match ($key) {
            'TITLE' => $item->title,
            'URL' => $item->url,
            'ATTRIBUTES' => $item->attr_title,
            'TARGET' => $item->target,
            'DESCRIPTION' => $item->description,
            default => null,
        };

        return htmlspecialchars($value ?? '');
    }

    private function enqueue_style(string $filePath): string
    {
        return $this->enqueue_style_full_uri(THEME_RESOURCES_URI . $filePath);
    }

    private function enqueue_style_full_uri(string $fullUri): string
    {
        $parsedPath = parse_url($fullUri, PHP_URL_PATH);

        $filename = pathinfo($parsedPath, PATHINFO_FILENAME);
        $extension = pathinfo($parsedPath, PATHINFO_EXTENSION);

        if (empty($filename) || empty($extension)) {
            $filename = md5($fullUri);
        }

        $handle = 'style-' . sanitize_title($filename);

        wp_enqueue_style($handle, $fullUri);

        return '';
    }

    private function full_path(string $filePath): string
    {
        return THEME_RESOURCES_URI . $filePath;
    }

    private function rest_of_blade(): string
    {
        return substr($this->blade, $this->cursor);
    }

    private static function ob_action(string $action) : string
    {
        ob_start();
        do_action($action);
        return ob_get_clean();
    }
}