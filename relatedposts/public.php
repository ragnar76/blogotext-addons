<?php
# *** LICENSE ***
# This file is a addon for BlogoText.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# 2016 Mickaël S. <contact@tiger-222.fr>
# *** LICENSE ***

// Include the posts list.
// To use in theme/$theme/post.html.
function a_relatedposts()
{
    $nbPosts = addon_get_setting('relatedposts', 'nb_posts');

    // 1. Get the post ID
    $postId = (string)filter_input(INPUT_GET, 'd');
    if (preg_match('#^\d{4}(/\d{2}){5}#', $postId)) {
        $postId = (int)substr(str_replace('/', '', $postId), 0, 14);
    } elseif (preg_match('#^\d{14}#', $postId)) {
        $postId = (int)substr($postId, 0, 14);
    }

    // 2. Get post tags
    try {
        $sql = $GLOBALS['db_handle']->prepare(
            'SELECT bt_tags
               FROM articles
              WHERE bt_statut = 1
                    AND bt_id = :id'
        );
        $sql->bindValue(':id', $postId, SQLITE3_INTEGER);
        $sql->execute();
        $tags = $sql->fetchAll(PDO::FETCH_ASSOC);
        $tags = current($tags);
    } catch (Exception $e) {
        return ((bool)DISPLAY_PHP_ERRORS) ? 'Error step 2 addon_relatedposts() : '.$e->getMessage() : '';
    }

    // 3. Find related posts based on a random tag from current article
    $tags = explode(', ', $tags['bt_tags']);
    shuffle($tags);
    $tag = current($tags);
    try {
        $sql = $GLOBALS['db_handle']->prepare(
            'SELECT bt_id, bt_title
               FROM articles
              WHERE bt_statut = 1
                    AND bt_id != :id
                    AND bt_tags LIKE :tag'
        );
        $sql->bindValue(':id', $postId, SQLITE3_INTEGER);
        $sql->bindValue(':tag', '%'.$tag.'%');
        $sql->execute();
        $relatedPosts = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return ((bool)DISPLAY_PHP_ERRORS) ? 'Error step 3 addon_relatedposts() : '.$e->getMessage() : '';
    }
    shuffle($relatedPosts);
    $relatedPosts = array_slice($relatedPosts, 0, $nbPosts);

    // 4. Generate the list
    $html = '<div class="related-posts">';
    $html .= '<p>';
        $html .= sprintf(addon_get_setting('relatedposts', 'sentence'), '<span class="category">'.htmlentities($tag).'</span>');
    $html .= '</p>';
    $html .= '<ul>';
    foreach ($relatedPosts as $post) {
        $decId = decode_id($post['bt_id']);
        $html .= '<li><a href="?d='.implode('/', $decId).'-'.title_url($post['bt_title']).'">'.$post['bt_title'].'</a></li>';
    }
    $html .= '</ul>';
    $html .= '</div>';

    return $html;
}