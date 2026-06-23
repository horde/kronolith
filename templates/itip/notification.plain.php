<?php echo $this->subject ?>

<?php echo $this->header ?>


<?php echo _("When:") ?> <?php echo (new IntlDateFormatter($GLOBALS['language'], IntlDateFormatter::SHORT, IntlDateFormatter::MEDIUM))->format($this->event->start->timestamp()) ?> – <?php echo (new IntlDateFormatter($GLOBALS['language'], IntlDateFormatter::SHORT, IntlDateFormatter::MEDIUM))->format($this->event->end->timestamp()) ?>

<?php if (strlen($this->recurrence)): ?>
<?php echo _("Recurrence") ?>:

<?php echo $this->recurrence ?>

<?php endif ?>
<?php if (strlen($this->event->location)): ?>
<?php echo _("Location:") ?> <?php echo $this->event->location ?>

<?php endif ?>
<?php if (!empty($this->organizer)): ?>
<?php echo _("Organizer:") ?> <?php echo $this->organizer ?>

<?php endif ?>
<?php if (count($this->event->attendees)): ?>
<?php echo _("Attendees:") ?>
<?php foreach ($this->event->attendees as $attendee): ?>
- <?php echo is_null($attendee->addressObject->host) ? $attendee->displayName : $attendee->displayName . ' <' . $attendee->email . '>' ?>

<?php endforeach ?>
<?php endif ?>
<?php if (strlen($this->event->description)): ?>
<?php echo _("Description:") ?>

<?php echo $this->event->description ?>

<?php endif ?>
<?php if ($this->linkAccept): ?>
<?php echo _("Respond to this invitation:") ?>

<?php echo _("Accept:") ?> <?php echo $this->linkAccept ?>

<?php echo _("Tentative:") ?> <?php echo $this->linkTentative ?>

<?php echo _("Decline:") ?> <?php echo $this->linkDecline ?>

<?php endif ?>
