<?php echo _("We would like to remind you of this upcoming event.") ?>


<?php echo $this->event->getTitle($this->user) ?>


<?php echo _("Location:") ?> <?php echo $this->event->getLocation($this->user) ?>


<?php echo _("Date:") ?> <?php echo $this->start->format($this->dateFormat, new \Horde\Date\Formatter\IcuFormatter(), $GLOBALS['language'] ?? 'en_US') ?>

<?php echo _("Time:") ?> <?php echo $this->event->start->format($this->timeFormat, new \Horde\Date\Formatter\IcuFormatter(), $GLOBALS['language'] ?? 'en_US') ?>


<?php if (!$this->event->isPrivate($this->user)) {
    echo $this->event->description;
} ?>
