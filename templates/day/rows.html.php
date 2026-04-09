<?php foreach ($this->rows as $row): ?>
 <tr>
  <td class="kronolith-first-col">
    <span><?php echo $row['slot'] ?></span>
  </td>
  <?php echo $row['row'] ?>
 </tr>
<?php endforeach ?>
