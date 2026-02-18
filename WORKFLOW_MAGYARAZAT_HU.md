# GitHub Copilot Agent Workflow - Teljes Magyarázat

## Összefoglaló

Ez a dokumentum részletesen elmagyarázza, hogy **miért és hogyan változott** a GitHub Copilot működése a korábbi "közvetlen kód a chatben" modellről az új "agents/task rendszerre", és gyakorlati megoldásokat kínál különböző használati esetekre.

---

## 1. Mi változott és miért?

### Régi workflow (direkt chat válaszok):
- ✅ **Azonnali**: Kód/diff rögtön megjelent a chat válaszban
- ✅ **Látható**: Minden változás egyből olvasható volt
- ❌ **Korlátozott**: Egyszerű, 1-2 fájlos módosítások
- ❌ **Nem tesztelt**: Nincs automatikus build/test futtatás
- ❌ **Nincs verziókezelés**: Nem készül PR, nem commitol

### Új workflow (agents/task rendszer):
- ✅ **Komplex feladatok**: Multi-fájl, build, test, lint futtatás
- ✅ **Automatizált QA**: Automatikus tesztelés, biztonsági ellenőrzés
- ✅ **PR integráció**: Változások commitolva, verziókezelve
- ✅ **Reviewable**: Code review eszközökkel megtekinthető
- ⚠️ **Lassabb**: 30s-5 perc a feladat komplexitásától függően
- ⚠️ **Kevésbé átlátható**: Folyamat háttérben fut

---

## 2. Mikor, hogyan generálódnak a fájlok?

### A folyamat lépései:

#### Fázis 1: Terv készítés (5-10 másodperc)
```
User kérés → Agent elemzés → Terv (checklist) → report_progress
```
- Agent létrehoz egy **checklistet** a tennivalókról
- Ez jelenik meg első válaszként
- **Ekkor még NINCS kód módosítás!**

#### Fázis 2: Implementálás (20 másodperc - 3 perc)
```
Fájl olvasás → Kód módosítás → Tesztelés → Validálás
```
- Agent módosítja a fájlokat
- Mindegyik módosítás **lokálisan történik** a git repo-ban
- **Nem látszik a chatben közvetlenül!**

#### Fázis 3: Commit & Push (5-10 másodperc)
```
git add . → git commit → git push → PR frissítés
```
- Minden módosítás commitolva lesz
- Push-olva a branch-re
- **Itt jelenik meg a GitHub UI-n!**

#### Fázis 4: Review & QA (10 másodperc - 1 perc)
```
Code review → CodeQL security scan → Összegzés
```
- Automatikus code review fut
- Biztonsági ellenőrzés (CodeQL)
- **Eredmény a chat végén látható**

---

## 3. Hol, meddig kell várni? Hogyan követhető?

### Követési pontok:

#### A) Chat ablakban:
```
✓ Terv elkészült          (5-10s)
✓ Fájlok módosítva        (30s-2m)  
✓ Commit készült          (5s)
✓ Push sikeres            (5s)
✓ Code review lefutott    (10-30s)
✓ Kész ✓                  (összesen: 1-5m)
```

#### B) GitHub PR oldalon:
1. Nyisd meg: `https://github.com/iszolik/iszolik/pulls`
2. Keresd meg a PR-t (általában `copilot/...` névvel)
3. **Files changed** tabon látod a diff-et
4. **Commits** tabon látod a commit history-t
5. **Checks** tabon látod a CI/test eredményeket

#### C) Időbecslések:
- **Egyszerű (1-2 fájl módosítás)**: 30-60 másodperc
- **Közepes (3-10 fájl + tesztek)**: 2-4 perc
- **Komplex (sok fájl + build + test)**: 4-8 perc

---

## 4. Vissza lehet-e kapcsolni a "chatben mutasd a kódot" módot?

### Rövid válasz: **Nem közvetlenül**, de van **workaround**!

### Megoldások különböző use case-ekre:

### ✅ USE CASE 1: "Csak gyors kód példát/snippetet kérek, nem kell PR!"

**KÉRÉSI MINTA:**
```
"Mutass egy példát arra, hogyan... 
[NE készíts PR-t, NE módosíts fájlokat, csak add meg a kódot!]"
```

**Vagy:**
```
"Explain how to implement X, but DON'T make any file changes. 
Just show the code in the chat."
```

**Eredmény:** Agent felismeri, hogy csak **tanácsadó válasz** kell, nem módosítás.

---

### ✅ USE CASE 2: "Konkrét diff kell egy fájlhoz"

**KÉRÉSI MINTA:**
```
"Show me the exact changes needed in [fájlnév] to fix [probléma].
Format the response as a unified diff.
DON'T create a PR yet."
```

**Eredmény:** Agent mutat egy diff-et markdown code blockban:
```diff
- old line
+ new line
```

---

### ✅ USE CASE 3: "Új fájl tartalma kell azonnal"

**KÉRÉSI MINTA:**
```
"Write the complete content of a new file [filename] that does [leírás].
Show it in a markdown code block.
DON'T create files yet, I'll decide if I want to use it."
```

**Eredmény:** Agent megadja a teljes fájltartalmat code blockban.

---

### ✅ USE CASE 4: "Gyors refactor javaslat review-ra"

**KÉRÉSI MINTA:**
```
"I'm considering refactoring [function/class]. 
Suggest the refactored version but DON'T apply it yet.
Show before/after side by side."
```

**Eredmény:** Összevetés a chatben, te döntöd el, alkalmazzuk-e.

---

### ✅ USE CASE 5: "PR kell, de látni akarom a kódot process közben"

**MEGOLDÁS:**

1. **Első lépés - Terv kérése:**
```
"Create a detailed plan (checklist only) for implementing [feature].
DON'T start implementation yet."
```

2. **Review a tervet** → ha jó, akkor:

3. **Második lépés - Implementálás fázisosan:**
```
"Implement step 1 from the checklist: [első pont részlete]"
```

4. **GitHub PR-en megnézed a változásokat** (Files changed tab)

5. **Ha jó, akkor folytatás:**
```
"Continue with step 2: [második pont]"
```

**Előny:** Látod minden lépés után a pontos diff-et GitHubon.

---

## 5. Legjobb gyakorlatok (Best Practices)

### 🎯 Ha GYORS VÁLASZT szeretnél (30s):
```
"Quick question, no file changes needed:
How do I [implement X] in [language/framework]?
Show example code."
```

### 🎯 Ha KONZULTÁCIÓT szeretnél (1-2m):
```
"I want to refactor [component], but I'm not sure about the approach.
Compare Option A vs Option B with code examples.
DON'T modify files yet."
```

### 🎯 Ha TELJES MEGOLDÁST szeretnél PR-rel (3-10m):
```
"Implement [feature] with full testing and documentation.
Create a PR with all necessary changes."
```

### 🎯 Ha EMERGENCY FIX kell (1-2m):
```
"Critical bug in [file]:[line]. 
Show me the minimal fix as a diff first.
If I approve, apply it and create a PR."
```

---

## 6. Troubleshooting - Gyakori problémák

### ❌ Probléma: "Túl sokáig tart, nem látom mi történik"

**Megoldás:**
1. Nézd meg a GitHub PR oldalt valós időben
2. Frissítsd a **Files changed** tabot 20 másodpercenként
3. Ha 5 perc után sincs eredmény → írd be: "Status update please?"

### ❌ Probléma: "Nem olyan kódot írt, amit akartam"

**Megelőzés:**
1. Először kérj **tervet/draft-ot** code blockban
2. Review-old
3. Finomítsd: "Jó, de változtasd meg [ezt a részt]"
4. Ha elfogadtad: "Now implement this in the actual files"

### ❌ Probléma: "Túl nagy PR lett, nem látom át"

**Megoldás:**
- GitHub PR oldalon kapcsold be: **"Show rich diff"**
- Vagy: **"Split diff"** view
- Vagy: `git diff` lokálisan a repo clone-ban

### ❌ Probléma: "Csak 1 sort kellett volna változtatni, de 10 fájlt módosított"

**Megelőzés:**
Használj **explicit constraint-eket**:
```
"Fix [bug] by changing ONLY [file.js]:[line].
Make the minimal possible change.
DON'T refactor other code."
```

---

## 7. Összehasonlító táblázat

| Szempont | Régi (direct chat) | Új (agents/PR) | Workaround |
|----------|-------------------|----------------|------------|
| **Válaszidő** | 5-10s | 1-5m | Use "no PR" flag → 10-20s |
| **Kód láthatóság** | Azonnal chatben | GitHub PR-en | Kérj "markdown code block"-ot |
| **Diff nézet** | Nincs (copy-paste) | GitHub diff UI | Kérj "unified diff" formátumot |
| **Tesztelés** | Manual | Automatic | "Show test plan, don't run" |
| **Verziókezelés** | Nincs | Git commit/push | "Generate git commands only" |
| **Review** | Manual | Automated | "Explain changes, don't apply" |

---

## 8. Gyakorlati példák - Példa kérések

### 📌 1. példa: Csak kód kell, NO PR

**Kérés:**
```
Write a JavaScript function to validate email addresses.
Show the complete code in markdown.
DON'T create any files or PR.
```

**Eredmény:** Code block a chatben, 10s alatt.

---

### 📌 2. példa: Diff előnézet, majd alkalmazás

**Kérés 1:**
```
I want to add error handling to /path/to/file.js:functionName.
Show me the diff FIRST (before/after), don't apply yet.
```

**Agent válasz:** Diff megjelenítése.

**Kérés 2 (ha tetszik):**
```
Looks good, now apply this change and create a PR.
```

---

### 📌 3. példa: Multi-step, iteratív fejlesztés

**Kérés 1:**
```
Create an implementation plan for adding user authentication.
List the steps as a checklist. DON'T start coding yet.
```

**Kérés 2:**
```
Implement step 1 and 2 from the plan. Show the code changes.
```

**GitHub review** → OK? → Folytatás.

**Kérés 3:**
```
Continue with steps 3-5.
```

---

## 9. Agent kulcsszavak és flag-ek

Használd ezeket a kulcsszavakat a kérésedben a kívánt viselkedés eléréséhez:

### ⛔ Módosítást TILTÓ flag-ek:
- `"DON'T create any files"`
- `"DON'T modify files"`
- `"NO PR needed"`
- `"Show only, don't apply"`
- `"Explain, don't implement"`
- `"Preview changes first"`

### ✅ Azonnali chatbeli output flag-ek:
- `"Show in markdown code block"`
- `"Display as unified diff"`
- `"Provide example code"`
- `"Explain with code snippet"`

### 🔄 Iteratív/step-by-step flag-ek:
- `"Create plan first, implement later"`
- `"Show step 1 only"`
- `"Wait for approval before continuing"`

### ⚡ Gyors/minimal flag-ek:
- `"Minimal change only"`
- `"Quick answer needed"`
- `"One-liner fix if possible"`

---

## 10. Támogatási szintek

### 🟢 TIER 1: Instant (5-30s) - Advisory Only
- Code példák, snippetek
- Magyarázatok, dokumentáció
- Architecture tanácsok
- Diff preview, mock implementation

**Hogyan kérd:** Használd a "DON'T modify files" flag-et.

### 🟡 TIER 2: Fast Track (30s-2m) - Minimal Changes
- 1-3 fájl módosítás
- Hotfix, bug javítás
- Config update
- Dokumentáció update

**Hogyan kérd:** "Quick fix", "minimal change", explicit file listával.

### 🟠 TIER 3: Standard (2-5m) - Feature Implementation
- Multi-file módosítások
- Új feature implementálás
- Tesztekkel, dokumentációval
- Full PR + review

**Hogyan kérd:** Normál feature request, engedd az agent-et dolgozni.

### 🔴 TIER 4: Complex (5-15m) - Large Scale
- Refactoring
- Migration
- Architecture változások
- Multi-komponens módosítás

**Hogyan kérd:** Részletes specifikáció, engedd hosszabb futási időt.

---

## 11. FAQ - Gyakran Ismételt Kérdések

### Q1: "Miért nem látom a kódot a chatben, mint régen?"

**A:** Az új rendszer **PR-alapú**, így a kód módosítások a GitHub PR felületén jelennek meg, nem a chatben. Ezt a Git best practices és code review követelmények miatt vezették be.

**Workaround:** Használd a "show in markdown, don't apply" flag-et.

---

### Q2: "Hogyan tudom meggyorsítani a folyamatot?"

**A:**
1. Használj explicit constraint-eket ("only modify X file")
2. Jelezd ha NEM kell PR ("show code only, no PR")
3. Kérj először tervet, review-old, aztán engedd a módosítást

---

### Q3: "Mi van, ha nem tetszik a generált kód?"

**A:**
1. **Módosítás előtt:** "Show me the plan/diff first"
2. **Módosítás után:** 
   - GitHub PR-en kommentálj
   - Vagy chatben: "Change [X] to [Y] in the PR"
   - Vagy: "Revert last change and try this approach instead"

---

### Q4: "Lehet-e 'debug mode'-ot kérni, ahol látom az agent gondolatmenetét?"

**A:** Részben. Próbáld:
```
"Create a detailed plan with reasoning for each step.
Explain WHY you choose each approach.
Then wait for my approval before coding."
```

---

### Q5: "Hogyan tudom követni, hogy az agent épp mit csinál?"

**A:**
1. Chat üzenetek figyelése (jelzi a lépéseket)
2. GitHub PR **Commits** tab valós idejű frissítése
3. Kérj status update-et: "What's the progress? Which files are done?"

---

## 12. Összefoglalás - TL;DR

### ✅ Mit tehetsz:

1. **Gyors kód példa kell?** → Használd: `"Show code in chat, DON'T create PR"`
2. **Diff preview?** → `"Show diff first, apply after approval"`
3. **Komplex feature?** → Engedd az agent-et dolgozni, nézd GitHub PR-en
4. **Iteratív fejlesztés?** → Kérj tervet, approve-old részletekben

### ❌ Mit NEM tehetsz:

- Nem kapcsolható vissza 100%-ban a régi mód
- Nem lehet teljesen elkerülni a GitHub PR workflow-t nagyobb módosításoknál
- Nem lehet az agent "output"-ját real-time látni (csak checkpoints-okon keresztül)

### 🎯 Legjobb stratégia:

**Kis kérés (< 50 sor kód)?** → "Show in chat"  
**Közepes kérés (50-200 sor)?** → "Plan first, then apply"  
**Nagy kérés (> 200 sor)?** → Engedd az agent workflow-t, GitHubon követed

---

## 13. Kapcsolat és visszajelzés

Ha további kérdésed van, vagy valami nem világos:

1. **GitHub Issue:** Nyiss issue-t a repo-ban kérdéseddel
2. **PR Comment:** Kommentálj a PR-ben közvetlenül
3. **Chat folytatás:** "I have a follow-up question about..."

---

**Verzió:** 1.0  
**Utolsó frissítés:** 2026-02-18  
**Nyelv:** Magyar (Hungarian)  
**Kapcsolódó dokumentumok:** README.md, CONTRIBUTING.md

---

## Appendix: Agent rendszer architektúra

```
User Request
     |
     v
Main Agent (Manager)
     |
     +-- explore agent (code search, fast analysis)
     |   [tools: grep, glob, view]
     |   [model: Haiku - fast]
     |
     +-- task agent (builds, tests, lints)
     |   [tools: bash, CLI tools]
     |   [model: Haiku - fast]
     |
     +-- general-purpose agent (complex multi-step)
     |   [tools: all tools]
     |   [model: Sonnet - high quality]
     |
     v
Deliverables:
- Git commits → GitHub PR
- Code review report
- Security scan results
- Chat summary
```

### Időzítés breakdown:

```
[0-5s]    Request analysis, routing
[5-20s]   Repository exploration (explore agent)
[20-60s]  Code modification (main/general agent)
[60-90s]  Testing, linting (task agent)
[90-120s] Git commit + push
[120-180s] Code review + security scan
[180s+]   Summary, finalization
```

---

**Ez a dokumentum segítségével most már pontosan tudod, hogyan működik az új workflow, és hogyan érheted el a régi "instant code" élményt, amikor szükséged van rá!** 🚀
