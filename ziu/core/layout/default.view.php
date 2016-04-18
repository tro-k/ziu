<!DOCTYPE html>
<html lang="ja">
<meta charset="utf-8" />
<head>
<title>Welcome to Ziu PHP Framework</title>
<?= asset_stylesheet_tag('css/ziu/default.css') ?>
<?= asset_javascript_tag('js/ziu/jquery.min.js') ?>
<?= asset_javascript_tag('js/ziu/default.js') ?>
<?= asset_javascript_tag('all') ?>
<?= asset_stylesheet_tag('all') ?>
<?= isset($content_for_javascript) ? $content_for_javascript : '' ?>
<?= isset($content_for_stylesheet) ? $content_for_stylesheet : '' ?>
</head>
<body>
<div class="title">
    <h1>Welcome to Ziu PHP Framework</h1>
</div>
<div class="contents">
    <div class="header_contents">
        <div class="menu">
            <ul>
                <li><a href="#">Z</a></li>
                <li><a href="#">i</a></li>
                <li><a href="#">u</a></li>
                <li><a href="#"></a></li>
                <li><a href="#">is</a></li>
                <li><a href="#"></a></li>
                <li><a href="#">Simple</a></li>
                <li><a href="#">and</a></li>
                <li><a href="#">Useful</a></li>
                <li><a href="#"></a></li>
                <li><a href="#">P</a></li>
                <li><a href="#">H</a></li>
                <li><a href="#">P</a></li>
                <li><a href="#">frame</a></li>
                <li><a href="#">work</a></li>
                <li><a href="#">.</a></li>
            </ul>
        </div>
    </div>
    <div class="main_contents">
        <div class="main">
            <?= render_content() ?>
        </div>
    </div>
    <div class="footer_contents">Ziu is released under the MIT license.</div>
</div>
</body>
</html>
