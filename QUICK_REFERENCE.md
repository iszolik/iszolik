# Quick Reference - GitHub Copilot Workflow Commands

## 🚀 Gyors parancsok / Quick Commands

### Ha CSAK KÓDOT szeretnél látni (NO PR) / For CODE ONLY (NO PR)

```
"Show me [what you need] in markdown.
DON'T create files or PR."
```

```
"Provide example code for [feature].
No file modifications needed."
```

```
"Write a function that [does X].
Display in code block only."
```

---

### Ha DIFF ELŐNÉZETET szeretnél / For DIFF PREVIEW

```
"Show the exact changes needed in [file] as a diff.
DON'T apply yet, I'll review first."
```

```
"Preview the modifications for [feature].
Format as unified diff."
```

```
"Compare before/after for [component].
Wait for my approval."
```

---

### Ha TERVET szeretnél / For PLAN ONLY

```
"Create an implementation plan for [feature].
List steps as checklist.
DON'T start coding."
```

```
"Break down [task] into steps.
Show the plan only."
```

```
"What's the approach to implement [X]?
Explain without making changes."
```

---

### Ha LÉPÉSRŐL LÉPÉSRE szeretnél haladni / For STEP-BY-STEP

```
"Implement step 1 only: [description].
Wait after completion."
```

```
"Continue with the next step from the plan."
```

```
"Show progress. Which steps are done?"
```

---

### Ha GYORS JAVÍTÁS kell / For QUICK FIX

```
"Minimal fix for bug in [file]:[line].
One-liner if possible."
```

```
"Emergency hotfix for [problem].
Show diff first, apply if I approve."
```

```
"Quick patch for [issue].
Modify ONLY [specific file]."
```

---

### Ha TELJES MEGOLDÁS kell / For COMPLETE SOLUTION

```
"Implement [feature] with tests and docs.
Create full PR."
```

```
"Add [functionality] to the project.
Include all necessary changes."
```

```
"Refactor [component] following best practices.
Full implementation with review."
```

---

## ⛔ Tiltó kulcsszavak / Blocking Keywords

**Használd ezeket, ha NEM szeretnél fájl módosítást:**

| Magyar | English |
|--------|---------|
| "NE hozz létre fájlt" | "DON'T create files" |
| "NE módosítsd a fájlokat" | "DON'T modify files" |
| "Nincs szükség PR-re" | "NO PR needed" |
| "Csak mutasd meg" | "Show only" |
| "NE alkalmazd még" | "DON'T apply yet" |
| "Csak magyarázd el" | "Explain only" |
| "Előnézet először" | "Preview first" |

---

## ✅ Chat output kulcsszavak / Chat Output Keywords

**Használd ezeket, ha chat-ben szeretnéd látni:**

| Magyar | English |
|--------|---------|
| "Mutasd markdown-ban" | "Show in markdown" |
| "Code block formában" | "In code block format" |
| "Diff formátumban" | "As unified diff" |
| "Példa kóddal" | "With example code" |
| "Kód snippet-tel" | "With code snippet" |

---

## 🎯 Workflow rövidítések / Workflow Shortcuts

### Azonnali válasz pattern / Instant response pattern:

```
"[Kérdés/Question] - Quick answer, no changes needed."
```

### Két lépéses pattern / Two-step pattern:

```
1. "[Kérés/Request] - Show plan only"
2. "Looks good, implement it now"
```

### Iteratív pattern / Iterative pattern:

```
1. "Plan for [X]"
2. "Step 1: [detail]"
3. [Review on GitHub]
4. "Step 2: [detail]"
5. [Review on GitHub]
...
```

---

## 📊 Időbecslések / Time Estimates

| Kérés típusa / Request Type | Idő / Time | Módszer / Method |
|------------------------------|------------|------------------|
| Kód példa / Code example | 5-15s | "Show in chat" |
| Diff előnézet / Diff preview | 10-20s | "Show diff" |
| Terv / Plan | 15-30s | "Create plan" |
| 1 fájl módosítás / 1 file change | 30-60s | "Modify only [file]" |
| 3-5 fájl / 3-5 files | 1-2m | "Quick fix" |
| Teljes feature / Full feature | 2-5m | "Implement [X]" |
| Nagy refactor / Large refactor | 5-15m | "Refactor [X]" |

---

## 🔍 Status ellenőrzés / Status Check

### Chat-ben / In Chat:

```
"Status update please?"
"Which files have been modified?"
"What's the current progress?"
"Show me what's done so far."
```

### GitHub-on / On GitHub:

1. Go to: `https://github.com/[owner]/[repo]/pulls`
2. Find PR (usually `copilot/[feature-name]`)
3. Check tabs:
   - **Files changed** → See diffs
   - **Commits** → See commit history
   - **Checks** → See CI/test results

---

## 🛠️ Troubleshooting Commands

### Ha túl sokáig tart / If taking too long:

```
"Status check - what's happening?"
"Cancel current task and show plan only"
"This is taking too long, simplify to minimal changes"
```

### Ha rossz irányba megy / If going wrong direction:

```
"Stop, this is not what I need"
"Revert last changes"
"Start over with this approach: [new direction]"
```

### Ha túl nagy lett a PR / If PR too large:

```
"This is too much. Break it into smaller PRs"
"Implement only the core feature, skip extras"
"Minimal changes only for [specific goal]"
```

---

## 💡 Pro Tips

### Tipp 1: Mindig explicit / Always be explicit

❌ Rossz / Bad:
```
"Fix the bug"
```

✅ Jó / Good:
```
"Fix the null pointer bug in user.service.js:45
by adding null check. Minimal change only."
```

---

### Tipp 2: Használj constraint-eket / Use constraints

❌ Rossz / Bad:
```
"Improve the authentication system"
```

✅ Jó / Good:
```
"Add password strength validation to auth.js.
DON'T refactor existing code.
Modify only the validatePassword function."
```

---

### Tipp 3: Két lépéses jóváhagyás / Two-step approval

✅ Biztonságos / Safe:
```
Step 1: "Show implementation plan for [X]"
[Review plan]
Step 2: "Approved, implement it"
```

---

### Tipp 4: Explicit fájl lista / Explicit file list

✅ Pontos / Precise:
```
"Modify ONLY these files:
- src/auth/login.js
- src/auth/session.js
DON'T touch any other files."
```

---

## 📝 Sablon kérések / Template Requests

### Sablon 1: Csak konzultáció / Consultation Only

```
I'm planning to [implement X].

Questions:
1. What's the best approach?
2. What are the trade-offs?
3. Any potential issues?

DON'T make changes yet, just advise.
```

---

### Sablon 2: Diff jóváhagyásos / Diff with Approval

```
I need to [change X] in [file].

Step 1: Show me the exact diff
Step 2: Wait for my approval
Step 3: Apply if approved
```

---

### Sablon 3: Inkrementális implementálás / Incremental Implementation

```
Feature: [description]

Approach:
1. First create the plan
2. Implement core functionality
3. Add tests
4. Add documentation

Let's start with step 1 - show me the plan.
```

---

### Sablon 4: Emergency hotfix / Sürgős javítás

```
URGENT: [problem description]

Requirements:
- Minimal change
- Single file if possible
- Show diff before applying
- Fast turnaround needed

Affected file: [path]
```

---

## 🌐 Hasznos linkek / Useful Links

### GitHub PR tracking:
```
https://github.com/[owner]/[repo]/pulls
```

### Direct PR link format:
```
https://github.com/[owner]/[repo]/pull/[PR_NUMBER]
```

### Files changed view:
```
https://github.com/[owner]/[repo]/pull/[PR_NUMBER]/files
```

---

## ⚡ Gyakori kombinációk / Common Combinations

### "Gyors példa" kombó / "Quick Example" Combo:

```
"Quick example: [what you need]
Show in markdown code block.
NO PR, NO files.
Under 50 lines preferred."
```

---

### "Biztonságos módosítás" kombó / "Safe Modification" Combo:

```
"[Feature request]

Constraints:
- Show plan first
- Modify only [specific files]
- Minimal changes
- Wait for approval before each step"
```

---

### "Teljes feature" kombó / "Full Feature" Combo:

```
"Implement [feature] with:
- Unit tests
- Integration tests
- Documentation
- Error handling
- Input validation

Create complete PR with all changes."
```

---

**Mentsd el ezt a fájlt gyors referenciának!**  
**Save this file for quick reference!** 🚀

---

**Verzió / Version:** 1.0  
**Utolsó frissítés / Last Updated:** 2026-02-18
