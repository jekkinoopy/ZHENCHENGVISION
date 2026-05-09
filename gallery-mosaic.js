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

        if (sL && landItem) sL.innerHTML = tag(landItem);

        if (sP && portraitItem) sP.innerHTML = tag(portraitItem);

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

        const grid = scope.querySelector(".mosaic-grid");

        const s3 = grid?.querySelector(".slot-3");

        const s4 = grid?.querySelector(".slot-4");

        const s5 = grid?.querySelector(".slot-5");

        if (!grid || !s3 || !s4 || !s5) return;

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

    }



    function syncFourthRowSizing(scope) {

        const row = scope.querySelector(".fourth-row");

        if (!row) return;

        const gap = parseFloat(getComputedStyle(row).gap || "14") || 14;

        const w = row.clientWidth;

        if (!w || w < 2) {

            row.style.height = "";

            return;

        }

        row.style.height = `${((w - 2 * gap) * 6) / 17}px`;

    }



    function renderGallery(mount, folderName, records, templateId, options) {

        const defaultFullId = templateId || "galleryLayoutTpl";

        options = options || {};



        if (!mount) return;



        mount.classList.remove(

            "gallery-mount--mosaic",

            "gallery-mount--fallback",

            "gallery-mount--compact"

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



        function applyFullMosaic(sliceRecords) {

            const tplEl = document.getElementById(defaultFullId);

            if (!tplEl) return false;

            mount.classList.remove("gallery-mount--compact");

            mount.classList.add("gallery-mount--mosaic");

            mount.appendChild(tplEl.content.cloneNode(true));

            const slots = mount.querySelectorAll(".mosaic-grid .slot");

            const fourth = mount.querySelectorAll(".fourth-row .slot");

            const chosen = chooseLayoutPhotos(sliceRecords, slots.length, fourth.length);

            slots.forEach((slot, i) => {

                const item = chosen.main[i];

                if (!item) return;

                slot.classList.toggle("slot-photo-portrait", item.orientation === "portrait");

                slot.innerHTML =

                    `<img src="images/${folderName}/${item.fileName}" alt="${escapeAlt(labelFor(item))}">`;

            });

            fourth.forEach((slot, i) => {

                const item = chosen.fourth[i];

                if (!item) return;

                slot.classList.toggle("slot-photo-portrait", item.orientation === "portrait");

                slot.innerHTML =

                    `<img src="images/${folderName}/${item.fileName}" alt="${escapeAlt(labelFor(item))}">`;

            });

            runLayoutSync(mount);

            return true;

        }



        if (unique.length >= MOSAIC_UNIQUE_MIN) {

            const sliceRecords = unique.slice(0, MOSAIC_UNIQUE_MIN);

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

        syncSecondRowSizing,

        syncFourthRowSizing,

        renderGallery,

    };

})(window);


