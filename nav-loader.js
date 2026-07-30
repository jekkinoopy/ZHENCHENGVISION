// 自動處理全站分頁圖示 (SVG 版本)
(function () {
    // 1. 移除可能存在的舊圖示標籤 (避免衝突)
    const existingIcon = document.querySelector('link[rel="icon"]');
    if (existingIcon) existingIcon.remove();

    // 2. 建立新的 SVG 圖示標籤
    const link = document.createElement('link');
    link.rel = 'icon';
    link.type = 'image/svg+xml'; // 指定為 SVG 格式
    link.href = '/images/favicon.svg?v=' + Date.now(); // 加上時間戳記，絕對不被快取擋住

    document.head.appendChild(link);

    // 3. 業主專屬全站快捷鍵 (Ctrl + Shift + S) -> 快速開啟網站地圖 (Sitemap)
    window.addEventListener('keydown', function (e) {
        if (e.ctrlKey && e.shiftKey && (e.key === 'S' || e.key === 's')) {
            e.preventDefault();
            const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
            window.location.href = basePath + 'sitemap.html';
        }
    });
})();