## Summary
- Fix Kronolith event dialog failing to reopen after the first close in classical browsers
- Always restore RedBox content to the document body and reset navigation state on close
- Ensure `loadNextView()` runs even when `editEvent()` throws during autocompleter reset

## Motivation
After opening and closing an event edit dialog once, further calendar clicks did nothing until a full page reload. Firefox reported `TypeError: can't access property "removeChild", element.parentNode is null`.

Root causes included a stuck `viewLoading` queue when reopen threw, `openLocation` not being reset on ESC, and RedBox overlay nodes remaining visible and intercepting clicks.

## Changes
- Add `_ensureEventDialogInBody()`, `_resetEventAutoCompleters()`, `_hideAutoCompleterOverlays()`, `_restoreRedBoxContent()`, and `_showEventDialog()` helpers
- Rewrite `closeRedBox()` to restore dialog DOM, reset `openLocation`, and hide RedBox nodes immediately
- Wrap the event branch of `go()` in `try/finally` so `loadNextView()` always runs
- Reset `viewLoading` in `onException()`
- Route ESC outside the form through `go(lastLocation)`
- Remove the `redBoxLoading` early return that blocked subsequent opens
- Show the dialog only after form/autocompleter setup completes in `editEventCallback`

Depends on companion PR in `horde/core` for RedBox and PrettyAutocompleter fixes.

## Test plan
- [ ] Open an existing event, cancel, open the same event again
- [ ] Open an event, cancel, open a different event
- [ ] Close with Cancel button, ESC inside the form, and ESC outside the form
- [ ] Create a new event on a day/week/month slot and verify the dialog opens repeatedly
- [ ] Edit an event with attendees and tags, close, reopen without console errors
