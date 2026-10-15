(() => {
    const tileLayers = new WeakMap();

    function addTiles(map) {
        if (!navigator.onLine || tileLayers.has(map)) {
            return;
        }

        const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        });

        tileLayers.set(map, tileLayer);
        tileLayer.on('tileerror', () => removeTiles(map));
        tileLayer.addTo(map);
    }

    function removeTiles(map) {
        const tileLayer = tileLayers.get(map);

        if (tileLayer && map.hasLayer(tileLayer)) {
            map.removeLayer(tileLayer);
        }

        tileLayers.delete(map);
    }

    window.addOfflineAwareMapTiles = (map) => {
        const addMapTiles = () => addTiles(map);
        const removeMapTiles = () => removeTiles(map);

        addMapTiles();
        window.addEventListener('online', addMapTiles);
        window.addEventListener('offline', removeMapTiles);
        map.once('unload', () => {
            removeMapTiles();
            window.removeEventListener('online', addMapTiles);
            window.removeEventListener('offline', removeMapTiles);
        });
    };
})();
