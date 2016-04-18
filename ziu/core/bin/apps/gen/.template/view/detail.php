
<?php if (isset($error)) : ?>
<div class="error"><?= nl2br($error) ?></div>
<?php endif; ?>

<table class="edit">
#confirm#
<tr>
<th>&nbsp;</th>
<td>
<a href="<?= $f->fetch('refer') ?>">Back</a>
</td>
</tr>
</table>

