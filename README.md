DO NOT MERGE CHANGES FROM THIS REPO's MASTER BRANCH
===================================================

Requests against upstream
-------------------------

- use maintaina-bare branch for pure code changes without touching composer.json, package.xml, changelog and with minimal edits to .horde.yml if required

Requests against this repo
--------------------------

- use maintaina-bare branch for pure code changes. Do not touch metadata files.

Upgrading this repo
-------------------

- rebase horde-upstream branch on horde upstream repo
- rebase maintaina-bare on horde-upstream and fix any conflicts
- rebase maintaina-composerfixed on maintaina-bare or drop and recreate the branch. Then generate a new composer.json. You will probably need a custom components/config/bin for a satis repo or downstream git repo
- rebase master branch on maintaina-composerfixed. This should never fail as they only differ by this readme
