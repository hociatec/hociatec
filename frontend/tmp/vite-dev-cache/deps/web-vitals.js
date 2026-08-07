import "./chunk-PX6F3LHL.js";

// node_modules/web-vitals/dist/web-vitals.js
var t = -1;
var n = () => t;
var e = (n2) => {
  addEventListener("pageshow", (e2) => {
    e2.persisted && (t = e2.timeStamp, n2(e2));
  }, true);
};
var i = (t2, n2, e2, i2) => {
  let o2, s2;
  return (a2) => {
    n2.value >= 0 && (a2 || i2) && (s2 = n2.value - (o2 ?? 0), (s2 || void 0 === o2) && (o2 = n2.value, n2.delta = s2, n2.rating = ((t3, n3) => t3 > n3[1] ? "poor" : t3 > n3[0] ? "needs-improvement" : "good")(n2.value, e2), t2(n2)));
  };
};
var o = (t2) => {
  requestAnimationFrame(() => requestAnimationFrame(t2));
};
var s = () => {
  const t2 = performance.getEntriesByType("navigation")[0];
  if (t2 && t2.responseStart > 0 && t2.responseStart < performance.now()) return t2;
};
var a = () => s()?.activationStart ?? 0;
var r = -1;
var c = /* @__PURE__ */ new Set();
var f = () => "hidden" !== document.visibilityState || document.prerendering ? 1 / 0 : 0;
var d = (t2) => {
  if ("hidden" === document.visibilityState) {
    if ("visibilitychange" === t2.type) for (const t3 of c) t3();
    isFinite(r) || (r = "visibilitychange" === t2.type ? t2.timeStamp : 0, removeEventListener("prerenderingchange", d, true));
  }
};
var h = (t2 = false) => {
  if (t2 && (r = 1 / 0), r < 0) {
    const t3 = a(), n2 = document.prerendering ? void 0 : globalThis.performance.getEntriesByType("visibility-state").find((n3) => "hidden" === n3.name && n3.startTime >= t3)?.startTime;
    r = n2 ?? f(), addEventListener("visibilitychange", d, true), addEventListener("prerenderingchange", d, true), e(() => {
      setTimeout(() => {
        r = f();
      });
    });
  }
  return { get firstHiddenTime() {
    return r;
  }, onHidden(t3) {
    c.add(t3);
  } };
};
var l = (t2, e2 = -1, i2, o2 = 0, r2, c2, f2) => {
  const d2 = s(), h2 = d2?.navigationId || 0;
  let l2 = "navigate";
  i2 ? l2 = i2 : n() >= 0 ? l2 = "back-forward-cache" : d2 && (document.prerendering || a() > 0 ? l2 = "prerender" : document.wasDiscarded ? l2 = "restore" : d2.type && (l2 = d2.type.replace(/_/g, "-")));
  return { name: t2, value: e2, rating: "good", delta: 0, entries: [], id: `v6-${Date.now()}-${Math.floor(8999999999999 * Math.random()) + 1e12}`, navigationType: l2, navigationId: o2 || h2, navigationInteractionId: r2, navigationURL: c2 || d2?.name, navigationStartTime: f2 || 0 };
};
var g = /* @__PURE__ */ new WeakMap();
function u(t2, n2) {
  let e2 = g.get(n2);
  return e2 || (e2 = /* @__PURE__ */ new WeakMap(), g.set(n2, e2)), e2.get(t2) || e2.set(t2, new n2()), e2.get(t2);
}
var v = class {
  t;
  i = 0;
  o = [];
  h(t2) {
    if (t2.hadRecentInput) return;
    const n2 = this.o[0], e2 = this.o.at(-1);
    this.i && n2 && e2 && t2.startTime - e2.startTime < 1e3 && t2.startTime - n2.startTime < 5e3 ? (this.i += t2.value, this.o.push(t2)) : (this.i = t2.value, this.o = [t2]), this.t?.(t2);
  }
};
var m = (t2, n2, e2 = {}) => {
  try {
    const i2 = t2.filter((t3) => PerformanceObserver.supportedEntryTypes.includes(t3));
    if (i2.length > 0) {
      const t3 = new PerformanceObserver((t4) => {
        queueMicrotask(() => {
          const e3 = t4.getEntries();
          i2.length > 1 && e3.sort((t5, n3) => t5.startTime + t5.duration - (n3.startTime + n3.duration)), n2(e3);
        });
      });
      for (const n3 of i2) t3.observe({ type: n3, buffered: true, ...e2 });
      return t3;
    }
  } catch {
  }
};
var p = (t2) => globalThis.PerformanceObserver?.supportedEntryTypes.includes("soft-navigation") && "function" == typeof globalThis.PerformanceSoftNavigation?.prototype?.getLargestInteractionContentfulPaint && t2 && t2.reportSoftNavs;
var b = (t2, n2) => {
  if (t2.set(n2.navigationId, n2), t2.size > 2) {
    const n3 = t2.keys().next().value;
    void 0 !== n3 && t2.delete(n3);
  }
};
var T = (t2) => {
  let n2 = false;
  return () => {
    n2 || (t2(), n2 = true);
  };
};
var y = class {
  l;
};
var E = (t2) => {
  document.prerendering ? addEventListener("prerenderingchange", t2, true) : t2();
};
var M = [1800, 3e3];
var _ = (t2, s2 = {}) => {
  const r2 = p(s2);
  E(() => {
    const c2 = u(s2, y), f2 = h();
    let d2, g2 = l("FCP");
    const v2 = m(["paint"], (t3) => {
      for (const n2 of t3) "first-contentful-paint" === n2.name && (v2.disconnect(), n2.startTime < f2.firstHiddenTime && (g2.value = Math.max(n2.startTime - a(), 0), g2.entries.push(n2), g2.navigationId = n2.navigationId || g2.navigationId, d2(true)));
    });
    if (v2 && (d2 = i(t2, g2, M, s2.reportAllChanges), e((e2) => {
      g2 = l("FCP", -1, "back-forward-cache", g2.navigationId, g2.navigationInteractionId, g2.navigationURL, n()), d2 = i(t2, g2, M, s2.reportAllChanges), o(() => {
        g2.value = performance.now() - e2.timeStamp, d2(true);
      });
    })), r2) {
      m(["soft-navigation"], (n2) => {
        n2.forEach((n3) => {
          c2.l && n3.navigationId && b(c2.l, n3);
          const e2 = Math.max((n3.presentationTime || n3.paintTime || 0) - n3.startTime, 0);
          g2 = l("FCP", e2, "soft-navigation", n3.navigationId, n3.interactionId, n3.name, n3.startTime), d2 = i(t2, g2, M, s2.reportAllChanges), d2(true);
        });
      }, s2);
    }
  });
};
var L = [0.1, 0.25];
var P = (t2, s2 = {}) => {
  const a2 = h();
  _(T(() => {
    let r2, c2 = l("CLS", 0);
    const f2 = u(s2, v), d2 = (n2, e2, o2, a3, d3) => {
      c2 = l("CLS", 0, n2, e2, o2, a3, d3), f2.i = 0, r2 = i(t2, c2, L, s2.reportAllChanges);
    }, h2 = (t3 = false) => {
      f2.i > c2.value && (c2.value = f2.i, c2.entries = f2.o), r2(t3);
    }, g2 = (t3) => {
      h2(true), d2("soft-navigation", t3.navigationId, t3.interactionId, t3.name, t3.startTime);
    }, b2 = (t3) => {
      for (const n2 of t3) "soft-navigation" !== n2.entryType ? f2.h(n2) : g2(n2);
      h2();
    }, T2 = ["layout-shift"];
    p(s2) && T2.push("soft-navigation");
    const y2 = m(T2, b2);
    y2 && (r2 = i(t2, c2, L, s2.reportAllChanges), a2.onHidden(() => {
      b2(y2.takeRecords()), r2(true);
    }), e(() => {
      d2("back-forward-cache", c2.navigationId, c2.navigationInteractionId, c2.navigationURL, n()), o(r2);
    }), setTimeout(r2));
  }));
};
var w = 0;
var k = 1 / 0;
var I = 0;
var C = (t2) => {
  for (const n2 of t2) n2.interactionId && (k = Math.min(k, n2.interactionId), I = Math.max(I, n2.interactionId), w = I ? (I - k) / 7 + 1 : 0);
};
var F;
var B = () => F ? w : performance.interactionCount ?? 0;
var N = () => {
  "interactionCount" in performance || F || (F = m(["event"], C, { durationThreshold: 0 }));
};
var S = 0;
var q = class {
  u = [];
  v = /* @__PURE__ */ new Map();
  m;
  p;
  T() {
    S = B(), this.u.length = 0, this.v.clear();
  }
  M(t2) {
    const n2 = B() - S, e2 = Math.min(this.u.length - 1, Math.floor(n2 / 50));
    return !n2 || -1 !== e2 || "soft-navigation" !== t2 && "back-forward-cache" !== t2 ? this.u[e2] : { _: 8, id: -1, entries: [] };
  }
  h(t2) {
    if (this.m?.(t2), !t2.interactionId && "first-input" !== t2.entryType) return;
    const n2 = this.u.at(-1);
    let e2 = this.v.get(t2.interactionId);
    if (e2 || this.u.length < 10 || t2.duration > n2._) {
      if (e2 ? t2.duration > e2._ ? (e2.entries = [t2], e2._ = t2.duration) : t2.duration === e2._ && t2.startTime === e2.entries[0].startTime && e2.entries.push(t2) : (e2 = { id: t2.interactionId, entries: [t2], _: t2.duration }, this.v.set(e2.id, e2), this.u.push(e2)), this.u.sort((t3, n3) => n3._ - t3._), this.u.length > 10) {
        const t3 = this.u.splice(10);
        for (const n3 of t3) this.v.delete(n3.id);
      }
      this.p?.(e2);
    }
  }
};
var A = (t2) => {
  const n2 = "requestIdleCallback" in globalThis ? 1e3 : 0, e2 = globalThis.requestIdleCallback || setTimeout, i2 = globalThis.cancelIdleCallback || clearTimeout;
  if ("hidden" === document.visibilityState) t2();
  else {
    const o2 = T(t2);
    let s2 = -1;
    const a2 = () => {
      i2(s2), o2();
    };
    addEventListener("visibilitychange", a2, { once: true, capture: true }), s2 = e2(() => {
      removeEventListener("visibilitychange", a2, { capture: true }), o2();
    }, { timeout: n2 });
  }
};
var x = [200, 500];
var H = (t2, o2 = {}) => {
  if (!globalThis.PerformanceEventTiming || !("interactionId" in PerformanceEventTiming.prototype)) return;
  const s2 = h();
  E(() => {
    N();
    let a2, r2 = l("INP");
    const c2 = u(o2, q), f2 = (n2, e2, s3, f3, d3) => {
      c2.T(), r2 = l("INP", -1, n2, e2, s3, f3, d3), a2 = i(t2, r2, x, o2.reportAllChanges);
    }, d2 = () => {
      const t3 = c2.M(r2.navigationType);
      t3 && t3._ !== r2.value && (r2.value = t3._, r2.entries = t3.entries, a2());
    }, h2 = (t3) => {
      d2(), a2(true), f2("soft-navigation", t3.navigationId, t3.interactionId, t3.name, t3.startTime);
    }, g2 = (t3, n2 = false) => {
      A(() => {
        for (const n3 of t3) "soft-navigation" !== n3.entryType ? c2.h(n3) : h2(n3);
        d2(), n2 && a2(true);
      });
    }, v2 = ["event", "first-input"];
    p(o2) && v2.push("soft-navigation");
    const b2 = m(v2, g2, { ...o2, durationThreshold: o2.durationThreshold ?? 40 });
    a2 = i(t2, r2, x, o2.reportAllChanges), b2 && (s2.onHidden(() => {
      g2(b2.takeRecords(), true);
    }), e(() => {
      f2("back-forward-cache", r2.navigationId, r2.navigationInteractionId, r2.navigationURL, n());
    }));
  });
};
var O = class {
  m;
  l;
  h(t2) {
    this.m?.(t2);
  }
};
var U = [2500, 4e3];
var W = (t2, s2 = {}) => {
  let r2 = false;
  const c2 = p(s2);
  E(() => {
    let f2, d2 = h(), g2 = l("LCP");
    const v2 = u(s2, O), p2 = (n2, e2, o2, a2, c3) => {
      g2 = l("LCP", -1, n2, e2, o2, a2, c3), f2 = i(t2, g2, U, s2.reportAllChanges), r2 = false, "soft-navigation" === n2 && (d2 = h(true));
    }, T2 = (t3) => {
      v2.l && t3.navigationId && b(v2.l, t3), r2 || f2(true), p2("soft-navigation", t3.navigationId, t3.interactionId, t3.name, t3.startTime);
      const n2 = t3.getLargestInteractionContentfulPaint?.();
      n2 && y2([n2]);
    }, y2 = (t3) => {
      s2.reportAllChanges || c2 || (t3 = t3.slice(-1));
      for (const n2 of t3) {
        if (!n2) continue;
        if ("soft-navigation" === n2.entryType) {
          T2(n2);
          continue;
        }
        let t4 = 0, e2 = [], i2 = n2.startTime;
        if ("largest-contentful-paint" === n2.entryType) t4 = Math.max(n2.startTime - a(), 0), v2.h(n2), e2 = [n2];
        else if ("interaction-contentful-paint" === n2.entryType) {
          const o2 = n2;
          if (!g2.navigationId) continue;
          if ("interactionId" in o2 && o2.interactionId != g2.navigationInteractionId) continue;
          i2 = o2.largestContentfulPaint?.renderTime || 0, t4 = Math.max(i2 - n2.startTime, 0), o2.largestContentfulPaint && (v2.h(o2.largestContentfulPaint), e2 = [o2.largestContentfulPaint]);
        }
        i2 < d2.firstHiddenTime && (g2.value = t4, g2.entries = e2, f2());
      }
    }, E2 = ["largest-contentful-paint"];
    c2 && E2.push("interaction-contentful-paint", "soft-navigation");
    const M2 = m(E2, y2);
    if (M2) {
      f2 = i(t2, g2, U, s2.reportAllChanges);
      const a2 = ["keydown", "click", "visibilitychange"], d3 = (t3) => {
        if (t3.isTrusted && !r2) {
          const t4 = g2.id;
          A(() => {
            if (!r2) {
              if (!c2) {
                M2.disconnect();
                for (const t5 of a2) removeEventListener(t5, d3, { capture: true });
              }
              t4 === g2.id && (r2 = true, f2(true));
            }
          });
        }
      };
      for (const t3 of a2) addEventListener(t3, d3, { capture: true });
      e((e2) => {
        p2("back-forward-cache", g2.navigationId, g2.navigationInteractionId, g2.navigationURL, n()), f2 = i(t2, g2, U, s2.reportAllChanges), o(() => {
          g2.value = performance.now() - e2.timeStamp, r2 = true, f2(true);
        });
      });
    }
  });
};
var $ = [800, 1800];
var D = (t2) => {
  document.prerendering ? E(() => D(t2)) : "complete" !== document.readyState ? addEventListener("load", () => D(t2), true) : setTimeout(t2);
};
var R = (t2, o2 = {}) => {
  const r2 = p(o2);
  let c2 = l("TTFB"), f2 = i(t2, c2, $, o2.reportAllChanges);
  D(() => {
    const d2 = s();
    if (d2) {
      const s2 = d2.responseStart;
      if (c2.value = Math.max(s2 - a(), 0), c2.entries = [d2], f2(true), e(() => {
        c2 = l("TTFB", 0, "back-forward-cache", c2.navigationId, c2.navigationInteractionId, c2.navigationURL, n()), f2 = i(t2, c2, $, o2.reportAllChanges), f2(true);
      }), r2) {
        m(["soft-navigation"], (n2) => {
          n2.forEach((n3) => {
            n3.navigationId && (c2 = l("TTFB", 0, "soft-navigation", n3.navigationId, n3.interactionId, n3.name, n3.startTime), c2.entries = [n3], f2 = i(t2, c2, $, o2.reportAllChanges), f2(true));
          });
        }, o2);
      }
    }
  });
};
export {
  L as CLSThresholds,
  M as FCPThresholds,
  x as INPThresholds,
  U as LCPThresholds,
  $ as TTFBThresholds,
  P as onCLS,
  _ as onFCP,
  H as onINP,
  W as onLCP,
  R as onTTFB
};
//# sourceMappingURL=web-vitals.js.map
