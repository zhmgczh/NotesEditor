<?php include('include.php'); ?>
<html>

<head>
    <title>提交待考詞彙</title>
    <script>
        <?php
        $wordsArray = explode('、', $_GET['word']);
        foreach ($wordsArray as $word) {
            add_word($word);
        }
        ?>
        window.location.replace('index.php');
    </script>
</head>

<body>
</body>

</html>