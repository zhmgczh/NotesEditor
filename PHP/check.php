<?php include('include.php'); ?>
<html>

<head>
    <title>翻轉確認狀態</title>
    <script>
        <?php check_word_character($_GET['word'], $_GET['character']); ?>
        window.location.replace('query.php?word=<?php echo urlencode($_GET['word']); ?>');
    </script>
</head>

<body>
</body>

</html>