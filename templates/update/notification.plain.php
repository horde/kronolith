<?php echo $this->header ?> (<?php printf(_("on %s at %s"), $this->event->start->format('short', new Horde\Date\Formatter\IcuFormatter(), $GLOBALS['language']), (new IntlDateFormatter($GLOBALS['language'], IntlDateFormatter::NONE, IntlDateFormatter::MEDIUM))->format($this->event->start->timestamp())) ?>)


<?php echo _("Calendar:") ?> <?php echo $this->calendar ?>


<?php if (strlen($this->event->location)): ?>
<?php echo _("Location:") ?> <?php echo ($this->private ? '' : $this->event->location) ?>


<?php endif; ?>
<?php if (count($this->event->attendees)): ?>
<?php echo _("Attendees:") ?> <?php echo $this->event->attendees ?>


<?php endif; ?>
<?php if (strlen($this->event->description)): ?>
<?php echo _("The following is a more detailed description of the event:") ?>


<?php echo ($this->private ? _("Busy") : $this->event->description) ?>


<?php endif; ?>
