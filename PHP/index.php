<?php include('include.php'); ?>
<html>

<head>
    <title>編輯《大陸居民臺灣正體字講義》</title>
</head>

<body style="text-align:center">
    <h1>編輯《大陸居民臺灣正體字講義》</h1>
    <h2>加入詞彙</h2>
    <form action="submit.php" method="get">
        <input type="text" id="word" name="word"><br><br>
        <input type="submit"><br><br>
        <input type="button"
            onclick="window.open('query.php?word='+encodeURIComponent(document.getElementById('word').value),'_blank').focus()"
            value="分析"></input>
    </form>
    <h2>待考詞彙表</h2>
    <?php
    $pairs = get_word_pairs();
    foreach ($pairs as $pair) {
        echo '<h3>' . $pair[0] . '</h3>';
        foreach ($pair[1] as $word) {
            echo '<p><a href="query.php?word=' . urlencode($word) . '" target="_blank">' . $word . '</a></p>';
        }
    }
    echo '<h3>' . $global_debug . '</h3>';
    ?>
</body>

</html>