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
            t.style.display = '';
        } else {
            c.classList.remove('horde-active');
            t.hidden = true;
            t.style.display = 'none';
        }
    });

    return false;
}

document.addEventListener('DOMContentLoaded', function() {
    var active = document.querySelector('#page .tabset ul li.horde-active');
    if (active) {
        ShowTab(active.id.substring(3));
    }
});
