<?php

/**
 * @file
 * Template to display a view as a table.
 *
 * - $title : The title of this group of rows.  May be empty.
 * - $header: An array of header labels keyed by field id.
 * - $caption: The caption for this table. May be empty.
 * - $header_classes: An array of header classes keyed by field id.
 * - $fields: An array of CSS IDs to use for each field id.
 * - $classes: A class or classes to apply to the table, based on settings.
 * - $row_classes: An array of classes to apply to each row, indexed by row
 *   number. This matches the index in $rows.
 * - $rows: An array of row items. Each row is an array of content.
 *   $rows are keyed by row number, fields within rows are keyed by field ID.
 * - $field_classes: An array of classes to apply to each field, indexed by
 *   field id, then row number. This matches the index in $rows.
 * @ingroup views_templates
 */

$color_warning = "LemonChiffon";
$color_error = "LightPink";

// Used for calculating conflicted post
$nids = get_nid_array_by_rows($rows);
$assignNodes = array();
foreach ($nids as $nid) {
    $assignNodes[$nid] = (array)node_load($nid);
}
$lang = end($assignNodes)['language'];

$termsCache = cache_get('one_time_serving_post_term_cache', 'cache');
if ($termsCache) 
{
    $terms = $termsCache->data;
} else {
    cache_set('one_time_serving_post_term_cache', jireh_serving_assignment_get_term_entity_array('one_time_serving_post'), 'cache', time() + 60);
    $terms = cache_get('one_time_serving_post_term_cache', 'cache')->data;
}


$postTidToNameArr = array();
foreach ($terms as $term) {
    $postTidToNameArr[$term->tid] = $term->name;
}

$conflictedPostArrays = array();
// for each assignment
foreach ($assignNodes as $nid => $assignNode) {
    $servantToPostArray = array();
    // for each post
    foreach ($assignNode as $field => $value) {
        if (preg_match ('/^field_/', $field) && isset($value[$lang][0]['tid']) && in_array($header[$field], $postTidToNameArr)) {
            $servingPostName = $header[$field];
            $servant_tid = $value[$lang][0]['tid'];
            if (!isset($servantToPostArray[$servant_tid]))
                $servantToPostArray[$servant_tid] = array();
            $servantToPostArray[$servant_tid][] = $servingPostName;
        }
    }
    
    // Find all corresponding conflicted posts for each assigned post
    $conflictedPostArrays[$nid] = jireh_serving_assignment_get_conflicted_post_arr($servantToPostArray, $lang, $terms);
}

function get_nid_array_by_rows(&$rows) {
    $nids = array();
    foreach ($rows as $row_count => $row) {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $row['title'], $vals, $index);
        $href = $vals[0]['attributes']['HREF'];
        $href_element = preg_split('/\//', $href);
        $nids[$row_count] = end($href_element);
        xml_parser_free($p);
    }
    
    return $nids;
}

?>
<table <?php if ($classes) { print 'class="'. $classes . '" '; } ?><?php print $attributes; ?>>
   <?php if (!empty($title) || !empty($caption)) : ?>
     <caption><?php print $caption . $title; ?></caption>
  <?php endif; ?>
  <?php if (!empty($header)) : ?>
    <thead>
      <tr>
        <?php foreach ($header as $field => $label): ?>
          <th <?php if ($header_classes[$field]) { print 'class="'. $header_classes[$field] . '" '; } ?> scope="col">
            <?php print $label; ?>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
  <?php endif; ?>
  <tbody>
    <?php foreach ($rows as $row_count => $row): ?>
      <tr <?php if ($row_classes[$row_count]) { print 'class="' . implode(' ', $row_classes[$row_count]) .'"'; } ?>>
        <?php 
        $nid = $nids[$row_count];
        foreach ($row as $field => $content) {?>
          <td <?php if ($field_classes[$field][$row_count]) {
              print 'class="'. $field_classes[$field][$row_count] . '" ';
              if (isset($conflictedPostArrays[$nid][$header[$field]]) && !empty($conflictedPostArrays[$nid][$header[$field]])) {
                  print ' bgcolor="' . $color_warning . '" ';
                  $correspondingConflictedPosts = $conflictedPostArrays[$nid][$header[$field]];
                  $conflictMsg = 'Conflicted with: ' . implode(", ", $correspondingConflictedPosts);
                  print ' title="' . $conflictMsg . '" ';
              }
          } ?><?php print drupal_attributes($field_attributes[$field][$row_count]); ?>>
            <?php print $content; ?>
          </td>
        <?php } ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
