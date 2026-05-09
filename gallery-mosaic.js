/**
 * 共用：圖庫檔名解析、方向偵測、依張數切換版型與馬賽克排版
 */
(function (global) {
    const MOSAIC_UNIQUE_MIN = 10;

    function parseBirdFile(fileName) {

        const cleanName = fileName.replace(/\.[^/.]+$/, "");

        const m = cleanName.match(/^(.*?)[_\-\s]?(\d{1,4})$/);

        return {

            species: m ? (m[1] || "Uncategorized").trim() : (cleanName || "Uncategorized"),

            photoTitle: m ? `Photo ${m[2]}` : "Untitled",

            fileName,

        };

    }



    async function enrichOrientation(records, folderName) {

        return Promise.all(

            records.map(

                (record) =>

                    new Promise((resolve) => {

                        const img = new Image();

                        img.onload = () =>

                            resolve({

                                ...record,

                                orientation: img.naturalHeight > img.naturalWidth ? "portrait" : "landscape",

                            });

                        img.onerror = () => resolve({ ...record, orientation: "landscape" });

                        img.src = `images/${folderName}/${record.fileName}`;

                    })

            )

        );

    }



    function dedupeByFileName(records) {

        const seen = new Set();

        const out = [];

        for (const r of records) {

            const k = r && r.fileName ? String(r.fileName) : "";

            if (!k || seen.has(k)) continue;

            seen.add(k);

            out.push(r);

        }

        return out;

    }



    function pickPhotosByPattern(records, patternChars) {

        const landscape = records.filter((r) => r.orientation !== "portrait");

        const portrait = records.filter((r) => r.orientation === "portrait");

        const used = new Set();

        function keyOf(r) {

            return r && r.fileName ? r.fileName : "";

        }

        function take(pool) {

            for (let i = 0; i < pool.length; i++) {

                const r = pool[i];

                const k = keyOf(r);

                if (k && !used.has(k)) {

                    used.add(k);

                    return r;

                }

            }

            return null;

        }

        function L() {

            return take(landscape) || take(portrait) || take(records);

        }

        function P() {

            return take(portrait) || take(landscape) || take(records);

        }

        return patternChars

            .split("")

            .map((ch) => (ch === "L" || ch === "l" ? L() : P()))

            .filter(Boolean);

    }



    /** 馬賽克滿版用：不重複 */

    function chooseLayoutPhotos(records, mainCount, fourthCount) {

        const landscape = records.filter((r) => r.orientation !== "portrait");

        const portrait = records.filter((r) => r.orientation === "portrait");

        const used = new Set();

        function keyOf(r) {

            return r && r.fileName ? r.fileName : "";

        }

        function takeUniqueFrom(pool) {

            for (let i = 0; i < pool.length; i++) {

                const r = pool[i];

                const k = keyOf(r);

                if (k && !used.has(k)) {

                    used.add(k);

                    return r;

                }

            }

            return null;

        }

        function pickLandscapeFirst() {

            return takeUniqueFrom(landscape) || takeUniqueFrom(portrait) || takeUniqueFrom(records);

        }

        function pickPortraitFirst() {

            return takeUniqueFrom(portrait) || takeUniqueFrom(landscape) || takeUniqueFrom(records);

        }

        /* 滿版馬賽克 7 格：第 3 格（左側高欄）優先直式，其餘格偏好橫式，較符合構圖也減少過度裁切 */
        const main = Array.from({ length: mainCount }, (_, i) =>
            mainCount === 7 && i === 2 ? pickPortraitFirst() : pickLandscapeFirst()
        );

        return {

            main,

            fourth: Array.from({ length: fourthCount }, (_, i) => (i === 1 ? pickLandscapeFirst() : pickPortraitFirst())),

        };

    }



    /** 順序取用前 N 張（馬賽克列順／檔順） */

    function takeSequential(records, n) {

        return records.slice(0, Math.min(n, records.length));

    }



    /** 四張／上ㄧ大三：先偏橫圖為主位，餘序取 */

    function pickOnePlusThree(records) {

        const avail = [...records];

        const used = new Set();

        const hero = pickPhotosByPattern(avail, "L")[0] || avail[0];

        if (!hero) return [];

        used.add(hero.fileName);

        const rest = avail.filter((r) => !used.has(r.fileName));

        return [hero, ...takeSequential(rest, 3)].filter(Boolean);

    }



    function normalizeFourLayout(v) {

        const s = String(v || "")

            .trim()

            .toLowerCase()

            .replace(/-/g, "");

        if (s === "oneplusthree" || s === "banner" || s === "1plus3" || s === "onetthree") {

            return "onePlusThree";

        }

        return "twoByTwo";

    }



    function escapeAlt(s) {

        return String(s).replace(/&/g, "&amp;").replace(/"/g, "&quot;");

    }



    function labelFor(item) {

        return `${item.species} - ${item.photoTitle}`;

    }



    /** 橫圖仍以 cover；偵測為直式者加 class → contain，避免強塞橫向裁切盒 */
    function fillSlots(mount, folderName, items) {

        const slots = mount.querySelectorAll(".slot");

        items.forEach((item, i) => {

            if (!slots[i] || !item) return;

            slots[i].classList.toggle("slot-photo-portrait", item.orientation === "portrait");

            slots[i].innerHTML =

                `<img src="images/${folderName}/${item.fileName}" alt="${escapeAlt(labelFor(item))}">`;

        });

    }



    function splitLandscapePortrait(items) {

        const L = items.filter((it) => it.orientation !== "portrait");

        const P = items.filter((it) => it.orientation === "portrait");

        return { L, P };

    }



    function fillMix1h1v(mount, folderName, landItem, portraitItem) {

        const root = mount.querySelector(".layout-mix--1h1v");

        if (!root) return;

        const sL = root.querySelector(".layout-mix-landscape");

        const sP = root.querySelector(".layout-mix-portrait");

        const tag = (item) => `<img src="images/${folderName}/${item.fileName}" alt="${escapeAlt(labelFor(item))}">`;

        if (sP && portraitItem) sP.innerHTML = tag(portraitItem);

        if (sL && landItem) sL.innerHTML = tag(landItem);

    }



    function fillMix2h1v(mount, folderName, portraitItem, land1, land2) {

        const root = mount.querySelector(".layout-mix--2h1v");

        if (!root) return;

        const sP = root.querySelector(".layout-mix-portrait-col");

        const h1 = root.querySelector(".layout-mix-h1");

        const h2 = root.querySelector(".layout-mix-h2");

        const tag = (item) => `<img src="images/${folderName}/${item.fileName}" alt="${escapeAlt(labelFor(item))}">`;

        if (sP && portraitItem) sP.innerHTML = tag(portraitItem);

        if (h1 && land1) h1.innerHTML = tag(land1);

        if (h2 && land2) h2.innerHTML = tag(land2);

    }



    /** 四格 2×2：兩橫列、每列兩格 → 同一列上下緣對齊（適用翠鳥 4 張等） */

    function fillFourTwoByTwo(mount, folderName, items) {

        const rows = mount.querySelectorAll(".layout-four-row-pair");

        items.forEach((item, i) => {

            const row = rows[Math.floor(i / 2)];

            const cell = row?.querySelectorAll(".layout-four-cell")[i % 2];

            const slot = cell?.querySelector(".slot");

            if (!slot || !item) return;

            slot.classList.toggle("slot-photo-portrait", item.orientation === "portrait");

            const src = `images/${folderName}/${item.fileName}`;

            const alt = escapeAlt(labelFor(item));

            const imgHtml = `<img src="${src}" alt="${alt}">`;

            slot.innerHTML =

                item.orientation === "portrait" ? imgHtml : `<div class="layout-gallery-cover-frame">${imgHtml}</div>`;

        });

    }



    function cloneTpl(id) {

        const tpl = document.getElementById(id);

        return tpl ? tpl.content.cloneNode(true) : null;

    }



    function fillFlexGrid(mount, folderName, items) {

        const root = mount.querySelector(".layout-flex-grid");

        if (!root) return;

        items.forEach((item) => {

            const cell = document.createElement("div");

            cell.className = "slot";

            cell.classList.toggle("slot-photo-portrait", item.orientation === "portrait");

            cell.innerHTML =

                `<img src="images/${folderName}/${item.fileName}" alt="${escapeAlt(labelFor(item))}">`;

            root.appendChild(cell);

        });

    }



    function runLayoutSync(mount) {

        syncSecondRowSizing(mount);

        syncFourthRowSizing(mount);

        requestAnimationFrame(() => {

            syncSecondRowSizing(mount);

            syncFourthRowSizing(mount);

        });

    }



    function syncSecondRowSizing(scope) {

        if (!scope || !scope.querySelectorAll) return;

        scope.querySelectorAll(".mosaic-grid").forEach((grid) => {

            const s3 = grid.querySelector(".slot-3");

            const s4 = grid.querySelector(".slot-4");

            const s5 = grid.querySelector(".slot-5");

            if (!s3 || !s4 || !s5) return;

            const gap = parseFloat(getComputedStyle(grid).gap || "14") || 14;

            const w = s4.clientWidth;

            if (!w || w < 2) {

                s3.style.height = "";

                s4.style.height = "";

                s5.style.height = "";

                return;

            }

            const h = w * (2 / 3);

            s4.style.height = `${h}px`;

            s5.style.height = `${h}px`;

            s3.style.height = `${h * 2 + gap}px`;

        });

    }



    function syncFourthRowSizing(scope) {

        if (!scope || !scope.querySelectorAll) return;

        scope.querySelectorAll(".fourth-row").forEach((row) => {

            row.style.height = "";

        });

    }



    /* 萬象多段版面：
     * - 每种 segment id 同頁最多一次
     * - composeSilhouette 同頁也不得重複：避免「看起來像同一種扁平雙橫／等分格」連續出現
     * - four-two-two（2×2 等分）不參與多段 composer：單獨 4 張仍走既有 branch，避免整頁像截圖那樣兩排複製感
     */
    const SEGMENT_CATALOG = [

        {
            id: "mix2h1v",
            capacity: 3,
            templateId: "galleryLayoutTplMix2h1v",
            composeSilhouette: "rail-2h-1v",
        },

        {
            id: "mosaic-second-row",
            capacity: 3,
            templateId: "galleryLayoutTpl3SecondRow",
            composeSilhouette: "mosaic-row2-three",
        },

        {
            id: "four-two-two",
            capacity: 4,
            templateId: "galleryLayoutTpl4TwoByTwo",
            composeSilhouette: "grid-2x2-equal",
            composeExcluded: true,
        },

        {
            id: "four-one-three",
            capacity: 4,
            templateId: "galleryLayoutTpl4OnePlusThree",
            composeSilhouette: "hero-1-plus-3",
        },

        {
            id: "pair",
            capacity: 2,
            templateId: "galleryLayoutTpl2",
            composeSilhouette: "strip-dual-equal",
        },

        {
            id: "mix1h1v",
            capacity: 2,
            templateId: "galleryLayoutTplMix1h1v",
            composeSilhouette: "split-1h-1v",
        },

        { id: "single", capacity: 1, templateId: "galleryLayoutTpl1", composeSilhouette: "solo-cell" },

    ];

    const SEGMENT_BY_ID = Object.fromEntries(SEGMENT_CATALOG.map((s) => [s.id, s]));

    function composerSegmentIdsInOrder() {

        return SEGMENT_CATALOG.filter((s) => !s.composeExcluded).map((s) => s.id);

    }

    function rotatedSegmentOrder(seedStr) {

        const baseOrder = composerSegmentIdsInOrder();

        if (!baseOrder.length) return [];

        const seed = String(seedStr || "")

            .split("")

            .reduce((a, c) => a + c.charCodeAt(0), 0);

        const rot = seed % baseOrder.length;

        return [...baseOrder.slice(rot), ...baseOrder.slice(0, rot)];

    }

    function dfsComposeUnique(rem, usedIds, usedSilhouettes, tryOrder) {

        if (rem === 0) return [];

        for (let oi = 0; oi < tryOrder.length; oi++) {

            const seg = SEGMENT_BY_ID[tryOrder[oi]];

            if (!seg || usedIds.has(seg.id) || seg.capacity > rem) continue;

            const sil = seg.composeSilhouette;

            if (sil && usedSilhouettes.has(sil)) continue;

            const nextUsed = new Set(usedIds);

            nextUsed.add(seg.id);

            const nextSil = new Set(usedSilhouettes);

            if (sil) nextSil.add(sil);

            const sub = dfsComposeUnique(rem - seg.capacity, nextUsed, nextSil, tryOrder);

            if (sub !== null) return [seg, ...sub];

        }

        return null;

    }

    function solveUniqueSegmentPlan(total, seedStr) {

        if (total < 1) return null;

        return dfsComposeUnique(total, new Set(), new Set(), rotatedSegmentOrder(seedStr));

    }

    function pullFromPool(pool, taken) {

        const names = new Set(taken.map((t) => (t && t.fileName ? t.fileName : "")));

        const next = pool.filter((r) => !names.has(r.fileName));

        pool.length = 0;

        pool.push(...next);

    }

    function takeSequentialFromPool(pool, n) {

        const k = Math.min(n, pool.length);

        const out = pool.slice(0, k);

        pool.splice(0, k);

        return out;

    }

    function pullPatternFromPool(pool, pattern) {

        const picked = pickPhotosByPattern([...pool], pattern);

        pullFromPool(pool, picked);

        return picked;

    }

    function pullMix2h1vFromPool(pool) {

        const snapshot = pool.slice();

        const landscape = snapshot.filter((r) => r.orientation !== "portrait");

        const portrait = snapshot.filter((r) => r.orientation === "portrait");

        const takenNames = new Set();

        function pullNextFromPreferred(preferredList) {

            for (let i = 0; i < preferredList.length; i++) {

                const want = preferredList[i];

                const k = want && want.fileName ? want.fileName : "";

                if (!k || takenNames.has(k)) continue;

                const idx = pool.findIndex((r) => r.fileName === k);

                if (idx < 0) continue;

                const [one] = pool.splice(idx, 1);

                takenNames.add(k);

                return one;

            }

            return null;

        }

        const p = pullNextFromPreferred(portrait) || pullNextFromPreferred(landscape) || pullNextFromPreferred(snapshot);

        const l1 = pullNextFromPreferred(landscape) || pullNextFromPreferred(snapshot);

        const l2 = pullNextFromPreferred(landscape) || pullNextFromPreferred(snapshot);

        return [p, l1, l2].filter(Boolean);

    }

    function pullMix1h1vFromPool(pool) {

        const landscape = pool.filter((r) => r.orientation !== "portrait");

        const portrait = pool.filter((r) => r.orientation === "portrait");

        let L = landscape[0];

        let P = portrait[0];

        if (!L && pool[0]) L = pool[0];

        if (!P || (L && P.fileName === L.fileName))

            P = portrait.find((r) => !L || r.fileName !== L.fileName) || pool.find((r) => !L || r.fileName !== L.fileName);

        let picked = [];

        if (L && P && L.fileName !== P.fileName) picked = [L, P];

        else picked = pool.slice(0, Math.min(2, pool.length));

        pullFromPool(pool, picked);

        return picked;

    }

    function pullOnePlusThreeFromPool(pool) {

        const chunk = pool.slice(0, Math.min(4, pool.length));

        const picked = pickOnePlusThree(chunk);

        pullFromPool(pool, picked);

        return picked.filter(Boolean);

    }

    function consumeSegmentFromPool(seg, pool, fourModeNormalized) {

        switch (seg.id) {

            case "single":

                return takeSequentialFromPool(pool, 1);

            case "pair":

                return takeSequentialFromPool(pool, seg.capacity);

            case "mix2h1v": {

                let out = pullMix2h1vFromPool(pool);

                while (out.length < seg.capacity && pool.length)

                    out.push(...takeSequentialFromPool(pool, seg.capacity - out.length));

                return out.slice(0, seg.capacity);

            }

            case "mix1h1v": {

                let out = pullMix1h1vFromPool(pool);

                while (out.length < seg.capacity && pool.length)

                    out.push(...takeSequentialFromPool(pool, seg.capacity - out.length));

                return out.slice(0, seg.capacity);

            }

            case "mosaic-second-row":

                return pullPatternFromPool(pool, "LPP").slice(0, seg.capacity);

            case "four-two-two":

                return takeSequentialFromPool(pool, seg.capacity);

            case "four-one-three": {

                if (fourModeNormalized === "onePlusThree") return pullOnePlusThreeFromPool(pool);

                return takeSequentialFromPool(pool, seg.capacity);

            }

            default:

                return takeSequentialFromPool(pool, seg.capacity || 1);

        }

    }

    function fillComposedSegment(seg, wrapper, folderName, items, fourModeNormalized) {

        switch (seg.id) {

            case "single":

            case "pair":

            case "mosaic-second-row":

                fillSlots(wrapper, folderName, items);

                break;

            case "mix2h1v": {

                fillMix2h1v(wrapper, folderName, items[0], items[1], items[2]);

                break;

            }

            case "mix1h1v": {

                const L = items.find((i) => i.orientation !== "portrait") || items[0];

                const P = items.find((i) => i.orientation === "portrait") || items[1];

                fillMix1h1v(wrapper, folderName, L, P);

                break;

            }

            case "four-two-two":

                fillFourTwoByTwo(wrapper, folderName, items);

                break;

            case "four-one-three":

                fillSlots(wrapper, folderName, items);

                break;

            case "flex-blob":

                fillFlexGrid(wrapper, folderName, items);

                break;

            default:

                fillSlots(wrapper, folderName, items);

        }

    }

    function renderComposedLayout(mount, folderName, records, plan, fourModeNormalized) {

        mount.classList.add("gallery-mount--composed");

        mount.classList.add("gallery-mount--compact");

        const pool = records.slice();

        plan.forEach((seg) => {

            const wrapper = document.createElement("section");

            wrapper.className = "gallery-layout-segment";

            wrapper.dataset.segmentId = seg.id;

            const frag = cloneTpl(seg.templateId);

            if (!frag) return;

            while (frag.firstChild) wrapper.appendChild(frag.firstChild);

            mount.appendChild(wrapper);

            const items = consumeSegmentFromPool(seg, pool, fourModeNormalized);

            fillComposedSegment(seg, wrapper, folderName, items, fourModeNormalized);

            runLayoutSync(wrapper);

        });

    }



    /** 文化影像（gallery-culture）8 張：2v → (2v+1h) → (1v+2v) */
    function pullPreferPortraitFromPool(pool) {

        const idx = pool.findIndex((r) => r && r.orientation === "portrait");

        if (idx >= 0) return pool.splice(idx, 1)[0];

        return pool.shift();

    }



    function pullPreferLandscapeFromPool(pool) {

        const idx = pool.findIndex((r) => r && r.orientation !== "portrait");

        if (idx >= 0) return pool.splice(idx, 1)[0];

        return pool.shift();

    }



    function fillCultureSlots(wrapper, folderName, items) {

        const slots = wrapper.querySelectorAll(".culture-slot");

        items.forEach((item, si) => {

            const slot = slots[si];

            if (!slot || !item) return;

            slot.classList.toggle("slot-photo-portrait", item.orientation === "portrait");

            slot.innerHTML =

                `<img src="images/${folderName}/${item.fileName}" alt="${escapeAlt(labelFor(item))}">`;

        });

    }



    function renderCultureEightCanonical(mount, folderName, uniqueRecords) {

        mount.classList.add("gallery-mount--composed");

        mount.classList.add("gallery-mount--compact");

        mount.classList.add("layout-culture-eight");

        const pool = uniqueRecords.slice();

        const blueprint = [

            {

                templateId: "galleryCultureTpl2v",

                take: () => [pullPreferPortraitFromPool(pool), pullPreferPortraitFromPool(pool)],

            },

            {

                templateId: "galleryCultureTpl2v1h",

                take: () => [

                    pullPreferPortraitFromPool(pool),

                    pullPreferPortraitFromPool(pool),

                    pullPreferLandscapeFromPool(pool),

                ],

            },

            {

                templateId: "galleryCultureTpl1v2v",

                take: () => [

                    pullPreferPortraitFromPool(pool),

                    pullPreferPortraitFromPool(pool),

                    pullPreferPortraitFromPool(pool),

                ],

            },

        ];

        const labels = ["2v", "2v1h", "1v2v"];

        blueprint.forEach((block, bi) => {

            const wrapper = document.createElement("section");

            wrapper.className = "gallery-layout-segment gallery-layout-segment--culture culture-eight-seg";

            wrapper.dataset.segmentId = labels[bi];

            const frag = cloneTpl(block.templateId);

            if (!frag) return;

            while (frag.firstChild) wrapper.appendChild(frag.firstChild);

            mount.appendChild(wrapper);

            const items = block.take();

            fillCultureSlots(wrapper, folderName, items);

        });

    }



    function renderGallery(mount, folderName, records, templateId, options) {

        const defaultFullId = templateId || "galleryLayoutTpl";

        options = options || {};



        if (!mount) return;



        mount.classList.remove(

            "gallery-mount--mosaic",

            "gallery-mount--fallback",

            "gallery-mount--compact",

            "gallery-mount--composed"

        );

        mount.innerHTML = "";



        const unique = dedupeByFileName(records);



        if (unique.length === 0) {

            mount.classList.add("gallery-mount--fallback");

            const p = document.createElement("p");

            p.className = "gallery-layout-note empty-msg";

            p.textContent = "此區尚無影像。";

            mount.appendChild(p);

            return;

        }



        const fourMode = normalizeFourLayout(options.fourLayout || mount.dataset.fourLayout);

        /* 文化影像：8 張固定為 2v + (2v+1h) + (1v+2v)，不經過泛用 composer／馬賽克 */
        if (folderName === "gallery-culture" && unique.length === 8) {

            renderCultureEightCanonical(mount, folderName, unique);

            runLayoutSync(mount);

            return;

        }

        const mosaicThreshold =
            typeof options.mosaicMin === "number" ? options.mosaicMin : MOSAIC_UNIQUE_MIN;

        const mosaicSliceCap =
            typeof options.mosaicSliceMax === "number" ? options.mosaicSliceMax : MOSAIC_UNIQUE_MIN;

        const composeUnique = Boolean(options.composeUniqueLayouts);

        const composeMinCount =
            typeof options.composeMinCount === "number" ? options.composeMinCount : 5;

        const composeMaxCount =
            typeof options.composeMaxCount === "number" ? options.composeMaxCount : 40;

        if (

            composeUnique &&

            unique.length >= composeMinCount &&

            unique.length <= composeMaxCount

        ) {

            const plan = solveUniqueSegmentPlan(unique.length, folderName);

            if (plan && plan.length) {

                renderComposedLayout(mount, folderName, unique, plan, fourMode);

                runLayoutSync(mount);

                return;

            }

        }



        function applyFullMosaic(sliceRecords) {

            const tplEl = document.getElementById(defaultFullId);

            if (!tplEl) return false;

            mount.classList.remove("gallery-mount--compact");

            mount.classList.add("gallery-mount--mosaic");

            mount.appendChild(tplEl.content.cloneNode(true));

            const slots = mount.querySelectorAll(".mosaic-grid .slot");

            const mainSlotCount = slots.length;

            const fourthSlotsN = Math.max(0, Math.min(3, sliceRecords.length - mainSlotCount));

            const fourthEl = mount.querySelector(".fourth-row");

            let fourthLive = [];

            if (fourthEl) {

                if (fourthSlotsN === 0) {

                    fourthEl.remove();

                } else {

                    fourthEl.classList.add(`fourth-row--slots-${fourthSlotsN}`);

                    const left = fourthEl.querySelector(".fourth-left");

                    const mid = fourthEl.querySelector(".fourth-mid");

                    const right = fourthEl.querySelector(".fourth-right");

                    if (fourthSlotsN === 1 && left && mid && right) {

                        left.remove();

                        right.remove();

                    } else if (fourthSlotsN === 2 && right) {

                        right.remove();

                    }

                    fourthLive = [...fourthEl.querySelectorAll(".slot")];

                }

            }

            const chosen = chooseLayoutPhotos(sliceRecords, mainSlotCount, fourthSlotsN);

            slots.forEach((slot, i) => {

                const item = chosen.main[i];

                if (!item) return;

                slot.classList.toggle("slot-photo-portrait", item.orientation === "portrait");

                slot.innerHTML =

                    `<img src="images/${folderName}/${item.fileName}" alt="${escapeAlt(labelFor(item))}">`;

            });

            fourthLive.forEach((slot, i) => {

                const item = chosen.fourth[i];

                if (!item) return;

                slot.classList.toggle("slot-photo-portrait", item.orientation === "portrait");

                slot.innerHTML =

                    `<img src="images/${folderName}/${item.fileName}" alt="${escapeAlt(labelFor(item))}">`;

            });

            runLayoutSync(mount);

            return true;

        }



        if (unique.length >= mosaicThreshold) {

            const sliceRecords = unique.slice(0, Math.min(unique.length, mosaicSliceCap));

            applyFullMosaic(sliceRecords);

            return;

        }



        mount.classList.add("gallery-mount--compact");



        if (unique.length === 1) {

            const frag = cloneTpl("galleryLayoutTpl1");

            if (!frag) return;

            mount.appendChild(frag);

            fillSlots(mount, folderName, unique);

            return;

        }



        if (unique.length === 2) {

            const { L, P } = splitLandscapePortrait(unique);

            if (L.length === 1 && P.length === 1) {

                const frag = cloneTpl("galleryLayoutTplMix1h1v");

                if (!frag) return;

                mount.appendChild(frag);

                fillMix1h1v(mount, folderName, L[0], P[0]);

                runLayoutSync(mount);

                return;

            }

            const frag = cloneTpl("galleryLayoutTpl2");

            if (!frag) return;

            mount.appendChild(frag);

            fillSlots(mount, folderName, takeSequential(unique, 2));

            return;

        }



        if (unique.length === 3) {

            const { L, P } = splitLandscapePortrait(unique);

            if (L.length === 2 && P.length === 1) {

                const frag = cloneTpl("galleryLayoutTplMix2h1v");

                if (!frag) return;

                mount.appendChild(frag);

                fillMix2h1v(mount, folderName, P[0], L[0], L[1]);

                return;

            }

            const frag = cloneTpl("galleryLayoutTpl3SecondRow");

            if (!frag) return;

            mount.appendChild(frag);

            const picked = pickPhotosByPattern(unique, "LPP");

            fillSlots(mount, folderName, picked);

            runLayoutSync(mount);

            return;

        }



        if (unique.length === 4) {

            if (fourMode === "onePlusThree") {

                const frag = cloneTpl("galleryLayoutTpl4OnePlusThree");

                if (!frag) return;

                mount.appendChild(frag);

                fillSlots(mount, folderName, pickOnePlusThree(unique));

            } else {

                const frag = cloneTpl("galleryLayoutTpl4TwoByTwo");

                if (!frag) return;

                mount.appendChild(frag);

                fillFourTwoByTwo(mount, folderName, takeSequential(unique, 4));

                runLayoutSync(mount);

            }

            return;

        }



        /** 5 ~ 9 張：自動格狀不重複 */

        const frag = cloneTpl("galleryLayoutTplFlex");

        if (!frag) return;

        mount.appendChild(frag);

        fillFlexGrid(mount, folderName, unique);

    }



    global.GalleryMosaic = {

        parseBirdFile,

        enrichOrientation,

        chooseLayoutPhotos,

        dedupeByFileName,

        MOSAIC_UNIQUE_MIN,

        SEGMENT_CATALOG,

        solveUniqueSegmentPlan,

        syncSecondRowSizing,

        syncFourthRowSizing,

        renderGallery,

    };

})(window);


