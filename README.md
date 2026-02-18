# iszolik/iszolik Repository

## 📚 Dokumentációk / Documentation

Ez a repository dokumentálja a GitHub Copilot Agent rendszer működését és tartalmaz gyakorlati útmutatókat a hatékony használathoz.

This repository documents the GitHub Copilot Agent system operation and contains practical guides for effective use.

---

## 📖 Elérhető dokumentumok / Available Documents

### 🇭🇺 Magyar nyelven / In Hungarian:

1. **[WORKFLOW_MAGYARAZAT_HU.md](WORKFLOW_MAGYARAZAT_HU.md)** (13KB)
   - Teljes körű magyarázat a workflow változásokról
   - Mikor, hogyan generálódnak a fájlok
   - Követési lehetőségek és időbecslések
   - Workaround-ok az azonnali kód megjelenítéshez
   - Gyakorlati példák és best practices
   - FAQ és troubleshooting

### 🇬🇧 In English:

2. **[WORKFLOW_EXPLAINED_EN.md](WORKFLOW_EXPLAINED_EN.md)** (13KB)
   - Complete explanation of workflow changes
   - When and how files are generated
   - Tracking options and time estimates
   - Workarounds for instant code display
   - Practical examples and best practices
   - FAQ and troubleshooting

### 🌐 Kétnyelvű / Bilingual:

3. **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** (8KB)
   - Gyors parancs referencia / Quick command reference
   - Kulcsszavak és flag-ek / Keywords and flags
   - Sablon kérések / Template requests
   - Időbecslések / Time estimates
   - Pro tippek / Pro tips

---

## 🚀 Gyors kezdés / Quick Start

### Ha azonnal szükséged van kódra / If you need code immediately:

```
"Show me [what you need] in markdown code block.
DON'T create files or PR."
```

### Ha diff előnézetet szeretnél / If you want diff preview:

```
"Show changes for [feature] as unified diff.
DON'T apply yet, wait for approval."
```

### Ha teljes PR-t szeretnél / If you want full PR:

```
"Implement [feature] with tests and documentation.
Create complete PR."
```

---

## 📊 Workflow összehasonlítás / Workflow Comparison

| Régi / Old | Új / New | Workaround |
|------------|----------|------------|
| Kód azonnal chat-ben / Code instant in chat | PR-alapú / PR-based | "Show in markdown, NO PR" |
| 5-10s | 1-5m | 10-20s with flags |
| Nincs verziókezelés / No version control | Git commit/push | "Show git commands only" |
| Nincs review / No review | Automated review | "Explain, don't apply" |

---

## 🎯 Use Case-ek / Use Cases

### 1️⃣ Gyors konzultáció / Quick Consultation (10-30s)
- Kód példák / Code examples
- Magyarázatok / Explanations  
- Tanácsok / Advice
- **Flag:** `"DON'T modify files"`

### 2️⃣ Kisebb módosítás / Small Change (30s-2m)
- 1-3 fájl / 1-3 files
- Bug fix / Bug fix
- Config update / Config update
- **Flag:** `"Minimal change, quick fix"`

### 3️⃣ Feature fejlesztés / Feature Development (2-5m)
- Multi-fájl / Multi-file
- Tesztekkel / With tests
- Dokumentációval / With docs
- **Flag:** Normal request

### 4️⃣ Nagy refactor / Large Refactor (5-15m)
- Sok fájl / Many files
- Architecture change / Architecture change
- Migration / Migration
- **Flag:** Detailed spec

---

## 🔑 Legfontosabb kulcsszavak / Most Important Keywords

### ⛔ Módosítás megakadályozása / Prevent Modifications:
- `"DON'T create files"`
- `"DON'T modify files"`
- `"NO PR needed"`
- `"Show only, don't apply"`

### ✅ Chat output kérése / Request Chat Output:
- `"Show in markdown"`
- `"Display as code block"`
- `"Format as diff"`
- `"Provide example code"`

### 🔄 Inkrementális munka / Incremental Work:
- `"Create plan first"`
- `"Step by step"`
- `"Wait for approval"`

---

## 📍 GitHub PR követés / GitHub PR Tracking

### Ahol megtalálod a PR-t / Where to find the PR:

```
https://github.com/iszolik/iszolik/pulls
```

### PR nézetei / PR views:

1. **Files changed** tab
   - Láthatod az összes módosítást / See all changes
   - Diff nézet / Diff view
   - Inline vagy split view / Inline or split view

2. **Commits** tab
   - Commit history / Commit history
   - Időrendi nézet / Chronological view

3. **Checks** tab
   - CI/CD státusz / CI/CD status
   - Test eredmények / Test results

---

## 💡 Pro Tippek / Pro Tips

### ✅ Tipp 1: Használj explicit constraint-eket / Use explicit constraints

```
"Modify ONLY src/auth/login.js
Change ONLY the validateUser function
DON'T refactor other code"
```

### ✅ Tipp 2: Két lépéses jóváhagyás / Two-step approval

```
Step 1: "Show implementation plan"
[Review]
Step 2: "Looks good, implement it"
```

### ✅ Tipp 3: Időbecslés előre / Estimate time upfront

- Egyszerű kérés / Simple request: 30-60s
- Közepes / Medium: 2-4m
- Komplex / Complex: 5-10m

### ✅ Tipp 4: Status check-ek / Status checks

```
"What's the progress?"
"Which files are modified?"
"Status update please?"
```

---

## 🛠️ Troubleshooting

### Probléma / Problem: Túl sokáig tart / Takes too long

**Megoldás / Solution:**
```
"This is taking long. Show what's done so far."
"Simplify to minimal changes only."
```

### Probléma / Problem: Nem azt csinálja, amit kértem / Not doing what I asked

**Megoldás / Solution:**
```
"Stop. Let me clarify: [explanation]"
"Show me the plan first before coding."
```

### Probléma / Problem: Túl nagy a PR / PR too large

**Megoldás / Solution:**
```
"Break this into smaller PRs"
"Implement only core feature, skip extras"
```

---

## 📞 Kapcsolat / Contact

Ha kérdésed van / If you have questions:

1. **GitHub Issue:** Nyiss issue-t / Open an issue
2. **PR Comment:** Kommentálj a PR-ben / Comment on PR
3. **Chat:** "I have a follow-up question..."

---

## 📚 További olvasnivalók / Further Reading

### Részletes dokumentáció / Detailed Documentation:
- Magyar: [WORKFLOW_MAGYARAZAT_HU.md](WORKFLOW_MAGYARAZAT_HU.md)
- English: [WORKFLOW_EXPLAINED_EN.md](WORKFLOW_EXPLAINED_EN.md)

### Gyors referencia / Quick Reference:
- [QUICK_REFERENCE.md](QUICK_REFERENCE.md)

---

## 🏗️ Repository struktúra / Repository Structure

```
iszolik/iszolik/
├── README.md                      (ez a fájl / this file)
├── WORKFLOW_MAGYARAZAT_HU.md     (teljes magyar útmutató / full HU guide)
├── WORKFLOW_EXPLAINED_EN.md       (teljes angol útmutató / full EN guide)
├── QUICK_REFERENCE.md             (gyors parancs lista / quick commands)
└── reservation.php                (példa fájl / example file)
```

---

## 🎓 Tanulási útvonal / Learning Path

### Kezdő / Beginner:
1. Olvasd el a [Quick Reference](QUICK_REFERENCE.md)-t
2. Próbálj ki egyszerű kéréseket "DON'T modify" flag-gel
3. Nézd meg a példákat

### Haladó / Intermediate:
1. Olvasd el a teljes [Workflow Magyarázat](WORKFLOW_MAGYARAZAT_HU.md)-ot
2. Próbáld a két-lépéses jóváhagyást
3. Gyakorold az inkrementális fejlesztést

### Expert:
1. Tanulj meg constraint-eket használni precízen
2. Optimalizáld a kéréseket időre
3. Kombináld a különböző technikákat

---

## 🔐 Biztonság / Security

Az agent rendszer automatikusan futtat:
The agent system automatically runs:

- **Code review** - Kód minőség ellenőrzés / Code quality check
- **CodeQL scan** - Biztonsági vizsgálat / Security scan
- **Dependency check** - Függőség ellenőrzés / Dependency check

---

## 📈 Teljesítmény / Performance

### Optimalizálási tippek / Optimization Tips:

1. **Kis kérések / Small requests:** Use "show only" flags (5-15s)
2. **Közepes / Medium:** Explicit file list (1-2m)
3. **Nagy / Large:** Let it run, check GitHub (3-8m)

---

## 🌟 Leggyakoribb kérések / Most Common Requests

### Top 5:

1. `"Show code example, NO PR"` (Advisory only)
2. `"Show diff first, apply if approved"` (Safe modification)
3. `"Create plan, implement step by step"` (Incremental)
4. `"Quick fix for [bug]"` (Emergency)
5. `"Implement [feature] with tests"` (Full solution)

---

## 📝 Changelog

### 2026-02-18 - v1.0
- ✅ Kezdeti dokumentáció létrehozva / Initial documentation created
- ✅ Magyar és angol verziók / Hungarian and English versions
- ✅ Quick reference guide hozzáadva / Quick reference guide added
- ✅ Gyakorlati példák / Practical examples
- ✅ Troubleshooting szekció / Troubleshooting section

---

## 🤝 Közreműködés / Contributing

Ha javítást vagy kiegészítést szeretnél:
If you want to improve or add:

1. Készíts issue-t / Create an issue
2. Vagy kommentálj PR-ben / Or comment on PR
3. Vagy küldj pull request-et / Or send a pull request

---

## 📜 License

This documentation is provided as-is for informational purposes.

---

## 🙏 Köszönetnyilvánítás / Acknowledgments

Köszönet mindenkinek, aki visszajelzést adott a workflow változásokról és segített a dokumentáció javításában.

Thanks to everyone who provided feedback on workflow changes and helped improve this documentation.

---

**Utolsó frissítés / Last Updated:** 2026-02-18  
**Verzió / Version:** 1.0  
**Karbantartó / Maintainer:** iszolik

---

**💡 Emlékeztető / Reminder:**

A legfontosabb kulcs a hatékony használathoz: **Légy explicit és használj megfelelő flag-eket!**

The most important key to effective use: **Be explicit and use appropriate flags!**

🚀 **Boldog kódolást! / Happy Coding!** 🚀
