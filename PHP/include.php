<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', '60000');
ini_set('post_max_size', '256M');
ini_set('upload_max_filesize', '256M');
ini_set('client_max_body_size', '256M');
date_default_timezone_set('Asia/Taipei');
$website_base_url = 'https://static.zh-tw.top';
$category_database_url = 'https://static.zh-tw.top/category_database.js';
$database_name = 'dbwords.db';
$entry_database = [];
$all_words = [];
$global_debug = '';
class MyDB extends SQLite3
{
    function __construct()
    {
        global $database_name;
        $this->open($database_name, SQLITE3_OPEN_READWRITE);
    }
}
function get_database_connection()
{
    return new MyDB();
}
// function get_database_connection()
// {
//     $result = new mysqli('sql109.infinityfree.com', 'if0_37224585', '3gEKMGTZnIpBQg', 'if0_37224585_words');
//     $result->set_charset('utf8');
//     return ($result);
// }
function compare_entities($x, $y)
{
    if (count($x[1]) < count($y[1])) {
        return -1;
    } elseif (count($x[1]) > count($y[1])) {
        return 1;
    } elseif ($x[0] < $y[0]) {
        return -1;
    } elseif ($x[0] > $y[0]) {
        return 1;
    } else {
        return 0;
    }
}
function get_all_words($article)
{
    $words = [];
    $mode = 0;
    $new_word = '';
    for ($i = 0; $i < mb_strlen($article, 'UTF-8'); ++$i) {
        if ($mode == 0) {
            if (mb_substr($article, $i, 1, 'UTF-8') == '「') {
                $mode = 1;
            }
        } elseif ($mode == 1) {
            if (mb_substr($article, $i, 1, 'UTF-8') == '（') {
                ++$mode;
            } elseif (mb_substr($article, $i, 1, 'UTF-8') == '」') {
                $words[] = $new_word;
                $new_word = '';
                $mode = 0;
            } else {
                $new_word .= mb_substr($article, $i, 1, 'UTF-8');
            }
        } else {
            if (mb_substr($article, $i, 1, 'UTF-8') == '）') {
                --$mode;
            } elseif (mb_substr($article, $i, 1, 'UTF-8') == '（') {
                ++$mode;
            }
        }
    }
    if ($new_word != '') {
        $words[] = $new_word;
    }
    return $words;
}
function get_checked($word, $character)
{
    global $all_words;
    $checked = true;
    foreach ($all_words[$character] as $entry) {
        $checked = $checked && in_array($word, $entry);
    }
    return $checked;
}
function get_word_pairs()
{
    global $entry_database;
    $conn = get_database_connection();
    $result = $conn->query('select word, character from words where checked=false order by character, word;');
    // $result = $conn->query('select word, char_ from words where checked=false order by char_, word;');
    $display = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // while ($row = $result->fetch_assoc()) {
        foreach ($entry_database[$row['character']] as $entry) {
            // foreach ($entry_database[$row['char_']] as $entry) {
            if (!array_key_exists($entry['title'], $display)) {
                $display[$entry['title']] = [];
            }
            $display[$entry['title']][] = $row['word'];
        }
    }
    $pairs = [];
    foreach ($display as $title => $words) {
        $pairs[] = [$title, $words];
    }
    usort($pairs, 'compare_entities');
    return $pairs;
}
function get_word_status($word)
{
    $conn = get_database_connection();
    $sql = "select character, checked from words where word = '" . $word . "' order by checked, character;";
    // $sql = "select char_, checked from words where word = '" . $word . "' order by checked, char_;";
    $result = $conn->query($sql);
    $display = array();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // while ($row = $result->fetch_assoc()) {
        $display[$row['character']] = $row['checked'];
        // $display[$row['char_']] = $row['checked'];
    }
    $conn->close();
    $colored_word = '';
    $ordered_characters = array();
    for ($i = 0; $i < mb_strlen($word, 'UTF-8'); $i++) {
        $character = mb_substr($word, $i, 1, 'UTF-8');
        if (array_key_exists($character, $display)) {
            $colored_word .= '<span style="background-color:yellow">' . htmlspecialchars($character, ENT_QUOTES, 'UTF-8') . '</span>';
            if (!in_array($character, $ordered_characters)) {
                $ordered_characters[] = $character;
            }
        } else {
            $colored_word .= htmlspecialchars($character, ENT_QUOTES, 'UTF-8');
        }
    }
    return array('display' => $display, 'ordered_characters' => $ordered_characters, 'colored_word' => $colored_word);
}
function get_next_word()
{
    global $entry_database;
    $conn = get_database_connection();
    $sql = 'select word, character from words where checked = 0 order by character, word;';
    // $sql = 'select word, char_ from words where checked = 0 order by char_, word;';
    $result = $conn->query($sql);
    $temporary = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // while ($row = $result->fetch_assoc()) {
        $character = $row['character'];
        // $character = $row['char_'];
        $word = $row['word'];
        if (isset($entry_database[$character])) {
            foreach ($entry_database[$character] as $entry) {
                if (!isset($temporary[$entry['title']])) {
                    $temporary[$entry['title']] = [];
                }
                $temporary[$entry['title']][] = $word;
            }
        }
    }
    $pairs = [];
    foreach ($temporary as $title => $words) {
        $pairs[] = [$title, $words];
    }
    usort($pairs, 'compare_entities');
    $next_word = null;
    foreach ($pairs as $pair) {
        foreach ($pair[1] as $word) {
            $next_word = $word;
            break;
        }
        if ($next_word !== null) {
            break;
        }
    }
    $conn->close();
    return $next_word;
}
function add_word($word, $connection = null)
{
    global $entry_database, $database_name;
    if (is_null($connection)) {
        $conn = get_database_connection();
    } else {
        $conn = $connection;
    }
    $insert_pairs = [];
    foreach (mb_str_split($word, 1, 'UTF-8') as $character) {
        if (array_key_exists($character, $entry_database)) {
            $insert_pairs[] = [$word, $character];
        }
    }
    foreach ($insert_pairs as $pair) {
        $checked = get_checked($pair[0], $pair[1]);
        $conn->exec("insert or ignore into words (word, character, checked) values ('" . $pair[0] . "','" . $pair[1] . "'," . ($checked ? 'true' : 'false') . ");");
        // $conn->query("replace into words (word, char_, checked) values ('" . $pair[0] . "','" . $pair[1] . "'," . ($checked ? 'true' : 'false') . ");");
        if ($checked) {
            $conn->exec("update or ignore words set checked=true where word='" . $pair[0] . "' and character='" . $pair[1] . "';");
            // $conn->query("update ignore words set checked=true where word='" . $pair[0] . "' and char_='" . $pair[1] . "';");
        }
    }
    if (is_null($connection)) {
        $conn->close();
    }
}
function check_word_character($word, $character)
{
    $conn = get_database_connection();
    $conn->exec("update or ignore words set checked=not checked where word='" . $word . "' and character='" . $character . "';");
    // $conn->query("update ignore words set checked=not checked where word='" . $word . "' and char_='" . $character . "';");
    $conn->close();
}
function get_search_color($article, $word)
{
    $colored = array_fill(0, mb_strlen($article, 'UTF-8'), false);
    $light_colored = array_fill(0, mb_strlen($article, 'UTF-8'), false);
    for ($length = mb_strlen($word, 'UTF-8'); $length > 1; --$length) {
        for ($i = 0; $i <= mb_strlen($word, 'UTF-8') - $length; ++$i) {
            $truncated_word = mb_substr($word, $i, $length);
            $start_indices = [];
            $pos = mb_strpos($article, $truncated_word, 0, 'UTF-8');
            while ($pos !== false) {
                $start_indices[] = $pos;
                $pos = mb_strpos($article, $truncated_word, $pos + 1, 'UTF-8');
            }
            foreach ($start_indices as $start_index) {
                for ($j = $start_index; $j < $start_index + $length; ++$j) {
                    $colored[$j] = true;
                }
            }
        }
    }
    for ($i = 0; $i < mb_strlen($word, 'UTF-8'); ++$i) {
        for ($j = 0; $j < mb_strlen($article, 'UTF-8'); ++$j) {
            if (mb_substr($article, $j, 1, 'UTF-8') == mb_substr($word, $i, 1, 'UTF-8')) {
                $light_colored[$j] = true;
            }
        }
    }
    $new_article = '';
    for ($i = 0; $i < mb_strlen($article, 'UTF-8'); ++$i) {
        if ($colored[$i]) {
            $new_article .= '<span style="background-color:yellow">' . mb_substr($article, $i, 1, 'UTF-8') . '</span>';
        } elseif ($light_colored[$i]) {
            $new_article .= '<span style="background-color:cyan">' . mb_substr($article, $i, 1, 'UTF-8') . '</span>';
        } else {
            $new_article .= mb_substr($article, $i, 1, 'UTF-8');
        }
    }
    return $new_article;
}
function daily_job()
{
    global $category_database_url, $entry_database, $all_words;
    $conn = get_database_connection();
    $result = $conn->query('select time,entry_database,all_words from status where id=1;');
    $fetched = false;
    if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // if ($row = $result->fetch_assoc()) {
        if ($row['time'] + 24 * 60 * 60 >= time()) {
            $entry_database = json_decode($row['entry_database'], true);
            $all_words = json_decode($row['all_words'], true);
            $fetched = true;
        }
    }
    if (!$fetched) {
        $category_database = json_decode(file_get_contents($category_database_url), true)['一簡多繁辨析'];
        $entry_database = [];
        $all_words = [];
        foreach ($category_database as $entry) {
            $title = $entry['title'];
            $split = explode('」→「', substr($title, strlen('一簡多繁辨析之「')));
            $zhengs = explode('、', $split[0]);
            $jians = explode('、', explode('」', $split[1])[0]);
            $new_title = implode('、', $zhengs) . '→' . implode('、', $jians);
            $entry['title'] = $new_title;
            $new_all_words = get_all_words($entry['description']);
            foreach ($zhengs as $zheng) {
                if (!array_key_exists($zheng, $entry_database)) {
                    $entry_database[$zheng] = [];
                }
                $entry_database[$zheng][] = $entry;
                if (!array_key_exists($zheng, $all_words)) {
                    $all_words[$zheng] = [];
                }
                $all_words[$zheng][] = $new_all_words;
            }
        }
        foreach ($all_words as $title => $words_list) {
            foreach ($words_list as $words) {
                foreach ($words as $word) {
                    add_word($word, $conn);
                }
            }
        }
        $sql = "insert or replace into status (id,time,entry_database,all_words) values (1," . strval(time()) . ",'" . str_replace("'", "''", json_encode($entry_database)) . "','" . str_replace("'", "''", json_encode($all_words)) . "');";
        // $sql = "replace into status (id,time,entry_database,all_words) values (1," . strval(time()) . ",'" . str_replace("'", "''", json_encode($entry_database)) . "','" . str_replace("'", "''", json_encode($all_words)) . "');";
        $conn->exec($sql);
        // $conn->query($sql);
        $fetched = true;
    }
    $conn->close();
}
daily_job();
header('Content-type: text/html; charset=utf-8');
?>