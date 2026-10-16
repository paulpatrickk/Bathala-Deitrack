/* ─── Address Picker: cascading Region > Province > City/Municipality > Barangay,
   plus a draggable Leaflet map pin. No free-typing allowed for admin divisions —
   every value comes from the official PSGC list. ─────────────────────────────── */

(function () {
  const DATA_BASE = '/assets/data';

  const regionSel   = document.getElementById('addr_region');
  const provinceSel = document.getElementById('addr_province');
  const citySel      = document.getElementById('addr_city');
  const barangaySel  = document.getElementById('addr_barangay');
  const latInput      = document.getElementById('addr_lat');
  const lngInput       = document.getElementById('addr_lng');
  const mapEl          = document.getElementById('addr_map');

  if (!regionSel || !provinceSel || !citySel || !barangaySel) return; // picker not on this page

  // Values the page rendered from the customer's saved record (may be empty on first use).
  const initial = {
    region:   regionSel.dataset.initial   || '',
    province: provinceSel.dataset.initial || '',
    city:     citySel.dataset.initial     || '',
    barangay: barangaySel.dataset.initial || '',
    lat: latInput ? parseFloat(latInput.value) : null,
    lng: lngInput ? parseFloat(lngInput.value) : null,
  };

  let allProvinces = [];
  let allCities = [];

  function resetSelect(sel, placeholder) {
    sel.innerHTML = `<option value="">${placeholder}</option>`;
    sel.disabled = true;
  }

  function fillSelect(sel, items, matchName) {
    sel.innerHTML = '<option value="">Select...</option>';
    let matchedCode = '';
    items.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item.code;
      opt.textContent = item.name;
      if (matchName && item.name.toLowerCase() === String(matchName).toLowerCase()) {
        opt.selected = true;
        matchedCode = item.code;
      }
      sel.appendChild(opt);
    });
    sel.disabled = false;
    return matchedCode;
  }

  async function loadJSON(path) {
    const res = await fetch(path);
    if (!res.ok) throw new Error('Failed to load ' + path);
    return res.json();
  }

  async function init() {
    const [regions, provinces, cities] = await Promise.all([
      loadJSON(`${DATA_BASE}/regions.json`),
      loadJSON(`${DATA_BASE}/provinces.json`),
      loadJSON(`${DATA_BASE}/cities.json`),
    ]);
    allProvinces = provinces;
    allCities = cities;

    const regionCode = fillSelect(regionSel, regions, initial.region);
    resetSelect(provinceSel, 'Select region first');
    resetSelect(citySel, 'Select province first');
    resetSelect(barangaySel, 'Select city first');

    if (regionCode) {
      await onRegionChange(regionCode, initial.province, () => onProvinceChange(true, initial.city, () => onCityChange(true, initial.barangay)));
    }
  }

  async function onRegionChange(regionCode, matchProvince, then) {
    if (!regionCode) {
      resetSelect(provinceSel, 'Select region first');
      resetSelect(citySel, 'Select province first');
      resetSelect(barangaySel, 'Select city first');
      return;
    }
    const list = allProvinces.filter(p => p.region_code === regionCode);
    fillSelect(provinceSel, list, matchProvince);
    resetSelect(citySel, 'Select province first');
    resetSelect(barangaySel, 'Select city first');
    if (provinceSel.value && then) await then();
  }

  async function onProvinceChange(skipReset, matchCity, then) {
    const provinceCode = provinceSel.value;
    if (!provinceCode) {
      resetSelect(citySel, 'Select province first');
      resetSelect(barangaySel, 'Select city first');
      return;
    }
    const list = allCities.filter(c => c.province_code === provinceCode);
    fillSelect(citySel, list, matchCity);
    resetSelect(barangaySel, 'Select city first');
    if (citySel.value && then) await then();
  }

  async function onCityChange(skipReset, matchBarangay) {
    const cityCode = citySel.value;
    if (!cityCode) {
      resetSelect(barangaySel, 'Select city first');
      return;
    }
    try {
      const names = await loadJSON(`${DATA_BASE}/barangays/${cityCode}.json`);
      const items = names.map(n => ({ code: n, name: n }));
      fillSelect(barangaySel, items, matchBarangay);
    } catch (e) {
      resetSelect(barangaySel, 'No barangay data');
    }
  }

  regionSel.addEventListener('change', () => onRegionChange(regionSel.value, ''));
  provinceSel.addEventListener('change', () => onProvinceChange(false, ''));
  citySel.addEventListener('change', () => onCityChange(false, ''));

  init();

  /* ─── Map pin (Leaflet + OpenStreetMap, no API key needed) ─────────────── */
  if (mapEl && window.L) {
    const defaultCenter = [14.5995, 120.9842]; // Metro Manila fallback
    const startLat = (initial.lat && !isNaN(initial.lat)) ? initial.lat : defaultCenter[0];
    const startLng = (initial.lng && !isNaN(initial.lng)) ? initial.lng : defaultCenter[1];

    const map = L.map(mapEl, { zoomControl: true }).setView([startLat, startLng], initial.lat ? 16 : 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

    function setCoords(lat, lng) {
      if (latInput) latInput.value = lat.toFixed(6);
      if (lngInput) lngInput.value = lng.toFixed(6);
    }
    if (initial.lat) setCoords(startLat, startLng);

    marker.on('dragend', () => {
      const pos = marker.getLatLng();
      setCoords(pos.lat, pos.lng);
    });

    map.on('click', (e) => {
      marker.setLatLng(e.latlng);
      setCoords(e.latlng.lat, e.latlng.lng);
    });

    const locateBtn = document.getElementById('addr_locate_btn');
    if (locateBtn) {
      locateBtn.addEventListener('click', () => {
        if (!navigator.geolocation) return;
        locateBtn.disabled = true;
        locateBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Locating...';
        navigator.geolocation.getCurrentPosition((pos) => {
          const { latitude, longitude } = pos.coords;
          map.setView([latitude, longitude], 17);
          marker.setLatLng([latitude, longitude]);
          setCoords(latitude, longitude);
          locateBtn.disabled = false;
          locateBtn.innerHTML = '<i class="bi bi-crosshair"></i> Use My Current Location';
        }, () => {
          locateBtn.disabled = false;
          locateBtn.innerHTML = '<i class="bi bi-crosshair"></i> Use My Current Location';
          alert('Could not get your location. You can still drag the pin on the map.');
        });
      });
    }

    // Leaflet sizes itself off the container's dimensions at creation time;
    // if the map sits inside a layout that finishes sizing after paint (flex/grid),
    // nudge it once more on the next frame.
    requestAnimationFrame(() => map.invalidateSize());
  }
})();
