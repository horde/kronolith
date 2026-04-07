<div>
 <?php echo _("Default Alarm Setting:") ?>
</div>

<div>
 <label for="alarm_value" class="hidden"><?php echo _("Alarm Value") ?></label>
 <input type="text" size="2" id="alarm_value" name="alarm_value" value="<?php echo $this->alarm_value ?>" />
 <label for="alarm_unit" class="hidden"><?php echo _("Alarm Unit") ?></label>
 <select id="alarm_unit" name="alarm_unit">
  <option value="1"<?php if (!empty($this->minute)): ?> selected="selected"<?php endif ?>><?php echo _("Minute(s)") ?></option>
  <option value="60"<?php if (!empty($this->hour)): ?> selected="selected"<?php endif ?>><?php echo _("Hour(s)") ?></option>
  <option value="1440"<?php if (!empty($this->day)): ?> selected="selected"<?php endif ?>><?php echo _("Day(s)") ?></option>
  <option value="10080"<?php if (!empty($this->week)): ?> selected="selected"<?php endif ?>><?php echo _("Week(s)") ?></option>
 </select>
 <?php echo _('before the event starts. A value of "0" means no default alarms.') ?>
</div>
