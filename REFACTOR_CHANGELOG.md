# Models Refactor — Change Log

Running log of changes made during the Models-folder refactor session, in order. Each section
covers one change: what changed, and for bigger changes, the background/theory/discussion that
led to the decision. Kept in the repo root per request so it survives independent of any single
chat session.

---

## 1. Naming convention: `HXI_` prefix

All new/renamed classes and files in this refactor use the `HXI_` prefix, replacing the older
`FFXIPH_`/`FFXIPackageHelper_` prefixes used throughout the rest of the codebase. This is a
deliberate standing decision (not limited to this refactor) — the rest of the codebase will be
renamed to match in a later, separate pass. `FFXIPH_ItemDescription` (in `FFXIPH_Item.php`) is
the one class intentionally left alone so far (see §9).

## 2. `sql_ASB` folder removed

Deleted entirely at request — it was a stale/deprecated duplicate of the `sql/` folder. `sql/` is
now the only trusted SQL schema reference. One consequence: the Character/EquipmentSet split
below was originally researched against `sql_ASB/dbequipsets.sql` (which defined `user_chars`/
`user_sets`); after the folder was deleted, that finding was re-verified directly against the live
query code in `FFXIPH_DatabaseQueryWrapper.php` instead, so it no longer depends on the deleted
folder.

## 3. Character / EquipmentSet split

**What changed:** `FFXIPH_Character` (deleted) is split into `HXI_Character` (userid, charid,
charname, race, merits, def) and a new `HXI_EquipmentSet` (usersetid, setname, mlvl, slvl, mjob,
sjob, equipment string).

**Background:** The old `FFXIPH_Character` mixed two separate DB rows into one class:
`user_chars` (one row per saved character) and `user_sets` (many rows per character — a character
can have multiple gear sets). Confirmed via the actual queries in `FFXIPH_DatabaseQueryWrapper.php`
(`getUserCharacters` selects `[charname, charid, race, merits, def]` from `user_chars`;
`getUserSetsFromUserID`/`fetchSet` select `[usersetid, mlvl, slvl, mjob, sjob, equipment, setname]`
from `user_sets`, one-to-many per user). This is why the old model couldn't represent "one
character, many sets."

**Discussion / preserved behavior:**
- `isDefault()` on the old class checked `race==0 && mlvl==0 && slvl==0 && mjob==0 && sjob==0`
  (used to detect "no query-string data at all" on a fresh page load). Split into
  `HXI_Character::isDefault()` (`race==0`) and `HXI_EquipmentSet::isDefault()` (mlvl/slvl/mjob/sjob
  all 0); call sites that need the old combined check now do
  `$character->isDefault() && $set->isDefault()`.
- `FFXIPackageHelper_Equipsets`'s constructor used to take one combined object and call
  `->toArray()` to build its internal `$sharedLink` flat array (read in ~15 places throughout that
  550-line file). Rather than rewrite all of those read sites, the constructor now takes
  `(HXI_Character $character, ?HXI_EquipmentSet $set = null)` and merges
  `array_merge($character->toArray(), $set->toArray())` into the same flat shape — a deliberate
  compatibility seam so a large, hard-to-test UI-generation file didn't need a blind rewrite.
- Verified against the client JS (`FFXIPackageHelper_DataManager.js`): the function that consumes
  a "character" API response (`updateCharacter`) only reads `charname/race/def/merits` — never
  `mjob/mlvl/equipment` from that response — so shrinking `HXI_Character::toURLsafeArray()` to
  character-only fields doesn't break the client.
- Merit encode/decode logic extracted into `HXI_MeritsCodec` (was inline in `FFXIPH_Character`).
- Found and fixed a real pre-existing bug while transcribing: the old
  `Character::setMerit($m, $value)` referenced undefined `$key`/`$value` variables instead of its
  own parameters, so it always wrote to the wrong array key. Fixed on `HXI_Character`.

## 4. `HXI_Race` enum

`FFXIPH_Races` (a plain static id→name array, effectively an enum) replaced with a real PHP 8.1
backed enum, `HXI_Race`. No SQL table backs race — it's a display-name lookup only.

## 5. Items: `HXI_Item`, `HXI_Weapon`, `HXI_Equipment`, `HXI_EquipSlot` (new)

**Background:** There was no real Item/Weapon/Equipment class in use anywhere before this. Actual
equipment was — and in most of the app, still is — untyped positional arrays, e.g.
`FFXIPackageHelper_Equipment` builds `[id, slot, rslot, mods, skilltype, name]` per slot, and other
code reads `$weapon[4]` by magic index for weapon-type checks.

**What was added:**
- `HXI_EquipSlot`: enum of the 16 gear slots (Main/Sub/Range/.../Feet), matching the existing
  grid ordering used throughout `FFXIPackageHelper_Equipsets`. Carries the `item_equipment.slot`
  bitmask value per slot (verified against `DatabaseQueryWrapper::getEquipment()`'s existing
  slot-bitmask switch statement).
- `HXI_Item`: base item (id, subId, name, sortName, type, stackSize, flags, auctionCategory,
  baseSell, level, iLevel, jobs, shieldSize, scriptType, slot/rslot/rslotlook, suLevel, mods) plus
  `longname`/`descr`/`races` (see §8).
- `HXI_Weapon extends HXI_Item`: adds skill/subskill/ilvlSkill/ilvlParry/ilvlMAcc/dmgType/hit/
  delay/dmg/unlockPoints, and real `isH2H()`/`is2Handed()` methods (weapon-skill-id based,
  matching the numbering already used elsewhere in this codebase).
- `HXI_Equipment`: the 16-slot loadout container (not a subtype of Item — a composition class per
  discussion with the requester, since "Equipment" means "a full 16-slot set," while weapons live
  in 4 of those slots alongside armor in the other 12).

`FFXIPH_ItemDescription` (old `FFXIPH_Item.php`) was left untouched per explicit instruction —
deferred to a future pass (see §9 for how it eventually connects to `dat_details`).

## 6. Mob / `HXI_MobStatCalculator` split

**What changed:** `FFXIPH_Mob` (566 lines) mixed plain data with heavy formula logic
(`importSQL`) and direct DB access (`new DatabaseQueryWrapper()` inside the model, in
`setModifiersFromSQL`). `FFXIPH_MobUtils` (also DB-coupled) had zero callers outside `FFXIPH_Mob`.

Both were absorbed: `HXI_Mob` is now pure data + accessors, `HXI_MobStatCalculator` owns the
formula logic and all the DB access, populating a `HXI_Mob` via its existing public setters.
`HXI_Mob::importSQL($SQLmob, $useLvl)` is kept as a one-line delegator to the calculator, so the
sole external call site (`FFXIPackageHelper_DataModel::buildMobStatsArray`) needed no changes
beyond the class rename.

## 7. `HXI_CharacterStatCalculator` (renamed from `FFXIPackageHelper_Stats`)

**Discussion:** The original plan (before this rename) was a shared `HXI_StatCalculator`
interface + `HXI_BaseStatCalculator` abstract class, with Mob and Character calculators as
concrete children. When asked to choose, the decision was: **mechanical rename only** for the
Character side (Option A) — no interface/base class. Given that, the same restraint was
deliberately extended to the Mob side too: an interface/abstract-base with only one real
implementer participating isn't worth building yet (premature abstraction with no second
consumer). Both calculators are standalone concrete classes for now; the shared contract is
explicitly deferred, not abandoned.

## 8. `sql/DAT_details.sql` + `DatabaseQueryWrapper::getFullItem()` + `HXI_ItemFactory`

**Background / theory:** Item data actually comes from two sources today. Mechanical stats (id,
slot/rslot bitmask, mods, weapon skill, level) come from the live DB (`item_basic`/
`item_equipment`/`item_weapon`/`item_mods`). Display text (name, description, valid races, and an
id-remap for custom server items ≥50000) comes from a separate ~75,000-line static PHP array,
`FFXIPackageHelper_ItemDetails`, generated once from the game client's DAT files (hence
`FFXIPH_ItemDescription`'s `importFromDAT()`/`DATid` naming — it was clearly designed to
eventually wrap this exact data).

`item_basic`/`item_equipment`/`item_weapon` are confirmed to be the private-server repo's own
schema, refreshed from upstream via separate update files (`dbupdate.sql`, `hxi_changes*.sql`) —
i.e. NOT owned by this project. This ruled out adding columns directly to those tables (anything
added would be wiped out on the next upstream refresh) — the new data needed its own table.

**Redundancy check before migrating anything:** cross-referenced each `ItemDetails` field against
existing DB columns/decoders before deciding what to actually migrate:
- `jobs` (array of job-name strings) — **fully redundant**. `item_equipment.jobs` is already a
  bitmask column, and this codebase already has a decoder for it
  (`ParserHelper::jobsFromInt()`/`checkJob()`). Not migrated.
- `lvl` — almost certainly the same data as `item_equipment.level`. Not migrated.
- `flags` — `item_basic.flags` already exists as a bitmask column; kept as-is at the requester's
  instruction (no spot-check needed, no migration).
- `name`, `longname`, `descr`, `races` — genuinely new, no DB equivalent exists. These (plus the
  ≥50000 custom-item replacement mapping) are what got migrated.

**Migration approach:** wrote a small Node.js script (no PHP available in this environment) to
parse the static PHP array text directly and emit `sql/DAT_details.sql`: a `dat_details` table
(itemid PK, name, longname, descr, races) with 7,526 real items. The 22 custom server items
(id ≥50000) were **pre-resolved and denormalized** at generation time — each gets its own row with
the borrowed item's display data already copied in — so no indirection/lookup logic is needed at
query time, unlike the old PHP `$replacement` array. Verified row count, spot-checked several
entries (including ones with embedded newlines and apostrophes) against the source array, and
confirmed no duplicate itemids before finalizing. Requires manual import — not applied to any live
DB from here.

**`DatabaseQueryWrapper::getFullItem($itemid)`** (new): one query joining all 5 tables
(`item_basic` + `item_equipment` + `item_weapon` + `item_mods` + `dat_details`), returning every
column needed to build a complete item — replacing the old pattern of a partial DB query plus a
separate `new FFXIPackageHelper_ItemDetails()` array-lookup.

**Overhead discussion:** `FFXIPackageHelper_ItemDetails` cannot cheaply look up one item — merely
instantiating it rebuilds the *entire* ~7,500-entry array in memory (PHP has no shared memory
between requests; OPcache only avoids re-parsing the source, not re-running the array-literal
construction), and this happens fresh on every request, sometimes multiple times per request
(`parseEquipment`, `updateGridItems`, `importlua_verify` each instantiate it separately). Joining
one more table on an indexed integer primary key is what a database is built to do efficiently, by
contrast. Conclusion: `getFullItem()` should be strictly cheaper, and the gap widens with how many
places in a request need item display data. (Not empirically benchmarked — no PHP/DB available in
this environment.)

**Bug fixed along the way:** `getItem()` aliased the weapon-skill column as `skilltype`, but
`getEquipment()` (the search path) aliased the same column as `skill`. `DataModel::parseEquipment()`
only ever reads `skilltype`, so every equipment SEARCH result silently got weapon-skill-type = 0
(single-item lookups were unaffected). Fixed by aliasing `getEquipment()`'s column as `skilltype`
too, matching `getItem()`.

## 9. `HXI_ItemFactory` + `FFXIPackageHelper_Equipment` wiring

`HXI_ItemFactory::fromFullItemRows($rows)` builds one `HXI_Item`/`HXI_Weapon` from a `getFullItem()`
result set (weapon vs. armor detected by whether `item_weapon` had a matching row via the LEFT
JOIN). `longname`/`descr`/`races` fields were added directly onto `HXI_Item` for this (safe, since
nothing depended on `HXI_Item`'s shape yet).

`FFXIPackageHelper_Equipment::queryItem()` now calls `getFullItem()` + the factory instead of the
old `getItem()` + `DataModel::parseEquipment()` + `ItemDetails`-array path. The real objects are
now the actual source of truth for equipping an item, stored per-slot
(`getItemObjects()`/`getWeaponInSlot()`).

**Deliberate compatibility seam:** `getEquipmentArray()` still returns the exact old
`[id, slot, rslot, mods, skilltype, name]` array (via a new `itemToLegacyModel()` converter), so
`HXI_CharacterStatCalculator` and the JS-facing grid needed zero changes for this step.
`isH2H()`/`is2Handed()` on `FFXIPackageHelper_Equipment` are untouched (still array-index based) —
this is intentionally staged (see the open item below): `HXI_CharacterStatCalculator` reads that
same legacy array in ~10 places, so retiring the array format is being done as its own separate,
reviewed step rather than in the same move as introducing the objects.

Out of scope for this step: the equipment **search** feature
(`FFXIPackageHelper_QueryController::queryEquipsetsSearchItems` / `APIModuleEquipmentSearch`) still
uses the old `getEquipment()` + `parseEquipment()` + `ItemDetails` path (it does benefit from the
skill/skilltype fix above, but hasn't been migrated to the factory).

## 10. `getEquipmentArray()` retired — everything migrated to `HXI_Item`/`HXI_Weapon` objects

The legacy `[id, slot, rslot, mods, skilltype, name]` array is gone. `getEquipmentArray()` and its
`itemToLegacyModel()` converter were deleted from `FFXIPackageHelper_Equipment`; `getItemObjects()`
(keyed 0-15, `?HXI_Item`) is now the only accessor, used everywhere a caller previously read the
array.

- `HXI_CharacterStatCalculator`: constructor's `$e` param is now the items array directly.
  `applyEquipment()` iterates items and calls `getMods()` instead of index `[3]`.
  `getATT()`/`getACC()`/`getWeaponSkillMerits()` resolve the main-hand weapon once
  (`$this->equipment[0] instanceof HXI_Weapon`) and call its own `->skill`/`->is2Handed()`/
  `->isH2H()` instead of `intval($this->equipment[0][4])` and the old static
  `FFXIPackageHelper_Equipment::is2Handed()`/`isH2H()` helpers.
- `FFXIPackageHelper_Equipment`: deleted the now-dead `$equipment` array property and the static
  `isH2H()`/`is2Handed()` methods (superseded by the object's own methods on `HXI_Weapon`).
- `APIModuleEquipsets.php` (3 call sites): `getEquipmentArray()` → `getItemObjects()`.
  `parseEquipmentLabels()` now reads `$item->name` instead of `$equipmentArray[$i][5]`.
- `FFXIPackageHelper_Equipsets.php`'s `showEquipsets()`: same swap.
- **Bug fixed along the way:** in the `equipsets_selectset` API action, `$newEquipmentArray` was
  referenced one line before it was actually assigned (a pre-existing bug — silently harmless
  before this change only because that positional constructor argument was unused downstream).
  Reordered so the assignment happens first.

Not migrated (deliberately out of scope): the equipment **search** feature still builds its own
separate array shape via the old `getEquipment()`/`parseEquipment()`/`ItemDetails` path — untouched.

## 11. Broader `FFXIPH_`/`FFXIPackageHelper_` → `HXI_` rename

Renamed the remaining 21 classes outside Models/ (Tabs/, helpers/, helpers/data/, Page Directs/) —
file + class name + extension.json + all call sites (~40 files touched). Scripted (Node.js,
word-boundary regex per class name) rather than done by hand given the size. `FFXIPH_ItemDescription`
still excluded (deferred, §9).

**Naming collision:** `FFXIPackageHelper_Equipment` (the old equipment-string-parsing helper) would
have collided with `HXI_Equipment` (the Models/ 16-slot container built earlier this session).
Renamed it to `HXI_EquipmentParser` instead — distinct from, and unrelated in role to, the domain
`HXI_Equipment` class.

Classes whose file names still carry the old prefix but whose class names never did (`ExclusionsHelper`,
`ParserHelper`, `DataModel`, `VanaTime`, `ZoneForecast`, `WeatherForecast_ElementMaps`) were left
alone — out of scope for a *class* rename.

## 12. Post-rename smoke test: search broke, then didn't

After importing `dat_details` and clearing the rename's cache lag, LSBSearch briefly returned no
results on all searches. Root cause: stale MediaWiki extension-registration/opcache after the
class rename — not a code bug. Confirmed fixed by a cache clear. Temporary `wfDebugLog('Other', ...)`
instrumentation was added across the equipment search path to localize it, then removed once
resolved. **Takeaway for next time:** clear MediaWiki's extension registration cache / reset
opcache immediately after any class rename, before concluding something broke.

## 13. Real enums for HXI_Variables' skill/mobType; HXI_ModDictionary for the mod ID lookup

`HXI_Variables.php` was a 1766-line grab-bag of legacy static lookup arrays (`$jobArrayByID`,
`$skill`, `$modArray` ~830 entries, `$effectType` ~640 entries, `$mobModArray`, etc.) — not real
PHP enums, just `public static $x = array(...)`. Asked whether to consolidate everything into that
file as "enums"; pushed back since the two real enums built earlier (`HXI_Race`, `HXI_EquipSlot`)
are a different construct (an actual `enum` declaration) than these array-based pseudo-enums, and
merging them in would break the one-symbol-per-file convention plus separate them from the domain
classes they belong next to.

Agreed instead to convert what's actually enum-shaped, and formalize the rest:
- `HXI_Skill` (new enum, `includes/Models/`): the 31 active combat/magic skill entries from
  `$skill` (commented-out/unused ids skipped). This array turned out to be dead — grepped and
  found zero live callers — so this is pure cleanup, zero call-site risk.
- `HXI_MobType` (new enum, `includes/Models/`): the 6 mob-type flags from `$mobType`. This one
  is a **bitmask**, not a discrete value — `FFXIPackageHelper_ParserHelper` checks
  `HXI_Variables::$mobType["NOTORIOUS"] & $mobType` (a raw mob's mobType can have multiple bits
  set). A plain enum still works fine for this: each case represents one named flag constant:
  the runtime combined value stays a plain int outside the enum, and the bitwise check becomes
  `HXI_MobType::Notorious->value & $mobType`. Updated all 3 call sites.
- `HXI_ModDictionary` (new class, `includes/helpers/data/`): `$modArray` (827 entries, item/mob
  modifier id -> name) extracted into its own class with `getName(int): string` (safe fallback
  `"UNKNOWN_MOD_" . $id` instead of an undefined-index warning) and `all(): array` (for the one
  caller that enumerates every mod name to zero-initialize a stat map). This is explicitly a
  runtime dictionary, not an enum — ids come from the DB and aren't a fixed closed set. Updated 4
  call sites across `HXI_CharacterStatCalculator`, `HXI_MobStatCalculator`, and
  `HXI_HTMLTableHelper`, removing the now-unnecessary `new HXI_Variables()` instantiations at each.

`$jobArrayByID`/`$jobArrayByName`/`$detectsBy`/`$effectStatus`/`$effectType`/`$mobModArray` were
left untouched in `HXI_Variables` — out of scope for this pass (not asked for), though
`$effectType` (~640 entries) and `$mobModArray` are similarly dictionary-shaped and could get the
same `HXI_ModDictionary` treatment later if wanted.

## 14. Equipment search migrated to the factory/objects

The equipment search feature (both the Equipsets modal slot-picker and the general LSBSearch
"Equipment Search" tab) was the last piece still on the old `getEquipment()`/`getEquipmentFromDB()`
+ `DataModel::parseEquipment()` + `HXI_ItemDetails`-array path — everything else already used
`HXI_Item`/`HXI_Weapon`. Migrated it:

- Widened `getEquipment()` and `getEquipmentFromDB()` in place (same WHERE-clause/filter behavior,
  untouched) to also join `item_weapon` and `dat_details` and select the full column set, matching
  `getFullItem()` — rather than duplicating them as parallel methods, since only the SELECT/JOIN
  needed to grow, not the search semantics.
- Added `HXI_ItemFactory::fromFullItemRowsGrouped($rows): array<int,HXI_Item>` — the single-item
  `fromFullItemRows()` was refactored to share a `buildFromGroup()` helper with this, which groups
  a bulk (interleaved, multi-item) row set by itemid before building one object per item. This is
  an improvement over the old `parseEquipment()`'s dedup logic, which grouped consecutive rows by
  matching *display name* rather than actual item id.
- `HXI_QueryController::queryEquipsetsSearchItems()` and `APIModuleEquipmentSearch::queryEquipment()`
  now build real `HXI_Item` objects and apply the job filter (`ParserHelper::checkJob`) themselves
  post-build, since they no longer go through `parseEquipment()`.
- `HXI_HTMLTableHelper::table_EquipmentQuery()` reads `$item->name/slot/jobs/level/descr` directly
  instead of the old associative-array shape; no longer needs `new HXI_ItemDetails()`.
- New `HXI_CustomItemIconMap` (the old `~22`-entry `$replacement` map, kept separately from
  `dat_details`): custom items (id ≥50000) have no client DAT art of their own, so their *icon*
  still needs to resolve to a real item's id even though `dat_details` already resolves their
  *display text* directly under their own id (baked in at generation time, see §8). Kept as a tiny
  dedicated class rather than adding a schema column, to avoid requiring another SQL re-import for
  22 rows.

**Now dead as a result:** `DataModel::parseEquipment()` has zero remaining callers anywhere in the
codebase. Left in place (it's a shared utility class with other still-used methods) rather than
deleted, since removing it wasn't explicitly asked for — flagging for a future cleanup pass.

## 15. JS/data-shape audit + a real bug found and fixed

Asked to double-check every JS file touching equipment/character data for shape mismatches against
this session's PHP changes. Traced `ActionAPI.js`, `DataManager.js`, `ModalWindow.js`,
`LuaSets.js`, `TabCharacters.js`, `TabEquipment.js` against every API response field. No mismatches
from this session's changes - `selectchar`/`equipLabels`/`grid`/`luaNames`/the search response all
still match what the JS reads.

Found two **pre-existing** issues (present before this session, not introduced by it):

- `changeGrid()` in `ActionAPI.js` reads `updatedGrid[1][4]` (`slot_longname`), but the PHP grid
  array only ever has 4 elements. Dead variable, never read afterward - harmless, left alone.
- **Fixed:** in `HXI_QueryController::queryEquipsetsSearchItems()`, custom items (id ≥50000) had
  their search-result `data-id` set to the *donor* item's id (via `HXI_CustomItemIconMap`) instead
  of their own real id. `data-id` is what gets sent server-side to actually equip the item
  (`DataManager.js`'s `updateEquipmentGrid`), so clicking a custom item in search results would
  equip the donor item instead. The grid-refresh code (`updateGridItems()`) already gets this
  right elsewhere - it resolves the donor id only for the icon filename, keeping the real itemid
  everywhere else. Fixed `queryEquipsetsSearchItems()` to do the same: `data-id` stays `$item->id`,
  only the `<img>` src uses the resolved donor id.

**Follow-up risk raised:** the fixed bug is pre-existing, so any set saved *before* this fix, where
a user searched for and selected one of the 22 custom items specifically (rather than using Import
Lua, which looks items up by name and was never affected), would have the donor id baked into its
saved `user_sets.equipment` string permanently — reloading that set pulls the wrong item forever.
Not auto-fixable: a saved set referencing a donor id is indistinguishable from one where a user
genuinely, intentionally equipped that real retail item. Offered a diagnostic query (rows
referencing any of the 22 donor ids, for manual review) - not run, no DB access here.

## 16. `HXI_Item::isCustomItem()`

Added a proper `isCustomItem(): bool` method (plus a named `CUSTOM_ITEM_ID_THRESHOLD = 50000`
constant) to `HXI_Item`, computed from `id`, so code can ask "is this a private-server custom item
or a genuine retail one" without re-deriving the `>= 50000` magic number each time. Requested as a
building block for reasoning about retail-vs-custom items generally (e.g. for future auditing of
the risk noted in §15) - not wired into any UI or diagnostic yet, just exposed on the model.

---

## Open / deferred items

- `FFXIPH_ItemDescription` (`FFXIPH_Item.php`) — still fully untouched. Natural next step once
  revisited: fold into `HXI_Item`/the `dat_details` data path rather than staying separate.
- Migrate the equipment **search** feature to the new factory/objects too.
- `HXI_StatCalculator` interface / `HXI_BaseStatCalculator` abstract base — not built. Worth
  revisiting only once both Mob and Character calculators are ready to share real code.
- Filename-only cleanup for the classes noted in §11 (file still says `FFXIPackageHelper_X.php`/
  `FFXIPH_X.php` even though the class inside never had that prefix) — cosmetic, low priority.
- No PHP CLI or live MediaWiki/DB available in this environment at any point — nothing in this log
  has been executed/tested end-to-end. Manual smoke test still needed.

## Commits

- `d81689d` — Character/Mob split + Item/Weapon/Equipment classes + HXI_ renaming (§§3-7, plus
  §2 sql_ASB removal).
- `c2a4775` — dat_details table + getFullItem() + HXI_ItemFactory, and the full
  getEquipmentArray() retirement (§§8-10).
- `3ef7abd` — CONTEXT.md + changelog housekeeping.
- (pending) — broader HXI_ rename, 21 classes (§11).
