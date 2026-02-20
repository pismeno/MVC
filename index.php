<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <?php wp_head(); ?>
</head>
<body>
<?php
if (have_posts()) {
    while (have_posts()) {
        the_post();
        $class = in_category('3') ? 'post-cat-three' : 'post';
        echo "<div class='" . $class . "'>";
        echo "<h2><a href='" . get_permalink() . "' rel='bookmark' title='Permanent Link to " . the_title_attribute() . "'>" . get_the_title() . "</a></h2>";
        echo "<small>" . get_the_time('F jS, Y') . " by " . get_the_author_posts_link() . "</small>";
        echo "<div class='entry'>" . get_the_content() . "</div>";
        echo "<p class='postmetadata'>Posted in " . get_the_category_list(', ') . "</p>";
        echo "</div>";
    }
} else {
    echo "<p>Sorry, no posts matched your criteria.</p>";
}
?>
<?php wp_footer(); ?>
</body>
</html>