
<?php if (isset($error)) : ?>
<div class="error"><?= nl2br($error) ?></div>
<?php endif; ?>

<?= $f->open(uri("$module/confirm")) ?>

<?= $f->hidden('#table_id#') ?>
<?= $f->hidden('refer') ?>

<table class="edit">
#input#
<th>&nbsp;</th>
<td>
<?= $f->submit('action', 'back') ?>
&nbsp;&nbsp;
<?= $f->submit('action', 'confirm') ?>
</td>
</tr>
</table>

<?= $f->close() ?>

