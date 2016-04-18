
<?php if (isset($error)) : ?>
<div class="error"><?= nl2br($error) ?></div>
<?php endif; ?>

<?= $f->open(uri("$module/execute"), 'post', TRUE) ?>

<table class="edit">
#confirm#
<tr>
<th>&nbsp;</th>
<td>
<?= $f->submit('action', 'back') ?>
&nbsp;&nbsp;
<?= $f->submit('action', 'save') ?>
</td>
</tr>
</table>

<?= $f->close() ?>

