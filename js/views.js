var eventTabs = null;

function ShowTab(tab)
{
    if (eventTabs === null) {
        eventTabs = Array.from(document.getElementById('page').querySelectorAll('.tabset ul li'));
    }

    eventTabs.forEach(function(c) {
        var t = document.getElementById(c.id.substring(3));
        if (!t) {
            return;
        }
        if (c.id == 'tab' + tab) {
            c.classList.add('horde-active');
            t.hidden = false;
        } else {
            c.classList.remove('horde-active');
            t.hidden = true;
        }
    });

    return false;
}
