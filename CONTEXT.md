# Context (read this first)

Minimal state for picking up this refactor with low token cost. Full background/rationale is in
REFACTOR_CHANGELOG.md — only open that if you need the "why."

## Project
MediaWiki extension (FFXIPackageHelper). PHP 8.1+. Branch: `major-rewrite`.
New/renamed classes use `HXI_` prefix. Class autoloading = `AutoloadClasses` map in
`extension.json` (not PSR-4) — any rename/move must update that map + every call site.

## Done
- `includes/Models/` refactored: `HXI_Character` + `HXI_EquipmentSet` (split from old
  `FFXIPH_Character`, which conflated `user_chars`+`user_sets`), `HXI_Mob` +
  `HXI_MobStatCalculator` (split from `FFXIPH_Mob`/`FFXIPH_MobUtils`), `HXI_Race` (enum),
  `HXI_MeritsCodec`, `HXI_Item`/`HXI_Weapon`/`HXI_Equipment`/`HXI_EquipSlot`/`HXI_ItemFactory` (new).
- `FFXIPackageHelper_Stats` renamed to `HXI_CharacterStatCalculator` (rename only, no shared
  interface with Mob's calculator — deliberately deferred).
- `sql/DAT_details.sql` (new table `dat_details`: itemid/name/longname/descr/races) +
  `DatabaseQueryWrapper::getFullItem($itemid)` (one query, all 5 item tables joined) +
  `HXI_ItemFactory::fromFullItemRows()` build a real item object instead of the old
  DB-row + 75k-line-static-array lookup.
- Legacy `[id,slot,rslot,mods,skilltype,name]` positional equipment array is fully retired.
  Everything (`HXI_CharacterStatCalculator`, `APIModuleEquipsets`, `HXI_Equipsets`)
  uses `HXI_EquipmentParser::getItemObjects()` (real `HXI_Item`/`HXI_Weapon`) now.
- `sql_ASB/` deleted (deprecated dupe of `sql/`).
- Two pre-existing bugs fixed: `Character::setMerit()` wrote to the wrong array key; `getEquipment()`
  aliased the weapon-skill column differently than `getItem()`, so search results always showed
  skill type 0.
- Broader `FFXIPH_`/`FFXIPackageHelper_` → `HXI_` rename done: 21 classes outside Models/
  (Tabs/helpers/Page Directs), ~40 files. `FFXIPackageHelper_Equipment` → `HXI_EquipmentParser`
  (collision avoidance — `HXI_Equipment` already exists as the Models/ 16-slot container).
- `sql/DAT_details.sql` imported by user; confirmed working (see cache-clear note below).
- `HXI_Variables`' array-based pseudo-enums cleaned up: `$skill` → real `HXI_Skill` enum (was
  dead/unused), `$mobType` → real `HXI_MobType` enum (bitmask flags — each case is one flag,
  combine/check via `->value` against the raw int), `$modArray` (827 entries) → `HXI_ModDictionary`
  class (`getName()`/`all()`) since it's DB-driven and not a fixed closed set.
  `$jobArrayByID`/`$effectType`/`$mobModArray`/etc. left as-is in Variables (out of scope, though
  `$effectType`/`$mobModArray` are similarly dictionary-shaped if this comes up again).
- Equipment **search** migrated too (`HXI_QueryController::queryEquipsetsSearchItems`,
  `APIModuleEquipmentSearch`): `getEquipment()`/`getEquipmentFromDB()` widened in place to join
  `item_weapon`+`dat_details`; new `HXI_ItemFactory::fromFullItemRowsGrouped()` builds real objects
  from a multi-item row set; new `HXI_CustomItemIconMap` (the old ~22-entry custom-item icon
  borrow map, kept separate from `dat_details` since icons still need a real item id even though
  display text is already resolved). `DataModel::parseEquipment()` is now fully dead (zero
  callers) — since deleted.

- **Gear Sets tab UI redesign** (uncommitted): FFXI-window styling in `resources/styles/HXI_Equipsets_GearSets.css`
  (replaces `HXI_Equipsets_SetList.css`); markup in `HXI_Equipsets::showEquipsets()` / `HXI_HTMLOptions::setsList()`.
  Responsive via CSS grid areas (phone 1 col, >=700px 2 col, >=1100px 3 col). Stats/resist/sets-list HTML
  is div-based now (API swaps it in via innerHTML; element ids unchanged). Not yet run on a real wiki.

## Not done / open
- `HXI_BaseStatCalculator` (abstract, shared modifier accumulator) exists; Mob and Character
  calculators extend it. No `HXI_StatCalculator` interface - their entry points differ.
- All remaining `FFXIPH_` / `FFXIPackageHelper_` prefixes were renamed to `HXI_`: PHP/JS/CSS
  file names, ResourceLoader module names, DOM ids and CSS classes, and `FFXIPH_ItemDescription`
  -> `HXI_ItemDescription` (`HXI_ItemDescription.php`). Historical comments naming deleted
  legacy classes (`FFXIPH_Character`, `FFXIPH_Mob`, `FFXIPH_MobUtils`, `FFXIPackageHelper_ItemDetails`)
  were deliberately left. Any on-wiki CSS/JS (e.g. MediaWiki:Common.css) targeting the old
  `FFXIPackageHelper_*` ids/classes must be updated to `HXI_*`.
- **After any class rename in this project, clear MediaWiki's extension-registration cache /
  reset opcache** before assuming a resulting failure is a code bug — this already happened once
  (LSBSearch briefly broke, cache clear fixed it, not a code issue).
- No PHP CLI or MediaWiki instance available in this environment at any point this session — all
  verification here is manual code review; the user has confirmed LSBSearch/DAT_details working
  in their actual environment, but no full smoke test of the Equipsets/Character/Set flow yet.

## Commits (branch `major-rewrite`)
- `d81689d` — Character/Mob split + Item/Weapon/Equipment classes + HXI_ renaming.
- `c2a4775` — dat_details table + getFullItem() + HXI_ItemFactory + full array retirement.
- `3ef7abd` — CONTEXT.md + changelog housekeeping.
- `1689e09` — broader HXI_ rename, 21 classes.
- `333042a`, `c6d403e`, `32b20f0`, `45c75bd`, `9bbd4c4`, `e708274` — DAT_details.sql INSERT-per-row
  fix + search debug logging added/removed (root cause was cache staleness, not code).
- (pending) — HXI_Skill/HXI_MobType/HXI_ModDictionary cleanup.

## Style note
User wants terse, direct replies — minimal pleasantries, no padding.
