<?php
namespace App;
class ViewBuilder
{
    private static array $TWO_PART_TAGS = ['POSTS'];

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

        $parts = explode(':', $key);
        $space = trim($parts[0]);
        $tag = trim($parts[1] ?? '');
        if (in_array($space, self::$TWO_PART_TAGS)) {
            return $this->processed_two_part_tag($space, $tag);
        }

        return match ($space) {
            'CSS' => $this->enqueue_style($tag),
            'WP_HEAD' => '{{ WP_HEAD }}',
            'WP_FOOTER' => '{{ WP_FOOTER }}',
            default => '',
        };
    }

    private function processed_two_part_tag(string $space, string $tag): string
    {
        if ($tag !== 'START') return '';

        $startCursor = $this->cursor;

        $pattern = '/{{\s*' . $space . ':END\s*}}/';

        if (preg_match($pattern, $this->blade, $matches, PREG_OFFSET_CAPTURE, $startCursor)) { // PREG_OFFSET_CAPTURE tells regex to return the string position index too
            $endTagPos = $matches[0][1];
            $length = strlen($matches[0][0]);

            $inbetween = substr($this->blade, $startCursor, $endTagPos - $startCursor);

            $this->cursor = $endTagPos + $length;

            return match ($space) {
                'POSTS' => $this->processed_posts($inbetween),
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
        $post_html = $bladeSource;

        $POST_KEYS = [
            'CLASS' => in_category('3') ? 'post-cat-three' : 'post',
            'TITLE' => get_the_title(),
            'PERMALINK' => get_permalink(),
            'THUMBNAIL' => get_the_post_thumbnail(),
            'CONTENT' => get_the_content(),
            'TIME' => get_the_time('F jS, Y'),
            'AUTHOR_POSTS_LINK' => get_the_author_posts_link(),
            'CATEGORIES' => get_the_category_list(', ')
        ];

        foreach ($POST_KEYS as $key => $value) {
            $pattern = '/{{\s*' . $key . '\s*}}/'; // \s* is for ignoring whitespace chars
            $post_html = preg_replace($pattern, $value, $post_html);
        }

        return $post_html;
    }

    private function enqueue_style(string $filePath): string
    {
        $handle = 'style-' . pathinfo($filePath, PATHINFO_FILENAME);
        $fileUrl = THEME_RESOURCES_URI . $filePath;

        wp_enqueue_style($handle, $fileUrl);

        return '';
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