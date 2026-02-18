# GitHub Copilot Agent Workflow - Complete Explanation

## Executive Summary

This document explains in detail **why and how** GitHub Copilot changed from the previous "direct code in chat" model to the new "agents/task system," and provides practical solutions for different use cases.

---

## 1. What Changed and Why?

### Old Workflow (Direct Chat Responses):
- ✅ **Immediate**: Code/diff appeared directly in chat response
- ✅ **Visible**: All changes immediately readable
- ❌ **Limited**: Simple, 1-2 file modifications only
- ❌ **Untested**: No automatic build/test execution
- ❌ **No Version Control**: No PR creation, no commits

### New Workflow (Agents/Task System):
- ✅ **Complex Tasks**: Multi-file, build, test, lint execution
- ✅ **Automated QA**: Automatic testing, security checks
- ✅ **PR Integration**: Changes committed, version controlled
- ✅ **Reviewable**: Viewable with code review tools
- ⚠️ **Slower**: 30s-5 minutes depending on task complexity
- ⚠️ **Less Transparent**: Process runs in background

---

## 2. When and How Are Files Generated?

### Process Steps:

#### Phase 1: Plan Creation (5-10 seconds)
```
User request → Agent analysis → Plan (checklist) → report_progress
```
- Agent creates a **checklist** of tasks
- This appears as the first response
- **NO code changes yet!**

#### Phase 2: Implementation (20 seconds - 3 minutes)
```
Read files → Modify code → Test → Validate
```
- Agent modifies files
- Each modification happens **locally** in git repo
- **Not directly visible in chat!**

#### Phase 3: Commit & Push (5-10 seconds)
```
git add . → git commit → git push → PR update
```
- All modifications are committed
- Pushed to branch
- **Now visible on GitHub UI!**

#### Phase 4: Review & QA (10 seconds - 1 minute)
```
Code review → CodeQL security scan → Summary
```
- Automated code review runs
- Security check (CodeQL)
- **Results visible at end of chat**

---

## 3. Where and How Long to Wait? How to Track?

### Tracking Points:

#### A) In Chat Window:
```
✓ Plan created           (5-10s)
✓ Files modified         (30s-2m)  
✓ Commit created         (5s)
✓ Push successful        (5s)
✓ Code review completed  (10-30s)
✓ Done ✓                 (total: 1-5m)
```

#### B) On GitHub PR Page:
1. Open: `https://github.com/iszolik/iszolik/pulls`
2. Find the PR (usually named `copilot/...`)
3. **Files changed** tab shows the diff
4. **Commits** tab shows commit history
5. **Checks** tab shows CI/test results

#### C) Time Estimates:
- **Simple (1-2 file changes)**: 30-60 seconds
- **Medium (3-10 files + tests)**: 2-4 minutes
- **Complex (many files + build + test)**: 4-8 minutes

---

## 4. Can We Switch Back to "Show Code in Chat" Mode?

### Short Answer: **Not directly**, but there are **workarounds**!

### Solutions for Different Use Cases:

### ✅ USE CASE 1: "Just need quick code example/snippet, no PR needed!"

**REQUEST PATTERN:**
```
"Show me an example of how to... 
[DON'T create a PR, DON'T modify files, just give me the code!]"
```

**Or:**
```
"Explain how to implement X, but DON'T make any file changes. 
Just show the code in the chat."
```

**Result:** Agent recognizes that only **advisory response** needed, not modifications.

---

### ✅ USE CASE 2: "Need specific diff for a file"

**REQUEST PATTERN:**
```
"Show me the exact changes needed in [filename] to fix [problem].
Format the response as a unified diff.
DON'T create a PR yet."
```

**Result:** Agent shows diff in markdown code block:
```diff
- old line
+ new line
```

---

### ✅ USE CASE 3: "Need new file content immediately"

**REQUEST PATTERN:**
```
"Write the complete content of a new file [filename] that does [description].
Show it in a markdown code block.
DON'T create files yet, I'll decide if I want to use it."
```

**Result:** Agent provides complete file content in code block.

---

### ✅ USE CASE 4: "Quick refactor suggestion for review"

**REQUEST PATTERN:**
```
"I'm considering refactoring [function/class]. 
Suggest the refactored version but DON'T apply it yet.
Show before/after side by side."
```

**Result:** Comparison in chat, you decide whether to apply.

---

### ✅ USE CASE 5: "PR needed, but want to see code during process"

**SOLUTION:**

1. **First Step - Request Plan:**
```
"Create a detailed plan (checklist only) for implementing [feature].
DON'T start implementation yet."
```

2. **Review the plan** → if good, then:

3. **Second Step - Implement in Phases:**
```
"Implement step 1 from the checklist: [first item detail]"
```

4. **Check changes on GitHub PR** (Files changed tab)

5. **If good, continue:**
```
"Continue with step 2: [second item]"
```

**Advantage:** You see the exact diff after each step on GitHub.

---

## 5. Best Practices

### 🎯 For QUICK RESPONSE (30s):
```
"Quick question, no file changes needed:
How do I [implement X] in [language/framework]?
Show example code."
```

### 🎯 For CONSULTATION (1-2m):
```
"I want to refactor [component], but I'm not sure about the approach.
Compare Option A vs Option B with code examples.
DON'T modify files yet."
```

### 🎯 For COMPLETE SOLUTION with PR (3-10m):
```
"Implement [feature] with full testing and documentation.
Create a PR with all necessary changes."
```

### 🎯 For EMERGENCY FIX (1-2m):
```
"Critical bug in [file]:[line]. 
Show me the minimal fix as a diff first.
If I approve, apply it and create a PR."
```

---

## 6. Troubleshooting - Common Issues

### ❌ Problem: "Takes too long, can't see what's happening"

**Solution:**
1. Check GitHub PR page in real-time
2. Refresh **Files changed** tab every 20 seconds
3. If no result after 5 minutes → type: "Status update please?"

### ❌ Problem: "Didn't write the code I wanted"

**Prevention:**
1. First ask for **plan/draft** in code block
2. Review it
3. Refine: "Good, but change [this part]"
4. Once accepted: "Now implement this in the actual files"

### ❌ Problem: "PR got too big, can't review it"

**Solution:**
- On GitHub PR page, enable: **"Show rich diff"**
- Or: **"Split diff"** view
- Or: `git diff` locally in repo clone

### ❌ Problem: "Only needed to change 1 line, but modified 10 files"

**Prevention:**
Use **explicit constraints**:
```
"Fix [bug] by changing ONLY [file.js]:[line].
Make the minimal possible change.
DON'T refactor other code."
```

---

## 7. Comparison Table

| Aspect | Old (direct chat) | New (agents/PR) | Workaround |
|--------|-------------------|-----------------|------------|
| **Response Time** | 5-10s | 1-5m | Use "no PR" flag → 10-20s |
| **Code Visibility** | Immediate in chat | GitHub PR | Request "markdown code block" |
| **Diff View** | None (copy-paste) | GitHub diff UI | Request "unified diff" format |
| **Testing** | Manual | Automatic | "Show test plan, don't run" |
| **Version Control** | None | Git commit/push | "Generate git commands only" |
| **Review** | Manual | Automated | "Explain changes, don't apply" |

---

## 8. Practical Examples - Sample Requests

### 📌 Example 1: Code only, NO PR

**Request:**
```
Write a JavaScript function to validate email addresses.
Show the complete code in markdown.
DON'T create any files or PR.
```

**Result:** Code block in chat, within 10s.

---

### 📌 Example 2: Diff preview, then apply

**Request 1:**
```
I want to add error handling to /path/to/file.js:functionName.
Show me the diff FIRST (before/after), don't apply yet.
```

**Agent response:** Shows diff.

**Request 2 (if approved):**
```
Looks good, now apply this change and create a PR.
```

---

### 📌 Example 3: Multi-step, iterative development

**Request 1:**
```
Create an implementation plan for adding user authentication.
List the steps as a checklist. DON'T start coding yet.
```

**Request 2:**
```
Implement step 1 and 2 from the plan. Show the code changes.
```

**GitHub review** → OK? → Continue.

**Request 3:**
```
Continue with steps 3-5.
```

---

## 9. Agent Keywords and Flags

Use these keywords in your request to achieve desired behavior:

### ⛔ Modification BLOCKING flags:
- `"DON'T create any files"`
- `"DON'T modify files"`
- `"NO PR needed"`
- `"Show only, don't apply"`
- `"Explain, don't implement"`
- `"Preview changes first"`

### ✅ Immediate chat output flags:
- `"Show in markdown code block"`
- `"Display as unified diff"`
- `"Provide example code"`
- `"Explain with code snippet"`

### 🔄 Iterative/step-by-step flags:
- `"Create plan first, implement later"`
- `"Show step 1 only"`
- `"Wait for approval before continuing"`

### ⚡ Quick/minimal flags:
- `"Minimal change only"`
- `"Quick answer needed"`
- `"One-liner fix if possible"`

---

## 10. Support Tiers

### 🟢 TIER 1: Instant (5-30s) - Advisory Only
- Code examples, snippets
- Explanations, documentation
- Architecture advice
- Diff preview, mock implementation

**How to request:** Use the "DON'T modify files" flag.

### 🟡 TIER 2: Fast Track (30s-2m) - Minimal Changes
- 1-3 file modifications
- Hotfix, bug fix
- Config update
- Documentation update

**How to request:** "Quick fix", "minimal change", with explicit file list.

### 🟠 TIER 3: Standard (2-5m) - Feature Implementation
- Multi-file modifications
- New feature implementation
- With tests, documentation
- Full PR + review

**How to request:** Normal feature request, let agent work.

### 🔴 TIER 4: Complex (5-15m) - Large Scale
- Refactoring
- Migration
- Architecture changes
- Multi-component modifications

**How to request:** Detailed specification, allow longer run time.

---

## 11. FAQ - Frequently Asked Questions

### Q1: "Why don't I see code in chat like before?"

**A:** The new system is **PR-based**, so code modifications appear on the GitHub PR interface, not in chat. This was introduced due to Git best practices and code review requirements.

**Workaround:** Use the "show in markdown, don't apply" flag.

---

### Q2: "How can I speed up the process?"

**A:**
1. Use explicit constraints ("only modify X file")
2. Indicate if NO PR needed ("show code only, no PR")
3. Request plan first, review it, then allow modification

---

### Q3: "What if I don't like the generated code?"

**A:**
1. **Before modification:** "Show me the plan/diff first"
2. **After modification:** 
   - Comment on GitHub PR
   - Or in chat: "Change [X] to [Y] in the PR"
   - Or: "Revert last change and try this approach instead"

---

### Q4: "Can I request 'debug mode' to see agent's thought process?"

**A:** Partially. Try:
```
"Create a detailed plan with reasoning for each step.
Explain WHY you choose each approach.
Then wait for my approval before coding."
```

---

### Q5: "How can I track what the agent is currently doing?"

**A:**
1. Watch chat messages (indicates steps)
2. Real-time refresh of GitHub PR **Commits** tab
3. Request status update: "What's the progress? Which files are done?"

---

## 12. Summary - TL;DR

### ✅ What You Can Do:

1. **Need quick code example?** → Use: `"Show code in chat, DON'T create PR"`
2. **Diff preview?** → `"Show diff first, apply after approval"`
3. **Complex feature?** → Let agent work, view on GitHub PR
4. **Iterative development?** → Request plan, approve in parts

### ❌ What You Cannot Do:

- Cannot switch back 100% to old mode
- Cannot completely avoid GitHub PR workflow for larger changes
- Cannot see agent "output" in real-time (only through checkpoints)

### 🎯 Best Strategy:

**Small request (< 50 lines code)?** → "Show in chat"  
**Medium request (50-200 lines)?** → "Plan first, then apply"  
**Large request (> 200 lines)?** → Let agent workflow run, track on GitHub

---

## 13. Contact and Feedback

If you have more questions or something is unclear:

1. **GitHub Issue:** Open an issue in the repo with your question
2. **PR Comment:** Comment directly on the PR
3. **Chat continuation:** "I have a follow-up question about..."

---

**Version:** 1.0  
**Last Updated:** 2026-02-18  
**Language:** English  
**Related Documents:** README.md, CONTRIBUTING.md

---

## Appendix: Agent System Architecture

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

### Timing Breakdown:

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

**With this document, you now know exactly how the new workflow operates and how to achieve the old "instant code" experience when you need it!** 🚀
