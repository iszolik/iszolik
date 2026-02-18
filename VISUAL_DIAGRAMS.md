# Visual Workflow Diagrams

## 🔄 Workflow összehasonlítás / Workflow Comparison

### RÉGI WORKFLOW / OLD WORKFLOW

```
┌─────────────┐
│ User kérés  │
│ User request│
└──────┬──────┘
       │
       │ [5-10 seconds]
       ▼
┌─────────────────────┐
│ Agent válasz        │
│ Agent response      │
│                     │
│ ┌─────────────────┐ │
│ │   CODE BLOCK    │ │
│ │   (markdown)    │ │
│ │                 │ │
│ │ function foo()  │ │
│ │   return bar;   │ │
│ │ }               │ │
│ └─────────────────┘ │
│                     │
│ Kész! / Done!       │
└─────────────────────┘
```

**Előnyök / Pros:**
- ✅ Gyors / Fast (5-10s)
- ✅ Látható / Visible
- ✅ Egyszerű / Simple

**Hátrányok / Cons:**
- ❌ Nincs PR / No PR
- ❌ Nincs teszt / No test
- ❌ Nincs review / No review

---

### ÚJ WORKFLOW / NEW WORKFLOW

```
┌─────────────┐
│ User kérés  │
│ User request│
└──────┬──────┘
       │
       ▼
╔══════════════════════════════════════════╗
║  AGENT RENDSZER / AGENT SYSTEM           ║
╠══════════════════════════════════════════╣
║                                          ║
║  Phase 1: TERV / PLAN [5-10s]           ║
║  ┌────────────────────────────────┐     ║
║  │ - Elemzés / Analysis           │     ║
║  │ - Checklist készítés / Create  │     ║
║  │ - report_progress              │     ║
║  └────────────────────────────────┘     ║
║            │                             ║
║            ▼                             ║
║  Phase 2: IMPLEMENTÁLÁS [30s-2m]        ║
║  ┌────────────────────────────────┐     ║
║  │ - Fájlok olvasása / Read files │     ║
║  │ - Kód módosítás / Modify code  │     ║
║  │ - Tesztelés / Testing          │     ║
║  │ - Validálás / Validation       │     ║
║  └────────────────────────────────┘     ║
║            │                             ║
║            ▼                             ║
║  Phase 3: COMMIT & PUSH [5-10s]         ║
║  ┌────────────────────────────────┐     ║
║  │ - git add .                    │     ║
║  │ - git commit -m "..."          │     ║
║  │ - git push origin branch       │     ║
║  └────────────────────────────────┘     ║
║            │                             ║
║            ▼                             ║
║  Phase 4: REVIEW & QA [10-30s]          ║
║  ┌────────────────────────────────┐     ║
║  │ - Code review                  │     ║
║  │ - CodeQL security scan         │     ║
║  │ - Összegzés / Summary          │     ║
║  └────────────────────────────────┘     ║
║                                          ║
╚═══════════════╤══════════════════════════╝
                │
                ▼
      ┌──────────────────┐
      │  GitHub PR       │
      │  ┌────────────┐  │
      │  │ Files      │  │
      │  │ changed    │  │
      │  └────────────┘  │
      │  ┌────────────┐  │
      │  │ Commits    │  │
      │  └────────────┘  │
      │  ┌────────────┐  │
      │  │ Checks     │  │
      │  └────────────┘  │
      └──────────────────┘
```

**Előnyök / Pros:**
- ✅ Teljes QA / Full QA
- ✅ Git verziókezelés / Version control
- ✅ Review-olható / Reviewable
- ✅ Biztonsági ellenőrzés / Security check

**Hátrányok / Cons:**
- ⚠️ Lassabb / Slower (1-5m)
- ⚠️ Nem látszik chat-ben / Not in chat
- ⚠️ GitHub tracking kell / Needs GitHub tracking

---

## 🔀 WORKAROUND: Azonnali kód / Instant Code

```
┌─────────────────────────────┐
│ User kérés + FLAG           │
│ User request + FLAG         │
│                             │
│ "Show code in markdown.     │
│  DON'T create files!"       │
└─────────────┬───────────────┘
              │
              │ [10-20 seconds]
              ▼
┌─────────────────────────────────┐
│ Agent válasz / Agent response   │
│                                 │
│ "Here's the code:"              │
│                                 │
│ ```javascript                   │
│ function validateEmail(email) { │
│   const regex = /^[^\s@]+@...  │
│   return regex.test(email);     │
│ }                               │
│ ```                             │
│                                 │
│ ✅ NO PR created                │
│ ✅ NO files modified            │
│ ✅ Instant in chat              │
└─────────────────────────────────┘
```

**Hogyan / How:**
- 🔑 Használd a flag-et / Use the flag: `"DON'T create files"`
- 🔑 Vagy / Or: `"Show only, don't apply"`
- 🔑 Vagy / Or: `"NO PR needed"`

---

## 🎯 Döntési fa / Decision Tree

```
                ┌─────────────────┐
                │   User kérés    │
                │   User request  │
                └────────┬────────┘
                         │
            ╔════════════╪════════════╗
            ║  KÉRDÉS / QUESTION:    ║
            ║  Kell PR? / Need PR?   ║
            ╚════════════╪════════════╝
                         │
         ┌───────────────┴───────────────┐
         │                               │
        IGEN / YES                      NEM / NO
         │                               │
         ▼                               ▼
┌─────────────────┐            ┌──────────────────┐
│  Új workflow    │            │  Flag használat  │
│  New workflow   │            │  Use flags       │
│                 │            │                  │
│ "Implement [X]  │            │ "Show [X]        │
│  with tests"    │            │  DON'T modify"   │
│                 │            │                  │
│ Idő: 1-5m      │            │ Idő: 10-30s     │
│ Time: 1-5m     │            │ Time: 10-30s    │
│                 │            │                  │
│ Output: GitHub  │            │ Output: Chat     │
│ PR              │            │ block            │
└─────────────────┘            └──────────────────┘
         │                               │
         ▼                               ▼
┌─────────────────┐            ┌──────────────────┐
│ Files changed   │            │ Copy-paste kód   │
│ Commits         │            │ Copy-paste code  │
│ Review          │            │ Manual apply     │
│ Merge           │            │                  │
└─────────────────┘            └──────────────────┘
```

---

## 📊 Use Case folyamat / Use Case Flow

### Use Case 1: Gyors kód példa / Quick Code Example

```
User: "Show me email validation function. NO PR."
  │
  │ [10s]
  ▼
Agent: ```js
       function validate(email) {...}
       ```
  │
  ▼
User: [Copy-paste to project]
```

---

### Use Case 2: Diff előnézet + jóváhagyás / Diff Preview + Approval

```
User: "Add error handling to auth.js. Show diff first."
  │
  │ [15s]
  ▼
Agent: ```diff
       - return user;
       + if (!user) throw new Error();
       + return user;
       ```
  │
  ▼
User: "Looks good, apply it and create PR"
  │
  │ [1-2m]
  ▼
Agent: ✅ PR created: #123
       Files changed: auth.js
  │
  ▼
User: [Review on GitHub]
```

---

### Use Case 3: Lépésről-lépésre / Step-by-step

```
User: "Create plan for adding authentication"
  │
  │ [10s]
  ▼
Agent: Plan:
       - [ ] 1. Add login form
       - [ ] 2. Add validation
       - [ ] 3. Add session
       - [ ] 4. Add tests
  │
  ▼
User: "Implement step 1 only"
  │
  │ [30s]
  ▼
Agent: ✅ Step 1 done
       PR updated with login form
  │
  ▼
User: [Review on GitHub]
  │
  ▼
User: "Continue with step 2"
  │
  │ [40s]
  ▼
Agent: ✅ Step 2 done
       PR updated with validation
  │
  ▼
[...continues...]
```

---

## 🚦 Workflow sebességek / Workflow Speeds

```
TIER 1: INSTANT (5-30s)
═══════════════════════════
┌────────────────────────┐
│ Advisory csak / only   │
│ "Show code, NO PR"     │
│ "Explain, don't apply" │
└────────────────────────┘

TIER 2: FAST TRACK (30s-2m)
═══════════════════════════════
┌────────────────────────┐
│ 1-3 fájl / file        │
│ "Quick fix"            │
│ "Minimal change"       │
└────────────────────────┘

TIER 3: STANDARD (2-5m)
═══════════════════════════════
┌────────────────────────┐
│ Multi-file             │
│ "Implement feature"    │
│ With tests + docs      │
└────────────────────────┘

TIER 4: COMPLEX (5-15m)
═══════════════════════════════
┌────────────────────────┐
│ Refactoring            │
│ Migration              │
│ Architecture change    │
└────────────────────────┘
```

---

## 🎓 Információ áramlás / Information Flow

```
            ┌──────────────────┐
            │   USER REQUEST   │
            └────────┬─────────┘
                     │
         ╔═══════════╧═══════════╗
         ║   FLAG DETECTION?     ║
         ╚═══════════╤═══════════╝
                     │
        ┌────────────┴────────────┐
        │                         │
   "DON'T modify"              Normal
        │                         │
        ▼                         ▼
┌───────────────┐        ┌────────────────┐
│ ADVISORY MODE │        │  AGENT SYSTEM  │
│               │        │                │
│ ┌───────────┐ │        │ ┌────────────┐ │
│ │ Analysis  │ │        │ │ Explore    │ │
│ └───────────┘ │        │ └────────────┘ │
│       │       │        │       │        │
│       ▼       │        │       ▼        │
│ ┌───────────┐ │        │ ┌────────────┐ │
│ │ Generate  │ │        │ │ Task       │ │
│ │ markdown  │ │        │ └────────────┘ │
│ └───────────┘ │        │       │        │
│       │       │        │       ▼        │
│       ▼       │        │ ┌────────────┐ │
│ ┌───────────┐ │        │ │ General-   │ │
│ │ Return to │ │        │ │ purpose    │ │
│ │ chat      │ │        │ └────────────┘ │
│ └───────────┘ │        │       │        │
└───────┬───────┘        └───────┬────────┘
        │                        │
        │                        ▼
        │                 ┌──────────────┐
        │                 │ Git commit   │
        │                 └──────┬───────┘
        │                        │
        │                        ▼
        │                 ┌──────────────┐
        │                 │ GitHub PR    │
        │                 └──────┬───────┘
        │                        │
        ▼                        ▼
   ┌─────────────────────────────────┐
   │       USER RECEIVES             │
   │                                 │
   │  Left: Code in chat             │
   │  Right: PR link + Files changed │
   └─────────────────────────────────┘
```

---

## 📈 Időskála összehasonlítás / Timeline Comparison

### Régi workflow / Old workflow:
```
[0s]──────[5s]──────[10s]
  │         │         │
Start    Response   Done ✅
         (code in
          chat)
```

### Új workflow ADVISORY móddal / New workflow with ADVISORY mode:
```
[0s]──────[10s]─────[20s]
  │         │         │
Start    Response   Done ✅
         (code in
          chat)
```

### Új workflow TELJES PR-rel / New workflow with FULL PR:
```
[0s]──[10s]───[30s]───[60s]───[90s]───[120s]──[180s]
  │     │       │       │       │       │       │
Start  Plan  Modify  Commit  Review   QA     Done ✅
                                             (GitHub
                                               PR)
```

---

## 🔑 Flag hatása / Flag Effect

```
KÉRÉS / REQUEST:
┌──────────────────────────────────────┐
│ "Implement feature X"                │
└──────────────┬───────────────────────┘
               │
               ▼
        [FLAG CHECK]
               │
    ┌──────────┴──────────┐
    │                     │
HAS FLAGS           NO FLAGS
"DON'T modify"           │
    │                    │
    ▼                    ▼
┌─────────┐      ┌──────────────┐
│ Chat    │      │ Agent System │
│ Output  │      │ + PR         │
│         │      │              │
│ 10-20s  │      │ 1-5 minutes  │
└─────────┘      └──────────────┘
```

---

## 📚 Dokumentáció struktúra / Documentation Structure

```
iszolik/iszolik/
    │
    ├─ README.md ◄──── START HERE
    │    │
    │    ├─ Overview
    │    ├─ Quick start
    │    └─ Links to other docs
    │
    ├─ QUICK_REFERENCE.md ◄──── FOR QUICK LOOKUP
    │    │
    │    ├─ Commands
    │    ├─ Flags
    │    └─ Templates
    │
    ├─ WORKFLOW_MAGYARAZAT_HU.md ◄──── DEEP DIVE (HU)
    │    │
    │    ├─ Complete explanation
    │    ├─ Use cases
    │    ├─ Best practices
    │    └─ FAQ
    │
    ├─ WORKFLOW_EXPLAINED_EN.md ◄──── DEEP DIVE (EN)
    │    │
    │    ├─ Complete explanation
    │    ├─ Use cases
    │    ├─ Best practices
    │    └─ FAQ
    │
    ├─ VISUAL_DIAGRAMS.md ◄──── YOU ARE HERE
    │    │
    │    └─ Visual representations
    │
    └─ IMPLEMENTATION_SUMMARY.md ◄──── FOR CONTRIBUTORS
         │
         └─ Technical details
```

---

## 🎯 Következtetés / Conclusion

```
╔════════════════════════════════════════════════════════╗
║                                                        ║
║  GYORS KÓD / QUICK CODE?                              ║
║  → Use flags: "DON'T modify files"                    ║
║  → Time: 10-30s                                       ║
║  → Output: Chat                                       ║
║                                                        ║
║  TELJES MEGOLDÁS / COMPLETE SOLUTION?                 ║
║  → Let agent work                                     ║
║  → Time: 1-5m                                         ║
║  → Output: GitHub PR                                  ║
║                                                        ║
║  MINDKETTŐ / BOTH?                                    ║
║  → Step 1: "Show plan only"                           ║
║  → Step 2: Review                                     ║
║  → Step 3: "Implement it"                             ║
║  → Time: 1-3m total                                   ║
║  → Output: Controlled PR                              ║
║                                                        ║
╚════════════════════════════════════════════════════════╝
```

---

**Használd ezeket a diagramokat a workflow megértéséhez!**  
**Use these diagrams to understand the workflow!** 📊

---

**Verzió / Version:** 1.0  
**Utolsó frissítés / Last Updated:** 2026-02-18
