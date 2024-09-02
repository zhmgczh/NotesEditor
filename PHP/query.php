<?php include('include.php'); ?>
<html>

<head>
    <title><?php if (isset($_GET['word'])) {
        echo $_GET['word'];
    } ?> - 編輯《大陸居民臺灣正體字講義》</title>
</head>

<body style="text-align:center">
    <h1>編輯《大陸居民臺灣正體字講義》</h1>
    <?php
    add_word($_GET['word']);
    $word_status = get_word_status($_GET['word']);
    ?>
    <h2><?php echo $word_status['colored_word'] ?></h2>
    <p><a href="next.php">查看下一待考詞彙</a></p>
    <?php
    foreach ($word_status['ordered_characters'] as $character) {
        echo '<h3>' . $character . '</h3>';
        $status = $word_status['display'][$character] ? '已確認' : '未確認';
        echo '<input type="button" onclick="javascript:location.href=\'check.php?word=' . urlencode($_GET['word']) . '&character=' . urlencode($character) . '\'" value="' . $status . '"></input>';
        foreach ($entry_database[$character] as $entry) {
            echo '<h4 style="color:red">' . $entry['title'] . '</h4>';
            $description = get_search_color($entry['description'], $_GET['word']);
            $article = '<p>' . str_replace("\n", '</p><p>', $description) . '</p>';
            echo $article;
            echo '<p><a href="' . $website_base_url . $entry['url'] . '" target="_blank">轉到 ' . $entry['title'] . '</a></p>';
        }
    }
    ?>
    <iframe src="https://www.moedict.tw/<?php echo urlencode($_GET['word']); ?>" height="350px" width="95%"
        data-ruffle-polyfilled=""></iframe>
    <p><a href="https://dict.revised.moe.edu.tw/search.jsp?md=1&word=<?php echo urlencode($_GET['word']); ?>&qMd=0&qCol=1"
            target="_blank"><?php echo $_GET['word']; ?></a></p>
</body>