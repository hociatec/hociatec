# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: public-translation.preload.playwright.ts >> prewarm-and-validate-public-translations
- Location: e2e/public-translation.preload.playwright.ts:290:1

# Error details

```
Error: Missing translation on 24 route(s)

expect(received).toHaveLength(expected)

Expected length: 0
Received length: 24
Received array:  [{"enSignals": 7, "frSignals": 7, "reason": "no-text-change", "route": "/", "similarity": 1, "status": "fail"}, {"enSignals": 8, "frSignals": 8, "reason": "no-text-change", "route": "/actualites/hociatec-renforce-son-accompagnement-numerique-de-proximite", "similarity": 1, "status": "fail"}, {"enSignals": 7, "frSignals": 7, "reason": "no-text-change", "route": "/appointments/book", "similarity": 1, "status": "fail"}, {"enSignals": 6, "frSignals": 6, "reason": "no-text-change", "route": "/beta-test", "similarity": 1, "status": "fail"}, {"enSignals": 5, "frSignals": 5, "reason": "no-text-change", "route": "/catalogue/location", "similarity": 1, "status": "fail"}, {"enSignals": 7, "frSignals": 7, "reason": "no-text-change", "route": "/catalogue/produits/iphone-17-pro-max-reconditionne-titane-naturel-128-go", "similarity": 1, "status": "fail"}, {"enSignals": 6, "frSignals": 6, "reason": "no-text-change", "route": "/catalogue/recherche?q=iphone", "similarity": 1, "status": "fail"}, {"enSignals": 6, "frSignals": 6, "reason": "no-text-change", "route": "/catalogue/smartphones", "similarity": 1, "status": "fail"}, {"enSignals": 5, "frSignals": 5, "reason": "no-text-change", "route": "/catalogue/vente", "similarity": 1, "status": "fail"}, {"enSignals": 8, "frSignals": 8, "reason": "no-text-change", "route": "/contact", "similarity": 1, "status": "fail"}, …]
```

# Test source

```ts
  229 |       reason: 'single-language-navigation-error',
  230 |     };
  231 |   }
  232 | 
  233 |   if (fr.status >= 500 && en.status >= 500) {
  234 |     return {
  235 |       route,
  236 |       status: 'skip',
  237 |       similarity: 1,
  238 |       frSignals: frenchSignals(fr.normalized),
  239 |       enSignals: frenchSignals(en.normalized),
  240 |       reason: 'backend-500',
  241 |     };
  242 |   }
  243 | 
  244 |   if (fr.text.trim() === '' || en.text.trim() === '') {
  245 |     return {
  246 |       route,
  247 |       status: 'skip',
  248 |       similarity: 1,
  249 |       frSignals: frenchSignals(fr.normalized),
  250 |       enSignals: frenchSignals(en.normalized),
  251 |       reason: 'empty-body',
  252 |     };
  253 |   }
  254 | 
  255 |   const similarity = jaccard(tokenize(fr.normalized), tokenize(en.normalized));
  256 |   const frSignals = frenchSignals(fr.normalized);
  257 |   const enSignals = frenchSignals(en.normalized);
  258 | 
  259 |   if (fr.normalized === en.normalized) {
  260 |     return {
  261 |       route,
  262 |       status: 'fail',
  263 |       similarity,
  264 |       frSignals,
  265 |       enSignals,
  266 |       reason: 'no-text-change',
  267 |     };
  268 |   }
  269 | 
  270 |   if (similarity > 0.97 && enSignals >= frSignals - 1) {
  271 |     return {
  272 |       route,
  273 |       status: 'warn',
  274 |       similarity,
  275 |       frSignals,
  276 |       enSignals,
  277 |       reason: 'weak-change',
  278 |     };
  279 |   }
  280 | 
  281 |   return {
  282 |     route,
  283 |     status: 'ok',
  284 |     similarity,
  285 |     frSignals,
  286 |     enSignals,
  287 |   };
  288 | };
  289 | 
  290 | test('prewarm-and-validate-public-translations', async ({ browser, request }) => {
  291 |   test.setTimeout(180_000);
  292 |   const context = await browser.newContext();
  293 |   const baseUrl = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:4173';
  294 | 
  295 |   const probe = await context.newPage();
  296 |   try {
  297 |     await probe.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 5_000 });
  298 |   } catch {
  299 |     await probe.close();
  300 |     test.skip(true, `Impossible de joindre ${baseUrl} depuis ce runner`);
  301 |   }
  302 |   await probe.close();
  303 | 
  304 |   const dynamicRoutes = await collectDynamicRoutes(request);
  305 |   const routes = Array.from(new Set([...STATIC_PUBLIC_ROUTES, ...dynamicRoutes])).sort();
  306 | 
  307 |   type RouteTranslationResult = Awaited<ReturnType<typeof evaluateRoute>>;
  308 |   const checks: RouteTranslationResult[] = [];
  309 |   for (const route of routes) {
  310 |     checks.push(await evaluateRoute(route, context));
  311 |   }
  312 | 
  313 |   await context.close();
  314 | 
  315 |   const failed = checks.filter((entry) => entry.status === 'fail');
  316 |   const warned = checks.filter((entry) => entry.status === 'warn');
  317 | 
  318 |   const report = checks
  319 |     .map(({ route, status, similarity, frSignals, enSignals, reason }) =>
  320 |       `${String(status).toUpperCase().padEnd(5)} ${route} | sim=${similarity.toFixed(2)} | fr=${frSignals} en=${enSignals} ${
  321 |         reason ? `| ${reason}` : ''
  322 |       }`,
  323 |     )
  324 |     .join('\n');
  325 | 
  326 |   // eslint-disable-next-line no-console
  327 |   console.log(`\nVerifying public translation coverage (FR -> EN)\n${report}\n`);
  328 | 
> 329 |   expect(failed, `Missing translation on ${failed.length} route(s)`).toHaveLength(0);
      |                                                                      ^ Error: Missing translation on 24 route(s)
  330 |   expect(warned, `Weak translation signal on ${warned.length} route(s)`).toHaveLength(0);
  331 | });
  332 | 
```