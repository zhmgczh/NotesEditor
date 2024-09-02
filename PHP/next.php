<?php include('include.php'); ?>
<html>

<head>
    <title>前往下一待考詞彙</title>
    <script>
        window.location.replace('query.php?word=<?php echo urlencode(get_next_word()); ?>');
    </script>
</head>

<body>
</body>

</html>