# Frontend Request Helper (v1)

## Decision
Use one shared `fetchJson` helper in storefront scripts instead of keeping two approaches in the same file.

## Why this is the chosen v1 pattern
- Removes repeated request boilerplate (`Content-Type`, `JSON.stringify`, credentials).
- Keeps each API call readable: endpoint + payload only.
- Reduces bugs when request settings need to change later.

## Before and After
Before (repeated in many places):

```js
await fetchJson(API.login, {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ email, password })
});
```

After (current pattern):

```js
await fetchJson(API.login, {
  method: "POST",
  body: { email, password }
});
```

Helper location:
- `ecommerce/assets/js/script.js`

## Learning note
Do not keep old implementations commented out in production files. Use Git history and docs like this for comparison and learning.
