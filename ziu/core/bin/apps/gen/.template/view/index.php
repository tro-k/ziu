
<div class="entry"><a href="<?= uri('#table#/add') ?>">New Entry</a></div>
<div class="shbox"><a href="#">Search Box</a></div>

<?php if (isset($error)) : ?>
<div class="error"><?= $error ?></div>
<?php endif; ?>

<?= $f->open(uri('#table#'), 'get') ?>
<table class="search">
<tr>
<th>#Table_id#</th>
<td><?= $f->text('#table_id#') ?></td>
</tr>
<tr>
<th>&nbsp;</th>
<td>
<?= $f->submit('action', 'search') ?>
&nbsp;
<?= $f->submit('action', 'reset') ?>
</td>
</tr>
</table>
<?= $f->close() ?>

<?= isset($page) ? $page : '' ?>

<table class="list">
<tr>
#index_th#
<th style="width: 150px;">&nbsp;</th>
</tr>
<?php if (! empty($list)) : ?>
<?php foreach ($list as $val) : ?>
<tr>
#index_td#
<td style="width: 150px;">
    <a href="<?= uri("#table#/detail/{$val['#table_id#']}") ?>">Detail</a>
    <a href="<?= uri("#table#/edit/{$val['#table_id#']}") ?>">Edit</a>
    <a href="<?= uri("#table#/delete/{$val['#table_id#']}") ?>" onclick="return confirm('Are you sure deleting [<?= $val['#table_id#'] ?>] of #Table_id#')">Delete</a>
</td>
</tr>
<?php endforeach; ?>
<?php else : ?>
<tr>
<td colspan="99">No data.</td>
</tr>
<?php endif; ?>
</table>

