/**
 * Preview map for Landmark lat/lng fields in EasyAdmin.
 * Syncs marker ↔ form inputs; click map or drag marker to update coordinates.
 */
(function () {
  var DEFAULT_CENTER = [53.902284, 27.561831]; // Minsk
  var DEFAULT_ZOOM = 12;
  var MARKER_ZOOM = 15;
  var LEAFLET_CDN = 'https://unpkg.com/leaflet@1.9.4/dist/';

  function findCoordInputs() {
    var lat =
      document.querySelector('input[name="Landmark[latitude]"]') ||
      document.querySelector('#Landmark_latitude') ||
      document.querySelector('input[id$="_latitude"]');
    var lng =
      document.querySelector('input[name="Landmark[longitude]"]') ||
      document.querySelector('#Landmark_longitude') ||
      document.querySelector('input[id$="_longitude"]');

    return { lat: lat, lng: lng };
  }

  function parseCoord(input) {
    if (!input) {
      return null;
    }
    var raw = String(input.value || '').trim().replace(',', '.');
    if (raw === '') {
      return null;
    }
    var value = Number(raw);
    return Number.isFinite(value) ? value : null;
  }

  function formatCoord(value) {
    return Number(value).toFixed(6);
  }

  function fixLeafletDefaultIcons() {
    if (!L || !L.Icon || !L.Icon.Default) {
      return;
    }
    // CDN builds look for marker images relative to the page URL; pin to unpkg.
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
      iconRetinaUrl: LEAFLET_CDN + 'images/marker-icon-2x.png',
      iconUrl: LEAFLET_CDN + 'images/marker-icon.png',
      shadowUrl: LEAFLET_CDN + 'images/marker-shadow.png',
    });
  }

  function insertMapContainer(afterEl) {
    var existing = document.getElementById('ea-landmark-map');
    if (existing) {
      return existing;
    }

    var wrap = document.createElement('div');
    wrap.className = 'form-group field-map';
    wrap.style.cssText =
      'max-width:none;width:min(1200px,calc(100vw - 300px));box-sizing:border-box;';
    wrap.innerHTML =
      '<label class="form-control-label">Карта расположения</label>' +
      '<div id="ea-landmark-map" style="height:560px;width:100%;border-radius:8px;border:1px solid #ddd;background:#f5f5f5;"></div>' +
      '<div class="form-text">Маркер показывает текущие координаты. Клик по карте или перетаскивание маркера обновляют широту и долготу.</div>';

    var row = afterEl.closest('.form-group') || afterEl.closest('.field-number') || afterEl.parentElement;
    if (row && row.parentElement) {
      row.parentElement.insertBefore(wrap, row.nextSibling);
    } else {
      afterEl.parentElement.appendChild(wrap);
    }

    // Stretch to the right edge of the admin content area when the form column is narrow.
    var content =
      document.querySelector('.content-wrapper') ||
      document.querySelector('.ea-content') ||
      document.querySelector('main');
    if (content) {
      var contentRight = content.getBoundingClientRect().right;
      var wrapLeft = wrap.getBoundingClientRect().left;
      var targetWidth = Math.min(1200, Math.max(wrap.getBoundingClientRect().width, contentRight - wrapLeft - 24));
      wrap.style.width = targetWidth + 'px';
    }

    return document.getElementById('ea-landmark-map');
  }

  function initMap() {
    if (typeof L === 'undefined') {
      return false;
    }

    var inputs = findCoordInputs();
    if (!inputs.lat || !inputs.lng) {
      return true;
    }

    var mapEl = insertMapContainer(inputs.lng);
    if (!mapEl || mapEl.dataset.mapReady === '1') {
      return true;
    }
    mapEl.dataset.mapReady = '1';

    fixLeafletDefaultIcons();

    var lat = parseCoord(inputs.lat);
    var lng = parseCoord(inputs.lng);
    var hasCoords = lat !== null && lng !== null;
    var center = hasCoords ? [lat, lng] : DEFAULT_CENTER;
    var zoom = hasCoords ? MARKER_ZOOM : DEFAULT_ZOOM;

    var map = L.map(mapEl).setView(center, zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    var marker = null;

    function setMarker(coords, pan) {
      if (!marker) {
        marker = L.marker(coords, { draggable: true }).addTo(map);
        marker.on('dragend', function () {
          var pos = marker.getLatLng();
          writeInputs(pos.lat, pos.lng);
        });
      } else {
        marker.setLatLng(coords);
      }
      if (pan) {
        map.setView(coords, Math.max(map.getZoom(), MARKER_ZOOM));
      }
    }

    function writeInputs(nextLat, nextLng) {
      inputs.lat.value = formatCoord(nextLat);
      inputs.lng.value = formatCoord(nextLng);
      inputs.lat.dispatchEvent(new Event('input', { bubbles: true }));
      inputs.lng.dispatchEvent(new Event('input', { bubbles: true }));
      inputs.lat.dispatchEvent(new Event('change', { bubbles: true }));
      inputs.lng.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function syncFromInputs(pan) {
      var nextLat = parseCoord(inputs.lat);
      var nextLng = parseCoord(inputs.lng);
      if (nextLat === null || nextLng === null) {
        if (marker) {
          map.removeLayer(marker);
          marker = null;
        }
        return;
      }
      setMarker([nextLat, nextLng], pan);
    }

    if (hasCoords) {
      setMarker(center, false);
    }

    map.on('click', function (e) {
      writeInputs(e.latlng.lat, e.latlng.lng);
      setMarker([e.latlng.lat, e.latlng.lng], false);
    });

    ['input', 'change', 'blur'].forEach(function (eventName) {
      inputs.lat.addEventListener(eventName, function () {
        syncFromInputs(true);
      });
      inputs.lng.addEventListener(eventName, function () {
        syncFromInputs(true);
      });
    });

    // Leaflet needs a reflow after EasyAdmin layout settles.
    setTimeout(function () {
      map.invalidateSize();
    }, 150);

    return true;
  }

  function boot(attempt) {
    if (initMap()) {
      return;
    }
    if (attempt >= 40) {
      return;
    }
    setTimeout(function () {
      boot(attempt + 1);
    }, 50);
  }

  function start() {
    boot(0);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
